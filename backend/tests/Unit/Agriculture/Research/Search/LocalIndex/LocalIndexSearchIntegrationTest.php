<?php

namespace Tests\Unit\Agriculture\Research\Search\LocalIndex;

use App\Contracts\ScientificSourceAdapterV2Interface;
use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Path\PathFamily;
use App\Services\Agriculture\Research\Search\Contract\ScientificSourceLicensingDeclaration;
use App\Services\Agriculture\Research\Search\Contract\ScientificSourceUsage;
use App\Services\Agriculture\Research\Search\Contract\ScientificSourceUsageStatus;
use App\Services\Agriculture\Research\Search\Family\Bulk\BulkDatasetReader;
use App\Services\Agriculture\Research\Search\Family\Bulk\BulkDatasetSourceProfile;
use App\Services\Agriculture\Research\Search\Family\OaiPmh\OaiPmhHarvester;
use App\Services\Agriculture\Research\Search\Family\Rest\RestJsonScientificSourceAdapter;
use App\Services\Agriculture\Research\Search\Family\Rest\RestJsonSourceProfile;
use App\Services\Agriculture\Research\Search\LocalIndex\DatabaseScientificRecordStore;
use App\Services\Agriculture\Research\Search\LocalIndex\InMemoryScientificRecordStore;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalIndexIngestionService;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalIndexInvariantViolation;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalIndexScientificSourceAdapter;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalIndexSearchEngine;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalIngestionReport;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalRecordRetentionPolicy;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalScientificIndex;
use App\Services\Agriculture\Research\Search\LocalIndex\ScientificRecordStore;
use App\Services\Agriculture\Research\Search\LocalIndex\SqliteFtsScientificIndex;
use App\Services\Agriculture\Research\Search\MultiSourceScientificSearchOrchestrator;
use App\Services\Agriculture\Research\Search\Registry\AlwaysEnabledSourceEnablement;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceAccessFamily;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceCapabilityDeclaration;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceCapabilityStatus;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceCatalog;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceExecutionGroup;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceLifecycle;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceOperation;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceRegistration;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceRegistryInvariantViolation;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceSelectionRole;
use App\Services\Agriculture\Research\Search\ScientificResultDeduplicator;
use App\Services\Agriculture\Research\Search\ScientificResultRanker;
use App\Services\Agriculture\Research\Search\ScientificSearchQueryBuilder;
use App\Services\Agriculture\Research\Search\ScientificSourceAdapterRegistry;
use App\Services\Agriculture\Research\Search\ScientificSourceSearchOutcome;
use App\Services\Agriculture\Research\Search\ScientificSourceSelector;
use DateTimeImmutable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\Support\DatabaseSafety;
use Tests\TestCase;

/**
 * ADR-023 §8.40 (R6): a harvested or bulk-loaded source is searched through its local index by
 * the same registry, selector and orchestrator as any remote source, with no HTTP request and
 * no source-specific branch.
 */
class LocalIndexSearchIntegrationTest extends TestCase
{
    private LocalScientificIndex $index;

    private InMemoryScientificRecordStore $store;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        config(['agricultural_intelligence.stage3_home_scholarly_concurrency' => false]);
        $this->index = new LocalScientificIndex();
        $this->store = new InMemoryScientificRecordStore();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_an_index_that_was_never_built_is_unavailable_not_empty(): void
    {
        $outcome = (new LocalIndexScientificSourceAdapter(self::profile(), $this->index, $this->store))->search('barley');

        $this->assertSame(ScientificSourceSearchOutcome::STATUS_UNAVAILABLE, $outcome->status);
        $this->assertSame('local_index_not_built', $outcome->error);
        $this->assertSame('local_scientific_index.v1', $outcome->observability['index_version']);
    }

    public function test_a_built_index_returns_stored_records_with_their_provenance(): void
    {
        $this->ingest();
        $adapter = new LocalIndexScientificSourceAdapter(self::profile(), $this->index, $this->store);

        $hit = $adapter->search('barley irrigation', 5);
        $miss = $adapter->search('cassava mosaic', 5);
        $empty = $adapter->search('  ', 5);

        $this->assertSame(ScientificSourceSearchOutcome::STATUS_SUCCESS, $hit->status);
        $this->assertCount(1, $hit->results);
        $record = $hit->results[0]->primaryProvenance();
        $this->assertSame('R-1', $record?->originalIdentifier);
        $this->assertSame(BulkDatasetReader::class, $record->adapterClass);
        $this->assertSame('bulk_dataset', $record->accessFamily);
        $this->assertSame('10.12345/bulk.1', $hit->results[0]->doi);
        $this->assertSame(1, $hit->observability['index_generation']);
        $this->assertSame([ScientificSourceSearchOutcome::STATUS_EMPTY, null], [$miss->status, $miss->error]);
        $this->assertSame([ScientificSourceSearchOutcome::STATUS_EMPTY, 'empty_query'], [$empty->status, $empty->error]);
        Http::assertNothingSent();
    }

    public function test_the_adapter_serves_only_the_local_index_families(): void
    {
        $this->assertTrue(LocalIndexScientificSourceAdapter::servesAccessFamily(ScientificSourceAccessFamily::OAI_PMH));
        $this->assertTrue(LocalIndexScientificSourceAdapter::servesAccessFamily(ScientificSourceAccessFamily::STATIC_DATASET));
        $this->assertFalse(LocalIndexScientificSourceAdapter::servesAccessFamily(ScientificSourceAccessFamily::REST_API));
        $this->assertFalse(LocalIndexScientificSourceAdapter::servesAccessFamily(ScientificSourceAccessFamily::RSS_ATOM));
        $this->assertFalse(RestJsonScientificSourceAdapter::servesAccessFamily(ScientificSourceAccessFamily::BULK_DATASET));

        $this->expectException(LocalIndexInvariantViolation::class);
        new LocalIndexScientificSourceAdapter(self::restProfile(), $this->index, $this->store);
    }

    public function test_a_registration_rejects_an_adapter_of_another_family(): void
    {
        $this->assertSame('bulk_dataset', self::registration(self::profile())->toArray()['family_profile']['access_family']);

        $this->expectException(ScientificSourceRegistryInvariantViolation::class);
        self::registration(self::profile(), RestJsonScientificSourceAdapter::class);
    }

    public function test_a_local_index_source_runs_through_the_production_orchestrator_without_http(): void
    {
        $this->prepareProductionScientificStore();
        $ingestion = $this->app->make(LocalIndexIngestionService::class);
        $store = $this->app->make(ScientificRecordStore::class);
        $engine = $this->app->make(LocalIndexSearchEngine::class);
        $this->assertInstanceOf(DatabaseScientificRecordStore::class, $store);
        $this->assertInstanceOf(SqliteFtsScientificIndex::class, $engine);
        $this->assertSame(SqliteFtsScientificIndex::INDEX_VERSION, $engine->version());

        $ingested = $ingestion->ingestSnapshot(self::profile(), base_path('tests/Fixtures/ScientificSourceFamilies/bulk_dataset_snapshot_v1.jsonl'), new DateTimeImmutable('2026-10-15T09:00:00+00:00'));
        $this->assertSame(LocalIngestionReport::COMPLETED, $ingested->status);
        $this->assertGreaterThan(0, $store->generation('dataset_example'));

        $catalog = new ScientificSourceCatalog([self::registration(self::profile())]);
        $orchestrator = $this->orchestrator($catalog);
        $this->assertInstanceOf(MultiSourceScientificSearchOrchestrator::class, $orchestrator);
        $adapter = (new ScientificSourceAdapterRegistry(app(), $catalog))->get('dataset_example');
        $this->assertInstanceOf(LocalIndexScientificSourceAdapter::class, $adapter);

        $report = $orchestrator->execute($this->plan(), 5, ['dataset_example']);

        $this->assertSame(['dataset_example'], $report->attemptedSources);
        $this->assertSame(['dataset_example'], $report->successfulSources);
        $this->assertNotSame([], $report->results);
        foreach ($report->results as $result) {
            $this->assertSame('dataset_example', $result->sourceKey);
            $this->assertSame(BulkDatasetReader::class, $result->primaryProvenance()?->adapterClass);
        }
        Http::assertNothingSent();
    }

    private function prepareProductionScientificStore(): void
    {
        DB::purge('scientific_records');
        foreach ([
            ScientificRecordStore::class,
            SqliteFtsScientificIndex::class,
            LocalIndexSearchEngine::class,
            LocalIndexIngestionService::class,
        ] as $abstract) {
            $this->app->forgetInstance($abstract);
        }
        DatabaseSafety::assertScientificRecordStoreIsIsolated($this->resolvedScientificDatabaseWithoutConnecting());
        Artisan::call('migrate', [
            '--database' => 'scientific_records',
            '--path' => 'database/migrations/2026_10_15_220000_create_scientific_source_record_store_tables.php',
            '--force' => true,
        ]);
    }

    private function resolvedScientificDatabaseWithoutConnecting(): ?string
    {
        if (! $this->app->resolved('db')) {
            return null;
        }
        $connections = $this->app->make('db')->getConnections();
        if (! isset($connections['scientific_records'])) {
            return null;
        }

        return (string) $connections['scientific_records']->getDatabaseName();
    }

    private function ingest(): void
    {
        $service = new LocalIndexIngestionService($this->store, $this->index, new LocalRecordRetentionPolicy(), new OaiPmhHarvester(), new BulkDatasetReader());
        $service->ingestSnapshot(self::profile(), base_path('tests/Fixtures/ScientificSourceFamilies/bulk_dataset_snapshot_v1.jsonl'), new DateTimeImmutable('2026-10-15T09:00:00+00:00'));
    }

    private function orchestrator(ScientificSourceCatalog $catalog): MultiSourceScientificSearchOrchestrator
    {
        $builder = Mockery::mock(ScientificSearchQueryBuilder::class);
        $builder->shouldReceive('buildVariantsFromPlan')->andReturn(['barley deficit irrigation']);
        $builder->shouldReceive('buildFromPlan')->andReturn('barley deficit irrigation');
        $builder->shouldReceive('buildConsensusRequestOptions')->andReturn([]);

        return new MultiSourceScientificSearchOrchestrator(
            new ScientificSourceAdapterRegistry(app(), $catalog),
            new ScientificSourceSelector($catalog),
            $builder,
            app(ScientificResultDeduplicator::class),
            app(ScientificResultRanker::class),
            catalog: $catalog,
        );
    }

    private static function profile(): BulkDatasetSourceProfile
    {
        return new BulkDatasetSourceProfile(
            sourceKey: 'dataset_example',
            displayName: 'Example dataset (fixture)',
            family: ScientificSourceAccessFamily::BULK_DATASET,
            format: BulkDatasetSourceProfile::FORMAT_JSONL,
            fieldPaths: ['identifier' => 'record_id', 'title' => 'title', 'doi' => 'doi', 'url' => 'landing', 'published' => 'issued', 'withdrawn' => 'withdrawn'],
            licensing: new ScientificSourceLicensingDeclaration(
                [
                    ScientificSourceUsage::RETRIEVAL->value => ScientificSourceUsageStatus::PERMITTED,
                    ScientificSourceUsage::METADATA_STORAGE->value => ScientificSourceUsageStatus::PERMITTED,
                ],
                termsReference: 'https://dataset.example.org/terms (fixture)',
                evidenceReference: 'test fixture: terms accepted for this test only',
            ),
        );
    }

    private static function restProfile(): RestJsonSourceProfile
    {
        return new RestJsonSourceProfile('dataset_example', 'REST source (fixture)', 'https://dataset.example.org/api', 'q', ['identifier' => 'id', 'title' => 'title']);
    }

    /** @param  class-string  $adapterClass */
    private static function registration(BulkDatasetSourceProfile $profile, string $adapterClass = LocalIndexScientificSourceAdapter::class): ScientificSourceRegistration
    {
        return new ScientificSourceRegistration(
            sourceKey: $profile->sourceKey,
            adapterClass: $adapterClass,
            adapterContractVersion: ScientificSourceAdapterV2Interface::V2_CONTRACT_VERSION,
            lifecycle: ScientificSourceLifecycle::LEGACY_STAGE3,
            accessFamily: ScientificSourceAccessFamily::BULK_DATASET,
            selectionRole: ScientificSourceSelectionRole::INTERNET_FIRST_DEFAULT,
            executionGroup: ScientificSourceExecutionGroup::INDEPENDENT_SCHOLARLY,
            enablement: new AlwaysEnabledSourceEnablement(),
            pathFamily: PathFamily::P12,
            capabilities: [new ScientificSourceCapabilityDeclaration(
                ScientificSourceOperation::SEARCH,
                ScientificSourceCapabilityStatus::VERIFIED_SUPPORTED,
                'test: fixture-verified local index search',
                '2026-10-01T00:00:00+00:00',
                '2099-01-01T00:00:00+00:00',
            )],
            familyProfile: $profile,
        );
    }

    private function plan(): KnowledgeQueryPlan
    {
        $query = new AgriculturalKnowledgeQuery(
            originalQuestion: 'barley yield under deficit irrigation',
            normalizedQuestion: 'barley yield under deficit irrigation',
            language: 'en',
            agriculturalDomain: 'agronomy',
            subject: ['type' => 'crop', 'value' => 'barley'],
            crop: 'barley',
            cropId: 'barley',
            scientificName: null,
            topic: 'irrigation',
            subtopic: null,
            requestedInformation: ['yield'],
            constraints: [],
            location: null,
            researchRequired: true,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            researchIntent: 'literature_review',
        );

        return new KnowledgeQueryPlan(
            normalizedQuery: $query,
            researchIntent: 'literature_review',
            agriculturalDomain: 'agronomy',
            subjectEntity: ['type' => 'crop', 'value' => 'barley'],
            topics: ['irrigation'],
            subtopics: [],
            requestedInformation: ['yield'],
            evidenceRequirements: ['peer_reviewed'],
            sourcePriorities: ['dataset_example'],
            primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            contextInput: [],
            readyForStage3: true,
        );
    }
}
