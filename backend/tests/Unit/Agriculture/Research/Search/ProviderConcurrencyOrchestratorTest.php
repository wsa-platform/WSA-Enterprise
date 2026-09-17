<?php

namespace Tests\Unit\Agriculture\Research\Search;

use App\Contracts\ScientificSourceAdapterInterface;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatRuntimePolicy;
use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Search\MultiSourceScientificSearchOrchestrator;
use App\Services\Agriculture\Research\Search\ScientificResultDeduplicator;
use App\Services\Agriculture\Research\Search\ScientificResultRanker;
use App\Services\Agriculture\Research\Search\ScientificSearchQueryBuilder;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;
use App\Services\Agriculture\Research\Search\ScientificSourceAdapterRegistry;
use App\Services\Agriculture\Research\Search\ScientificSourceSearchOutcome;
use App\Services\Agriculture\Research\Search\ScientificSourceSelector;
use Illuminate\Concurrency\ConcurrencyManager;
use Illuminate\Support\Facades\Concurrency;
use Mockery;
use Tests\TestCase;

class ProviderConcurrencyOrchestratorTest extends TestCase
{
    private string $tracePath;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'agricultural_intelligence.faostat.enabled' => true,
            'agricultural_intelligence.openalex.enabled' => true,
            'agricultural_intelligence.crossref.enabled' => true,
            'agricultural_intelligence.semantic_scholar.enabled' => true,
            'agricultural_intelligence.stage3_home_scholarly_concurrency' => true,
        ]);
        $this->tracePath = tempnam(sys_get_temp_dir(), 'p4d-trace-');
        $this->assertNotFalse($this->tracePath);
    }

    protected function tearDown(): void
    {
        if (isset($this->tracePath) && is_file($this->tracePath)) {
            @unlink($this->tracePath);
        }
        Concurrency::swap($this->app->make(ConcurrencyManager::class));
        Mockery::close();
        parent::tearDown();
    }

    public function test_home_generic_research_overlaps_independent_scholarly_providers(): void
    {
        $openAlex = $this->recording('openalex', [$this->successOutcome('openalex', 'oa-1', '10.1000/p4d-oa')], 2_000_000);
        $crossref = $this->recording('crossref', [$this->successOutcome('crossref', 'cr-1', '10.1000/p4d-cr')], 2_000_000);
        $semantic = $this->recording('semantic_scholar', [$this->successOutcome('semantic_scholar', 's2-1', '10.1000/p4d-s2')], 2_000_000);

        $this->orchestrator($openAlex, $crossref, $semantic)
            ->execute($this->homePlan(), 5, ['openalex', 'crossref', 'semantic_scholar']);

        $starts = $this->eventTimes('start');
        $ends = $this->eventTimes('end');

        $this->assertCount(2, $starts['openalex'] ?? [], 'MAX_VARIANTS_PER_PROVIDER remains 2');
        $this->assertNotEmpty($starts['crossref'] ?? []);
        $this->assertNotEmpty($starts['semantic_scholar'] ?? []);
        $this->assertTrue(
            $this->intervalsOverlap(
                $starts['openalex'][0],
                $ends['openalex'][0],
                $starts['crossref'][0],
                $ends['crossref'][0],
            ),
            'OpenAlex and Crossref first variants must overlap rather than wait sequentially',
        );
        $this->assertTrue(
            $this->intervalsOverlap(
                $starts['openalex'][0],
                $ends['openalex'][0],
                $starts['semantic_scholar'][0],
                $ends['semantic_scholar'][0],
            ),
            'OpenAlex and Semantic Scholar first variants must overlap rather than wait sequentially',
        );
    }

    public function test_deterministic_merge_ignores_completion_order(): void
    {
        $doi = '10.1000/p4d-shared';
        $openAlex = $this->recording('openalex', [
            $this->successOutcome('openalex', 'oa-shared', $doi, 'OpenAlex shared title'),
            $this->successOutcome('openalex', 'oa-2', '10.1000/p4d-oa-2'),
        ], 350_000);
        $crossref = $this->recording('crossref', [
            $this->successOutcome('crossref', 'cr-1', '10.1000/p4d-cr-1'),
            $this->successOutcome('crossref', 'cr-2', '10.1000/p4d-cr-2'),
        ], 180_000);
        $semantic = $this->recording('semantic_scholar', [
            $this->successOutcome('semantic_scholar', 's2-shared', $doi, 'Semantic Scholar shared title'),
            $this->successOutcome('semantic_scholar', 's2-2', '10.1000/p4d-s2-2'),
        ], 40_000);

        $report = $this->orchestrator($openAlex, $crossref, $semantic)
            ->execute($this->homePlan(), 5, ['openalex', 'crossref', 'semantic_scholar']);

        $outcomeKeys = array_map(
            static fn (ScientificSourceSearchOutcome $outcome): string => $outcome->sourceKey,
            $report->sourceOutcomes,
        );
        $this->assertSame(
            ['openalex', 'openalex', 'crossref', 'crossref', 'semantic_scholar', 'semantic_scholar'],
            $outcomeKeys,
        );

        $deduped = app(ScientificResultDeduplicator::class)->deduplicate($report->results);
        $shared = null;
        foreach ($deduped as $result) {
            if ($result->doi === $doi) {
                $shared = $result;
                break;
            }
        }
        $this->assertNotNull($shared);
        $this->assertSame('openalex', $shared->sourceKey);
        $this->assertContains('openalex', $shared->foundBySources);
        $this->assertContains('semantic_scholar', $shared->foundBySources);
    }

    public function test_one_scholarly_failure_does_not_abort_independent_providers(): void
    {
        $openAlex = $this->recording('openalex', [
            new ScientificSourceSearchOutcome(
                sourceKey: 'openalex',
                status: ScientificSourceSearchOutcome::STATUS_FAILED,
                error: 'http_error',
                httpStatus: 500,
            ),
        ]);
        $crossref = $this->recording('crossref', [$this->successOutcome('crossref', 'cr-1', '10.1000/p4d-cr-ok')]);
        $semantic = $this->recording('semantic_scholar', [$this->successOutcome('semantic_scholar', 's2-1', '10.1000/p4d-s2-ok')]);

        $report = $this->orchestrator($openAlex, $crossref, $semantic)
            ->execute($this->homePlan(), 5, ['openalex', 'crossref', 'semantic_scholar']);

        $this->assertContains('openalex', $report->failedSources);
        $this->assertContains('crossref', $report->successfulSources);
        $this->assertContains('semantic_scholar', $report->successfulSources);
        $this->assertNotEmpty($this->eventTimes('start')['crossref'] ?? []);
        $this->assertNotEmpty($this->eventTimes('start')['semantic_scholar'] ?? []);
    }

    public function test_scholarly_wave_inherits_parent_deadline_not_a_fresh_child_start(): void
    {
        config(['agricultural_intelligence.search_time_budget_seconds' => 20]);

        $fao = $this->recording(
            FaoStatRuntimePolicy::canonicalSourceKey(),
            [$this->completeFaoOutcome()],
            5_000_000,
        );
        $openAlex = $this->recording('openalex', [$this->successOutcome('openalex', 'oa-1', '10.1000/p4d-budget-oa')]);
        $crossref = $this->recording('crossref', [$this->successOutcome('crossref', 'cr-1', '10.1000/p4d-budget-cr')]);

        $report = $this->orchestrator($fao, $openAlex, $crossref)
            ->execute($this->homePlan(), 5, ['fao_stat', 'openalex', 'crossref']);

        $this->assertSame(20, $report->planSummary['search_time_budget_seconds']);
        $this->assertSame([], $report->planSummary['adapters_skipped_time_budget']);

        $scholarlyStarts = array_values(array_filter(
            $this->traceEvents('start'),
            static fn (array $event): bool => in_array($event['key'], ['openalex', 'crossref'], true),
        ));
        $this->assertNotEmpty($scholarlyStarts);
        foreach ($scholarlyStarts as $event) {
            $this->assertNotNull($event['remaining_option']);
            $this->assertNotNull($event['live_remaining']);
            $this->assertSame(20, $event['budget_seconds']);
            $this->assertGreaterThan(1.0, (float) $event['live_remaining']);
            $this->assertLessThan(
                (float) $event['budget_seconds'] - 3.5,
                (float) $event['live_remaining'],
                'child must inherit the parent deadline; a fresh start() would still show ~20s remaining',
            );
            $this->assertLessThan(
                (float) $event['budget_seconds'] - 3.5,
                (float) $event['remaining_option'],
            );
        }
    }

    public function test_process_pool_failure_does_not_replay_scholarly_search(): void
    {
        $this->mockProcessDriverRun(function (array $tasks): array {
            $wave = [];
            foreach ($tasks as $key => $task) {
                $wave[$key] = $task();
            }
            throw new \RuntimeException('simulated scholarly process pool failure');
        });

        $openAlex = $this->recording('openalex', [$this->successOutcome('openalex', 'oa-1', '10.1000/p4d-no-replay-oa')]);
        $crossref = $this->recording('crossref', [$this->successOutcome('crossref', 'cr-1', '10.1000/p4d-no-replay-cr')]);
        $semantic = $this->recording('semantic_scholar', [$this->successOutcome('semantic_scholar', 's2-1', '10.1000/p4d-no-replay-s2')]);

        $report = $this->orchestrator($openAlex, $crossref, $semantic, ['variant-a'])
            ->execute($this->homePlan(), 5, ['openalex', 'crossref', 'semantic_scholar']);

        $this->assertCount(1, $this->eventTimes('start')['openalex'] ?? []);
        $this->assertCount(1, $this->eventTimes('start')['crossref'] ?? []);
        $this->assertCount(1, $this->eventTimes('start')['semantic_scholar'] ?? []);
        $this->assertContains('openalex', $report->failedSources);
        $this->assertContains('crossref', $report->failedSources);
        $this->assertContains('semantic_scholar', $report->failedSources);
    }

    public function test_missing_wave_result_does_not_replay_scholarly_search(): void
    {
        $this->mockProcessDriverRun(function (array $tasks): array {
            $wave = [];
            foreach ($tasks as $key => $task) {
                $wave[$key] = $task();
            }
            unset($wave['openalex']);

            return $wave;
        });

        $openAlex = $this->recording('openalex', [$this->successOutcome('openalex', 'oa-1', '10.1000/p4d-missing-oa')]);
        $crossref = $this->recording('crossref', [$this->successOutcome('crossref', 'cr-1', '10.1000/p4d-missing-cr')]);

        $report = $this->orchestrator($openAlex, $crossref, null, ['variant-a'])
            ->execute($this->homePlan(), 5, ['openalex', 'crossref']);

        $this->assertCount(1, $this->eventTimes('start')['openalex'] ?? []);
        $this->assertCount(1, $this->eventTimes('start')['crossref'] ?? []);
        $this->assertContains('openalex', $report->failedSources);
        $this->assertContains('crossref', $report->successfulSources);
    }

    public function test_max_variants_per_provider_remains_two(): void
    {
        $openAlex = $this->recording('openalex', [
            $this->successOutcome('openalex', 'oa-1', '10.1000/p4d-v1'),
            $this->successOutcome('openalex', 'oa-2', '10.1000/p4d-v2'),
            $this->successOutcome('openalex', 'oa-3', '10.1000/p4d-v3'),
        ]);
        $crossref = $this->recording('crossref', [
            $this->successOutcome('crossref', 'cr-1', '10.1000/p4d-cr-v1'),
            $this->successOutcome('crossref', 'cr-2', '10.1000/p4d-cr-v2'),
            $this->successOutcome('crossref', 'cr-3', '10.1000/p4d-cr-v3'),
        ]);

        $this->orchestrator($openAlex, $crossref, null, ['variant-a', 'variant-b', 'variant-c'])
            ->execute($this->homePlan(), 5, ['openalex', 'crossref']);

        $starts = $this->eventTimes('start');
        $this->assertCount(2, $starts['openalex']);
        $this->assertCount(2, $starts['crossref']);
        $this->assertSame(['variant-a', 'variant-b'], $this->queriesFor('openalex'));
        $this->assertSame(['variant-a', 'variant-b'], $this->queriesFor('crossref'));
    }

    public function test_faostat_remains_first_sequential_and_p4b_complete_observation_stops_extra_variants(): void
    {
        $fao = $this->recording(
            FaoStatRuntimePolicy::canonicalSourceKey(),
            [$this->completeFaoOutcome()],
            80_000,
        );
        $openAlex = $this->recording('openalex', [$this->successOutcome('openalex', 'oa-1', '10.1000/p4d-after-fao')], 80_000);
        $crossref = $this->recording('crossref', [$this->successOutcome('crossref', 'cr-1', '10.1000/p4d-after-fao-cr')], 80_000);

        $report = $this->orchestrator($fao, $openAlex, $crossref)
            ->execute($this->homePlan(), 5, ['openalex', 'crossref', 'fao_stat']);

        $this->assertSame('fao_stat', $report->selectedSources[0] ?? null);
        $this->assertCount(1, $this->eventTimes('start')['fao_stat'] ?? []);
        $this->assertGreaterThanOrEqual(1, count($this->eventTimes('start')['openalex'] ?? []));
        $this->assertGreaterThanOrEqual(1, count($this->eventTimes('start')['crossref'] ?? []));

        $faoEnds = $this->eventTimes('end')['fao_stat'];
        $scholarlyStarts = array_merge(
            $this->eventTimes('start')['openalex'] ?? [],
            $this->eventTimes('start')['crossref'] ?? [],
        );
        $this->assertNotEmpty($scholarlyStarts);
        $this->assertLessThanOrEqual(min($scholarlyStarts), max($faoEnds));
    }

    public function test_rate_limited_provider_skips_remaining_variants_without_stopping_others(): void
    {
        $openAlex = $this->recording('openalex', [
            new ScientificSourceSearchOutcome(
                sourceKey: 'openalex',
                status: ScientificSourceSearchOutcome::STATUS_UNAVAILABLE,
                error: 'rate_limited',
                httpStatus: 429,
            ),
            $this->successOutcome('openalex', 'oa-should-not-run', '10.1000/p4d-oa-skip'),
        ]);
        $crossref = $this->recording('crossref', [
            $this->successOutcome('crossref', 'cr-1', '10.1000/p4d-cr-429'),
            $this->successOutcome('crossref', 'cr-2', '10.1000/p4d-cr-429-b'),
        ]);

        $report = $this->orchestrator($openAlex, $crossref)
            ->execute($this->homePlan(), 5, ['openalex', 'crossref']);

        $this->assertCount(1, $this->eventTimes('start')['openalex'] ?? []);
        $this->assertCount(2, $this->eventTimes('start')['crossref'] ?? []);
        $this->assertContains('crossref', $report->successfulSources);
    }

    public function test_crop_profile_does_not_enter_scholarly_concurrency_path(): void
    {
        Concurrency::shouldReceive('driver')->never();

        $openAlex = $this->recording('openalex', [$this->successOutcome('openalex', 'oa-1', '10.1000/p4d-crop-oa')]);
        $crossref = $this->recording('crossref', [$this->successOutcome('crossref', 'cr-1', '10.1000/p4d-crop-cr')]);
        $semantic = $this->recording('semantic_scholar', [$this->successOutcome('semantic_scholar', 's2-1', '10.1000/p4d-crop-s2')]);
        $orchestrator = $this->orchestrator($openAlex, $crossref, $semantic);

        $gate = new \ReflectionMethod($orchestrator, 'shouldOverlapIndependentScholarlyProviders');
        $gate->setAccessible(true);
        $this->assertFalse($gate->invoke($orchestrator, $this->cropPlan()));
        $this->assertTrue($gate->invoke($orchestrator, $this->homePlan()));

        $report = $orchestrator->execute($this->cropPlan(), 5, ['openalex', 'crossref', 'semantic_scholar']);

        $this->assertSame('crop_profile', $this->cropPlan()->toAgriculturalResearchPlan()->intent);
        $this->assertContains('openalex', $report->successfulSources);
        $this->assertContains('crossref', $report->successfulSources);
        $this->assertContains('semantic_scholar', $report->successfulSources);
        $this->assertSame(
            ['openalex', 'openalex', 'crossref', 'crossref', 'semantic_scholar', 'semantic_scholar'],
            array_map(
                static fn (ScientificSourceSearchOutcome $outcome): string => $outcome->sourceKey,
                $report->sourceOutcomes,
            ),
        );
    }

    public function test_home_generic_research_enters_concurrency_path(): void
    {
        $this->assertSame('generic_research', $this->homePlan()->toAgriculturalResearchPlan()->intent);
        $this->assertFalse($this->homePlan()->toAgriculturalResearchPlan()->isCropProfileIntent());

        $openAlex = $this->recording('openalex', [$this->successOutcome('openalex', 'oa-1', '10.1000/p4d-home-oa')], 750_000);
        $crossref = $this->recording('crossref', [$this->successOutcome('crossref', 'cr-1', '10.1000/p4d-home-cr')], 750_000);

        $this->orchestrator($openAlex, $crossref)
            ->execute($this->homePlan(), 5, ['openalex', 'crossref']);

        $starts = $this->eventTimes('start');
        $ends = $this->eventTimes('end');
        $this->assertLessThan($ends['openalex'][0], $starts['crossref'][0]);
        $this->assertLessThan($ends['crossref'][0], $starts['openalex'][0]);
    }

    public function test_orchestrator_does_not_own_or_alter_p4c_direct_evidence_contract(): void
    {
        $source = file_get_contents(
            base_path('app/Services/Agriculture/Research/Search/MultiSourceScientificSearchOrchestrator.php')
        );
        $this->assertIsString($source);
        $this->assertStringNotContainsString('direct_evidence_gate', $source);
        $this->assertStringNotContainsString('hasSufficientScientificSynthesis', $source);
        $this->assertStringNotContainsString('payloadHasSufficientScientificResult', $source);

        $openAlex = $this->recording('openalex', [$this->successOutcome('openalex', 'oa-1', '10.1000/p4d-p4c-oa')]);
        $crossref = $this->recording('crossref', [$this->successOutcome('crossref', 'cr-1', '10.1000/p4d-p4c-cr')]);
        $report = $this->orchestrator($openAlex, $crossref)
            ->execute($this->homePlan(), 5, ['openalex', 'crossref']);

        $this->assertArrayNotHasKey('direct_evidence_gate', $report->planSummary);
        $this->assertArrayNotHasKey('legacy_skipped', $report->planSummary);
    }

    private function mockProcessDriverRun(callable $run): void
    {
        $driver = Mockery::mock(\Illuminate\Contracts\Concurrency\Driver::class);
        $driver->shouldReceive('run')->once()->andReturnUsing($run);
        Concurrency::shouldReceive('driver')->with('process')->andReturn($driver);
    }

    /**
     * @param  list<ScientificSourceSearchOutcome>  $outcomes
     */
    private function recording(
        string $sourceKey,
        array $outcomes,
        int $delayMicroseconds = 0,
    ): P4dRecordingScientificSourceAdapter {
        return new P4dRecordingScientificSourceAdapter(
            $sourceKey,
            $this->tracePath,
            $outcomes,
            $delayMicroseconds,
        );
    }

    private function orchestrator(
        ScientificSourceAdapterInterface $first,
        ScientificSourceAdapterInterface $second,
        ?ScientificSourceAdapterInterface $third = null,
        array $variants = ['variant-a', 'variant-b'],
    ): MultiSourceScientificSearchOrchestrator {
        $adapters = [$first, $second];
        if ($third !== null) {
            $adapters[] = $third;
        }

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

        $builder = Mockery::mock(ScientificSearchQueryBuilder::class);
        $builder->shouldReceive('buildVariantsFromPlan')->andReturn($variants);
        $builder->shouldReceive('buildFromPlan')->andReturn($variants[0]);
        $builder->shouldReceive('buildConsensusRequestOptions')->andReturn([]);

        return new MultiSourceScientificSearchOrchestrator(
            $registry,
            app(ScientificSourceSelector::class),
            $builder,
            app(ScientificResultDeduplicator::class),
            app(ScientificResultRanker::class),
        );
    }

    private function successOutcome(
        string $sourceKey,
        string $identifier,
        string $doi,
        ?string $title = null,
    ): ScientificSourceSearchOutcome {
        return new ScientificSourceSearchOutcome(
            sourceKey: $sourceKey,
            status: ScientificSourceSearchOutcome::STATUS_SUCCESS,
            results: [
                new ScientificSearchResult(
                    sourceKey: $sourceKey,
                    sourceIdentifier: $identifier,
                    title: $title ?? 'Barley irrigation water requirement '.$identifier,
                    authors: ['A Researcher'],
                    publicationYear: 2021,
                    doi: $doi,
                    canonicalUrl: 'https://example.test/'.$identifier,
                    abstract: 'Irrigation requirement',
                    journal: 'Ag Journal',
                    foundBySources: [$sourceKey],
                ),
            ],
        );
    }

    private function completeFaoOutcome(): ScientificSourceSearchOutcome
    {
        $key = FaoStatRuntimePolicy::canonicalSourceKey();

        return new ScientificSourceSearchOutcome(
            sourceKey: $key,
            status: ScientificSourceSearchOutcome::STATUS_SUCCESS,
            results: [
                new ScientificSearchResult(
                    sourceKey: $key,
                    sourceIdentifier: 'QCL|59|15|5510|2020',
                    title: 'Wheat — Production — Egypt — 2020',
                    authors: [],
                    publicationYear: null,
                    doi: null,
                    canonicalUrl: 'https://faostatservices.fao.org/api/v1/en/data/QCL',
                    abstract: 'Value: 9101785 t',
                    journal: null,
                    foundBySources: [$key],
                    relevanceMetadata: ['not_literature' => true],
                    rawMetadata: [
                        'faostat' => [
                            'item' => 'Wheat',
                            'area' => 'Egypt',
                            'year' => '2020',
                            'element' => 'Production',
                            'value' => '9101785',
                            'unit' => 't',
                        ],
                    ],
                ),
            ],
        );
    }

    private function homePlan(): KnowledgeQueryPlan
    {
        return $this->plan([]);
    }

    private function cropPlan(): KnowledgeQueryPlan
    {
        return $this->plan([
            'selected_crop_id' => 'wheat',
            'selected_crop_name' => 'Wheat',
        ]);
    }

    /**
     * @param  array<string, mixed>  $contextInput
     */
    private function plan(array $contextInput): KnowledgeQueryPlan
    {
        $query = new AgriculturalKnowledgeQuery(
            originalQuestion: 'barley production quantity Brazil 2019',
            normalizedQuestion: 'barley production quantity Brazil 2019',
            language: 'en',
            agriculturalDomain: 'agronomy',
            subject: ['type' => 'crop', 'value' => 'barley'],
            crop: 'barley',
            cropId: 'barley',
            scientificName: null,
            topic: 'production',
            subtopic: null,
            requestedInformation: ['quantity'],
            constraints: [
                'question_type' => 'statistical',
                'year' => '2019',
            ],
            location: 'brazil',
            researchRequired: true,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            researchIntent: 'statistical_lookup',
        );

        return new KnowledgeQueryPlan(
            normalizedQuery: $query,
            researchIntent: 'statistical_lookup',
            agriculturalDomain: 'agronomy',
            subjectEntity: ['type' => 'crop', 'value' => 'barley'],
            topics: ['production'],
            subtopics: [],
            requestedInformation: ['quantity'],
            evidenceRequirements: ['official_statistics'],
            sourcePriorities: ['fao_stat', 'openalex', 'crossref', 'semantic_scholar'],
            primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            contextInput: $contextInput,
            readyForStage3: true,
        );
    }

    private function intervalsOverlap(float $aStart, float $aEnd, float $bStart, float $bEnd): bool
    {
        return $aStart < $bEnd && $bStart < $aEnd;
    }

    /**
     * @return list<string>
     */
    private function queriesFor(string $sourceKey): array
    {
        $queries = [];
        foreach ($this->traceEvents('start') as $event) {
            if (($event['key'] ?? null) === $sourceKey) {
                $queries[] = (string) $event['query'];
            }
        }

        return $queries;
    }

    /**
     * @return array<string, list<float>>
     */
    private function eventTimes(string $event): array
    {
        $grouped = [];
        foreach ($this->traceEvents($event) as $row) {
            $grouped[$row['key']][] = (float) $row['t'];
        }

        return $grouped;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function traceEvents(string $event): array
    {
        if (! is_file($this->tracePath)) {
            return [];
        }
        $rows = [];
        foreach (file($this->tracePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $decoded = json_decode($line, true);
            if (! is_array($decoded) || ($decoded['event'] ?? null) !== $event) {
                continue;
            }
            $rows[] = $decoded;
        }

        return $rows;
    }
}
