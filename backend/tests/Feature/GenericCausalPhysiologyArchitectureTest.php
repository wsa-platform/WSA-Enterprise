<?php

namespace Tests\Feature;

use App\Services\Agriculture\Research\AgriculturalEntityCatalog;
use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\QueryUnderstandingService;
use App\Services\Agriculture\Research\ResearchPlanner;
use App\Services\Agriculture\Research\Search\AgriculturalScientificSearchService;
use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use App\Services\Agriculture\Research\Search\ScientificSearchQueryBuilder;
use App\Services\Agriculture\Research\Synthesis\AnswerComposer;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use App\Services\Agriculture\Research\Validation\EvidenceValidationExecutionReport;
use App\Services\Agriculture\Research\Validation\EvidenceValidationStatus;
use App\Services\Agriculture\Research\Validation\ScientificEvidenceItem;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Generic causal plant-physiology architecture: كيف تؤثر vs كيف أزرع,
 * entity-less salinity→uptake questions, identity-free DIRECT, and factual Stage 5.
 *
 * Uses HEAD-stable AgriculturalKnowledgeQuery fields (cropId, scientificName, subject,
 * constraints) plus Q2-owned catalog/QUS/builder/directness/composer APIs.
 */
class GenericCausalPhysiologyArchitectureTest extends TestCase
{
    private const CANONICAL_Q2 = 'كيف تؤثر ملوحة التربة على امتصاص الماء بواسطة النبات؟';

    private const D7 = 'كيف تؤثر الملوحة على امتصاص الماء في النبات؟';

    public function test_q2_canonical_understanding_is_entity_less_causal_physiology(): void
    {
        $understood = $this->understand(self::CANONICAL_Q2);

        $this->assertEntityLess($understood);
        $this->assertSame('causes', $understood->constraints['question_type'] ?? null);
        $this->assertSame('salinity_physiology', $understood->constraints['scientific_sense'] ?? null);
        $this->assertSame('effect', $understood->constraints['scientific_intent_qualifier'] ?? null);
        $this->assertNotSame('cultivation', $understood->researchIntent);
        $this->assertNotSame('irrigation', $understood->researchIntent);
        $this->assertSame(AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR, $understood->ambiguityState);
        $this->assertNotSame('soil', $understood->subject['type'] ?? null);
        $this->assertNotSame('land', $understood->subject['type'] ?? null);
        $this->assertTrue(AgriculturalEntityCatalog::asksCausalAffectQuestion(self::CANONICAL_Q2));
        $this->assertFalse(AgriculturalEntityCatalog::asksHowToProcedureQuestion(self::CANONICAL_Q2));
    }

    public function test_q2_question_type_is_causes(): void
    {
        $this->assertSame('causes', $this->understand(self::CANONICAL_Q2)->constraints['question_type'] ?? null);
        $this->assertNotSame('quantity', $this->understand(self::CANONICAL_Q2)->constraints['question_type'] ?? null);
        $this->assertNotSame('recommendation', $this->understand(self::CANONICAL_Q2)->constraints['question_type'] ?? null);
    }

    public function test_q2_required_evidence_type_is_causal_relationship(): void
    {
        $this->assertSame(
            'causal_relationship',
            $this->understand(self::CANONICAL_Q2)->constraints['required_evidence_type'] ?? null,
        );
    }

    public function test_q2_entity_less_state_does_not_invent_crop(): void
    {
        $understood = $this->understand(self::CANONICAL_Q2);
        $this->assertEntityLess($understood);
        $this->assertNotSame('wheat', $understood->cropId);
        $this->assertNotSame('pomegranate', $understood->cropId);
        $this->assertNotSame('tomato', $understood->cropId);
        $this->assertNotSame('maize', $understood->cropId);
    }

    public function test_q2_sense_remains_salinity_physiology(): void
    {
        $this->assertSame('salinity_physiology', $this->understand(self::CANONICAL_Q2)->constraints['scientific_sense'] ?? null);
    }

    public function test_q2_requested_factors_distinguish_salinity_and_uptake(): void
    {
        $understood = $this->understand(self::CANONICAL_Q2);
        $factors = $understood->constraints['scientific_factors'] ?? [];
        $roles = $understood->constraints['scientific_factor_roles'] ?? [];
        $this->assertContains('salinity', $factors);
        $this->assertContains('water', $factors);
        $this->assertSame('requested', $roles['salinity'] ?? null);
        $this->assertSame('requested', $roles['water'] ?? null);
    }

    public function test_q2_salinity_is_requested_causal_factor(): void
    {
        $understood = $this->understand(self::CANONICAL_Q2);
        $this->assertSame('requested', $understood->constraints['scientific_factor_roles']['salinity'] ?? null);
        $this->assertSame('causes', $understood->constraints['question_type'] ?? null);
    }

    public function test_howto_kayfa_does_not_become_causes(): void
    {
        $plant = $this->understand('كيف أزرع القمح؟');
        $this->assertSame('wheat', $plant->cropId);
        $this->assertNotSame('causes', $plant->constraints['question_type'] ?? null);
        $this->assertSame('recommendation', $plant->constraints['question_type'] ?? null);
        $this->assertTrue(AgriculturalEntityCatalog::asksHowToProcedureQuestion('كيف أزرع القمح؟'));
        $this->assertFalse(AgriculturalEntityCatalog::asksCausalAffectQuestion('كيف أزرع القمح؟'));

        $irrigateWheat = $this->understand('كيف أروي القمح؟');
        $this->assertSame('wheat', $irrigateWheat->cropId);
        $this->assertSame('recommendation', $irrigateWheat->constraints['question_type'] ?? null);
        $this->assertNotSame('causes', $irrigateWheat->constraints['question_type'] ?? null);

        $irrigate = $this->understand('كيف أروي الطماطم؟');
        $this->assertSame('tomato', $irrigate->cropId);
        $this->assertSame('recommendation', $irrigate->constraints['question_type'] ?? null);

        $fertilizer = $this->understand('كيف أستخدم السماد؟');
        $this->assertSame('recommendation', $fertilizer->constraints['question_type'] ?? null);
        $this->assertNotSame('causes', $fertilizer->constraints['question_type'] ?? null);
    }

    public function test_english_causal_and_howto_disambiguation(): void
    {
        $causal = $this->understand('How does soil salinity affect plant water uptake?');
        $this->assertSame('causes', $causal->constraints['question_type'] ?? null);
        $this->assertEntityLess($causal);

        $absorption = $this->understand('How does soil salinity affect water absorption by plants?');
        $this->assertSame('causes', $absorption->constraints['question_type'] ?? null);

        $howtoWheat = $this->understand('How do I irrigate wheat?');
        $this->assertSame('wheat', $howtoWheat->cropId);
        $this->assertSame('recommendation', $howtoWheat->constraints['question_type'] ?? null);
        $this->assertNotSame('causes', $howtoWheat->constraints['question_type'] ?? null);

        $howto = $this->understand('How do I irrigate tomatoes?');
        $this->assertSame('tomato', $howto->cropId);
        $this->assertSame('recommendation', $howto->constraints['question_type'] ?? null);
    }

    public function test_wheat_salinity_effect_remains_named_crop_causes(): void
    {
        $wheat = $this->understand('كيف تؤثر ملوحة التربة على نمو القمح؟');
        $this->assertSame('wheat', $wheat->cropId);
        $this->assertSame('causes', $wheat->constraints['question_type'] ?? null);
        $this->assertSame('causal_relationship', $wheat->constraints['required_evidence_type'] ?? null);
        $this->assertSame('salinity_physiology', $wheat->constraints['scientific_sense'] ?? null);

        $effect = $this->understand('ما تأثير الملوحة على القمح؟');
        $this->assertSame('wheat', $effect->cropId);
        $this->assertSame('causes', $effect->constraints['question_type'] ?? null);
    }

    public function test_kam_does_not_match_inside_kayfa(): void
    {
        $signals = AgriculturalEntityCatalog::questionTypeSignals();
        $this->assertContains('كيف', $signals['recommendation']);
        $this->assertContains('كم', $signals['quantity']);

        $q2 = $this->understand(self::CANONICAL_Q2);
        $this->assertSame('causes', $q2->constraints['question_type'] ?? null);
        $this->assertNotSame('quantity', $q2->constraints['question_type'] ?? null);

        $quantity = $this->understand('كم كمية السماد اللازمة للقمح؟');
        $this->assertSame('quantity', $quantity->constraints['question_type'] ?? null);
        $this->assertNotSame('causes', $quantity->constraints['question_type'] ?? null);
    }

    public function test_q2_and_d7_share_entity_less_causal_class(): void
    {
        $q2 = $this->understand(self::CANONICAL_Q2);
        $d7 = $this->understand(self::D7);

        foreach ([$q2, $d7] as $understood) {
            $this->assertEntityLess($understood);
            $this->assertSame('causes', $understood->constraints['question_type'] ?? null);
            $this->assertSame('causal_relationship', $understood->constraints['required_evidence_type'] ?? null);
            $this->assertSame('salinity_physiology', $understood->constraints['scientific_sense'] ?? null);
            $this->assertSame(AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR, $understood->ambiguityState);
        }
    }

    public function test_soil_inventory_and_management_are_not_forced_entity_less_causal(): void
    {
        $land = $this->understand('ما أنواع الأراضي الزراعية في مصر؟');
        $this->assertSame('land_classification', $land->constraints['scientific_sense'] ?? $land->researchIntent);
        $this->assertNotSame('causes', $land->constraints['question_type'] ?? null);

        $soil = $this->understand('How should soil fertility management be planned for cereal crops?');
        $this->assertNotSame('causes', $soil->constraints['question_type'] ?? null);
    }

    public function test_q2_search_variants_preserve_physiology_target(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery(['query' => self::CANONICAL_Q2]);
        $variants = app(ScientificSearchQueryBuilder::class)->buildVariantsFromPlan($plan);
        $this->assertNotEmpty($variants);
        $this->assertGreaterThanOrEqual(2, count($variants));
        $this->assertLessThanOrEqual(5, count($variants));

        $executed = array_slice($variants, 0, 2);
        $joined = mb_strtolower(implode(' | ', $executed));
        $this->assertTrue($this->mentionsSalinity($joined), $joined);
        $this->assertTrue($this->mentionsUptake($joined), $joined);
        $this->assertTrue(str_contains($joined, 'plant') || str_contains($joined, 'physiology'), $joined);

        foreach ($executed as $variant) {
            $normalized = mb_strtolower($variant);
            $this->assertFalse(
                $this->isClimateOnlyVariant($normalized),
                'Leading variant is climate-only: '.$variant,
            );
            $this->assertFalse(
                $this->isYieldOnlyVariant($normalized),
                'Leading variant is yield-only: '.$variant,
            );
        }
    }

    public function test_q2_does_not_use_constraint_first_for_causal_physiology(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery(['query' => self::CANONICAL_Q2]);
        $first = mb_strtolower((string) (app(ScientificSearchQueryBuilder::class)->buildVariantsFromPlan($plan)[0] ?? ''));
        $this->assertTrue($this->mentionsUptake($first) || $this->mentionsSalinity($first), $first);
        $this->assertFalse($this->isClimateOnlyVariant($first), $first);
    }

    public function test_q2_scientific_terminology_not_arabic_literal_only(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery(['query' => self::CANONICAL_Q2]);
        $joined = mb_strtolower(implode(' | ', app(ScientificSearchQueryBuilder::class)->buildVariantsFromPlan($plan)));
        $this->assertTrue($this->mentionsSalinity($joined), $joined);
        $this->assertTrue($this->mentionsUptake($joined), $joined);
        $this->assertFalse(str_contains($joined, 'كيف تؤثر ملوحة'), $joined);
    }

    public function test_q5_saline_constraint_behavior_remains(): void
    {
        $q5 = $this->understand('ما أفضل المحاصيل التي يمكن زراعتها في الأراضي الصحراوية ذات المياه المالحة؟');
        $this->assertSame('recommendation', $q5->constraints['question_type'] ?? null);
        $this->assertNull($q5->cropId);
        $types = $this->constraintTypes($q5);
        $this->assertContains('arid_environment', $types);
        $this->assertContains('saline_water', $types);
        $this->assertFalse(AgriculturalEntityCatalog::asksCausalAffectQuestion(
            'ما أفضل المحاصيل التي يمكن زراعتها في الأراضي الصحراوية ذات المياه المالحة؟',
        ));
    }

    public function test_q2_directness_requires_salinity_and_uptake(): void
    {
        $plan = $this->plan(self::CANONICAL_Q2);
        $correct = $this->direct(
            $plan,
            'Salinity effects on plant water uptake and osmotic adjustment',
            'This study measures how salinity reduces water absorption in plants through osmotic and ionic effects.',
        );
        $this->assertSame(ScientificEvidenceDirectnessAssessor::DIRECT, $correct['directness']);
    }

    public function test_q2_correct_salinity_uptake_physiology_is_direct_eligible(): void
    {
        $this->test_q2_directness_requires_salinity_and_uptake();
    }

    public function test_q2_wue_plus_salt_is_not_direct(): void
    {
        $direct = $this->direct(
            $this->plan(self::CANONICAL_Q2),
            'Salinity and water use efficiency in maize yield',
            'Salt stress reduced maize yield and water use efficiency without measuring root water absorption.',
        );
        $this->assertNotSame(ScientificEvidenceDirectnessAssessor::DIRECT, $direct['directness']);
    }

    public function test_q2_yield_only_is_not_direct(): void
    {
        $direct = $this->direct(
            $this->plan(self::CANONICAL_Q2),
            'Salinity effects on plant yield under salt stress',
            'Salt stress reduced shoot biomass and grain yield under saline irrigation.',
        );
        $this->assertNotSame(ScientificEvidenceDirectnessAssessor::DIRECT, $direct['directness']);
    }

    public function test_q2_negated_absorption_is_not_direct(): void
    {
        $direct = $this->direct(
            $this->plan(self::CANONICAL_Q2),
            'Salinity effects on plant yield under salt stress',
            'Salt stress reduced shoot biomass and grain yield. No water absorption measurements were reported.',
        );
        $this->assertNotSame(ScientificEvidenceDirectnessAssessor::DIRECT, $direct['directness']);
    }

    public function test_q2_generic_water_is_not_direct(): void
    {
        $direct = $this->direct(
            $this->plan(self::CANONICAL_Q2),
            'Irrigation water availability in dryland agriculture',
            'This paper discusses water availability and irrigation water supply without salinity physiology.',
        );
        $this->assertNotSame(ScientificEvidenceDirectnessAssessor::DIRECT, $direct['directness']);
    }

    public function test_q2_tomato_irrigation_is_not_direct(): void
    {
        $direct = $this->direct(
            $this->plan(self::CANONICAL_Q2),
            'Drip irrigation scheduling for tomato in arid regions',
            'Solanum lycopersicum irrigation quantity under arid conditions without salinity.',
        );
        $this->assertNotSame(ScientificEvidenceDirectnessAssessor::DIRECT, $direct['directness']);
    }

    public function test_q2_wheat_yield_salinity_is_not_direct_for_uptake(): void
    {
        $direct = $this->direct(
            $this->plan(self::CANONICAL_Q2),
            'Salinity stress effects on Triticum aestivum yield and physiology',
            'Wheat salinity tolerance reduces growth and yield under saline irrigation.',
        );
        $this->assertNotSame(ScientificEvidenceDirectnessAssessor::DIRECT, $direct['directness']);
    }

    public function test_q2_bare_water_signal_is_not_an_uptake_synonym(): void
    {
        $signals = AgriculturalEntityCatalog::physiologyWaterUptakeSignals();
        $this->assertNotContains('water', $signals);
        $this->assertNotContains('water use', $signals);
        $this->assertNotContains('irrigation', $signals);
        $this->assertContains('water uptake', $signals);
        $this->assertContains('water absorption', $signals);

        $synonyms = AgriculturalEntityCatalog::scientificSynonymsForFactor('water', 'salinity_physiology');
        $this->assertNotContains('water', $synonyms);
        $this->assertNotContains('water use', $synonyms);
    }

    public function test_q2_supporting_only_is_not_sufficient(): void
    {
        $plan = $this->plan(self::CANONICAL_Q2);
        $report = app(AnswerComposer::class)->compose($plan, $this->validationReport([
            $this->usableItem(
                'Salinity stress effects on Triticum aestivum yield and physiology',
                'Wheat salinity tolerance reduces growth and yield under saline irrigation.',
                '10.1000/wheat-yield-salinity',
                ['entity_matched' => false, 'topic_matched' => true, 'evidence_directness' => ScientificEvidenceDirectnessAssessor::SUPPORTING],
            ),
        ]));
        $this->assertFalse((bool) ($report->researchMetadata['evidence_sufficient'] ?? false));
    }

    public function test_q2_correct_direct_may_be_sufficient(): void
    {
        $plan = $this->plan(self::CANONICAL_Q2);
        $report = app(AnswerComposer::class)->compose($plan, $this->validationReport([
            $this->usableItem(
                'Salinity effects on plant water uptake and osmotic adjustment',
                'This study measures how salinity reduces water absorption in plants through osmotic and ionic effects.',
                '10.1000/salinity-uptake',
                ['entity_matched' => false, 'topic_matched' => true, 'evidence_directness' => ScientificEvidenceDirectnessAssessor::DIRECT],
            ),
        ]));
        $this->assertTrue((bool) ($report->researchMetadata['evidence_sufficient'] ?? false));
    }

    public function test_q2_mixed_evidence_sufficiency_uses_only_qualifying_direct(): void
    {
        $plan = $this->plan(self::CANONICAL_Q2);
        $composer = app(AnswerComposer::class);

        $mixed = $composer->compose($plan, $this->validationReport([
            $this->usableItem(
                'Salinity effects on plant water uptake and osmotic adjustment',
                'This study measures how salinity reduces water absorption in plants through osmotic and ionic effects.',
                '10.1000/salinity-uptake-mixed',
                ['entity_matched' => false, 'topic_matched' => true, 'evidence_directness' => ScientificEvidenceDirectnessAssessor::DIRECT],
            ),
            $this->usableItem(
                'Salinity stress effects on Triticum aestivum yield and physiology',
                'Wheat salinity tolerance reduces growth and yield under saline irrigation.',
                '10.1000/wheat-yield-mixed',
                ['entity_matched' => false, 'topic_matched' => true, 'evidence_directness' => ScientificEvidenceDirectnessAssessor::SUPPORTING],
            ),
        ]));
        $this->assertTrue((bool) ($mixed->researchMetadata['evidence_sufficient'] ?? false));

        $supportingOnly = $composer->compose($plan, $this->validationReport([
            $this->usableItem(
                'Salinity stress effects on Triticum aestivum yield and physiology',
                'Wheat salinity tolerance reduces growth and yield under saline irrigation.',
                '10.1000/wheat-yield-only-mix',
                ['entity_matched' => false, 'topic_matched' => true, 'evidence_directness' => ScientificEvidenceDirectnessAssessor::SUPPORTING],
            ),
        ]));
        $this->assertFalse((bool) ($supportingOnly->researchMetadata['evidence_sufficient'] ?? false));
    }

    public function test_q2_insufficient_when_no_usable_direct(): void
    {
        $plan = $this->plan(self::CANONICAL_Q2);
        $report = app(AnswerComposer::class)->compose($plan, $this->validationReport([]));
        $this->assertFalse((bool) ($report->researchMetadata['evidence_sufficient'] ?? false));
    }

    public function test_q2_provider_failure_isolates_and_falls_back(): void
    {
        Http::fake([
            'api.openalex.org/works*' => Http::response(['results' => []], 429),
            'api.semanticscholar.org/graph/v1/paper/search*' => Http::response(['data' => []], 200),
            'api.crossref.org/works*' => Http::response(['message' => ['items' => [[
                'DOI' => '10.1000/salinity-uptake-fallback',
                'title' => ['Salinity effects on plant water uptake and osmotic adjustment'],
                'abstract' => 'This study measures how salinity reduces water absorption in plants through osmotic and ionic effects.',
                'publisher' => 'University of Agriculture',
                'container-title' => ['Journal of Plant Physiology'],
                'issued' => ['date-parts' => [[2022]]],
                'author' => [['given' => 'A', 'family' => 'Researcher']],
            ]]]], 200),
        ]);

        $plan = $this->plan(self::CANONICAL_Q2);
        $report = app(AgriculturalScientificSearchService::class)->search($plan);
        $this->assertContains('crossref', $report->successfulSources);
        $this->assertNotSame('all_sources_failed', $report->status);
        $this->assertGreaterThan(0, count($report->results));
    }

    public function test_production_pipeline_has_no_q2_literal_branches(): void
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
            $this->assertDoesNotMatchRegularExpression('/if\s*\([^)]*(?:Q2|كيف تؤثر ملوحة|امتصاص الماء بواسطة النبات)/u', $source, $path);
        }
    }

    /**
     * Entity-less on HEAD-stable fields: no cropId, no scientific name, subject is not a crop.
     */
    private function assertEntityLess(object $understood): void
    {
        $this->assertNull($understood->cropId);
        $this->assertNull($understood->scientificName);
        $this->assertNotSame('crop', $understood->subject['type'] ?? null);
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

    private function mentionsSalinity(string $joined): bool
    {
        return str_contains($joined, 'salinity')
            || str_contains($joined, 'salt stress')
            || str_contains($joined, 'saline');
    }

    private function mentionsUptake(string $joined): bool
    {
        return str_contains($joined, 'water uptake')
            || str_contains($joined, 'water absorption')
            || str_contains($joined, 'osmotic')
            || str_contains($joined, 'plant water relations')
            || str_contains($joined, 'root water uptake');
    }

    private function isClimateOnlyVariant(string $normalized): bool
    {
        $hasClimate = str_contains($normalized, 'climate') || str_contains($normalized, 'environmental requirements');
        $hasPhysiology = $this->mentionsUptake($normalized) || str_contains($normalized, 'physiology');

        return $hasClimate && ! $this->mentionsSalinity($normalized) && ! $hasPhysiology;
    }

    private function isYieldOnlyVariant(string $normalized): bool
    {
        return str_contains($normalized, 'yield')
            && ! $this->mentionsUptake($normalized)
            && ! str_contains($normalized, 'physiology');
    }

    /**
     * @return list<string>
     */
    private function constraintTypes(object $understood): array
    {
        $constraints = $understood->constraints['environmental_constraints'] ?? [];
        if (! is_array($constraints)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn ($row): string => is_array($row) ? (string) ($row['type'] ?? '') : '',
            $constraints,
        )));
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
            claimTopic: 'salinity',
            evidenceText: $abstract,
            validationStatus: EvidenceValidationStatus::EVIDENCE_USABLE,
            validationFailures: [],
            claimRelationship: ClaimEvidenceRelationship::SUPPORTED,
            confidence: 0.8,
            qualityScore: 80.0,
            qualityFactors: array_merge([
                'entity_matched' => false,
                'topic_matched' => true,
            ], $quality),
            sourceAttribution: ['evidence_directness' => $quality['evidence_directness'] ?? ScientificEvidenceDirectnessAssessor::SUPPORTING],
        );
    }
}
