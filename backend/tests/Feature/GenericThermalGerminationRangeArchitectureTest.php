<?php

namespace Tests\Feature;

use App\Services\Agriculture\Research\AgriculturalEntityCatalog;
use App\Services\Agriculture\Research\QueryUnderstandingService;
use App\Services\Agriculture\Research\ResearchPlanner;
use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use App\Services\Agriculture\Research\Search\ScientificSearchQueryBuilder;
use App\Services\Agriculture\Research\Synthesis\AnswerComposer;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use App\Services\Agriculture\Research\Validation\EvidenceValidationExecutionReport;
use App\Services\Agriculture\Research\Validation\EvidenceValidationStatus;
use App\Services\Agriculture\Research\Validation\ScientificEvidenceItem;
use Tests\TestCase;

/**
 * Generic thermal seed-germination optimal-range architecture (Q1 family).
 * Not tomato-literal: temperature + germination + suitable/optimal/range.
 */
class GenericThermalGerminationRangeArchitectureTest extends TestCase
{
    private const CANONICAL = 'ما درجة الحرارة المناسبة لإنبات بذور الطماطم؟';

    private const BEST = 'ما أفضل درجة حرارة لإنبات بذور الطماطم؟';

    private const MA_HIYA = 'ما هي درجة الحرارة المناسبة لإنبات بذور الطماطم؟';

    private const KAM = 'كم درجة الحرارة المناسبة لإنبات بذور الطماطم؟';

    private const EN_SUITABLE = 'What is the suitable temperature for tomato seed germination?';

    private const EN_OPTIMAL = 'What is the optimal temperature for tomato seed germination?';

    /**
     * @return list<string>
     */
    private function q1Family(): array
    {
        return [
            self::CANONICAL,
            self::BEST,
            self::MA_HIYA,
            self::KAM,
            self::EN_SUITABLE,
            self::EN_OPTIMAL,
            'ما درجة الحرارة المثلى لإنبات بذور الطماطم؟',
            'ما درجة الحرارة المناسبة لإنبات بذور الطماطم',
            'ما  درجة   الحرارة المناسبة لإنبات بذور الطماطم؟',
            'ما درجة الحرارة المناسبة لإنبات tomato؟',
            'كم درجة حرارة إنبات بذور الطماطم؟',
        ];
    }

    public function test_q1_family_is_range_not_definition_or_quantity(): void
    {
        foreach ($this->q1Family() as $query) {
            $understood = $this->understand($query);
            $this->assertSame('range', $understood->constraints['question_type'] ?? null, $query);
            $this->assertSame(
                'numeric_range_or_optimal_value',
                $understood->constraints['required_evidence_type'] ?? null,
                $query,
            );
            $this->assertNotSame('definition', $understood->constraints['question_type'] ?? null, $query);
            $this->assertNotSame('quantity', $understood->constraints['question_type'] ?? null, $query);
            $this->assertSame('seed_germination', $understood->constraints['scientific_sense'] ?? null, $query);
            $this->assertSame('tomato', $understood->cropId, $query);
            $this->assertSame('Solanum lycopersicum', $understood->scientificName, $query);
            $this->assertContains('temperature', $understood->constraints['scientific_factors'] ?? [], $query);
            $this->assertContains('germination', $understood->constraints['scientific_factors'] ?? [], $query);
            $this->assertNotSame('cultivation', $understood->researchIntent, $query);
            $this->assertSame('crop', $understood->subject['type'] ?? null, $query);
            $this->assertNotSame('soil', $understood->subject['type'] ?? null, $query);
        }
    }

    public function test_ma_hiya_is_not_definition(): void
    {
        $understood = $this->understand(self::MA_HIYA);
        $this->assertSame('range', $understood->constraints['question_type'] ?? null);
        $this->assertNotSame('definition', $understood->constraints['question_type'] ?? null);
        $this->assertSame('optimal_range', $understood->constraints['scientific_intent_qualifier'] ?? null);
    }

    public function test_kam_temperature_is_not_fertilizer_quantity(): void
    {
        $kam = $this->understand(self::KAM);
        $this->assertSame('range', $kam->constraints['question_type'] ?? null);
        $this->assertNotSame('quantity', $kam->constraints['question_type'] ?? null);
        $this->assertSame('numeric_range_or_optimal_value', $kam->constraints['required_evidence_type'] ?? null);

        $fertilizer = $this->understand('كم كيلو سماد أحتاج؟');
        $this->assertSame('quantity', $fertilizer->constraints['question_type'] ?? null);
        $this->assertNotSame('range', $fertilizer->constraints['question_type'] ?? null);

        $water = $this->understand('كم لتر ماء أحتاج؟');
        $this->assertSame('quantity', $water->constraints['question_type'] ?? null);

        $hectare = $this->understand('كم هكتار؟');
        $this->assertSame('quantity', $hectare->constraints['question_type'] ?? null);
    }

    public function test_howto_and_causal_traps_are_not_range(): void
    {
        $plant = $this->understand('كيف أزرع الطماطم؟');
        $this->assertSame('recommendation', $plant->constraints['question_type'] ?? null);
        $this->assertSame('tomato', $plant->cropId);

        $irrigate = $this->understand('كيف أروي الطماطم؟');
        $this->assertSame('recommendation', $irrigate->constraints['question_type'] ?? null);
        $this->assertSame('tomato', $irrigate->cropId);

        $causal = $this->understand('كيف تؤثر درجة الحرارة على إنبات بذور الطماطم؟');
        $this->assertSame('causes', $causal->constraints['question_type'] ?? null);
        $this->assertNotSame('range', $causal->constraints['question_type'] ?? null);
        $this->assertSame('tomato', $causal->cropId);
    }

    public function test_q3_irrigation_quantity_is_not_stolen_as_range(): void
    {
        $q3 = $this->understand('ما كمية الري المناسبة للطماطم؟');
        $this->assertSame('quantity', $q3->constraints['question_type'] ?? null);
        $this->assertSame(
            'numeric_rate_or_quantity',
            $q3->constraints['required_evidence_type'] ?? null,
        );
        $this->assertNotSame('range', $q3->constraints['question_type'] ?? null);

        $fertilizer = $this->understand('كم كيلو سماد أحتاج؟');
        $this->assertSame('quantity', $fertilizer->constraints['question_type'] ?? null);
        $this->assertNotSame('range', $fertilizer->constraints['question_type'] ?? null);
    }

    public function test_q5_recommendation_is_not_stolen_as_range(): void
    {
        $q5 = $this->understand('ما أفضل المحاصيل التي يمكن زراعتها في الأراضي الصحراوية ذات المياه المالحة؟');
        $this->assertSame('recommendation', $q5->constraints['question_type'] ?? null);
        $this->assertNotSame('range', $q5->constraints['question_type'] ?? null);
    }

    public function test_generic_plant_does_not_become_tomato(): void
    {
        $understood = $this->understand('ما درجة الحرارة المناسبة لإنبات بذور النبات؟');
        $this->assertNotSame('tomato', $understood->cropId);
        $this->assertNotSame('Solanum lycopersicum', $understood->scientificName);
    }

    public function test_soil_is_context_not_subject_when_crop_and_germination_are_asked(): void
    {
        $understood = $this->understand('ما درجة حرارة التربة المناسبة لإنبات بذور الطماطم؟');
        $this->assertSame('tomato', $understood->cropId);
        $this->assertSame('crop', $understood->subject['type'] ?? null);
        $this->assertNotSame('soil', $understood->subject['type'] ?? null);
        $this->assertSame('range', $understood->constraints['question_type'] ?? null);
    }

    public function test_species_remain_distinct(): void
    {
        $tomato = $this->understand(self::CANONICAL);
        $this->assertSame('tomato', $tomato->cropId);

        $wheat = $this->understand('ما درجة الحرارة المناسبة لإنبات بذور القمح؟');
        $this->assertSame('wheat', $wheat->cropId);
        $this->assertNotSame('tomato', $wheat->cropId);

        $potato = $this->understand('ما درجة الحرارة المناسبة لإنبات بذور البطاطس؟');
        $this->assertSame('potato', $potato->cropId);

        $sweet = $this->understand('ما أفضل درجة حرارة لإنبات بذور البطاطا الحلوة؟');
        $this->assertSame('sweet-potato', $sweet->cropId);
        $this->assertNotSame('potato', $sweet->cropId);
    }

    public function test_search_variants_preserve_thermal_germination_semantics(): void
    {
        $plan = $this->plan(self::CANONICAL);
        $variants = app(ScientificSearchQueryBuilder::class)->buildVariantsFromPlan($plan);
        $this->assertNotEmpty($variants);
        $first = mb_strtolower((string) ($variants[0] ?? ''));
        $joined = mb_strtolower(implode(' | ', $variants));

        $this->assertTrue(
            str_contains($joined, 'solanum lycopersicum') || str_contains($joined, 'tomato'),
            $joined,
        );
        $this->assertTrue(str_contains($first, 'temperature') || str_contains($first, 'thermal'), $first);
        $this->assertTrue(str_contains($first, 'germinat'), $first);
        $this->assertFalse(preg_match('/\bcultivation\b/u', $joined) === 1, $joined);
        $this->assertFalse(preg_match('/\bproduction\b/u', $joined) === 1, $joined);
        $this->assertFalse(str_contains($joined, 'egypt') || str_contains($joined, 'libya'), $joined);
    }

    public function test_valid_thermal_direct_requires_celsius_answerability(): void
    {
        $plan = $this->plan(self::MA_HIYA);
        $direct = $this->direct(
            $plan,
            'Effect of temperature on tomato seed germination',
            'Solanum lycopersicum seed germination is optimal near 25 °C under controlled temperature regimes.',
        );
        $this->assertSame(ScientificEvidenceDirectnessAssessor::DIRECT, $direct['directness']);
    }

    public function test_qualifier_overlap_without_numeric_range_is_not_direct(): void
    {
        $plan = $this->plan(self::MA_HIYA);
        $overlap = $this->direct(
            $plan,
            'Tomato seed germination under field conditions',
            'Solanum lycopersicum seed germination was evaluated using suitable temperature regimes without reporting a numeric optimum.',
        );
        $this->assertNotSame(ScientificEvidenceDirectnessAssessor::DIRECT, $overlap['directness']);
    }

    public function test_optimization_substring_is_not_optimal_direct(): void
    {
        $plan = $this->plan(self::EN_OPTIMAL);
        $optimization = $this->direct(
            $plan,
            'Optimization of tomato seed processing',
            'This paper describes optimization of Solanum lycopersicum seed handling without germination temperature values.',
        );
        $this->assertNotSame(ScientificEvidenceDirectnessAssessor::DIRECT, $optimization['directness']);
    }

    public function test_negated_thermal_evidence_is_not_direct(): void
    {
        $plan = $this->plan(self::CANONICAL);
        $negated = $this->direct(
            $plan,
            'Tomato seed germination without thermal optima',
            'Solanum lycopersicum germination was tested. No optimal temperature or °C values were reported.',
        );
        $this->assertNotSame(ScientificEvidenceDirectnessAssessor::DIRECT, $negated['directness']);
    }

    public function test_wrong_factor_and_wrong_species_are_not_direct(): void
    {
        $plan = $this->plan(self::CANONICAL);

        $yield = $this->direct(
            $plan,
            'Tomato yield under irrigation scheduling',
            'Solanum lycopersicum fruit yield increased under drip irrigation without seed germination temperature.',
        );
        $this->assertNotSame(ScientificEvidenceDirectnessAssessor::DIRECT, $yield['directness']);

        $wheat = $this->direct(
            $plan,
            'Wheat seed germination temperature optimum at 20 °C',
            'Triticum aestivum seed germination is optimal near 20 °C.',
        );
        $this->assertNotSame(ScientificEvidenceDirectnessAssessor::DIRECT, $wheat['directness']);
    }

    public function test_stage5_supporting_only_is_not_sufficient(): void
    {
        $plan = $this->plan(self::CANONICAL);
        $composer = app(AnswerComposer::class);
        $supporting = $this->usableItem(
            'Tomato growth under different temperatures',
            'Solanum lycopersicum vegetative growth responds to temperature in field crops.',
            '10.1000/q1-supporting-a',
            [
                'entity_matched' => true,
                'topic_matched' => true,
                'evidence_directness' => ScientificEvidenceDirectnessAssessor::SUPPORTING,
            ],
        );

        $zero = $composer->compose($plan, $this->validationReport([]));
        $this->assertFalse((bool) ($zero->researchMetadata['evidence_sufficient'] ?? false));

        $one = $composer->compose($plan, $this->validationReport([$supporting]));
        $this->assertFalse((bool) ($one->researchMetadata['evidence_sufficient'] ?? false));

        $two = $composer->compose($plan, $this->validationReport([
            $supporting,
            $this->usableItem(
                'Tomato biomass under heat',
                'Solanum lycopersicum shoot biomass increased without reporting germination °C.',
                '10.1000/q1-supporting-b',
                [
                    'entity_matched' => true,
                    'topic_matched' => true,
                    'evidence_directness' => ScientificEvidenceDirectnessAssessor::SUPPORTING,
                ],
            ),
        ]));
        $this->assertFalse((bool) ($two->researchMetadata['evidence_sufficient'] ?? false));
        $this->assertNotSame('supported_answer', $two->researchMetadata['sufficiency_mode'] ?? null);
    }

    public function test_stage5_valid_direct_is_sufficient(): void
    {
        $plan = $this->plan(self::CANONICAL);
        $composer = app(AnswerComposer::class);
        $directItem = $this->usableItem(
            'Effect of temperature on tomato seed germination',
            'Solanum lycopersicum seed germination is optimal near 25 °C under controlled temperature regimes.',
            '10.1000/q1-direct',
            [
                'entity_matched' => true,
                'topic_matched' => true,
                'evidence_directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
            ],
        );

        $oneDirect = $composer->compose($plan, $this->validationReport([$directItem]));
        $this->assertTrue((bool) ($oneDirect->researchMetadata['evidence_sufficient'] ?? false));
        $this->assertSame('sufficient_direct_evidence', $oneDirect->researchMetadata['sufficiency_mode'] ?? null);

        $mixed = $composer->compose($plan, $this->validationReport([
            $directItem,
            $this->usableItem(
                'Tomato growth under different temperatures',
                'Solanum lycopersicum vegetative growth responds to temperature in field crops.',
                '10.1000/q1-mix-supporting',
                [
                    'entity_matched' => true,
                    'topic_matched' => true,
                    'evidence_directness' => ScientificEvidenceDirectnessAssessor::SUPPORTING,
                ],
            ),
        ]));
        $this->assertTrue((bool) ($mixed->researchMetadata['evidence_sufficient'] ?? false));
    }

    public function test_stage5_wrong_factor_and_negated_are_insufficient(): void
    {
        $plan = $this->plan(self::CANONICAL);
        $composer = app(AnswerComposer::class);

        $wrong = $composer->compose($plan, $this->validationReport([
            $this->usableItem(
                'Tomato yield under irrigation scheduling',
                'Solanum lycopersicum fruit yield increased under drip irrigation.',
                '10.1000/q1-wrong-factor',
                [
                    'entity_matched' => true,
                    'topic_matched' => false,
                    'evidence_directness' => ScientificEvidenceDirectnessAssessor::SUPPORTING,
                ],
            ),
        ]));
        $this->assertFalse((bool) ($wrong->researchMetadata['evidence_sufficient'] ?? false));

        $negated = $composer->compose($plan, $this->validationReport([
            $this->usableItem(
                'Tomato seed germination without thermal optima',
                'No optimal temperature or °C values were reported for Solanum lycopersicum germination.',
                '10.1000/q1-negated',
                [
                    'entity_matched' => true,
                    'topic_matched' => true,
                    'evidence_directness' => ScientificEvidenceDirectnessAssessor::SUPPORTING,
                ],
            ),
        ]));
        $this->assertFalse((bool) ($negated->researchMetadata['evidence_sufficient'] ?? false));
    }

    public function test_production_pipeline_has_no_q1_literal_branches(): void
    {
        $pipeline = [
            app_path('Services/Agriculture/Research/QueryUnderstandingService.php'),
            app_path('Services/Agriculture/Research/AgriculturalEntityCatalog.php'),
            app_path('Services/Agriculture/Research/Search/ScientificSearchQueryBuilder.php'),
            app_path('Services/Agriculture/Research/Search/ScientificEvidenceDirectnessAssessor.php'),
            app_path('Services/Agriculture/Research/Synthesis/AnswerComposer.php'),
            app_path('Services/Agriculture/Research/AgriculturalResearchAgent.php'),
        ];
        foreach ($pipeline as $path) {
            $source = (string) file_get_contents($path);
            $this->assertDoesNotMatchRegularExpression(
                '/if\s*\([^)]*(?:Q1|ما هي درجة الحرارة المناسبة لإنبات بذور الطماطم|ما أفضل درجة حرارة لإنبات بذور الطماطم)/u',
                $source,
                $path,
            );
        }

        $this->assertTrue(AgriculturalEntityCatalog::isThermalGerminationRangeQuestion(self::MA_HIYA));
        $this->assertFalse(AgriculturalEntityCatalog::isThermalGerminationRangeQuestion('كيف أزرع الطماطم؟'));
        $this->assertFalse(AgriculturalEntityCatalog::isThermalGerminationRangeQuestion('كم كيلو سماد أحتاج؟'));
    }

    public function test_arabic_diacritics_paraphrase_stays_range(): void
    {
        $understood = $this->understand('مَا دَرَجَةُ الْحَرَارَةِ الْمُنَاسِبَةِ لِإِنْبَاتِ بُذُورِ الطَّمَاطِم؟');
        $this->assertSame('range', $understood->constraints['question_type'] ?? null);
        $this->assertNotSame('definition', $understood->constraints['question_type'] ?? null);
    }

    private function understand(string $query): object
    {
        return app(QueryUnderstandingService::class)->understand(['query' => $query]);
    }

    private function plan(string $query): object
    {
        return app(ResearchPlanner::class)->planKnowledgeQuery(['query' => $query]);
    }

    /**
     * @return array<string, mixed>
     */
    private function direct(object $plan, string $title, string $abstract): array
    {
        return app(ScientificEvidenceDirectnessAssessor::class)->assess($plan, $title, $abstract);
    }

    /**
     * @param  list<ScientificEvidenceItem>  $items
     */
    private function validationReport(array $items): EvidenceValidationExecutionReport
    {
        return new EvidenceValidationExecutionReport(
            status: 'validation_completed',
            validatedEvidence: $items,
            rejectedEvidence: [],
            sourcesReceived: count($items),
            validatedCount: count($items),
            rejectedCount: 0,
            duplicateCount: 0,
            conflictingCount: 0,
            evidenceSufficient: $items !== [],
            validatorsUsed: ['test'],
            qualityDistribution: [],
            searchSummary: [],
            observability: [],
        );
    }

    /**
     * @param  array<string, mixed>  $quality
     */
    private function usableItem(string $title, string $abstract, string $doi, array $quality): ScientificEvidenceItem
    {
        return new ScientificEvidenceItem(
            evidenceId: 'ev-'.$doi,
            sourceId: $doi,
            sourceKey: 'openalex',
            sourceType: 'journal_article',
            publicationTitle: $title,
            authors: ['Researcher'],
            institution: 'University of Agriculture',
            journal: 'Plant Physiology',
            doi: $doi,
            url: 'https://doi.org/'.$doi,
            publicationYear: 2022,
            retrievedAt: '2026-01-01T00:00:00Z',
            agriculturalDomain: 'plant_physiology',
            claimTopic: 'germination',
            evidenceText: $abstract,
            validationStatus: EvidenceValidationStatus::EVIDENCE_USABLE,
            validationFailures: [],
            claimRelationship: ClaimEvidenceRelationship::SUPPORTED,
            confidence: 0.8,
            qualityScore: 80.0,
            qualityFactors: array_merge([
                'entity_matched' => true,
                'topic_matched' => true,
            ], $quality),
            sourceAttribution: ['evidence_directness' => $quality['evidence_directness'] ?? ScientificEvidenceDirectnessAssessor::SUPPORTING],
        );
    }
}
