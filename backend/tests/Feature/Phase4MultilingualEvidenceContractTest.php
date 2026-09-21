<?php

namespace Tests\Feature;

use App\Services\Agriculture\Research\Feedback\PositiveResearchFeedbackService;
use App\Services\Agriculture\Research\ResearchPlanner;
use App\Services\Agriculture\Research\Synthesis\AnswerComposer;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use App\Services\Agriculture\Research\Validation\EvidenceValidationExecutionReport;
use App\Services\Agriculture\Research\Validation\EvidenceValidationStatus;
use App\Services\Agriculture\Research\Validation\ScientificEvidenceItem;
use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use Tests\TestCase;

/**
 * Phase 4 — multilingual evidence path + R7 boundary.
 *
 * R2: answer_language follows the question language; UI/platform locale is independent.
 */
class Phase4MultilingualEvidenceContractTest extends TestCase
{
    /**
     * @return list<array{0: string, 1: string, 2: string}>
     */
    public static function languageMatrixProvider(): array
    {
        return [
            'en_q_ar_ui' => ['What irrigation methods improve wheat yield?', 'en', 'ar'],
            'ar_q_en_ui' => ['ما هي طرق الري التي تحسن إنتاج القمح؟', 'ar', 'en'],
            'fr_q_tr_ui' => ['Quelles méthodes d\'irrigation améliorent le rendement du blé ?', 'fr', 'tr'],
            'tr_q_fr_ui' => ['Buğday verimini artıran sulama yöntemleri nelerdir?', 'tr', 'fr'],
        ];
    }

    /**
     * @dataProvider languageMatrixProvider
     */
    public function test_answer_language_follows_question_not_ui(
        string $question,
        string $questionLang,
        string $uiLang,
    ): void {
        app()->setLocale($uiLang);

        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => $question,
        ]);

        $this->assertSame($questionLang, $plan->normalizedQuery->language);
        $this->assertSame(
            $questionLang,
            $plan->normalizedQuery->constraints['question_language'] ?? $plan->normalizedQuery->language,
        );

        $answerLanguage = (string) ($plan->normalizedQuery->constraints['answer_language']
            ?? $plan->normalizedQuery->language);
        $this->assertSame(
            $questionLang,
            $answerLanguage,
            'R2: answer_language must follow question language, not UI locale '.$uiLang,
        );
        $this->assertNotSame(
            $uiLang,
            $answerLanguage,
            'UI locale must remain independent of answer_language when they differ',
        );

        $item = $this->item('ml-1');
        $validation = new EvidenceValidationExecutionReport(
            status: 'validation_completed',
            validatedEvidence: [$item],
            rejectedEvidence: [],
            sourcesReceived: 1,
            validatedCount: 1,
            rejectedCount: 0,
            duplicateCount: 0,
            conflictingCount: 0,
            evidenceSufficient: true,
            validatorsUsed: [],
            qualityDistribution: [],
            searchSummary: ['search_status' => 'search_completed'],
            observability: [],
        );

        $synthesis = app(AnswerComposer::class)->compose($plan, $validation);
        $this->assertSame(
            $questionLang,
            $synthesis->language,
            'Composer must emit question-aligned answer language (R2)',
        );
    }

    public function test_r7_negative_feedback_is_not_persisted_as_scientific_truth(): void
    {
        $service = app(PositiveResearchFeedbackService::class);
        $result = $service->record([
            'polarity' => PositiveResearchFeedbackService::POLARITY_NEGATIVE,
            'question' => 'What irrigation methods improve wheat yield?',
            'organization_id' => 1,
        ]);

        $this->assertFalse((bool) ($result['persisted'] ?? true));
        $this->assertSame('negative_feedback_not_persisted', $result['reason'] ?? null);
    }

    private function item(string $id): ScientificEvidenceItem
    {
        return new ScientificEvidenceItem(
            evidenceId: $id,
            sourceId: 'src-'.$id,
            sourceKey: 'openalex',
            sourceType: 'peer_reviewed_journal',
            publicationTitle: 'Wheat irrigation fixture '.$id,
            authors: ['Fixture'],
            institution: 'Fixture',
            journal: 'Fixture Journal',
            doi: '10.9999/ml-'.$id,
            url: 'https://example.test/ml/'.$id,
            publicationYear: 2021,
            retrievedAt: '2026-09-21T00:00:00+00:00',
            agriculturalDomain: 'field_crops',
            claimTopic: 'irrigation',
            evidenceText: 'Wheat drip irrigation improved yield in multi-year field trials.',
            validationStatus: EvidenceValidationStatus::EVIDENCE_USABLE,
            validationFailures: [],
            claimRelationship: ClaimEvidenceRelationship::SUPPORTED,
            confidence: 0.85,
            qualityScore: 85.0,
            qualityFactors: [
                'evidence_directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
                'answer_eligible' => true,
            ],
            sourceAttribution: [
                'evidence_directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
            ],
            cropOrEntity: 'wheat',
        );
    }
}
