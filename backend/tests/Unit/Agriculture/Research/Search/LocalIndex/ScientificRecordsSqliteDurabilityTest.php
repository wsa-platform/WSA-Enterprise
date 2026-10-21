<?php

namespace Tests\Unit\Agriculture\Research\Search\LocalIndex;

use App\Services\Agriculture\Research\Onboarding\NextGateEligibleSeatCatalog;
use App\Services\Agriculture\Research\Search\Family\Bulk\BulkDatasetReader;
use App\Services\Agriculture\Research\Search\Family\Bulk\BulkDatasetSourceProfile;
use App\Services\Agriculture\Research\Search\Family\OaiPmh\OaiPmhHarvester;
use App\Services\Agriculture\Research\Search\LocalIndex\DatabaseScientificRecordStore;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalIndexIngestionService;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalIndexScientificSourceAdapter;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalRecordRetentionPolicy;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalIndexSearchEngine;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalScientificIndex;
use App\Services\Agriculture\Research\Search\LocalIndex\ScientificRecordStore;
use App\Services\Agriculture\Research\Search\LocalIndex\SqliteFtsScientificIndex;
use App\Services\Agriculture\Research\Search\ScientificSourceSearchOutcome;
use DateTimeImmutable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\DatabaseSafety;
use Tests\TestCase;

/**
 * Proves the dedicated scientific_records sqlite connection is isolated from default DB
 * and persists across store instance rebuilds (process-boundary stand-in).
 * Does not claim PostgreSQL production readiness.
 */
class ScientificRecordsSqliteDurabilityTest extends TestCase
{
    private string $dbPath = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->dbPath = storage_path('framework/testing/scientific_records_durability_'.uniqid('', true).'.sqlite');
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.scientific_records' => [
                'driver' => 'sqlite',
                'url' => '',
                'database' => $this->dbPath,
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
            'agricultural_intelligence.local_index.connection' => 'scientific_records',
        ]);
        DB::purge('scientific_records');
        foreach ([
            ScientificRecordStore::class,
            SqliteFtsScientificIndex::class,
            LocalIndexSearchEngine::class,
            LocalIndexIngestionService::class,
        ] as $abstract) {
            $this->app->forgetInstance($abstract);
        }
        DatabaseSafety::assertScientificRecordStoreIsIsolated();
        touch($this->dbPath);
        Artisan::call('migrate', [
            '--database' => 'scientific_records',
            '--path' => 'database/migrations/2026_10_15_220000_create_scientific_source_record_store_tables.php',
            '--force' => true,
        ]);
    }

    protected function tearDown(): void
    {
        DB::purge('scientific_records');
        if ($this->dbPath !== '') {
            DatabaseSafety::deleteOwnedSqliteFiles($this->dbPath);
        }
        parent::tearDown();
    }

    public function test_scientific_records_connection_is_isolated_from_default_and_persists(): void
    {
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->assertSame($this->dbPath, config('database.connections.scientific_records.database'));
        $this->assertTrue(Schema::connection('scientific_records')->hasTable('scientific_source_records'));
        $this->assertFalse(Schema::connection('sqlite')->hasTable('scientific_source_records'));

        $seat = NextGateEligibleSeatCatalog::get('G1-01');
        $this->assertInstanceOf(BulkDatasetSourceProfile::class, $seat?->familyProfile);
        $profile = $seat->familyProfile;

        $storeA = new DatabaseScientificRecordStore(app('db'), 'scientific_records');
        $index = new LocalScientificIndex();
        $ingestion = new LocalIndexIngestionService(
            $storeA,
            $index,
            new LocalRecordRetentionPolicy(),
            new OaiPmhHarvester(),
            new BulkDatasetReader(),
        );
        $path = base_path('tests/Fixtures/ScientificSourceFamilies/next_gate_agris_fixture_v1.jsonl');
        $report = $ingestion->ingestSnapshot($profile, $path, new DateTimeImmutable('2026-10-18T12:00:00+00:00'));
        $this->assertSame('completed', $report->status);
        $this->assertGreaterThan(0, $storeA->generation('agris'));

        // New store instance = separate process stand-in against the same sqlite file.
        $storeB = new DatabaseScientificRecordStore(app('db'), 'scientific_records');
        $this->assertSame($storeA->generation('agris'), $storeB->generation('agris'));
        $active = $storeB->readActive('agris');
        $this->assertGreaterThan(0, count($active['records']));

        $ingestionB = new LocalIndexIngestionService(
            $storeB,
            new LocalScientificIndex(),
            new LocalRecordRetentionPolicy(),
            new OaiPmhHarvester(),
            new BulkDatasetReader(),
        );
        $ingestionB->rebuildIndex('agris');
        $adapter = new LocalIndexScientificSourceAdapter($profile, app(LocalScientificIndex::class), $storeB);
        // rebuildIndex writes into the service's index; use a fresh pair sharing storeB.
        $indexB = new LocalScientificIndex();
        $ingestionC = new LocalIndexIngestionService(
            $storeB,
            $indexB,
            new LocalRecordRetentionPolicy(),
            new OaiPmhHarvester(),
            new BulkDatasetReader(),
        );
        $ingestionC->rebuildIndex('agris');
        $adapter = new LocalIndexScientificSourceAdapter($profile, $indexB, $storeB);
        $hit = $adapter->search('wheat', 5);
        $this->assertSame(ScientificSourceSearchOutcome::STATUS_SUCCESS, $hit->status);
        $this->assertSame('agris', $hit->results[0]->sourceKey);
    }
}
