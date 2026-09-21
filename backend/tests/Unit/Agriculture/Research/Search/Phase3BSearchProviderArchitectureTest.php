<?php

namespace Tests\Unit\Agriculture\Research\Search;

use App\Contracts\ScientificSourceAdapterInterface;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatDeveloperPortalAdapter;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatRuntimePolicy;
use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Search\MultiSourceScientificSearchOrchestrator;
use App\Services\Agriculture\Research\Search\ScientificResultDeduplicator;
use App\Services\Agriculture\Research\Search\ScientificResultRanker;
use App\Services\Agriculture\Research\Search\ScientificSearchQueryBuilder;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;
use App\Services\Agriculture\Research\Search\ScientificSearchVariantBudget;
use App\Services\Agriculture\Research\Search\ScientificSourceAdapterRegistry;
use App\Services\Agriculture\Research\Search\ScientificSourceSearchOutcome;
use App\Services\Agriculture\Research\Search\ScientificSourceSelector;
use App\Support\ScientificHttp;
use Mockery;
use Tests\TestCase;

/**
 * Phase 3-B: Search & Provider Architecture — generalized contract tests.
 */
class Phase3BSearchProviderArchitectureTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_variant_budget_suppresses_equivalents_and_caps_global_fanout(): void
    {
        $applied = ScientificSearchVariantBudget::apply([
            'Maize yield Egypt',
            'maize  yield   egypt',
            'MAIZE YIELD EGYPT',
            'Rice production Brazil',
            'Barley harvested area Morocco',
            'Sorghum salinity research',
            'Potato irrigation efficiency',
            'extra seventh',
        ]);

        $this->assertSame(5, $applied['output_count']);
        $this->assertSame(2, $applied['equivalent_suppressed']);
        $this->assertSame(1, $applied['truncated']);
        $this->assertSame(ScientificSearchVariantBudget::MAX_VARIANTS, 5);
        $this->assertSame(
            ['Maize yield Egypt', 'Rice production Brazil', 'Barley harvested area Morocco', 'Sorghum salinity research', 'Potato irrigation efficiency'],
            $applied['variants'],
        );
    }

    public function test_timeout_and_retry_bounds_remain_configured(): void
    {
        $this->assertSame(15, ScientificHttp::timeoutSeconds());
        $this->assertSame(4, ScientificHttp::timeoutSeconds(4.2));
        $this->assertSame(3, ScientificHttp::MAX_RATE_LIMIT_ATTEMPTS);
    }

    public function test_registry_and_selector_agree_on_canonical_keys(): void
    {
        $registry = app(ScientificSourceAdapterRegistry::class);
        $keys = $registry->registeredSourceKeys();
        $this->assertContains('openalex', $keys);
        $this->assertContains('crossref', $keys);
        $this->assertContains('semantic_scholar', $keys);
        $this->assertContains('consensus', $keys);
        $this->assertContains(FaoStatRuntimePolicy::canonicalSourceKey(), $keys);
        $this->assertSame('fao_stat', FaoStatRuntimePolicy::canonicalSourceKey());
        $this->assertInstanceOf(FaoStatDeveloperPortalAdapter::class, $registry->get('fao_stat'));
        $this->assertContains('consensus', ScientificSourceSelector::OPTIONAL_SOURCES);
        $this->assertNotContains('consensus', ScientificSourceSelector::DEFAULT_INTERNET_FIRST_SOURCES);
    }

    public function test_query_builder_preserves_entity_location_and_property_semantics(): void
    {
        $builder = app(ScientificSearchQueryBuilder::class);

        foreach ([
            ['crop' => 'maize', 'location' => 'Egypt', 'property' => 'yield', 'topic' => 'yield'],
            ['crop' => 'rice', 'location' => 'Brazil', 'property' => 'production', 'topic' => 'production'],
            ['crop' => 'barley', 'location' => 'Morocco', 'property' => 'harvested area', 'topic' => 'area harvested'],
        ] as $case) {
            $plan = $this->plan(
                question: "What was {$case['crop']} {$case['property']} in {$case['location']} in 2020?",
                crop: $case['crop'],
                location: $case['location'],
                topic: $case['topic'],
                questionType: 'statistical',
                intent: 'agricultural_economics',
                propertyTerms: [$case['property']],
            );
            $variants = $builder->buildVariantsFromPlan($plan);
            $this->assertNotEmpty($variants);
            $blob = mb_strtolower(implode(' | ', $variants));
            $this->assertStringContainsString(mb_strtolower($case['crop']), $blob);
            $this->assertTrue(
                str_contains($blob, mb_strtolower($case['property']))
                    || str_contains($blob, mb_strtolower($case['topic']))
                    || str_contains($blob, 'yield')
                    || str_contains($blob, 'production')
                    || str_contains($blob, 'harvest'),
                "Semantic property must survive for {$case['crop']}",
            );
            $this->assertLessThanOrEqual(5, count($variants));
        }
    }

    public function test_selector_is_semantic_deterministic_and_respects_activation(): void
    {
        config([
            'agricultural_intelligence.faostat.enabled' => true,
            'agricultural_intelligence.openalex.enabled' => true,
            'agricultural_intelligence.crossref.enabled' => false,
            'agricultural_intelligence.semantic_scholar.enabled' => true,
        ]);

        $selector = app(ScientificSourceSelector::class);
        $scholarly = $this->plan(
            question: 'What mechanisms explain soil salinity stress in sorghum?',
            crop: 'sorghum',
            location: null,
            topic: 'soil salinity',
            questionType: 'mechanism',
            intent: 'generic_research',
        );
        $statistical = $this->plan(
            question: 'What was maize production in Egypt in 2020?',
            crop: 'maize',
            location: 'Egypt',
            topic: 'production statistics',
            questionType: 'statistical',
            intent: 'agricultural_economics',
        );

        $trace = $selector->selectionTrace($scholarly);
        $this->assertContains('fao_stat', $trace['selected']);
        $this->assertContains('openalex', $trace['selected']);
        $this->assertNotContains('crossref', $trace['selected']);
        $this->assertContains('crossref', $trace['skipped_inactive']);
        $this->assertContains('consensus', $trace['optional_not_selected']);
        $this->assertSame('CONSIDERED', $trace['faostat_consideration']);
        $this->assertSame($trace['selected'], $selector->selectSources($statistical));

        config(['agricultural_intelligence.faostat.enabled' => false]);
        $off = $selector->selectionTrace($scholarly);
        $this->assertNotContains('fao_stat', $off['selected']);
        $this->assertContains('fao_stat', $off['skipped_inactive']);
    }

    public function test_orchestrator_isolates_failure_timeout_empty_and_exposes_observability(): void
    {
        config([
            'agricultural_intelligence.openalex.enabled' => true,
            'agricultural_intelligence.crossref.enabled' => true,
            'agricultural_intelligence.semantic_scholar.enabled' => true,
            'agricultural_intelligence.faostat.enabled' => false,
            'agricultural_intelligence.stage3_home_scholarly_concurrency' => false,
        ]);

        $openAlex = $this->adapter('openalex', [
            new ScientificSourceSearchOutcome(
                sourceKey: 'openalex',
                status: ScientificSourceSearchOutcome::STATUS_FAILED,
                error: 'timeout',
                httpStatus: 0,
                observability: ['retry_count' => 2],
            ),
        ]);
        $crossref = $this->adapter('crossref', [
            new ScientificSourceSearchOutcome(
                sourceKey: 'crossref',
                status: ScientificSourceSearchOutcome::STATUS_EMPTY,
                results: [],
            ),
        ]);
        $semantic = $this->adapter('semantic_scholar', [
            new ScientificSourceSearchOutcome(
                sourceKey: 'semantic_scholar',
                status: ScientificSourceSearchOutcome::STATUS_SUCCESS,
                results: [
                    new ScientificSearchResult(
                        sourceKey: 'semantic_scholar',
                        sourceIdentifier: 's2-ok',
                        title: 'Sorghum salinity tolerance review',
                        authors: ['A'],
                        publicationYear: 2021,
                        doi: '10.1000/phase3b-s2',
                        canonicalUrl: 'https://example.test/s2-ok',
                        abstract: 'Generalized salinity research.',
                        journal: 'Ag Journal',
                        foundBySources: ['semantic_scholar'],
                    ),
                ],
            ),
        ]);

        $report = $this->orchestrator([$openAlex, $crossref, $semantic], ['sorghum salinity', 'SORGHUM  SALINITY'])
            ->execute($this->plan(
                question: 'soil salinity research for sorghum',
                crop: 'sorghum',
                location: null,
                topic: 'soil salinity',
                questionType: 'research',
                intent: 'generic_research',
            ), 5, ['openalex', 'crossref', 'semantic_scholar']);

        $this->assertContains('openalex', $report->failedSources);
        $this->assertContains('crossref', $report->emptySources);
        $this->assertContains('semantic_scholar', $report->successfulSources);
        $this->assertNotEmpty($report->results);

        $obs = $report->planSummary['search_observability'] ?? null;
        $this->assertIsArray($obs);
        $this->assertSame(1, $obs['variant_count']);
        $this->assertSame(1, $obs['variant_equivalent_suppressed']);
        $this->assertGreaterThanOrEqual(1, $obs['timeouts']);
        $this->assertGreaterThanOrEqual(2, $obs['retry_counts']);
        $this->assertArrayHasKey('provider_status', $obs);
        $this->assertSame('sequential', $obs['concurrency_mode']);
        $this->assertSame(2, $obs['max_variants_per_provider']);
        $this->assertArrayHasKey('result_pipeline', $report->planSummary);
        $this->assertArrayHasKey('search_observability', $report->toArray()['plan']);
        $this->assertArrayHasKey('stage3_elapsed_ms', $report->toArray()['plan']);
    }

    public function test_provider_variant_budget_remains_two(): void
    {
        config([
            'agricultural_intelligence.openalex.enabled' => true,
            'agricultural_intelligence.crossref.enabled' => false,
            'agricultural_intelligence.semantic_scholar.enabled' => false,
            'agricultural_intelligence.faostat.enabled' => false,
            'agricultural_intelligence.stage3_home_scholarly_concurrency' => false,
        ]);

        $calls = [];
        $adapter = Mockery::mock(ScientificSourceAdapterInterface::class);
        $adapter->shouldReceive('sourceKey')->andReturn('openalex');
        $adapter->shouldReceive('isEnabled')->andReturn(true);
        $adapter->shouldReceive('search')->andReturnUsing(
            function (string $q) use (&$calls): ScientificSourceSearchOutcome {
                $calls[] = $q;

                return new ScientificSourceSearchOutcome(
                    sourceKey: 'openalex',
                    status: ScientificSourceSearchOutcome::STATUS_EMPTY,
                );
            }
        );

        $report = $this->orchestrator([$adapter], [
            'alpha query',
            'beta query',
            'gamma query',
        ])->execute($this->plan(
            question: 'generic agriculture research',
            crop: 'potato',
            location: null,
            topic: 'irrigation',
            questionType: 'research',
            intent: 'generic_research',
        ), 5, ['openalex']);

        $this->assertCount(2, $calls);
        $this->assertSame(['alpha query', 'beta query'], $calls);
        $obs = $report->planSummary['search_observability'];
        $this->assertSame(2, $obs['provider_variant_counts']['openalex']['attempted']);
    }

    public function test_deduplicator_preserves_distinct_doi_and_statistical_identity(): void
    {
        $dedup = app(ScientificResultDeduplicator::class);
        $a = new ScientificSearchResult(
            sourceKey: 'openalex',
            sourceIdentifier: 'oa-1',
            title: 'Same Title Different Doi',
            authors: ['A'],
            publicationYear: 2020,
            doi: '10.1000/a',
            canonicalUrl: 'https://example.test/a',
            abstract: null,
            journal: null,
            foundBySources: ['openalex'],
        );
        $b = new ScientificSearchResult(
            sourceKey: 'crossref',
            sourceIdentifier: 'cr-1',
            title: 'Same Title Different Doi',
            authors: ['B'],
            publicationYear: 2020,
            doi: '10.1000/b',
            canonicalUrl: 'https://example.test/b',
            abstract: null,
            journal: null,
            foundBySources: ['crossref'],
        );
        $dup = new ScientificSearchResult(
            sourceKey: 'semantic_scholar',
            sourceIdentifier: 's2-1',
            title: 'Same Title Different Doi',
            authors: ['C'],
            publicationYear: 2020,
            doi: '10.1000/a',
            canonicalUrl: 'https://example.test/a2',
            abstract: null,
            journal: null,
            foundBySources: ['semantic_scholar'],
        );

        $merged = $dedup->deduplicate([$a, $b, $dup]);
        $this->assertCount(2, $merged);

        $statA = $this->statisticalResult('Maize', 'Egypt', '2020', 'Production', '56', '59', '2510', '100');
        $statB = $this->statisticalResult('Maize', 'Egypt', '2020', 'Yield', '56', '59', '2413', '5.5');
        $statDup = $this->statisticalResult('Maize', 'Egypt', '2020', 'Production', '56', '59', '2510', '100');
        $stats = $dedup->deduplicate([$statA, $statB, $statDup]);
        $this->assertCount(2, $stats);
    }

    public function test_home_vs_crop_concurrency_policy_is_preserved(): void
    {
        $orchestrator = new \ReflectionClass(MultiSourceScientificSearchOrchestrator::class);
        $method = $orchestrator->getMethod('shouldOverlapIndependentScholarlyProviders');
        $method->setAccessible(true);
        $instance = app(MultiSourceScientificSearchOrchestrator::class);

        config(['agricultural_intelligence.stage3_home_scholarly_concurrency' => true]);
        $home = $this->plan(
            question: 'sorghum salinity research overview',
            crop: 'sorghum',
            location: null,
            topic: 'salinity',
            questionType: 'research',
            intent: 'generic_research',
        );
        $crop = $this->plan(
            question: 'crop profile for sorghum',
            crop: 'sorghum',
            location: null,
            topic: 'crop profile',
            questionType: 'research',
            intent: 'crop_profile',
            contextInput: [
                'selected_crop_id' => 'sorghum',
                'selected_crop_name' => 'Sorghum',
            ],
        );

        $this->assertTrue($method->invoke($instance, $home));
        $this->assertFalse($method->invoke($instance, $crop));
    }

    /**
     * @param  list<ScientificSourceAdapterInterface>  $adapters
     * @param  list<string>  $variants
     */
    private function orchestrator(array $adapters, array $variants): MultiSourceScientificSearchOrchestrator
    {
        $registry = Mockery::mock(ScientificSourceAdapterRegistry::class);
        $registry->shouldReceive('resolveMany')->andReturnUsing(
            function (array $keys) use ($adapters): array {
                $map = [];
                foreach ($adapters as $adapter) {
                    $map[$adapter->sourceKey()] = $adapter;
                }
                $resolved = [];
                foreach ($keys as $key) {
                    if (isset($map[$key])) {
                        $resolved[] = $map[$key];
                    }
                }

                return $resolved;
            }
        );

        $queryBuilder = Mockery::mock(ScientificSearchQueryBuilder::class);
        $queryBuilder->shouldReceive('buildVariantsFromPlan')->andReturn($variants);
        $queryBuilder->shouldReceive('buildFromPlan')->andReturn($variants[0] ?? 'agriculture');
        $queryBuilder->shouldReceive('buildConsensusRequestOptions')->andReturn([]);

        return new MultiSourceScientificSearchOrchestrator(
            $registry,
            app(ScientificSourceSelector::class),
            $queryBuilder,
            app(ScientificResultDeduplicator::class),
            app(ScientificResultRanker::class),
        );
    }

    /**
     * @param  list<ScientificSourceSearchOutcome>  $outcomes
     */
    private function adapter(string $key, array $outcomes): ScientificSourceAdapterInterface
    {
        $adapter = Mockery::mock(ScientificSourceAdapterInterface::class);
        $adapter->shouldReceive('sourceKey')->andReturn($key);
        $adapter->shouldReceive('isEnabled')->andReturn(true);
        $adapter->shouldReceive('search')->andReturn(...$outcomes);

        return $adapter;
    }

    private function plan(
        string $question,
        string $crop,
        ?string $location,
        string $topic,
        string $questionType,
        string $intent,
        array $propertyTerms = [],
        array $contextInput = [],
    ): KnowledgeQueryPlan {
        $constraints = [
            'question_type' => $questionType,
        ];
        if ($propertyTerms !== []) {
            $constraints['requested_property_query_terms'] = $propertyTerms;
            $constraints['requested_property_surface'] = implode(' ', $propertyTerms);
        }

        $query = new AgriculturalKnowledgeQuery(
            originalQuestion: $question,
            normalizedQuestion: $question,
            language: 'en',
            agriculturalDomain: 'agronomy',
            subject: ['type' => 'crop', 'value' => $crop, 'label' => ucfirst($crop)],
            crop: $crop,
            cropId: $crop,
            scientificName: null,
            topic: $topic,
            subtopic: null,
            requestedInformation: $propertyTerms !== [] ? $propertyTerms : ['research'],
            constraints: $constraints,
            location: $location,
            researchRequired: true,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            researchIntent: $intent,
        );

        return new KnowledgeQueryPlan(
            normalizedQuery: $query,
            researchIntent: $intent,
            agriculturalDomain: 'agronomy',
            subjectEntity: ['type' => 'crop', 'value' => $crop, 'label' => ucfirst($crop)],
            topics: [$topic],
            subtopics: [],
            requestedInformation: $propertyTerms !== [] ? $propertyTerms : ['research'],
            evidenceRequirements: ['peer_reviewed'],
            sourcePriorities: ['openalex', 'crossref', 'semantic_scholar'],
            primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            contextInput: $contextInput,
            readyForStage3: true,
        );
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
            ],
            rawMetadata: [
                'faostat' => [
                    'item' => $entity,
                    'item_code' => $itemCode,
                    'area' => $location,
                    'area_code' => $areaCode,
                    'element' => $property,
                    'query_element_code' => $queryElement,
                    'response_element_code' => $queryElement === '2510' ? '5510' : ($queryElement === '2413' ? '5419' : '5312'),
                    'element_code' => $queryElement === '2510' ? '5510' : ($queryElement === '2413' ? '5419' : '5312'),
                    'year' => $year,
                    'unit' => 't',
                    'value' => $value,
                ],
            ],
        );
    }
}

