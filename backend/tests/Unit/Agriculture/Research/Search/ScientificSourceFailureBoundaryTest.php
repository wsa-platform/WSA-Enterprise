<?php

namespace Tests\Unit\Agriculture\Research\Search;

use App\Contracts\ScientificSourceAdapterInterface;
use App\Contracts\ScientificSourceAdapterV2Interface;
use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Search\Adapters\ConsensusScientificSourceAdapter;
use App\Services\Agriculture\Research\Search\Adapters\CrossRefScientificSourceAdapter;
use App\Services\Agriculture\Research\Search\Adapters\OpenAlexScientificSourceAdapter;
use App\Services\Agriculture\Research\Search\Adapters\SemanticScholarScientificSourceAdapter;
use App\Services\Agriculture\Research\Search\Contract\ScientificSourceAdapterDescriptor;
use App\Services\Agriculture\Research\Search\Contract\ScientificSourceLicensingDeclaration;
use App\Services\Agriculture\Research\Search\Contract\ScientificSourceRetryPolicy;
use App\Services\Agriculture\Research\Search\Contract\ScientificSourceSearchRequest;
use App\Services\Agriculture\Research\Search\MultiSourceScientificSearchOrchestrator;
use App\Services\Agriculture\Research\Search\ScientificResultDeduplicator;
use App\Services\Agriculture\Research\Search\ScientificResultRanker;
use App\Services\Agriculture\Research\Search\ScientificSearchQueryBuilder;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;
use App\Services\Agriculture\Research\Search\ScientificSourceAdapterRegistry;
use App\Services\Agriculture\Research\Search\ScientificSourceSearchOutcome;
use App\Services\Agriculture\Research\Search\ScientificSourceSelector;
use Illuminate\Concurrency\ConcurrencyManager;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\StrayRequestException;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Concurrency;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Mockery;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * ADR-023 §8.36 increment R3: only expected source failures become failed outcomes, in the
 * HTTP adapters and on both the sequential and the concurrent orchestrator path.
 */
class ScientificSourceFailureBoundaryTest extends TestCase
{
    private const SECRET = 'SECRET-BOUNDARY-KEY';

    /** @var list<MessageLogged> */
    private array $logged = [];

    protected function setUp(): void
    {
        parent::setUp();
        Sleep::fake();
        config([
            'wsa.consensus_api_key' => 'boundary-consensus-key',
            'wsa.openalex_api_key' => self::SECRET,
            'agricultural_intelligence.openalex.enabled' => true,
            'agricultural_intelligence.crossref.enabled' => true,
            'agricultural_intelligence.stage3_home_scholarly_concurrency' => true,
        ]);
        $this->logged = [];
        Event::listen(MessageLogged::class, function (MessageLogged $event): void {
            $this->logged[] = $event;
        });
    }

    protected function tearDown(): void
    {
        Concurrency::swap($this->app->make(ConcurrencyManager::class));
        Mockery::close();
        parent::tearDown();
    }

    /** @return array<string, array{class-string<ScientificSourceAdapterInterface>}> */
    public static function httpAdapters(): array
    {
        return [
            'openalex' => [OpenAlexScientificSourceAdapter::class],
            'crossref' => [CrossRefScientificSourceAdapter::class],
            'semantic_scholar' => [SemanticScholarScientificSourceAdapter::class],
            'consensus' => [ConsensusScientificSourceAdapter::class],
        ];
    }

    /** @param  class-string<ScientificSourceAdapterInterface>  $adapterClass */
    #[DataProvider('httpAdapters')]
    public function test_http_adapter_propagates_a_defect_raised_inside_the_client(string $adapterClass): void
    {
        $defect = new \LogicException('boundary defect');
        Http::fake(static fn () => throw $defect);

        try {
            app($adapterClass)->search('wheat yield', 3, []);
            $this->fail('A defect inside the HTTP client must not become a source outcome.');
        } catch (\LogicException $caught) {
            $this->assertSame($defect, $caught);
        }
    }

    /** @param  class-string<ScientificSourceAdapterInterface>  $adapterClass */
    #[DataProvider('httpAdapters')]
    public function test_http_adapter_propagates_the_stray_request_test_signal(string $adapterClass): void
    {
        Http::preventStrayRequests();
        Http::fake(['https://boundary.unrelated.test/*' => Http::response([], 200)]);

        $this->expectException(StrayRequestException::class);

        app($adapterClass)->search('wheat yield', 3, []);
    }

    /** @param  class-string<ScientificSourceAdapterInterface>  $adapterClass */
    #[DataProvider('httpAdapters')]
    public function test_http_adapter_contains_a_connection_failure_without_logging_its_message(string $adapterClass): void
    {
        Http::fake(static fn () => throw new ConnectionException(
            'cURL error 7: Failed to connect for https://api.example.test/works?api_key='.self::SECRET
        ));

        $outcome = app($adapterClass)->search('wheat yield', 3, []);

        $this->assertSame(ScientificSourceSearchOutcome::STATUS_FAILED, $outcome->status);
        $this->assertSame([], $outcome->results);
        $warnings = array_filter($this->logged, static fn (MessageLogged $e): bool => $e->level === 'warning');
        $this->assertNotSame([], $warnings, 'the contained failure is still logged');
        $this->assertNoSecretOrMessageLogged();
    }

    public function test_concurrent_child_defect_propagates_through_the_pool(): void
    {
        $defect = new \LogicException('child defect');
        $this->runTasksInProcess();
        $openAlex = new BoundaryScriptedAdapter('openalex', static fn () => throw $defect);
        $crossref = new BoundaryScriptedAdapter('crossref', static fn (string $q) => self::success('crossref', $q));

        try {
            $this->orchestrator([$openAlex, $crossref])->execute($this->plan(), 5, ['openalex', 'crossref']);
            $this->fail('A defect inside a concurrent task must propagate.');
        } catch (\LogicException $caught) {
            $this->assertSame($defect, $caught);
        }
    }

    /** @return array<string, array{\Throwable}> */
    public static function recreatedChildExceptions(): array
    {
        return [
            'runtime' => [new \RuntimeException('re-created child defect')],
            'plain exception' => [new \Exception('re-created child exception')],
            'type error' => [new \TypeError('re-created child type error')],
            'stray request' => [new StrayRequestException('https://example.test')],
            'phpunit assertion' => [new AssertionFailedError('re-created child assertion')],
        ];
    }

    #[DataProvider('recreatedChildExceptions')]
    public function test_a_child_exception_recreated_by_the_driver_propagates(\Throwable $recreated): void
    {
        $this->mockProcessDriverRun(static fn () => throw $recreated);
        $adapters = [
            new BoundaryScriptedAdapter('openalex', static fn (string $q) => self::success('openalex', $q)),
            new BoundaryScriptedAdapter('crossref', static fn (string $q) => self::success('crossref', $q)),
        ];

        $caught = null;
        try {
            $this->orchestrator($adapters)->execute($this->plan(), 5, ['openalex', 'crossref']);
        } catch (\Throwable $throwable) {
            $caught = $throwable;
        }

        $this->assertSame($recreated, $caught);
    }

    /** @return array<string, array{\Throwable}> */
    public static function processPoolFailures(): array
    {
        return [
            'child exit code' => [new \Exception('Concurrent process failed with exit code [255]. Message: '.self::SECRET)],
            'process start' => [new \Symfony\Component\Process\Exception\RuntimeException('Unable to launch a new process. '.self::SECRET)],
        ];
    }

    #[DataProvider('processPoolFailures')]
    public function test_a_process_pool_failure_is_contained_without_logging_its_message(\Throwable $failure): void
    {
        $this->mockProcessDriverRun(static fn () => throw $failure);
        $adapters = [
            new BoundaryScriptedAdapter('openalex', static fn (string $q) => self::success('openalex', $q)),
            new BoundaryScriptedAdapter('crossref', static fn (string $q) => self::success('crossref', $q)),
        ];

        $report = $this->orchestrator($adapters)->execute($this->plan(), 5, ['openalex', 'crossref']);

        $this->assertContains('openalex', $report->failedSources);
        $this->assertContains('crossref', $report->failedSources);
        $poolWarnings = array_filter(
            $this->logged,
            static fn (MessageLogged $e): bool => str_contains($e->message, 'concurrency process pool failed'),
        );
        $this->assertCount(1, $poolWarnings);
        $this->assertSame($failure::class, array_values($poolWarnings)[0]->context['exception']);
        $this->assertNoSecretOrMessageLogged();
    }

    public function test_an_expected_failure_inside_a_concurrent_task_fails_only_its_source(): void
    {
        $this->runTasksInProcess();
        $openAlex = new BoundaryScriptedAdapter('openalex', static fn () => throw new ConnectionException('refused '.self::SECRET));
        $crossref = new BoundaryScriptedAdapter('crossref', static fn (string $q) => self::success('crossref', $q));

        $report = $this->orchestrator([$openAlex, $crossref])->execute($this->plan(), 5, ['openalex', 'crossref']);

        $this->assertContains('openalex', $report->failedSources);
        $this->assertContains('crossref', $report->successfulSources);
        $this->assertCount(1, $openAlex->queries, 'remaining variants are skipped after an adapter exception');
        $this->assertContains(
            ['source' => 'openalex', 'error' => 'adapter_exception', 'failure_category' => 'transport', 'retryable' => true],
            $report->planSummary['search_observability']['source_failures'],
        );
        $this->assertNoSecretOrMessageLogged();
    }

    public function test_sequential_path_classifies_v1_outcome_errors_in_observability(): void
    {
        config(['agricultural_intelligence.stage3_home_scholarly_concurrency' => false]);
        $openAlex = new BoundaryScriptedAdapter('openalex', static fn () => new ScientificSourceSearchOutcome(
            sourceKey: 'openalex',
            status: ScientificSourceSearchOutcome::STATUS_UNAVAILABLE,
            error: 'rate_limited',
            httpStatus: 429,
        ));
        $crossref = new BoundaryScriptedAdapter('crossref', static fn (string $q) => self::success('crossref', $q));

        $report = $this->orchestrator([$openAlex, $crossref])->execute($this->plan(), 5, ['openalex', 'crossref']);

        $this->assertSame(
            [['source' => 'openalex', 'error' => 'rate_limited', 'failure_category' => 'rate_limited', 'retryable' => true]],
            $report->planSummary['search_observability']['source_failures'],
        );
    }

    public function test_v2_adapter_receives_the_builder_query_text_unchanged(): void
    {
        config(['agricultural_intelligence.stage3_home_scholarly_concurrency' => false]);
        $v2 = new BoundaryV2Adapter('openalex');

        $this->orchestrator([$v2])->execute($this->plan(), 5, ['openalex']);

        $this->assertSame(
            ['variant-a', 'variant-b'],
            array_map(static fn (ScientificSourceSearchRequest $r): string => $r->queryText, $v2->requests),
        );
        $this->assertSame(5, $v2->requests[0]->limit);
        $this->assertNotNull($v2->requests[0]->remainingBudgetSeconds());
    }

    public function test_v2_request_limit_is_at_least_one_as_every_v1_adapter_clamps_it(): void
    {
        config(['agricultural_intelligence.stage3_home_scholarly_concurrency' => false]);
        $v2 = new BoundaryV2Adapter('openalex');

        $this->orchestrator([$v2])->execute($this->plan(), 0, ['openalex']);

        $this->assertNotSame([], $v2->requests);
        $this->assertSame(1, $v2->requests[0]->limit);
    }

    private function assertNoSecretOrMessageLogged(): void
    {
        foreach ($this->logged as $event) {
            if (array_key_exists('exception', $event->context)) {
                $this->assertArrayNotHasKey('message', $event->context, $event->message);
            }
            $this->assertStringNotContainsString(self::SECRET, $event->message.json_encode($event->context));
        }
    }

    private function runTasksInProcess(): void
    {
        $this->mockProcessDriverRun(
            static fn (array $tasks): array => array_map(static fn (callable $task): mixed => $task(), $tasks),
        );
    }

    private function mockProcessDriverRun(callable $run): void
    {
        $driver = Mockery::mock(\Illuminate\Contracts\Concurrency\Driver::class);
        $driver->shouldReceive('run')->once()->andReturnUsing($run);
        Concurrency::shouldReceive('driver')->with('process')->andReturn($driver);
    }

    /** @param  list<ScientificSourceAdapterInterface>  $adapters */
    private function orchestrator(array $adapters): MultiSourceScientificSearchOrchestrator
    {
        $registry = Mockery::mock(ScientificSourceAdapterRegistry::class);
        $registry->shouldReceive('resolveMany')->andReturnUsing(
            static function (array $keys) use ($adapters): array {
                $resolved = [];
                foreach ($keys as $key) {
                    foreach ($adapters as $adapter) {
                        if ($adapter->sourceKey() === $key) {
                            $resolved[] = $adapter;
                        }
                    }
                }

                return $resolved;
            }
        );

        $builder = Mockery::mock(ScientificSearchQueryBuilder::class);
        $builder->shouldReceive('buildVariantsFromPlan')->andReturn(['variant-a', 'variant-b']);
        $builder->shouldReceive('buildFromPlan')->andReturn('variant-a');
        $builder->shouldReceive('buildConsensusRequestOptions')->andReturn([]);

        return new MultiSourceScientificSearchOrchestrator(
            $registry,
            app(ScientificSourceSelector::class),
            $builder,
            app(ScientificResultDeduplicator::class),
            app(ScientificResultRanker::class),
        );
    }

    public static function success(string $sourceKey, string $query): ScientificSourceSearchOutcome
    {
        return new ScientificSourceSearchOutcome(
            sourceKey: $sourceKey,
            status: ScientificSourceSearchOutcome::STATUS_SUCCESS,
            results: [
                new ScientificSearchResult(
                    sourceKey: $sourceKey,
                    sourceIdentifier: $sourceKey.'-'.md5($query),
                    title: 'Barley irrigation water requirement '.$sourceKey,
                    authors: ['A Researcher'],
                    publicationYear: 2021,
                    doi: '10.1000/boundary-'.$sourceKey.'-'.md5($query),
                    canonicalUrl: 'https://example.test/'.$sourceKey,
                    abstract: 'Irrigation requirement',
                    journal: 'Ag Journal',
                    foundBySources: [$sourceKey],
                ),
            ],
        );
    }

    private function plan(): KnowledgeQueryPlan
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
            sourcePriorities: ['openalex', 'crossref'],
            primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            contextInput: [],
            readyForStage3: true,
        );
    }
}

class BoundaryScriptedAdapter implements ScientificSourceAdapterInterface
{
    /** @var list<string> */
    public array $queries = [];

    public function __construct(
        private readonly string $key,
        private readonly \Closure $behaviour,
    ) {}

    public function sourceKey(): string
    {
        return $this->key;
    }

    public function displayName(): string
    {
        return 'Boundary '.$this->key;
    }

    public function search(string $query, int $limit = 10, array $options = []): ScientificSourceSearchOutcome
    {
        $this->queries[] = $query;

        return ($this->behaviour)($query);
    }
}

class BoundaryV2Adapter implements ScientificSourceAdapterV2Interface
{
    /** @var list<ScientificSourceSearchRequest> */
    public array $requests = [];

    public function __construct(private readonly string $key) {}

    public function sourceKey(): string
    {
        return $this->key;
    }

    public function displayName(): string
    {
        return 'Boundary v2 '.$this->key;
    }

    public function search(string $query, int $limit = 10, array $options = []): ScientificSourceSearchOutcome
    {
        throw new \LogicException('The orchestrator must call searchRequest() on a v2 adapter.');
    }

    public function searchRequest(ScientificSourceSearchRequest $request): ScientificSourceSearchOutcome
    {
        $this->requests[] = $request;

        return new ScientificSourceSearchOutcome($this->key, ScientificSourceSearchOutcome::STATUS_EMPTY);
    }

    public function descriptor(): ScientificSourceAdapterDescriptor
    {
        return new ScientificSourceAdapterDescriptor(
            sourceKey: $this->key,
            licensing: ScientificSourceLicensingDeclaration::unresolved(),
            retryPolicy: ScientificSourceRetryPolicy::none(),
        );
    }
}
