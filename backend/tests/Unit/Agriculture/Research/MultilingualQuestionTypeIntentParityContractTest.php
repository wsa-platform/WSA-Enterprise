<?php

namespace Tests\Unit\Agriculture\Research;

use App\Services\Agriculture\Research\ResearchPlanner;
use Tests\TestCase;

/**
 * M-S3-ML: scientifically equivalent questions across AR/EN/FR/TR must keep
 * intent-aligned question_type / required_evidence_type when CSQ qualifier is set.
 *
 * Guards against surface copula openers (ما هو / nedir / what is) forcing
 * definitional_statement after scientific_intent_qualifier is already resolved.
 */
final class MultilingualQuestionTypeIntentParityContractTest extends TestCase
{
    /**
     * @return list<array{0: string, 1: string}>
     */
    public static function irrigationRequirementQuestions(): array
    {
        return [
            ['en', 'What is the crop water requirement of wheat?'],
            ['fr', 'Quel est le besoin en eau de la culture du blé?'],
            ['ar', 'ما هو الاحتياج المائي لمحصول القمح؟'],
            ['tr', 'Buğdayın sulama suyu ihtiyacı nedir?'],
        ];
    }

    /**
     * @dataProvider irrigationRequirementQuestions
     */
    public function test_irrigation_requirement_questions_share_requirements_type(string $lang, string $question): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => $question,
            'force_execute' => true,
        ]);
        $nq = $plan->normalizedQuery;

        $this->assertSame($lang, $nq->language, 'detected language for: '.$question);
        $this->assertSame('crop_water_requirement', $nq->constraints['scientific_sense'] ?? null);
        $this->assertSame('requirement', $nq->constraints['scientific_intent_qualifier'] ?? null);
        $this->assertSame('requirements', $nq->constraints['question_type'] ?? null);
        $this->assertSame(
            'requirement_specification',
            $nq->constraints['required_evidence_type'] ?? null,
        );
        $this->assertSame('wheat', $nq->cropId);
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    public static function salinityEffectQuestions(): array
    {
        return [
            ['en', 'How does soil salinity affect tomato growth?'],
            ['fr', 'Comment la salinité du sol affecte-t-elle la croissance de la tomate?'],
            ['ar', 'كيف تؤثر ملوحة التربة على نمو الطماطم؟'],
            ['tr', 'Toprak tuzluluğu domates büyümesini nasıl etkiler?'],
        ];
    }

    /**
     * @dataProvider salinityEffectQuestions
     */
    public function test_salinity_effect_questions_share_causes_type(string $lang, string $question): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => $question,
            'force_execute' => true,
        ]);
        $nq = $plan->normalizedQuery;

        $this->assertSame($lang, $nq->language);
        $this->assertSame('salinity_physiology', $nq->constraints['scientific_sense'] ?? null);
        $this->assertSame('causes', $nq->constraints['question_type'] ?? null);
        $this->assertSame('causal_relationship', $nq->constraints['required_evidence_type'] ?? null);
        $this->assertSame('tomato', $nq->cropId);
    }

    public function test_bare_definition_without_requirement_intent_stays_definition(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'What is photosynthesis?',
            'force_execute' => true,
        ]);
        $nq = $plan->normalizedQuery;

        $this->assertSame('definition', $nq->constraints['question_type'] ?? null);
        $this->assertSame('definitional_statement', $nq->constraints['required_evidence_type'] ?? null);
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    public static function fertilizerRecommendationQuestions(): array
    {
        return [
            ['en', 'What is the recommended nitrogen fertilizer rate for wheat?'],
            ['fr', 'Quelle est la dose recommandée d\'engrais azoté pour le blé?'],
            ['ar', 'ما هي الجرعة الموصى بها من السماد النيتروجيني للقمح؟'],
            ['tr', 'Buğday için önerilen azotlu gübre dozu nedir?'],
        ];
    }

    /**
     * @dataProvider fertilizerRecommendationQuestions
     */
    public function test_fertilizer_recommendation_questions_share_plant_nutrition_type(
        string $lang,
        string $question,
    ): void {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => $question,
            'force_execute' => true,
        ]);
        $nq = $plan->normalizedQuery;

        $this->assertSame($lang, $nq->language);
        $this->assertSame('plant_nutrition', $nq->constraints['scientific_sense'] ?? null);
        $this->assertSame('recommendation', $nq->constraints['question_type'] ?? null);
        $this->assertSame(
            'recommendation_or_best_practice',
            $nq->constraints['required_evidence_type'] ?? null,
        );
        $this->assertSame('wheat', $nq->cropId);
    }
}
