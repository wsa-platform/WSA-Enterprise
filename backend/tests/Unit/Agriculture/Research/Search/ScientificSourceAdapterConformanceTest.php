<?php

namespace Tests\Unit\Agriculture\Research\Search;

use App\Contracts\ScientificSourceAdapterInterface;
use App\Contracts\ScientificSourceAdapterV2Interface;
use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\Coexistence\Stage3CghiaCoexistenceBoundary;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Path\PathFamily;
use App\Services\Agriculture\Research\Search\Contract\DelegatesSearchRequestToSearch;
use App\Services\Agriculture\Research\Search\Contract\ScientificSourceRetryPolicy;
use App\Services\Agriculture\Research\Search\MultiSourceScientificSearchOrchestrator;
use App\Services\Agriculture\Research\Search\Registry\AlwaysEnabledSourceEnablement;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceAccessFamily;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceCapabilityDeclaration;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceCatalog;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceExecutionGroup;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceLifecycle;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceRegistration;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceSelectionRole;
use App\Services\Agriculture\Research\Search\ScientificQueryCompiler;
use App\Services\Agriculture\Research\Search\ScientificResultDeduplicator;
use App\Services\Agriculture\Research\Search\ScientificResultRanker;
use App\Services\Agriculture\Research\Search\ScientificSearchQueryBuilder;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;
use App\Services\Agriculture\Research\Search\ScientificSourceAdapterRegistry;
use App\Services\Agriculture\Research\Search\ScientificSourceFailure;
use App\Services\Agriculture\Research\Search\ScientificSourceProfileCatalog;
use App\Services\Agriculture\Research\Search\ScientificSourceSearchOutcome;
use App\Services\Agriculture\Research\Search\ScientificSourceSelector;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\StrayRequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Shared conformance suite: every catalog registration must pass it, so a new source
 * is checked by adding its registration, not by writing a parallel suite.
 * All HTTP is faked; stray requests fail the test.
 */
class ScientificSourceAdapterConformanceTest extends TestCase
{
    private const OUTCOME_STATUSES = [
        ScientificSourceSearchOutcome::STATUS_SUCCESS,
        ScientificSourceSearchOutcome::STATUS_EMPTY,
        ScientificSourceSearchOutcome::STATUS_FAILED,
        ScientificSourceSearchOutcome::STATUS_UNAVAILABLE,
    ];

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        Sleep::fake();
        config([
            'agricultural_intelligence.faostat.enabled' => true,
            'agricultural_intelligence.faostat.username' => 'conformance-user',
            'agricultural_intelligence.faostat.password' => 'conformance-pass',
        ]);
    }

    /** @return array<string, array{string}> */
    public static function registeredSourceKeys(): array
    {
        $keys = [];
        foreach (ScientificSourceCatalog::default()->sourceKeys() as $key) {
            $keys[$key] = [$key];
        }

        return $keys;
    }

    #[DataProvider('registeredSourceKeys')]
    public function test_registered_adapter_is_structurally_conformant(string $sourceKey): void
    {
        $registration = ScientificSourceCatalog::default()->get($sourceKey);
        $adapter = app(ScientificSourceAdapterRegistry::class)->get($sourceKey);

        $this->assertInstanceOf(ScientificSourceAdapterV2Interface::class, $adapter);
        $this->assertInstanceOf($registration->adapterClass, $adapter);
        $this->assertSame($sourceKey, $adapter->sourceKey());
        $this->assertNotSame('', trim($adapter->displayName()));
        $this->assertSame(ScientificSourceAdapterV2Interface::V2_CONTRACT_VERSION, $registration->adapterContractVersion);
        $this->assertNotNull(ScientificSourceProfileCatalog::get($sourceKey));
    }

    #[DataProvider('registeredSourceKeys')]
    public function test_registered_adapter_declares_only_what_its_code_establishes(string $sourceKey): void
    {
        $adapter = app(ScientificSourceAdapterRegistry::class)->get($sourceKey);
        $descriptor = $adapter->descriptor();

        $this->assertSame($sourceKey, $descriptor->sourceKey);
        $this->assertSame(ScientificSourceAdapterV2Interface::V2_CONTRACT_VERSION, $descriptor->contractVersion);
        // Declared licensing is allowed only when terms + evidence accompany it (constructor invariant).
        // Unresolved remains fail-closed: declaration ≠ blanket permission for every usage.
        if ($descriptor->licensing->isFullyUnresolved()) {
            $this->assertNull($descriptor->licensing->licenceIdentifier);
        } else {
            $this->assertNotNull($descriptor->licensing->termsReference, $sourceKey.' resolved licensing needs terms_reference');
            $this->assertNotNull($descriptor->licensing->evidenceReference, $sourceKey.' resolved licensing needs evidence_reference');
        }
        $this->assertEquals(ScientificSourceRetryPolicy::none(), $descriptor->retryPolicy);
        $this->assertSame(1, $descriptor->maxConcurrentRequests);
        // Null = undeclared (not "unlimited"). Non-null values must be established by the adapter/profile.
        if ($descriptor->paginationSupported !== null) {
            $this->assertIsBool($descriptor->paginationSupported);
        }
        if ($descriptor->requestsPerMinute !== null) {
            $this->assertGreaterThanOrEqual(1, $descriptor->requestsPerMinute, $sourceKey.' requestsPerMinute');
        }
        if ($descriptor->maxResultsPerRequest !== null) {
            $this->assertGreaterThanOrEqual(1, $descriptor->maxResultsPerRequest, $sourceKey.' maxResultsPerRequest');
        }
        $this->assertContains(DelegatesSearchRequestToSearch::class, class_uses($adapter), 'searchRequest() must reach the unchanged v1 search()');
    }

    #[DataProvider('registeredSourceKeys')]
    public function test_registered_adapter_classifies_a_provider_server_error(string $sourceKey): void
    {
        Http::fake(['*' => Http::response(['error' => 'conformance'], 503)]);

        $outcome = app(ScientificSourceAdapterRegistry::class)->get($sourceKey)->search('wheat yield', 3, []);

        $this->assertSame($sourceKey, $outcome->sourceKey);
        $this->assertContains($outcome->status, [
            ScientificSourceSearchOutcome::STATUS_FAILED,
            ScientificSourceSearchOutcome::STATUS_UNAVAILABLE,
            ScientificSourceSearchOutcome::STATUS_EMPTY,
        ]);
        $this->assertSame([], $outcome->results);
    }

    #[DataProvider('registeredSourceKeys')]
    public function test_registered_adapter_contains_a_connection_failure(string $sourceKey): void
    {
        Http::fake(static fn () => throw new ConnectionException('conformance: connection refused'));

        $outcome = app(ScientificSourceAdapterRegistry::class)->get($sourceKey)->search('wheat yield', 3, []);

        $this->assertSame($sourceKey, $outcome->sourceKey);
        $this->assertContains($outcome->status, self::OUTCOME_STATUSES);
        $this->assertNotSame(ScientificSourceSearchOutcome::STATUS_SUCCESS, $outcome->status);
        $this->assertSame([], $outcome->results);
    }

    #[DataProvider('registeredSourceKeys')]
    public function test_registered_adapter_never_reports_success_without_results_or_secrets(string $sourceKey): void
    {
        Http::fake(['*' => Http::response([], 200)]);

        $outcome = app(ScientificSourceAdapterRegistry::class)->get($sourceKey)->search('wheat yield', 3, []);

        $this->assertSame($sourceKey, $outcome->sourceKey);
        $this->assertContains($outcome->status, self::OUTCOME_STATUSES);
        if ($outcome->status === ScientificSourceSearchOutcome::STATUS_SUCCESS) {
            $this->assertNotSame([], $outcome->results);
        }
        foreach ($outcome->results as $result) {
            $this->assertInstanceOf(ScientificSearchResult::class, $result);
            $this->assertSame($sourceKey, $result->sourceKey);
        }
        $serialized = json_encode($outcome->toArray());
        $this->assertStringNotContainsString('conformance-pass', (string) $serialized);
    }

    /** @return array<string, array{\Closure(): \Throwable, string}> */
    public static function expectedSourceFailures(): array
    {
        return [
            'transport failure' => [
                static fn (): \Throwable => new ConnectionException('request to https://example.test/?api_key=secret-token failed'),
                'transport',
            ],
            'declared source failure' => [
                static fn (): \Throwable => new ScientificSourceFailure('payload with secret-token is unusable'),
                'source',
            ],
        ];
    }

    #[DataProvider('expectedSourceFailures')]
    public function test_sequential_orchestration_contains_an_expected_source_failure(\Closure $makeFailure, string $category): void
    {
        ConformanceRecordingAdapter::$calls = [];
        $failure = $makeFailure();
        ConformanceThrowingAdapter::$throwable = $failure;

        $report = $this->throwingThenRecordingOrchestrator()->execute(
            $this->plan(),
            5,
            [ConformanceThrowingAdapter::KEY, ConformanceRecordingAdapter::KEY],
        );

        $this->assertSame([ConformanceThrowingAdapter::KEY], $report->failedSources);
        $this->assertSame([ConformanceRecordingAdapter::KEY], $report->emptySources);
        $this->assertSame(
            [ConformanceThrowingAdapter::KEY, ConformanceRecordingAdapter::KEY],
            $report->attemptedSources,
        );
        $this->assertNotSame([], ConformanceRecordingAdapter::$calls);

        $thrown = array_values(array_filter(
            $report->sourceOutcomes,
            static fn (ScientificSourceSearchOutcome $o): bool => $o->sourceKey === ConformanceThrowingAdapter::KEY,
        ));
        $this->assertCount(1, $thrown, 'A failing adapter must not be retried for the remaining variants.');
        $this->assertSame(ScientificSourceSearchOutcome::STATUS_FAILED, $thrown[0]->status);
        $this->assertSame('adapter_exception', $thrown[0]->error);
        $this->assertSame(
            ['exception_class' => $failure::class, 'failure_category' => $category],
            $thrown[0]->observability,
        );
        $this->assertStringNotContainsString('secret-token', (string) json_encode($thrown[0]->toArray()));
    }

    /** @return array<string, array{\Closure(): \Throwable}> */
    public static function defectsAndTestSignals(): array
    {
        return [
            'generic runtime exception' => [static fn (): \Throwable => new \RuntimeException('unexpected')],
            'logic exception' => [static fn (): \Throwable => new \LogicException('programming defect')],
            'type error' => [static fn (): \Throwable => new \TypeError('programming defect')],
            'test assertion' => [static fn (): \Throwable => new AssertionFailedError('assertion inside a fake')],
            'stray request guard' => [static fn (): \Throwable => new StrayRequestException('https://example.test')],
        ];
    }

    #[DataProvider('defectsAndTestSignals')]
    public function test_sequential_orchestration_propagates_defects_and_test_signals(\Closure $makeThrowable): void
    {
        $throwable = $makeThrowable();
        ConformanceThrowingAdapter::$throwable = $throwable;

        $caught = null;
        try {
            $this->throwingThenRecordingOrchestrator()->execute(
                $this->plan(),
                5,
                [ConformanceThrowingAdapter::KEY, ConformanceRecordingAdapter::KEY],
            );
        } catch (\Throwable $e) {
            $caught = $e;
        }

        $this->assertSame($throwable, $caught);
    }

    private function throwingThenRecordingOrchestrator(): MultiSourceScientificSearchOrchestrator
    {
        $catalog = new ScientificSourceCatalog([
            $this->testRegistration(ConformanceThrowingAdapter::KEY, ConformanceThrowingAdapter::class),
            $this->testRegistration(ConformanceRecordingAdapter::KEY, ConformanceRecordingAdapter::class),
        ]);

        return new MultiSourceScientificSearchOrchestrator(
            new ScientificSourceAdapterRegistry($this->app, $catalog),
            new ScientificSourceSelector($catalog),
            app(ScientificSearchQueryBuilder::class),
            app(ScientificResultDeduplicator::class),
            app(ScientificResultRanker::class),
            new ScientificQueryCompiler(),
            new Stage3CghiaCoexistenceBoundary(),
            $catalog,
        );
    }

    private function testRegistration(string $key, string $adapterClass): ScientificSourceRegistration
    {
        return new ScientificSourceRegistration(
            sourceKey: $key,
            adapterClass: $adapterClass,
            adapterContractVersion: ScientificSourceAdapterInterface::CONTRACT_VERSION,
            lifecycle: ScientificSourceLifecycle::LEGACY_STAGE3,
            accessFamily: ScientificSourceAccessFamily::REST_API,
            selectionRole: ScientificSourceSelectionRole::OPTIONAL_EXPLICIT_ONLY,
            executionGroup: ScientificSourceExecutionGroup::SEQUENTIAL,
            enablement: new AlwaysEnabledSourceEnablement(),
            pathFamily: PathFamily::P12,
            capabilities: [ScientificSourceCapabilityDeclaration::legacyOperationalBasis('conformance test')],
        );
    }

    private function plan(): KnowledgeQueryPlan
    {
        $query = new AgriculturalKnowledgeQuery(
            originalQuestion: 'barley water requirement',
            normalizedQuestion: 'barley water requirement',
            language: 'en',
            agriculturalDomain: 'agronomy',
            subject: ['type' => 'crop', 'value' => 'barley'],
            crop: 'barley',
            cropId: 'barley',
            scientificName: null,
            topic: 'irrigation',
            subtopic: null,
            requestedInformation: ['evidence'],
            constraints: ['question_type' => 'quantity', 'scientific_sense' => 'crop_water_requirement'],
            location: 'brazil',
            researchRequired: true,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            researchIntent: 'irrigation',
        );

        return new KnowledgeQueryPlan(
            normalizedQuery: $query,
            researchIntent: 'irrigation',
            agriculturalDomain: 'agronomy',
            subjectEntity: ['type' => 'crop', 'value' => 'barley'],
            topics: ['irrigation'],
            subtopics: [],
            requestedInformation: ['evidence'],
            evidenceRequirements: ['peer_reviewed'],
            sourcePriorities: [],
            primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            readyForStage3: true,
        );
    }
}

final class ConformanceThrowingAdapter implements ScientificSourceAdapterInterface
{
    public const KEY = 'conformance_throwing';

    public static ?\Throwable $throwable = null;

    public function sourceKey(): string
    {
        return self::KEY;
    }

    public function displayName(): string
    {
        return 'Conformance throwing adapter';
    }

    public function search(string $query, int $limit = 10, array $options = []): ScientificSourceSearchOutcome
    {
        throw self::$throwable ?? new \RuntimeException('no throwable configured');
    }
}

final class ConformanceRecordingAdapter implements ScientificSourceAdapterInterface
{
    public const KEY = 'conformance_recording';

    /** @var list<string> */
    public static array $calls = [];

    public function sourceKey(): string
    {
        return self::KEY;
    }

    public function displayName(): string
    {
        return 'Conformance recording adapter';
    }

    public function search(string $query, int $limit = 10, array $options = []): ScientificSourceSearchOutcome
    {
        self::$calls[] = $query;

        return new ScientificSourceSearchOutcome(self::KEY, ScientificSourceSearchOutcome::STATUS_EMPTY);
    }
}
