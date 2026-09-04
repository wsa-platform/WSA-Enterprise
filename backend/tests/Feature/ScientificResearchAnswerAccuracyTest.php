<?php

namespace Tests\Feature;

use App\Services\Agriculture\Research\QueryUnderstandingService;
use App\Services\Agriculture\Research\ResearchPlanner;
use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use App\Services\Agriculture\Research\Search\ScientificEvidenceRelevanceGate;
use App\Services\Agriculture\Research\Search\ScientificSearchQueryBuilder;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;
use App\Services\Agriculture\Research\Synthesis\AnswerComposer;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceMatcher;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use App\Services\Agriculture\Research\Validation\EvidenceValidationExecutionReport;
use App\Services\Agriculture\Research\Validation\EvidenceValidationStatus;
use App\Services\Agriculture\Research\Validation\ScientificEvidenceItem;
use Tests\TestCase;

/**
 * Answer-accuracy matrix: structured intent, multi-query, directness, grounded composer.
 */
class ScientificResearchAnswerAccuracyTest extends TestCase
{
    /** A — ginger temperature effect → growth/physiology, not extraction. */
    public function test_matrix_a_ginger_temperature_effect_directness(): void
    {
        $understood = app(QueryUnderstandingService::class)->understand([
            'query' => 'ما هو تأثير درجة الحرارة على نبات الزنجبيل؟',
        ]);
        $this->assertSame('ginger', $understood->cropId);
        $this->assertSame('Zingiber officinale', $understood->scientificName);
        $this->assertContains('temperature', $understood->constraints['scientific_factors'] ?? []);
        $this->assertSame('plant_growth', $understood->constraints['scientific_sense'] ?? null);
        $this->assertSame('effect', $understood->constraints['scientific_intent_qualifier'] ?? null);

        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما هو تأثير درجة الحرارة على نبات الزنجبيل؟',
        ]);
        $variants = app(ScientificSearchQueryBuilder::class)->buildVariantsFromPlan($plan);
        $this->assertGreaterThanOrEqual(2, count($variants));
        $joined = implode(' ', $variants);
        $this->assertStringContainsString('Zingiber officinale', $joined);
        $this->assertTrue(str_contains($joined, 'temperature') || str_contains($joined, 'thermal'));
        $this->assertTrue(
            str_contains($joined, 'growth')
            || str_contains($joined, 'physiology')
            || str_contains($joined, 'cultivation'),
        );

        $assessor = app(ScientificEvidenceDirectnessAssessor::class);
        $direct = $assessor->assess(
            $plan,
            'Effect of temperature on Zingiber officinale rhizome growth',
            'Temperature regimes affect ginger plant growth and physiology under field cultivation.',
        );
        $this->assertSame(ScientificEvidenceDirectnessAssessor::DIRECT, $direct['directness']);

        $extraction = $assessor->assess(
            $plan,
            'Microwave-assisted extraction of Zingiber officinale at process temperature',
            'MAE extraction yield of ginger oleoresin under temperature optimization.',
        );
        $this->assertSame(ScientificEvidenceDirectnessAssessor::IRRELEVANT, $extraction['directness']);
    }

    /** B — tomato germination temperature; reject sewage sludge. */
    public function test_matrix_b_tomato_germination_rejects_sludge(): void
    {
        $understood = app(QueryUnderstandingService::class)->understand([
            'query' => 'ما أفضل درجة حرارة لإنبات بذور الطماطم؟',
        ]);
        $this->assertSame('tomato', $understood->cropId);
        $this->assertSame('Solanum lycopersicum', $understood->scientificName);
        $this->assertContains('temperature', $understood->constraints['scientific_factors'] ?? []);
        $this->assertContains('germination', $understood->constraints['scientific_factors'] ?? []);
        $this->assertSame('seed_germination', $understood->constraints['scientific_sense'] ?? null);
        $this->assertSame('optimal_range', $understood->constraints['scientific_intent_qualifier'] ?? null);

        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما أفضل درجة حرارة لإنبات بذور الطماطم؟',
        ]);
        $assessor = app(ScientificEvidenceDirectnessAssessor::class);

        $direct = $assessor->assess(
            $plan,
            'Effect of temperature on tomato seed germination',
            'Solanum lycopersicum seed germination rate under controlled temperature regimes.',
        );
        $this->assertSame(ScientificEvidenceDirectnessAssessor::DIRECT, $direct['directness']);

        $supporting = $assessor->assess(
            $plan,
            'Tomato growth under different temperatures',
            'Solanum lycopersicum vegetative growth responds to temperature in field crops.',
        );
        $this->assertSame(ScientificEvidenceDirectnessAssessor::SUPPORTING, $supporting['directness']);

        $gate = app(ScientificEvidenceRelevanceGate::class);
        $this->assertFalse($gate->isRelevant(
            $plan,
            'Sewage sludge effect on tomato growth',
            'Municipal sewage sludge amendments alter tomato biomass and vegetative canopy development.',
        ));

        $composer = app(AnswerComposer::class);
        $sludgeOnly = $composer->compose($plan, $this->validationReport([
            $this->usableEvidence(
                'sludge',
                'Municipal sewage sludge amendments alter tomato biomass and vegetative canopy development.',
                ClaimEvidenceRelationship::PARTIALLY_SUPPORTED,
                [
                    'publicationTitle' => 'Sewage sludge effect on tomato growth',
                    'doi' => '10.1000/sludge',
                    'directness' => ScientificEvidenceDirectnessAssessor::BACKGROUND,
                ],
            ),
        ]));
        $this->assertContains($sludgeOnly->status, ['no_validated_evidence', 'insufficient_evidence']);
    }

    /** C — wheat water requirement. */
    public function test_matrix_c_wheat_water_requirement(): void
    {
        $understood = app(QueryUnderstandingService::class)->understand([
            'query' => 'ما احتياجات القمح من المياه؟',
        ]);
        $this->assertSame('wheat', $understood->cropId);
        $this->assertSame('Triticum aestivum', $understood->scientificName);
        $this->assertContains('water', $understood->constraints['scientific_factors'] ?? []);
        $this->assertSame('crop_water_requirement', $understood->constraints['scientific_sense'] ?? null);

        $plan = app(ResearchPlanner::class)->planKnowledgeQuery(['query' => 'ما احتياجات القمح من المياه؟']);
        $variants = app(ScientificSearchQueryBuilder::class)->buildVariantsFromPlan($plan);
        $joined = implode(' ', $variants);
        $this->assertStringContainsString('Triticum aestivum', $joined);
        $this->assertTrue(
            str_contains($joined, 'water')
            || str_contains($joined, 'irrigation')
            || str_contains($joined, 'evapotranspiration'),
        );

        $direct = app(ScientificEvidenceDirectnessAssessor::class)->assess(
            $plan,
            'Crop water requirement of Triticum aestivum under irrigation',
            'Wheat irrigation requirement and evapotranspiration water use in field production.',
        );
        $this->assertSame(ScientificEvidenceDirectnessAssessor::DIRECT, $direct['directness']);
    }

    /** D — wheat salinity. */
    public function test_matrix_d_wheat_salinity(): void
    {
        $understood = app(QueryUnderstandingService::class)->understand([
            'query' => 'ما تأثير الملوحة على القمح؟',
        ]);
        $this->assertSame('wheat', $understood->cropId);
        $this->assertContains('salinity', $understood->constraints['scientific_factors'] ?? []);
        $this->assertSame('salinity_physiology', $understood->constraints['scientific_sense'] ?? null);

        $plan = app(ResearchPlanner::class)->planKnowledgeQuery(['query' => 'ما تأثير الملوحة على القمح؟']);
        $direct = app(ScientificEvidenceDirectnessAssessor::class)->assess(
            $plan,
            'Salinity stress effects on Triticum aestivum yield and physiology',
            'Wheat salinity tolerance reduces growth and yield under saline irrigation.',
        );
        $this->assertSame(ScientificEvidenceDirectnessAssessor::DIRECT, $direct['directness']);
    }

    /** E — ginger drying temperature (processing sense). */
    public function test_matrix_e_ginger_drying_sense(): void
    {
        $understood = app(QueryUnderstandingService::class)->understand([
            'query' => 'ما تأثير درجة حرارة تجفيف الزنجبيل؟',
        ]);
        $this->assertSame('ginger', $understood->cropId);
        $this->assertContains('drying', $understood->constraints['scientific_factors'] ?? []);
        $this->assertSame('drying_processing', $understood->constraints['scientific_sense'] ?? null);

        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما تأثير درجة حرارة تجفيف الزنجبيل؟',
        ]);
        $this->assertTrue(app(ScientificEvidenceRelevanceGate::class)->isRelevant(
            $plan,
            'Drying temperature effects on Zingiber officinale rhizome quality',
            'Hot air drying of ginger at controlled temperature preserves quality.',
        ));
    }

    /** F — ginger storage temperature. */
    public function test_matrix_f_ginger_storage_sense(): void
    {
        $understood = app(QueryUnderstandingService::class)->understand([
            'query' => 'ما أفضل درجة حرارة لتخزين الزنجبيل؟',
        ]);
        $this->assertSame('ginger', $understood->cropId);
        $this->assertContains('storage', $understood->constraints['scientific_factors'] ?? []);
        $this->assertSame('storage', $understood->constraints['scientific_sense'] ?? null);

        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما أفضل درجة حرارة لتخزين الزنجبيل؟',
        ]);
        $this->assertTrue(app(ScientificEvidenceRelevanceGate::class)->isRelevant(
            $plan,
            'Storage temperature for Zingiber officinale rhizomes',
            'Post-harvest storage of ginger under controlled temperature shelf life.',
        ));
    }

    /** G — agricultural economics remains valid. */
    public function test_matrix_g_agricultural_economics(): void
    {
        $understood = app(QueryUnderstandingService::class)->understand([
            'query' => 'ما الجدوى الاقتصادية لإنتاج القمح؟',
        ]);
        $this->assertSame('wheat', $understood->cropId);
        $this->assertSame('agricultural_economics', $understood->researchIntent);
        $this->assertSame('agricultural_economics', $understood->constraints['scientific_sense'] ?? null);

        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما الجدوى الاقتصادية لإنتاج القمح؟',
        ]);
        $this->assertTrue(app(ScientificEvidenceRelevanceGate::class)->isRelevant(
            $plan,
            'Agricultural economics of wheat production profitability',
            'Farm economics and agricultural economics feasibility of Triticum aestivum production.',
        ));
    }

    /** H — agricultural extension / farmer adoption. */
    public function test_matrix_h_agricultural_extension(): void
    {
        $understood = app(QueryUnderstandingService::class)->understand([
            'query' => 'ما أثر الإرشاد الزراعي على تبني المزارعين لتقنيات الري؟',
        ]);
        $this->assertSame('agricultural_extension', $understood->constraints['scientific_sense'] ?? null);

        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما أثر الإرشاد الزراعي على تبني المزارعين لتقنيات الري؟',
        ]);
        $this->assertTrue(app(ScientificEvidenceRelevanceGate::class)->isRelevant(
            $plan,
            'Agricultural extension effects on farmer adoption of irrigation technologies',
            'Farmer behavior and agricultural extension adoption of irrigation practices.',
        ));
    }

    /** I — unrelated psych/econ blocked on crop physiology. */
    public function test_matrix_i_unrelated_psych_econ_blocked(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما هو تأثير درجة الحرارة على نبات الزنجبيل؟',
        ]);
        $gate = app(ScientificEvidenceRelevanceGate::class);
        $this->assertFalse($gate->isRelevant(
            $plan,
            'Ginger psychology and consumer anxiety disorder therapy',
            'Psychological effects of ginger aroma on anxiety disorder patients.',
        ));
        $this->assertFalse($gate->isRelevant(
            $plan,
            'Ginger market economics and stock market volatility',
            'Economics of ginger commodity trading and stock market pricing.',
        ));
        $this->assertFalse($gate->isRelevant(
            $plan,
            'Effect of ginger and organic selenium on broiler chickens under high ambient temperature',
            'Ginger at 5 g/kg improved growth performance in broiler chickens exposed to high ambient temperature.',
        ));
    }

    public function test_adversarial_wrong_topic_wrong_crop_wrong_sense(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما أفضل درجة حرارة لإنبات بذور الطماطم؟',
        ]);
        $assessor = app(ScientificEvidenceDirectnessAssessor::class);
        $matcher = app(ClaimEvidenceMatcher::class);

        // Entity correct, topic wrong
        $wrongTopic = $assessor->assess(
            $plan,
            'Potassium deficiency in Solanum lycopersicum leaves',
            'Tomato potassium nutrition and leaf chlorosis under greenhouse fertigation.',
        );
        $this->assertNotSame(ScientificEvidenceDirectnessAssessor::DIRECT, $wrongTopic['directness']);

        // Topic correct, crop wrong
        $wrongCrop = $assessor->assess(
            $plan,
            'Effect of temperature on wheat seed germination',
            'Triticum aestivum seed germination under temperature regimes.',
        );
        $this->assertSame(ScientificEvidenceDirectnessAssessor::IRRELEVANT, $wrongCrop['directness']);

        // Entity+topic present but sense wrong (growth vs germination)
        $wrongSense = $assessor->assess(
            $plan,
            'Tomato growth under different temperatures',
            'Solanum lycopersicum vegetative growth responds to temperature in agriculture.',
        );
        $this->assertSame(ScientificEvidenceDirectnessAssessor::SUPPORTING, $wrongSense['directness']);

        // Incidental keyword overlap
        $incidental = new ScientificSearchResult(
            'openalex', 'W-inc', 'Laboratory apparatus temperature logging near tomato samples',
            ['A'], 2021, '10.1000/inc', 'https://doi.org/10.1000/inc',
            'Instrument thermal sensor readings near tomato tissue in a laboratory reactor.',
            'Journal', ['openalex'],
        );
        $match = $matcher->match(
            $plan,
            $incidental,
            'Instrument thermal sensor readings near tomato tissue in a laboratory reactor.',
            EvidenceValidationStatus::SCIENTIFICALLY_TRUSTWORTHY,
        );
        $this->assertSame(ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE, $match['relationship']);
    }

    public function test_adversarial_sole_background_not_composed_as_answer(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما أفضل درجة حرارة لإنبات بذور الطماطم؟',
        ]);
        $composer = app(AnswerComposer::class);

        $background = $this->usableEvidence(
            'bg',
            'Tomato fields occur in many climates; sewage sludge also affects tomato biomass at 25 C.',
            ClaimEvidenceRelationship::PARTIALLY_SUPPORTED,
            [
                'publicationTitle' => 'Sewage sludge effect on tomato growth at ambient temperature',
                'doi' => '10.1000/bg-tomato',
                'directness' => ScientificEvidenceDirectnessAssessor::BACKGROUND,
            ],
        );

        $report = $composer->compose($plan, $this->validationReport([$background]));
        $this->assertContains($report->status, ['no_validated_evidence', 'insufficient_evidence']);
        $this->assertStringNotContainsString('25', $report->answer);
        $this->assertStringNotContainsString('sewage', mb_strtolower($report->answer));
    }

    public function test_adversarial_direct_preferred_over_supporting_for_summary(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما أفضل درجة حرارة لإنبات بذور الطماطم؟',
        ]);
        $composer = app(AnswerComposer::class);

        $supporting = $this->usableEvidence(
            'sup',
            'Tomato vegetative growth increases under warm temperature regimes in field agriculture.',
            ClaimEvidenceRelationship::PARTIALLY_SUPPORTED,
            [
                'publicationTitle' => 'Tomato growth under different temperatures',
                'doi' => '10.1000/sup-growth',
                'directness' => ScientificEvidenceDirectnessAssessor::SUPPORTING,
            ],
        );
        $direct = $this->usableEvidence(
            'dir',
            'Solanum lycopersicum seed germination is optimal near 25 C under controlled temperature regimes.',
            ClaimEvidenceRelationship::SUPPORTED,
            [
                'publicationTitle' => 'Effect of temperature on tomato seed germination',
                'doi' => '10.1000/dir-germ',
                'directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
            ],
        );

        $report = $composer->compose($plan, $this->validationReport([$supporting, $direct]));
        $this->assertNotSame('no_validated_evidence', $report->status);
        $this->assertNotSame('insufficient_evidence', $report->status);
        $this->assertStringContainsString('germination', mb_strtolower($report->answer));
        $this->assertStringContainsString('25', $report->answer);
    }

    public function test_multi_query_variants_executed_shape(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما هو تأثير درجة الحرارة على نبات الزنجبيل؟',
        ]);
        $variants = app(ScientificSearchQueryBuilder::class)->buildVariantsFromPlan($plan);
        $this->assertGreaterThanOrEqual(2, count($variants));
        $this->assertLessThanOrEqual(5, count($variants));
        $this->assertSame($variants[0], app(ScientificSearchQueryBuilder::class)->buildFromPlan($plan));
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
            validatorsUsed: [],
            qualityDistribution: [],
            searchSummary: [],
            observability: [],
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function usableEvidence(
        string $evidenceId,
        string $text,
        string $relationship,
        array $overrides = [],
    ): ScientificEvidenceItem {
        $directness = $overrides['directness'] ?? ScientificEvidenceDirectnessAssessor::DIRECT;

        return new ScientificEvidenceItem(
            evidenceId: $evidenceId,
            sourceId: (string) ($overrides['sourceId'] ?? 'source-'.$evidenceId),
            sourceKey: 'openalex',
            sourceType: (string) ($overrides['sourceType'] ?? 'university_research'),
            publicationTitle: (string) ($overrides['publicationTitle'] ?? 'Scientific publication title'),
            authors: ['Dr Researcher'],
            institution: (string) ($overrides['institution'] ?? 'University of Agriculture'),
            journal: 'Journal of Agronomy',
            doi: array_key_exists('doi', $overrides) ? $overrides['doi'] : '10.1000/'.$evidenceId,
            url: array_key_exists('url', $overrides) ? $overrides['url'] : 'https://doi.org/10.1000/'.$evidenceId,
            publicationYear: 2023,
            retrievedAt: now()->toIso8601String(),
            agriculturalDomain: 'field_crops',
            claimTopic: 'topic',
            evidenceText: $text,
            validationStatus: EvidenceValidationStatus::EVIDENCE_USABLE,
            validationFailures: [],
            claimRelationship: $relationship,
            confidence: 0.8,
            qualityScore: 75.0,
            qualityFactors: [
                'not_scientific_certainty' => true,
                'evidence_directness' => $directness,
            ],
            sourceAttribution: [
                'organization' => 'University of Agriculture',
                'source_type' => 'university_research',
                'evidence_directness' => $directness,
            ],
        );
    }
}
