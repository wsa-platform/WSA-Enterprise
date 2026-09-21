<?php

namespace Tests\Feature;

use App\Services\Agriculture\Research\Synthesis\AnswerComposer;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use App\Services\Agriculture\Research\Validation\EvidenceValidationExecutionReport;
use Tests\TestCase;

/**
 * Phase 5 Unit B1 — R2 answer language follows the question, not UI/app locale.
 */
class Phase5UnitB1AnswerLanguageContractTest extends TestCase
{
    use Phase5UnitATestFixtures;

    /**
     * @return list<array{0: string, 1: string, 2: string, 3: string}>
     */
    public static function questionVsUiMatrix(): array
    {
        return [
            'ar_q_en_ui' => ['ما إنتاج القمح في مصر؟', 'ar', 'en', 'ar'],
            'en_q_ar_ui' => ['What is wheat production in Egypt?', 'en', 'ar', 'en'],
            'fr_q_en_ui' => ['Quelle est la production de blé en Égypte ?', 'fr', 'en', 'fr'],
            'tr_q_en_ui' => ['Mısırda buğday üretimi nedir?', 'tr', 'en', 'tr'],
        ];
    }

    /**
     * @dataProvider questionVsUiMatrix
     */
    public function test_composer_answer_language_follows_question_not_ui(
        string $question,
        string $questionLang,
        string $uiLang,
        string $expectedAnswerLang,
    ): void {
        app()->setLocale($uiLang);

        $plan = $this->phase5Plan(
            question: $question,
            constraints: ['answer_language' => $questionLang],
            language: $questionLang,
        );

        $report = app(AnswerComposer::class)->compose($plan, $this->directValidation());

        $this->assertSame($expectedAnswerLang, $report->language);
        $this->assertNotSame($uiLang, $report->language);
        $this->assertSame($uiLang, app()->getLocale());
    }

    public function test_ui_locale_change_does_not_change_answer_language(): void
    {
        $plan = $this->phase5Plan(
            question: 'ما إنتاج القمح في مصر؟',
            constraints: ['answer_language' => 'ar'],
            language: 'ar',
        );

        app()->setLocale('en');
        $first = app(AnswerComposer::class)->compose($plan, $this->directValidation());

        app()->setLocale('fr');
        $second = app(AnswerComposer::class)->compose($plan, $this->directValidation());

        $this->assertSame('ar', $first->language);
        $this->assertSame('ar', $second->language);
        $this->assertSame($first->language, $second->language);
    }

    public function test_empty_answer_language_uses_question_language_not_ui_locale(): void
    {
        app()->setLocale('en');

        $plan = $this->phase5Plan(
            question: 'ما إنتاج القمح في مصر؟',
            constraints: [],
            language: 'ar',
        );

        $this->assertSame('', trim((string) ($plan->normalizedQuery->constraints['answer_language'] ?? '')));

        $report = app(AnswerComposer::class)->compose($plan, $this->directValidation());

        $this->assertSame('ar', $report->language);
        $this->assertNotSame('en', $report->language);
        $this->assertSame('en', app()->getLocale());
    }

    public function test_composer_answer_language_path_does_not_call_get_locale_for_resolution(): void
    {
        $source = file_get_contents(base_path('app/Services/Agriculture/Research/Synthesis/AnswerComposer.php'));
        $this->assertIsString($source);
        $this->assertStringNotContainsString(
            'app()->getLocale()',
            $source,
            'AnswerComposer must not use app()->getLocale() for answer-language resolution',
        );
    }

    private function directValidation(): EvidenceValidationExecutionReport
    {
        return $this->phase5Validation([
            $this->phase5Evidence('b1-e1', ClaimEvidenceRelationship::SUPPORTED),
        ], true);
    }
}
