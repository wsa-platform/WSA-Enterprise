<?php

namespace Tests\Pgsql;

use App\Services\Agriculture\Research\Search\LocalIndex\DatabaseScientificRecordStore;
use App\Services\Agriculture\Research\Search\LocalIndex\HarvestCheckpoint;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalIndexIngestionService;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalIndexScientificSourceAdapter;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalIngestionReport;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalRecordState;
use App\Services\Agriculture\Research\Search\LocalIndex\ScientificRecordGenerationMerge;
use App\Services\Agriculture\Research\Search\ScientificSourceSearchOutcome;
use DateTimeImmutable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\Support\BindsPgsqlLocalIndexForGuardedSuite;
use Tests\Support\LocalIndexFixtures;
use Tests\TestCase;

/**
 * Durability across a restart of the PostgreSQL server itself (ADR-023 §8.41; phpunit.pgsql.xml).
 *
 * Two phases in two separate PHP processes, with the database server restarted in between on a
 * persistent data volume. Skipped unless WSA_STORE_RESTART_PHASE is "write" or "verify"; the
 * verify phase also requires WSA_STORE_WRITE_POSTMASTER_START, the server start time printed by
 * the write phase, and fails unless the server has restarted since.
 */
final class ScientificRecordStoreServerRestartTest extends TestCase
{
    use BindsPgsqlLocalIndexForGuardedSuite;
    use LocalIndexFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        if (! in_array(getenv('WSA_STORE_RESTART_PHASE'), ['write', 'verify'], true)) {
            $this->markTestSkipped('Runs only as an explicit write/verify pair around a PostgreSQL server restart.');
        }
        $this->assertSame('pgsql', DB::connection()->getDriverName());
        $this->assertStringEndsWith('_test', (string) DB::selectOne('SELECT current_database() AS name')->name);
        $this->bindLocalIndexToGuardedPgsqlTesting();
        Http::preventStrayRequests();
    }

    public function test_committed_state_survives_a_database_server_restart(): void
    {
        getenv('WSA_STORE_RESTART_PHASE') === 'write' ? $this->write() : $this->verify();
    }

    private function write(): void
    {
        Artisan::call('migrate:fresh', ['--force' => true]);
        $ingestion = app(LocalIndexIngestionService::class);
        $now = new DateTimeImmutable('2026-10-15T09:00:00+00:00');

        $this->assertSame(LocalIngestionReport::COMPLETED, $ingestion->ingestSnapshot(self::fixtureBulkProfile(), self::fixturePath('bulk_dataset_snapshot_v1.jsonl'), $now)->status);
        $this->assertSame(LocalIngestionReport::COMPLETED, $ingestion->ingestSnapshot(self::fixtureBulkProfile(), self::fixturePath('bulk_dataset_snapshot_v2.jsonl'), $now)->status);
        self::fakeOaiResponses(['oai_pmh_list_records_page.xml']);
        $this->assertSame(LocalIngestionReport::PAUSED, $ingestion->harvestOaiPmh(self::fixtureOaiProfile(), $now, maxPages: 1)->status);

        fwrite(STDERR, 'WSA_STORE_WRITE_POSTMASTER_START='.DB::selectOne('SELECT pg_postmaster_start_time()::text AS t')->t."\n");
    }

    private function verify(): void
    {
        $before = (string) getenv('WSA_STORE_WRITE_POSTMASTER_START');
        $now = (string) DB::selectOne('SELECT pg_postmaster_start_time()::text AS t')->t;
        $this->assertNotSame('', $before, 'the write phase start time is required');
        $this->assertTrue((bool) DB::selectOne('SELECT ?::timestamptz < pg_postmaster_start_time() AS restarted', [$before])->restarted, "server started at {$now}, not after {$before}");
        $this->assertTrue(Schema::hasTable(DatabaseScientificRecordStore::RECORDS_TABLE));

        $store = new DatabaseScientificRecordStore(app('db'));
        $this->assertSame([2, 1], [$store->generation('dataset_example'), $store->generation('repository_example')]);
        $r1 = $store->get('dataset_example', 'R-1');
        $this->assertSame([LocalRecordState::ACTIVE, 'Barley yield under deficit irrigation: revised', 1, 2], [$r1?->state, $r1->result?->title, (int) DB::table(DatabaseScientificRecordStore::RECORDS_TABLE)->where('original_identifier', 'R-1')->value('first_generation'), $r1->generation]);
        $this->assertSame(ScientificRecordGenerationMerge::contentHash($r1->result), $r1->contentHash);
        $this->assertSame([LocalRecordState::WITHDRAWN, ScientificRecordGenerationMerge::ABSENT_FROM_SNAPSHOT], [$store->get('dataset_example', 'R-2')?->state, $store->get('dataset_example', 'R-2')->tombstoneReason]);
        $this->assertSame([LocalRecordState::WITHDRAWN, 'flagged_withdrawn_in_dataset'], [$store->get('dataset_example', 'R-3')?->state, $store->get('dataset_example', 'R-3')->tombstoneReason]);
        $checkpoint = $store->checkpoint('repository_example');
        $this->assertSame([HarvestCheckpoint::IN_PROGRESS, 'agri-2026-page-2'], [$checkpoint?->state, $checkpoint->resumptionToken]);

        $adapter = app()->make(LocalIndexScientificSourceAdapter::class, ['profile' => self::fixtureBulkProfile()]);
        $wheat = $adapter->search('wheat rust');
        $this->assertSame([ScientificSourceSearchOutcome::STATUS_SUCCESS, 2], [$wheat->status, $wheat->observability['index_generation']]);
        $this->assertSame(['R-4'], array_map(static fn ($r): ?string => $r->primaryProvenance()?->originalIdentifier, $wheat->results));
        $this->assertSame(ScientificSourceSearchOutcome::STATUS_EMPTY, $adapter->search('soil moisture')->status);

        self::fakeOaiResponses(['oai_pmh_last_page.xml']);
        $resumed = app(LocalIndexIngestionService::class)->harvestOaiPmh(self::fixtureOaiProfile(), new DateTimeImmutable('2026-10-15T10:00:00+00:00'));
        $this->assertSame([LocalIngestionReport::COMPLETED, HarvestCheckpoint::COMPLETE], [$resumed->status, $resumed->checkpoint?->state]);
    }
}
