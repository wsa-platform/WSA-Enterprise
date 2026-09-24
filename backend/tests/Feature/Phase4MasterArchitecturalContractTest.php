<?php

namespace Tests\Feature;

use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use App\Services\Agriculture\Research\Search\ScientificEvidenceModality;
use App\Services\Agriculture\Research\Search\ScientificResultDeduplicator;
use App\Services\Agriculture\Research\Search\ScientificSearchExecutionReport;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;
use App\Services\Agriculture\Research\Validation\AgriculturalScientificValidationService;
use App\Services\Agriculture\Research\Validation\EvidenceQualityRanker;
use App\Services\Agriculture\Research\Validation\EvidenceValidationStatus;
use App\Services\Agriculture\Research\Validation\EvidenceVerificationLayer;
use App\Services\Agriculture\Research\Validation\ScientificEvidenceItem;
use Tests\TestCase;

/**
 * Phase 4 master architectural contracts: modality, observation, directness,
 * sufficiency, ranking, deduplication, traceability, and crop genericity.
 */
class Phase4MasterArchitecturalContractTest extends TestCase
{
    /**
     * @return list<list<string>>
     */
    public static function cropProvider(): array
    {
        return [
            ['wheat'],
            ['maize'],
            ['rice'],
            ['barley'],
            ['tomato'],
            ['potato'],
            ['strawberry'],
            ['lettuce'],
            ['uncatalogued_crop_x'],
        ];
    }

    /**
     * @return list<list<string>>
     */
    public static function questionClassProvider(): array
    {
        return [
            ['requirements'],
            ['cultivation'],
            ['irrigation'],
            ['temperature'],
            ['yield'],
            ['disease'],
            ['comparison'],
            ['geography'],
            ['time-specific'],
        ];
    }

    public function test_scholarly_direct_and_supporting_remain_distinct(): void
    {
        $plan = $this->quantityPlan('maize', 'Kenya', '2020', 'What irrigation methods improve maize yield?');
        $paper = $this->scholarlyResult(
            'Maize drip irrigation yield response in Kenya field trials',
            '10.9999/maize-direct',
            'Maize drip irrigation improved grain yield across multi-year field trials in Kenya.',
        );
        $background = $this->scholarlyResult(
            'General notes on African cereal agronomy',
            '10.9999/maize-support',
            'This review discusses cereal agronomy without a Kenya maize irrigation trial.',
        );

        $report = $this->validate($plan, [$paper, $background]);
        $items = array_merge($report->validatedEvidence, $report->rejectedEvidence, $report->retainedEvidence);
        $this->assertNotEmpty($items);
        foreach ($items as $item) {
            $this->assertNotSame('direct_statistical', $item->qualityFactors['evidence_directness'] ?? null);
            $this->assertSame(
                ScientificEvidenceModality::SCHOLARLY,
                $item->qualityFactors['evidence_modality'] ?? null,
            );
            $this->assertSame('same_species', $item->qualityFactors['species_relation'] ?? null);
        }

        $direct = new ScientificEvidenceItem(
            evidenceId: 'scholarly-direct',
            sourceId: 'd1',
            sourceKey: 'openalex',
            sourceType: 'journal',
            publicationTitle: 'Maize irrigation trial',
            authors: ['A'],
            institution: null,
            journal: 'Agronomy Journal',
            doi: '10.9999/direct-item',
            url: 'https://example.test/direct-item',
            publicationYear: 2021,
            retrievedAt: now()->toIso8601String(),
            agriculturalDomain: 'agronomy',
            claimTopic: 'irrigation',
            evidenceText: 'Maize drip irrigation improved yield.',
            validationStatus: EvidenceValidationStatus::EVIDENCE_USABLE,
            validationFailures: [],
            claimRelationship: 'supported',
            confidence: 0.8,
            qualityScore: 70.0,
            qualityFactors: [
                'evidence_directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
                'evidence_modality' => ScientificEvidenceModality::SCHOLARLY,
            ],
            sourceAttribution: [],
        );
        $supporting = new ScientificEvidenceItem(
            evidenceId: 'scholarly-support',
            sourceId: 's1',
            sourceKey: 'openalex',
            sourceType: 'journal',
            publicationTitle: 'Cereal review',
            authors: ['A'],
            institution: null,
            journal: 'Agronomy Journal',
            doi: '10.9999/support-item',
            url: 'https://example.test/support-item',
            publicationYear: 2019,
            retrievedAt: now()->toIso8601String(),
            agriculturalDomain: 'agronomy',
            claimTopic: 'agronomy',
            evidenceText: 'General cereal notes.',
            validationStatus: EvidenceValidationStatus::EVIDENCE_USABLE,
            validationFailures: [],
            claimRelationship: 'partially_supported',
            confidence: 0.3,
            qualityScore: 90.0,
            qualityFactors: [
                'evidence_directness' => ScientificEvidenceDirectnessAssessor::SUPPORTING,
                'evidence_modality' => ScientificEvidenceModality::SCHOLARLY,
            ],
            sourceAttribution: [],
        );
        $ranked = app(EvidenceQualityRanker::class)->rank([$supporting, $direct]);
        $this->assertSame('scholarly-direct', $ranked[0]->evidenceId);
        $this->assertSame(ScientificEvidenceDirectnessAssessor::SUPPORTING, $ranked[1]->qualityFactors['evidence_directness']);
    }

    public function test_statistical_direct_preserves_observation_bag_and_null_publication_year(): void
    {
        $plan = $this->quantityPlan('rice', 'Egypt', '2020', 'How much rice production in Egypt 2020?');
        $stat = $this->statisticalResult('Rice', 'Egypt', '2020', 'Production', 't', '4800000');

        $report = $this->validate($plan, [$stat]);
        $this->assertNotEmpty($report->validatedEvidence);
        $item = $report->validatedEvidence[0];
        $observation = $item->qualityFactors['observation'] ?? [];

        $this->assertTrue($report->evidenceSufficient);
        $this->assertSame(ScientificEvidenceDirectnessAssessor::DIRECT, $item->qualityFactors['evidence_directness'] ?? null);
        $this->assertSame(ScientificEvidenceModality::DIRECT_STATISTICAL, $item->qualityFactors['evidence_modality'] ?? null);
        $this->assertNull($item->publicationYear);
        $this->assertSame('2020', (string) ($observation['year'] ?? ''));
        $this->assertSame('Egypt', (string) ($observation['area'] ?? ''));
        $this->assertSame('Rice', (string) ($observation['item'] ?? ''));
        $this->assertSame('Production', (string) ($observation['element'] ?? ''));
        $this->assertSame('t', (string) ($observation['unit'] ?? ''));
        $this->assertSame('4800000', (string) ($observation['value'] ?? ''));
        $this->assertSame(['national_stat'], $item->sourceAttribution['provenance']['found_by_sources'] ?? null);
        $this->assertArrayHasKey('observation', $item->qualityFactors);
        $this->assertSame('2020', (string) ($item->qualityFactors['observation_year'] ?? ''));
    }

    public function test_statistical_supporting_or_mismatch_is_not_promoted_to_direct(): void
    {
        $plan = $this->quantityPlan('barley', 'Spain', '2018', 'How much barley production in Spain 2018?');
        $wrong = $this->statisticalResult('Rice', 'Spain', '2018', 'Production', 't', '1000');

        $report = $this->validate($plan, [$wrong]);
        $this->assertFalse($report->evidenceSufficient);
        $this->assertSame(0, (int) ($report->searchSummary['direct_evidence_count'] ?? -1));
        $this->assertNotEmpty($report->rejectedEvidence);
        $this->assertNotSame(
            ScientificEvidenceDirectnessAssessor::DIRECT,
            $report->rejectedEvidence[0]->qualityFactors['evidence_directness'] ?? null,
        );
    }

    public function test_distinct_statistical_observations_are_not_merged(): void
    {
        $a = $this->statisticalResult('Potato', 'France', '2019', 'Production', 't', '1000');
        $b = $this->statisticalResult('Potato', 'France', '2019', 'Yield', 't/ha', '3.2', 'national_stat_b');
        $c = $this->statisticalResult('Potato', 'France', '2020', 'Production', 't', '1100', 'national_stat_c');

        $deduped = app(ScientificResultDeduplicator::class)->deduplicate([$a, $b, $c]);
        $this->assertCount(3, $deduped);
    }

    public function test_shared_statistical_query_url_does_not_collapse_distinct_values(): void
    {
        $left = $this->statisticalResult('Lettuce', 'Spain', '2021', 'Production', 't', '10');
        $right = new ScientificSearchResult(
            sourceKey: 'national_stat',
            sourceIdentifier: 'lettuce-spain-2021-alt',
            title: 'Lettuce — Production — Spain — 2021',
            authors: [],
            publicationYear: null,
            doi: null,
            canonicalUrl: $left->canonicalUrl,
            abstract: 'Value: 99 t',
            journal: null,
            foundBySources: ['national_stat'],
            relevanceMetadata: [
                'evidence_type' => ScientificEvidenceModality::DIRECT_STATISTICAL,
                'not_literature' => true,
            ],
            rawMetadata: [
                'observation' => [
                    'item' => 'Lettuce',
                    'area' => 'Spain',
                    'year' => '2021',
                    'element' => 'Production',
                    'unit' => 't',
                    'value' => '99',
                ],
            ],
        );

        $deduped = app(ScientificResultDeduplicator::class)->deduplicate([$left, $right]);
        $this->assertCount(2, $deduped);
    }

    public function test_scholarly_merge_preserves_union_provenance(): void
    {
        $openalex = $this->scholarlyResult(
            'Tomato salinity physiology',
            '10.1000/tomato-shared',
            'Tomato salinity physiology under irrigation.',
            'openalex',
        );
        $crossref = new ScientificSearchResult(
            sourceKey: 'crossref',
            sourceIdentifier: 'crossref-tomato',
            title: 'Tomato salinity physiology',
            authors: ['B Author'],
            publicationYear: 2021,
            doi: '10.1000/tomato-shared',
            canonicalUrl: 'https://doi.org/10.1000/tomato-shared',
            abstract: 'Tomato salinity physiology under irrigation.',
            journal: 'Ag Journal',
            foundBySources: ['crossref'],
            relevanceMetadata: ['provenance' => ['provider' => 'crossref']],
            rawMetadata: ['provenance' => ['provider' => 'crossref', 'query' => 'tomato salinity']],
        );

        $deduped = app(ScientificResultDeduplicator::class)->deduplicate([$openalex, $crossref]);
        $this->assertCount(1, $deduped);
        $this->assertEqualsCanonicalizing(['openalex', 'crossref'], $deduped[0]->foundBySources);
        $this->assertEqualsCanonicalizing(
            ['openalex', 'crossref'],
            $deduped[0]->rawMetadata['provenance']['found_by_sources'] ?? [],
        );
    }

    public function test_title_and_year_are_not_a_universal_scholarly_identity(): void
    {
        $a = $this->scholarlyResult('Shared agronomy title', '10.1000/one', 'Paper one.', 'openalex');
        $b = $this->scholarlyResult('Shared agronomy title', '10.1000/two', 'Paper two.', 'semantic_scholar');

        $deduped = app(ScientificResultDeduplicator::class)->deduplicate([$a, $b]);
        $this->assertCount(2, $deduped);
    }

    public function test_item_dispositions_cover_every_candidate(): void
    {
        $plan = $this->quantityPlan('wheat', 'Egypt', '2020', 'How much wheat production in Egypt 2020?');
        $usable = $this->statisticalResult('Wheat', 'Egypt', '2020', 'Production', 't', '9000000');
        $rejected = $this->statisticalResult('Maize', 'Egypt', '2020', 'Production', 't', '100', 'national_stat_b');
        $report = $this->validate($plan, [$usable, $rejected]);

        $dispositions = $report->observability['item_dispositions'] ?? [];
        $this->assertCount(2, $dispositions);
        $this->assertEqualsCanonicalizing(
            ['usable', 'rejected'],
            array_column($dispositions, 'disposition'),
        );
        $accounted = count($report->validatedEvidence)
            + count($report->rejectedEvidence)
            + count($report->retainedEvidence);
        $this->assertSame(2, $accounted);
    }

    public function test_supporting_only_is_not_verified_answer_sufficient(): void
    {
        $supporting = new ScientificEvidenceItem(
            evidenceId: 'support-only',
            sourceId: 's1',
            sourceKey: 'openalex',
            sourceType: 'journal',
            publicationTitle: 'Background cereal review',
            authors: ['A'],
            institution: null,
            journal: 'Ag Journal',
            doi: '10.9999/support-only',
            url: 'https://example.test/support-only',
            publicationYear: 2020,
            retrievedAt: now()->toIso8601String(),
            agriculturalDomain: 'agronomy',
            claimTopic: 'agronomy',
            evidenceText: 'General cereal notes.',
            validationStatus: EvidenceValidationStatus::EVIDENCE_USABLE,
            validationFailures: [],
            claimRelationship: 'supported',
            confidence: 0.4,
            qualityScore: 40.0,
            qualityFactors: [
                'evidence_directness' => ScientificEvidenceDirectnessAssessor::SUPPORTING,
                'answer_eligible' => true,
                'evidence_modality' => ScientificEvidenceModality::SCHOLARLY,
            ],
            sourceAttribution: [],
        );
        $ranked = app(EvidenceQualityRanker::class)->rank([$supporting]);
        $this->assertSame(ScientificEvidenceDirectnessAssessor::SUPPORTING, $ranked[0]->qualityFactors['evidence_directness']);
        $this->assertFalse(app(EvidenceVerificationLayer::class)->isPrimaryCitationEligible(
            (string) $ranked[0]->qualityFactors['evidence_directness'],
        ));
    }

    public function test_citation_eligibility_is_direct_class_only(): void
    {
        $layer = app(EvidenceVerificationLayer::class);
        $this->assertTrue($layer->isPrimaryCitationEligible(ScientificEvidenceDirectnessAssessor::DIRECT));
        $this->assertTrue($layer->isPrimaryCitationEligible('direct_statistical'));
        $this->assertFalse($layer->isPrimaryCitationEligible(ScientificEvidenceDirectnessAssessor::SUPPORTING));
        $this->assertFalse($layer->isPrimaryCitationEligible(ScientificEvidenceDirectnessAssessor::RELATED));
    }

    public function test_validation_ranker_places_statistical_direct_ahead_of_supporting(): void
    {
        $directStat = new ScientificEvidenceItem(
            evidenceId: 'stat-direct',
            sourceId: 'obs',
            sourceKey: 'national_stat',
            sourceType: 'official_statistics',
            publicationTitle: 'Rice production Egypt 2020',
            authors: [],
            institution: null,
            journal: null,
            doi: null,
            url: 'https://stats.example.test/rice',
            publicationYear: null,
            retrievedAt: now()->toIso8601String(),
            agriculturalDomain: 'agronomy',
            claimTopic: 'Production',
            evidenceText: 'Value: 1 t',
            validationStatus: EvidenceValidationStatus::EVIDENCE_USABLE,
            validationFailures: [],
            claimRelationship: 'supported',
            confidence: 0.8,
            qualityScore: 20.0,
            qualityFactors: [
                'evidence_directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
                'evidence_modality' => ScientificEvidenceModality::DIRECT_STATISTICAL,
                'ranking_class' => 0,
            ],
            sourceAttribution: [],
        );
        $supporting = new ScientificEvidenceItem(
            evidenceId: 'paper-support',
            sourceId: 'doi',
            sourceKey: 'openalex',
            sourceType: 'journal',
            publicationTitle: 'High scoring supporting review',
            authors: ['A'],
            institution: null,
            journal: 'Ag Journal',
            doi: '10.9999/high-support',
            url: 'https://example.test/high-support',
            publicationYear: 2024,
            retrievedAt: now()->toIso8601String(),
            agriculturalDomain: 'agronomy',
            claimTopic: 'agronomy',
            evidenceText: 'Supporting review.',
            validationStatus: EvidenceValidationStatus::EVIDENCE_USABLE,
            validationFailures: [],
            claimRelationship: 'partially_supported',
            confidence: 0.9,
            qualityScore: 99.0,
            qualityFactors: [
                'evidence_directness' => ScientificEvidenceDirectnessAssessor::SUPPORTING,
                'evidence_modality' => ScientificEvidenceModality::SCHOLARLY,
                'ranking_class' => 1,
            ],
            sourceAttribution: [],
        );

        $ranked = app(EvidenceQualityRanker::class)->rank([$supporting, $directStat]);
        $this->assertSame('stat-direct', $ranked[0]->evidenceId);
        $this->assertSame('paper-support', $ranked[1]->evidenceId);
    }

    public function test_evl_has_no_wheat_specific_control_flow(): void
    {
        $source = file_get_contents(app_path('Services/Agriculture/Research/Validation/EvidenceVerificationLayer.php'));
        $this->assertIsString($source);
        $this->assertStringNotContainsString("'wheat production'", $source);
        $this->assertStringNotContainsString("'wheat yield'", $source);
        $this->assertDoesNotMatchRegularExpression('/if\s*\(\s*\$crop\s*===/', $source);
    }

    public function test_home_and_crop_share_statistical_validation_behavior(): void
    {
        $home = $this->quantityPlan('tomato', 'Spain', '2018', 'How much tomato production in Spain 2018?');
        $crop = $this->quantityPlan('tomato', 'Spain', '2018', 'How much tomato production in Spain 2018?', [
            'selected_crop_id' => 'tomato',
            'knowledge_option' => 'farming-needs',
        ]);
        $stat = $this->statisticalResult('Tomato', 'Spain', '2018', 'Production', 't', '5000');

        $homeReport = $this->validate($home, [$stat]);
        $cropReport = $this->validate($crop, [$stat]);
        $this->assertSame(
            $homeReport->validatedEvidence[0]->qualityFactors['evidence_directness'] ?? null,
            $cropReport->validatedEvidence[0]->qualityFactors['evidence_directness'] ?? null,
        );
        $this->assertSame(
            $homeReport->validatedEvidence[0]->qualityFactors['observation'] ?? null,
            $cropReport->validatedEvidence[0]->qualityFactors['observation'] ?? null,
        );
        $this->assertSame($homeReport->evidenceSufficient, $cropReport->evidenceSufficient);
    }

    /**
     * @dataProvider cropProvider
     */
    public function test_statistical_architecture_is_crop_generic(string $crop): void
    {
        $label = ucfirst($crop);
        $plan = $this->quantityPlan($crop, 'Italy', '2022', 'How much '.$crop.' production in Italy 2022?');
        $stat = $this->statisticalResult($label, 'Italy', '2022', 'Production', 't', '123');
        $report = $this->validate($plan, [$stat]);

        $this->assertSame(1, count($report->validatedEvidence) + count($report->rejectedEvidence) + count($report->retainedEvidence));
        $this->assertArrayHasKey('item_dispositions', $report->observability);
        $item = $report->validatedEvidence[0] ?? $report->rejectedEvidence[0] ?? null;
        $this->assertNotNull($item);
        $this->assertSame(ScientificEvidenceModality::DIRECT_STATISTICAL, $item->qualityFactors['evidence_modality'] ?? null);
        $this->assertNull($item->publicationYear);
        $this->assertSame('Italy', (string) (($item->qualityFactors['observation']['area'] ?? '')));
        $this->assertSame('2022', (string) (($item->qualityFactors['observation']['year'] ?? '')));
    }

    /**
     * @dataProvider questionClassProvider
     */
    public function test_question_class_does_not_invent_statistical_publication_year(string $questionClass): void
    {
        $plan = $this->quantityPlan('maize', 'Kenya', '2019', $questionClass.' question for maize in Kenya 2019');
        $stat = $this->statisticalResult('Maize', 'Kenya', '2019', 'Production', 't', '77');
        $report = $this->validate($plan, [$stat]);
        $item = $report->validatedEvidence[0] ?? $report->rejectedEvidence[0] ?? null;
        $this->assertNotNull($item);
        $this->assertNull($item->publicationYear);
        $this->assertSame('2019', (string) ($item->qualityFactors['observation_year'] ?? ''));
    }

    public function test_evidence_capability_is_distinct_from_verified_sufficiency(): void
    {
        $plan = $this->quantityPlan('barley', 'Spain', '2018', 'How much barley production in Spain 2018?');
        $wrong = $this->statisticalResult('Rice', 'Spain', '2018', 'Production', 't', '1000');
        $report = $this->validate($plan, [$wrong]);

        $this->assertArrayHasKey('evidence_capability', $report->observability);
        $this->assertFalse($report->evidenceSufficient);
        $this->assertIsBool($report->observability['evidence_capability']);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function quantityPlan(
        string $crop,
        string $location,
        string $year,
        string $question,
        array $context = [],
    ): KnowledgeQueryPlan {
        $query = new AgriculturalKnowledgeQuery(
            originalQuestion: $question,
            normalizedQuestion: $question,
            language: 'en',
            agriculturalDomain: 'agronomy',
            subject: ['type' => 'crop', 'value' => $crop],
            crop: $crop,
            cropId: $crop,
            scientificName: null,
            topic: 'production',
            subtopic: null,
            requestedInformation: ['quantity'],
            constraints: [
                'question_type' => 'quantity',
                'requested_property_key' => 'production',
                'requested_property_surface' => 'production quantity',
                'requested_property_query_terms' => ['production', 'quantity'],
                'year' => $year,
            ],
            location: $location,
            researchRequired: true,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            researchIntent: 'agricultural_economics',
        );

        return new KnowledgeQueryPlan(
            normalizedQuery: $query,
            researchIntent: 'agricultural_economics',
            agriculturalDomain: 'agronomy',
            subjectEntity: ['type' => 'crop', 'value' => $crop],
            topics: ['production'],
            subtopics: [],
            requestedInformation: ['quantity'],
            evidenceRequirements: ['official_statistics'],
            sourcePriorities: ['openalex', 'crossref', 'semantic_scholar', 'fao_stat'],
            primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            contextInput: $context,
            readyForStage3: true,
        );
    }

    private function statisticalResult(
        string $entity,
        string $location,
        string $year,
        string $property,
        string $unit,
        string $value,
        string $sourceKey = 'national_stat',
    ): ScientificSearchResult {
        return new ScientificSearchResult(
            sourceKey: $sourceKey,
            sourceIdentifier: implode('|', [$entity, $location, $property, $year, $unit, $value]),
            title: $entity.' — '.$property.' — '.$location.' — '.$year,
            authors: [],
            publicationYear: null,
            doi: null,
            canonicalUrl: 'https://stats.example.test/obs/'.rawurlencode($entity.$location.$year.$property.$unit.$value),
            abstract: 'Value: '.$value.' '.$unit,
            journal: null,
            foundBySources: [$sourceKey],
            relevanceMetadata: [
                'evidence_type' => ScientificEvidenceModality::DIRECT_STATISTICAL,
                'not_literature' => true,
            ],
            rawMetadata: [
                'observation' => [
                    'item' => $entity,
                    'area' => $location,
                    'year' => $year,
                    'element' => $property,
                    'unit' => $unit,
                    'value' => $value,
                    'flag' => 'A',
                ],
                'provenance' => [
                    'query' => $entity.' '.$property.' '.$location.' '.$year,
                ],
            ],
        );
    }

    private function scholarlyResult(
        string $title,
        string $doi,
        string $abstract,
        string $sourceKey = 'openalex',
    ): ScientificSearchResult {
        return new ScientificSearchResult(
            sourceKey: $sourceKey,
            sourceIdentifier: $doi,
            title: $title,
            authors: ['A Researcher'],
            publicationYear: 2021,
            doi: $doi,
            canonicalUrl: 'https://doi.org/'.$doi,
            abstract: $abstract,
            journal: 'Agronomy Journal',
            foundBySources: [$sourceKey],
            relevanceMetadata: [
                'species_relation' => 'same_species',
                'evidence_directness' => ScientificEvidenceDirectnessAssessor::SUPPORTING,
            ],
            rawMetadata: [
                'provenance' => ['provider' => $sourceKey],
            ],
        );
    }

    /**
     * @param  list<ScientificSearchResult>  $results
     */
    private function validate(KnowledgeQueryPlan $plan, array $results): \App\Services\Agriculture\Research\Validation\EvidenceValidationExecutionReport
    {
        return app(AgriculturalScientificValidationService::class)->validate(
            $plan,
            new ScientificSearchExecutionReport(
                status: 'search_completed',
                searchQuery: $plan->normalizedQuery->normalizedQuestion,
                selectedSources: ['openalex', 'fao_stat'],
                attemptedSources: ['openalex', 'fao_stat'],
                successfulSources: ['openalex', 'fao_stat'],
                failedSources: [],
                emptySources: [],
                sourceOutcomes: [],
                results: $results,
                deduplicatedResults: $results,
                planSummary: [],
                internetFirst: true,
                searchQueries: [$plan->normalizedQuery->normalizedQuestion],
            ),
        );
    }
}
