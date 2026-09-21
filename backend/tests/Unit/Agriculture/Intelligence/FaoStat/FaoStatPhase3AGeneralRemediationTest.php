<?php

namespace Tests\Unit\Agriculture\Intelligence\FaoStat;

use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatDeveloperPortalTokenManager;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatPipelineOutcome;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatProviderArchitecture;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatProviderQueryIdentity;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatQclElementSemantics;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatQclVerifiedDimensionMap;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatSearchOptionsResolver;
use App\Services\Agriculture\Intelligence\Registry\AgriculturalProviderRegistry;
use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\ResearchPlanner;
use App\Services\Agriculture\Research\Search\AgriculturalScientificSearchService;
use App\Services\Agriculture\Research\Search\ScientificResultDeduplicator;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;
use App\Services\Agriculture\Research\Search\ScientificSourceAdapterRegistry;
use App\Services\Agriculture\Research\Search\ScientificStatisticalClaimAligner;
use App\Services\Agriculture\Research\Search\ScientificStructuredObservation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FaoStatPhase3AGeneralRemediationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        app(FaoStatDeveloperPortalTokenManager::class)->reset();
        config([
            'agricultural_intelligence.faostat.enabled' => true,
            'agricultural_intelligence.faostat.base_url' => 'https://faostatservices.fao.org/api/v1',
            'agricultural_intelligence.faostat.allowed_host' => 'faostatservices.fao.org',
            'agricultural_intelligence.faostat.username' => 'portal-user',
            'agricultural_intelligence.faostat.password' => 'portal-pass',
            'agricultural_intelligence.faostat.lang' => 'en',
            'agricultural_intelligence.faostat.allowed_domains' => ['QCL'],
            'agricultural_intelligence.fao.enabled' => false,
            'agricultural_intelligence.openalex.enabled' => true,
            'agricultural_intelligence.crossref.enabled' => true,
            'agricultural_intelligence.semantic_scholar.enabled' => true,
        ]);
        $this->app->forgetInstance(ScientificSourceAdapterRegistry::class);
        $this->app->forgetInstance(AgriculturalProviderRegistry::class);
    }

    /**
     * @dataProvider measureResolutionProvider
     */
    public function test_measure_resolution_is_general(string $question, string $expectedElement, string $expectedMeasure): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery(['query' => $question]);
        $options = FaoStatSearchOptionsResolver::fromPlan($plan);
        $this->assertSame($expectedElement, $options['element'] ?? null, $question);
        $this->assertSame($expectedMeasure, $options['requested_measures'] ?? null, $question);
        $this->assertSame('resolved', $options['element_resolution_status'] ?? null, $question);
    }

    /**
     * @return list<array{0: string, 1: string, 2: string}>
     */
    public static function measureResolutionProvider(): array
    {
        return [
            ['What was wheat production quantity in Italy in 2022?', '2510', 'production_quantity'],
            ['What was maize yield in Brazil in 2019?', '2413', 'yield'],
            ['What was the area harvested for wheat in France in 2021?', '2312', 'area_harvested'],
            ['What was corn production in Egypt in 2020?', '2510', 'production_quantity'],
        ];
    }

    public function test_multi_measure_question_decomposes_when_dimensions_complete(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'What were wheat yield and production quantity in Italy in 2022?',
        ]);
        $options = FaoStatSearchOptionsResolver::fromPlan($plan);
        $queries = FaoStatSearchOptionsResolver::canonicalQueriesFromPlan($plan);
        $this->assertArrayNotHasKey('element', $options);
        $this->assertSame(
            FaoStatPipelineOutcome::DECOMPOSED_MEASURES,
            $options['element_resolution_status'] ?? null,
        );
        $this->assertGreaterThanOrEqual(2, count($queries));
        $elements = array_values(array_filter(array_map(
            static fn (array $q): ?string => $q['element'] ?? null,
            $queries,
        )));
        $this->assertContains('2413', $elements);
        $this->assertContains('2510', $elements);
        $this->assertCount(count($elements), array_unique($elements));
    }

    public function test_multi_measure_incomplete_dims_stay_ambiguous_without_http(): void
    {
        Http::fake();
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'What were yield and production quantity in Atlantis in 2099?',
        ]);
        $options = FaoStatSearchOptionsResolver::fromPlan($plan);
        $this->assertSame(
            FaoStatPipelineOutcome::AMBIGUOUS_MEASURES,
            $options['element_resolution_status'] ?? null,
        );
        $this->assertArrayNotHasKey('element', $options);
        app(AgriculturalScientificSearchService::class)->search($plan, 5, ['fao_stat']);
        Http::assertNothingSent();
    }

    public function test_explicit_element_conflicts_with_contradictory_structured_surface(): void
    {
        $plan = $this->statisticalPlan([
            'area' => '106', 'item' => '15', 'element' => '2510', 'year' => '2022',
            'crop' => 'Wheat', 'country' => 'Italy',
        ]);
        $query = $plan->normalizedQuery;
        $constraints = is_array($query->constraints) ? $query->constraints : [];
        $constraints['requested_property_surface'] = 'yield';
        $forced = new KnowledgeQueryPlan(
            normalizedQuery: new AgriculturalKnowledgeQuery(
                originalQuestion: $query->originalQuestion,
                normalizedQuestion: $query->normalizedQuestion,
                language: $query->language,
                agriculturalDomain: $query->agriculturalDomain,
                subject: $query->subject,
                crop: $query->crop,
                cropId: $query->cropId,
                scientificName: $query->scientificName,
                topic: $query->topic,
                subtopic: $query->subtopic,
                requestedInformation: $query->requestedInformation,
                constraints: $constraints,
                location: $query->location,
                researchRequired: $query->researchRequired,
                ambiguityState: $query->ambiguityState,
                clarificationRequirements: $query->clarificationRequirements,
                researchIntent: $query->researchIntent,
            ),
            researchIntent: $plan->researchIntent,
            agriculturalDomain: $plan->agriculturalDomain,
            subjectEntity: $plan->subjectEntity,
            topics: $plan->topics,
            subtopics: $plan->subtopics,
            requestedInformation: $plan->requestedInformation,
            evidenceRequirements: $plan->evidenceRequirements,
            sourcePriorities: $plan->sourcePriorities,
            primaryResearchStrategy: $plan->primaryResearchStrategy,
            researchSequence: $plan->researchSequence,
            ambiguityState: $plan->ambiguityState,
            clarificationRequirements: $plan->clarificationRequirements,
            readyForStage3: true,
        );
        $options = FaoStatSearchOptionsResolver::fromPlan($forced);
        $this->assertSame(FaoStatPipelineOutcome::MEASURE_CONFLICT, $options['element_resolution_status'] ?? null);
        $this->assertArrayNotHasKey('element', $options);
    }

    public function test_verified_dimension_map_is_versioned_and_unknown_labels_do_not_probe(): void
    {
        FaoStatQclVerifiedDimensionMap::resetCache();
        $this->assertNotSame('', FaoStatQclVerifiedDimensionMap::version());
        $this->assertArrayHasKey('wheat', FaoStatQclVerifiedDimensionMap::items());
        Http::fake();
        $got = app(\App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatDomainScopedCodeResolver::class)
            ->resolve('QCL', 'items', 'unknowncropxyz');
        $this->assertSame('unresolved', $got['status']);
        Http::assertNothingSent();
    }

    public function test_registry_model_b_single_faostat_identity(): void
    {
        $this->assertSame('fao_stat', FaoStatProviderArchitecture::canonicalSourceKey());
        $adapter = app(ScientificSourceAdapterRegistry::class)->get('fao_stat');
        $this->assertInstanceOf(
            \App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatDeveloperPortalAdapter::class,
            $adapter,
        );
        $provider = app(AgriculturalProviderRegistry::class)->get('fao_stat');
        $this->assertNotNull($provider);
        $this->assertSame('fao_stat', $provider->descriptor()->id);
        $this->assertSame(
            FaoStatProviderArchitecture::canonicalAdapterClass(),
            \App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatDeveloperPortalAdapter::class,
        );
    }

    public function test_result_pipeline_distinguishes_raw_from_final_survivors(): void
    {
        Http::fake($this->authAnd([
            'faostatservices.fao.org/api/v1/en/data/*' => Http::response([
                'metadata' => ['output_type' => 'OBJECTS'],
                'data' => [$this->row([
                    'area' => '106', 'item' => '15', 'element' => '2510', 'year' => '2022',
                    'response' => '5510', 'label' => 'Production', 'crop' => 'Wheat', 'country' => 'Italy',
                ])],
            ], 200),
        ]));
        $report = app(AgriculturalScientificSearchService::class)->search($this->statisticalPlan([
            'area' => '106', 'item' => '15', 'element' => '2510', 'year' => '2022',
            'crop' => 'Wheat', 'country' => 'Italy',
        ]), 5, ['fao_stat']);
        $pipeline = $report->planSummary['result_pipeline'] ?? [];
        $this->assertSame('post_rank_filtered_survivors', $pipeline['deduplicated_results_means'] ?? null);
        $this->assertArrayHasKey('stages', $pipeline);
        $this->assertSame(
            $pipeline['final_survivor_count'] ?? null,
            count($report->deduplicatedResults),
        );
        $this->assertGreaterThanOrEqual(
            count($report->deduplicatedResults),
            (int) ($pipeline['raw_retrieved_count'] ?? 0),
        );
    }

    public function test_unresolved_dimensions_never_invent_codes_or_http(): void
    {
        Http::fake();
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'What was quinoa production in Atlantis in 2099?',
        ]);
        $options = FaoStatSearchOptionsResolver::fromPlan($plan);
        $this->assertArrayNotHasKey('area', $options);
        $this->assertArrayNotHasKey('item', $options);

        $report = app(AgriculturalScientificSearchService::class)->search($plan, 5, ['fao_stat']);
        Http::assertNothingSent();
        $this->assertSame(
            FaoStatPipelineOutcome::INCOMPLETE_FILTERS,
            $report->planSummary['faostat_pipeline_outcome']['stage'] ?? null,
        );
    }

    public function test_numeric_crop_taxonomy_id_is_not_treated_as_faostat_item_code(): void
    {
        $plan = $this->planWithCrop('999001', 'mystery crop', 'What was mystery crop production in Italy in 2022?');
        $options = FaoStatSearchOptionsResolver::fromPlan($plan);
        $this->assertArrayNotHasKey('item', $options);
        $this->assertNotSame('999001', $options['item'] ?? null);
    }

    public function test_query_and_response_element_codes_remain_distinct_but_compatible(): void
    {
        $this->assertTrue(FaoStatQclElementSemantics::codesCompatible('2510', '5510'));
        $this->assertTrue(FaoStatQclElementSemantics::codesCompatible('2413', '5412'));
        $this->assertTrue(FaoStatQclElementSemantics::codesCompatible('2312', '5312'));
        $this->assertFalse(FaoStatQclElementSemantics::codesCompatible('2510', '5412'));
        $this->assertNotSame(
            FaoStatQclElementSemantics::QUERY_PRODUCTION_QUANTITY,
            FaoStatQclElementSemantics::RESPONSE_PRODUCTION,
        );
    }

    public function test_provider_query_identity_canonicalizes_equivalent_dimensions(): void
    {
        $a = FaoStatProviderQueryIdentity::fromOptions([
            'domain' => 'QCL', 'area' => '106', 'item' => '15', 'element' => '2510', 'year' => '2022',
        ]);
        $b = FaoStatProviderQueryIdentity::fromOptions([
            'domain' => 'QCL', 'area_code' => '106', 'item_code' => '15', 'query_element_code' => '2510', 'year' => '2022',
        ]);
        $c = FaoStatProviderQueryIdentity::fromOptions([
            'domain' => 'QCL', 'area' => '68', 'item' => '15', 'element' => '2510', 'year' => '2022',
        ]);
        $this->assertSame($a, $b);
        $this->assertNotSame($a, $c);
        $this->assertNull(FaoStatProviderQueryIdentity::fromOptions(['area' => '106', 'item' => '15']));
    }

    public function test_deduplicator_keeps_single_valid_statistical_observation(): void
    {
        $result = $this->statisticalResult('Wheat', 'Italy', '2022', 'Production', '15', '106', '2510', '6609520');
        $deduped = app(ScientificResultDeduplicator::class)->deduplicate([$result, $result]);
        $this->assertCount(1, $deduped);
        $this->assertSame('6609520', $deduped[0]->rawMetadata['faostat']['value'] ?? null);
    }

    /**
     * @dataProvider generalizedPortalCaseProvider
     * @param  array<string, string>  $case
     */
    public function test_generalized_portal_success_for_multiple_areas_items_measures(array $case): void
    {
        Http::fake($this->authAnd([
            'faostatservices.fao.org/api/v1/en/data/*' => Http::response([
                'metadata' => ['output_type' => 'OBJECTS'],
                'data' => [$this->row($case)],
            ], 200),
        ]));

        $plan = $this->statisticalPlan($case);
        $report = app(AgriculturalScientificSearchService::class)->search($plan, 5, ['fao_stat']);

        $dataUrls = [];
        foreach (Http::recorded() as $pair) {
            $url = $pair[0]->url();
            if (str_contains($url, '/data/')) {
                $dataUrls[] = $url;
            }
        }
        $this->assertNotEmpty($dataUrls, $case['crop'].'/'.$case['country']);
        parse_str((string) parse_url($dataUrls[0], PHP_URL_QUERY), $query);
        $this->assertSame($case['area'], $query['area'] ?? null, $case['crop'].'/'.$case['country']);
        $this->assertSame($case['item'], $query['item'] ?? null);
        $this->assertSame($case['element'], $query['element'] ?? null);
        $this->assertSame($case['year'], $query['year'] ?? null);

        $this->assertNotEmpty($report->deduplicatedResults, $case['crop'].'/'.$case['country']);
        $this->assertSame(
            FaoStatPipelineOutcome::SUCCESS,
            $report->planSummary['faostat_pipeline_outcome']['stage'] ?? null,
            $case['crop'].'/'.$case['country'],
        );
        $row = $report->deduplicatedResults[0];
        $this->assertSame($case['element'], $row->relevanceMetadata['query_element_code'] ?? null);
        $this->assertSame($case['response'], $row->relevanceMetadata['response_element_code'] ?? null);
        $this->assertNotSame(
            $row->relevanceMetadata['query_element_code'],
            $row->relevanceMetadata['response_element_code'],
        );
        $this->assertSame($case['item'], $row->rawMetadata['faostat']['item_code'] ?? null);
        $this->assertSame($case['area'], $row->rawMetadata['faostat']['area_code'] ?? null);
    }

    /**
     * @return list<array{0: array<string, string>}>
     */
    public static function generalizedPortalCaseProvider(): array
    {
        return [
            'italy_wheat_production' => [[
                'area' => '106', 'item' => '15', 'element' => '2510', 'year' => '2022',
                'response' => '5510', 'label' => 'Production', 'crop' => 'Wheat', 'country' => 'Italy',
            ]],
            'brazil_maize_yield' => [[
                'area' => '21', 'item' => '56', 'element' => '2413', 'year' => '2019',
                'response' => '5412', 'label' => 'Yield', 'crop' => 'Maize', 'country' => 'Brazil',
            ]],
            'france_wheat_area_harvested' => [[
                'area' => '68', 'item' => '15', 'element' => '2312', 'year' => '2021',
                'response' => '5312', 'label' => 'Area harvested', 'crop' => 'Wheat', 'country' => 'France',
            ]],
        ];
    }

    public function test_mechanism_question_rejects_retrieved_row_with_diagnosable_outcome(): void
    {
        Http::fake($this->authAnd([
            'faostatservices.fao.org/api/v1/en/data/*' => Http::response([
                'metadata' => ['output_type' => 'OBJECTS'],
                'data' => [$this->row([
                    'area' => '106', 'item' => '15', 'element' => '2510', 'year' => '2022',
                    'response' => '5510', 'label' => 'Production', 'crop' => 'Wheat', 'country' => 'Italy',
                ])],
            ], 200),
        ]));

        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'How does wheat photosynthesis work in Italy?',
        ]);
        // Force complete filters into constraints to simulate retrieved-but-rejected path.
        $query = $plan->normalizedQuery;
        $constraints = is_array($query->constraints) ? $query->constraints : [];
        $constraints = array_merge($constraints, [
            'area' => '106', 'item' => '15', 'element' => '2510', 'year' => '2022',
        ]);
        $forced = new KnowledgeQueryPlan(
            normalizedQuery: new AgriculturalKnowledgeQuery(
                originalQuestion: $query->originalQuestion,
                normalizedQuestion: $query->normalizedQuestion,
                language: $query->language,
                agriculturalDomain: $query->agriculturalDomain,
                subject: $query->subject,
                crop: $query->crop,
                cropId: $query->cropId,
                scientificName: $query->scientificName,
                topic: $query->topic,
                subtopic: $query->subtopic,
                requestedInformation: $query->requestedInformation,
                constraints: $constraints,
                location: $query->location,
                researchRequired: $query->researchRequired,
                ambiguityState: $query->ambiguityState,
                clarificationRequirements: $query->clarificationRequirements,
                researchIntent: $query->researchIntent,
            ),
            researchIntent: $plan->researchIntent,
            agriculturalDomain: $plan->agriculturalDomain,
            subjectEntity: $plan->subjectEntity,
            topics: $plan->topics,
            subtopics: $plan->subtopics,
            requestedInformation: $plan->requestedInformation,
            evidenceRequirements: $plan->evidenceRequirements,
            sourcePriorities: $plan->sourcePriorities,
            primaryResearchStrategy: $plan->primaryResearchStrategy,
            researchSequence: $plan->researchSequence,
            ambiguityState: $plan->ambiguityState,
            clarificationRequirements: $plan->clarificationRequirements,
            readyForStage3: true,
        );

        $report = app(AgriculturalScientificSearchService::class)->search($forced, 5, ['fao_stat']);
        $this->assertSame([], $report->deduplicatedResults);
        $stage = $report->planSummary['faostat_pipeline_outcome']['stage'] ?? null;
        $this->assertContains($stage, [
            FaoStatPipelineOutcome::REJECTED_FAO_GATE,
            FaoStatPipelineOutcome::REJECTED_DOWNSTREAM,
            FaoStatPipelineOutcome::EMPTY_RESULT,
        ]);
    }

    public function test_aligner_accepts_dual_element_codes_and_rejects_cross_measure(): void
    {
        $aligner = new ScientificStatisticalClaimAligner();
        $plan = $this->statisticalPlan([
            'area' => '106', 'item' => '15', 'element' => '2510', 'year' => '2022',
            'crop' => 'Wheat', 'country' => 'Italy',
        ]);
        $ok = new ScientificStructuredObservation(
            entity: 'Wheat',
            location: 'Italy',
            year: '2022',
            property: 'Production',
            unit: 't',
            value: '1',
            propertyCode: '5510',
        );
        $this->assertTrue($aligner->assess($plan, $ok)['relevant']);

        $yieldObs = new ScientificStructuredObservation(
            entity: 'Wheat',
            location: 'Italy',
            year: '2022',
            property: 'Yield',
            unit: 't/ha',
            value: '1',
            propertyCode: '5412',
        );
        $this->assertFalse($aligner->assess($plan, $yieldObs)['relevant']);
        $this->assertContains('property', $aligner->assess($plan, $yieldObs)['mismatches']);
    }

    public function test_measure_family_does_not_prefer_yield_when_only_production_requested(): void
    {
        $this->assertSame(
            'production_quantity',
            ScientificStatisticalClaimAligner::measureFamily('wheat production quantity Italy 2022'),
        );
        $this->assertSame('', ScientificStatisticalClaimAligner::measureFamily(
            'wheat yield and production quantity Italy 2022',
        ));
    }

    public function test_italy_wheat_2022_remains_regression_only(): void
    {
        Http::fake($this->authAnd([
            'faostatservices.fao.org/api/v1/en/data/*' => Http::response([
                'metadata' => ['output_type' => 'OBJECTS'],
                'data' => [$this->row([
                    'area' => '106', 'item' => '15', 'element' => '2510', 'year' => '2022',
                    'response' => '5510', 'label' => 'Production', 'crop' => 'Wheat', 'country' => 'Italy', 'value' => '6609520',
                ])],
            ], 200),
        ]));
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'What was wheat production in Italy in 2022?',
        ]);
        $options = FaoStatSearchOptionsResolver::fromPlan($plan);
        $this->assertSame(['106', '15', '2510', '2022'], [
            $options['area'] ?? null,
            $options['item'] ?? null,
            $options['element'] ?? null,
            $options['year'] ?? null,
        ]);
        $report = app(AgriculturalScientificSearchService::class)->search($plan, 5, ['fao_stat']);
        $this->assertNotEmpty($report->deduplicatedResults);
    }

    /**
     * @param  array<string, string>  $case
     */
    private function statisticalPlan(array $case): KnowledgeQueryPlan
    {
        $crop = $case['crop'] ?? 'Wheat';
        $country = $case['country'] ?? 'Italy';
        $year = $case['year'] ?? '2022';
        $measure = match ($case['element'] ?? '2510') {
            '2413' => 'yield',
            '2312' => 'area harvested',
            default => 'production quantity',
        };
        $question = "What was {$crop} {$measure} in {$country} in {$year}?";
        $query = new AgriculturalKnowledgeQuery(
            originalQuestion: $question,
            normalizedQuestion: $question,
            language: 'en',
            agriculturalDomain: 'agronomy',
            subject: ['type' => 'crop', 'value' => strtolower($crop), 'label' => $crop],
            crop: $crop,
            cropId: strtolower($crop),
            scientificName: null,
            topic: 'production statistics',
            subtopic: null,
            requestedInformation: ['quantity'],
            constraints: [
                'question_type' => 'statistical',
                'domain' => 'QCL',
                'area' => $case['area'],
                'item' => $case['item'],
                'element' => $case['element'],
                'year' => $case['year'],
                'requested_property_surface' => $measure,
            ],
            location: $country,
            researchRequired: true,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            researchIntent: 'agricultural_economics',
        );

        return new KnowledgeQueryPlan(
            normalizedQuery: $query,
            researchIntent: 'agricultural_economics',
            agriculturalDomain: 'agronomy',
            subjectEntity: ['type' => 'crop', 'value' => strtolower($crop), 'label' => $crop],
            topics: ['production statistics'],
            subtopics: [],
            requestedInformation: ['quantity'],
            evidenceRequirements: ['official_statistics'],
            sourcePriorities: ['fao_stat', 'openalex'],
            primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            readyForStage3: true,
        );
    }

    private function planWithCrop(string $cropId, string $crop, string $question): KnowledgeQueryPlan
    {
        $query = new AgriculturalKnowledgeQuery(
            originalQuestion: $question,
            normalizedQuestion: $question,
            language: 'en',
            agriculturalDomain: 'agronomy',
            subject: ['type' => 'crop', 'value' => $cropId, 'label' => $crop],
            crop: $crop,
            cropId: $cropId,
            scientificName: null,
            topic: 'production',
            subtopic: null,
            requestedInformation: ['quantity'],
            constraints: ['question_type' => 'statistical'],
            location: 'Italy',
            researchRequired: true,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            researchIntent: 'agricultural_economics',
        );

        return new KnowledgeQueryPlan(
            normalizedQuery: $query,
            researchIntent: 'agricultural_economics',
            agriculturalDomain: 'agronomy',
            subjectEntity: ['type' => 'crop', 'value' => $cropId, 'label' => $crop],
            topics: ['production'],
            subtopics: [],
            requestedInformation: ['quantity'],
            evidenceRequirements: ['official_statistics'],
            sourcePriorities: ['fao_stat'],
            primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            readyForStage3: true,
        );
    }

    /**
     * @param  array<string, string>  $case
     * @return array<string, mixed>
     */
    private function row(array $case): array
    {
        return [
            'Domain Code' => 'QCL',
            'Domain' => 'Crops and livestock products',
            'Area Code' => $case['area'],
            'Area' => $case['country'],
            'Element Code' => $case['response'],
            'Element' => $case['label'],
            'Item Code' => $case['item'],
            'Item' => $case['crop'],
            'Year Code' => $case['year'],
            'Year' => $case['year'],
            'Unit' => $case['label'] === 'Yield' ? 't/ha' : ($case['label'] === 'Area harvested' ? 'ha' : 't'),
            'Value' => $case['value'] ?? '1000',
            'Flag' => 'A',
            'Flag Description' => 'Official value',
            'Note' => '',
        ];
    }

    private function statisticalResult(
        string $entity,
        string $location,
        string $year,
        string $property,
        string $itemCode,
        string $areaCode,
        string $queryElement,
        string $value,
    ): ScientificSearchResult {
        return new ScientificSearchResult(
            sourceKey: 'fao_stat',
            sourceIdentifier: "QCL|{$areaCode}|{$itemCode}|{$queryElement}|{$year}",
            title: "{$entity} — {$property} — {$location} — {$year}",
            authors: [],
            publicationYear: null,
            doi: null,
            canonicalUrl: 'https://faostatservices.fao.org/api/v1/en/data/QCL',
            abstract: "Value: {$value}",
            journal: null,
            foundBySources: ['fao_stat'],
            relevanceMetadata: [
                'evidence_type' => 'direct_statistical_evidence',
                'query_element_code' => $queryElement,
                'response_element_code' => '5510',
            ],
            rawMetadata: [
                'faostat' => [
                    'item' => $entity,
                    'item_code' => $itemCode,
                    'area' => $location,
                    'area_code' => $areaCode,
                    'element' => $property,
                    'query_element_code' => $queryElement,
                    'response_element_code' => '5510',
                    'element_code' => '5510',
                    'year' => $year,
                    'unit' => 't',
                    'value' => $value,
                ],
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function authAnd(array $extra): array
    {
        return array_merge([
            'faostatservices.fao.org/api/v1/auth/login' => Http::response([
                'AuthenticationResult' => [
                    'AccessToken' => 'test-access-token',
                    'RefreshToken' => 'test-refresh-token',
                    'ExpiresIn' => 3600,
                    'TokenType' => 'Bearer',
                ],
            ], 200),
        ], $extra);
    }
}
