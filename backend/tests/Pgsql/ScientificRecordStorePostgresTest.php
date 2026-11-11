<?php

namespace Tests\Pgsql;

use App\Services\Agriculture\Research\Search\Family\Bulk\BulkDatasetReader;
use App\Services\Agriculture\Research\Search\Family\OaiPmh\OaiPmhHarvester;
use App\Services\Agriculture\Research\Search\LocalIndex\DatabaseScientificRecordStore;
use App\Services\Agriculture\Research\Search\LocalIndex\HarvestCheckpoint;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalIndexIngestionService;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalIndexScientificSourceAdapter;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalIngestionReport;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalRecordRetentionPolicy;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalRecordState;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalScientificIndex;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalStoreFailure;
use App\Services\Agriculture\Research\Search\LocalIndex\ScientificRecordGenerationMerge;
use App\Services\Agriculture\Research\Search\LocalIndex\ScientificRecordStore;
use App\Services\Agriculture\Research\Search\ScientificSourceSearchOutcome;
use DateTimeImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\Support\BindsPgsqlLocalIndexForGuardedSuite;
use Tests\Support\LocalIndexFixtures;
use Tests\TestCase;

/**
 * The durable record store of the local index on PostgreSQL (ADR-023 §8.41; phpunit.pgsql.xml only).
 *
 * Ingestion and search run in worker processes (tests/Pgsql/bin/scientific-record-store-worker.php)
 * that resolve every service from the application container, so what one process commits is
 * read back by another process with a fresh store and a fresh index. Interruption is a worker
 * killed while its commit is still uncommitted; contention is a worker blocked, as PostgreSQL
 * itself reports, on the source row this connection holds.
 */
final class ScientificRecordStorePostgresTest extends TestCase
{
    use BindsPgsqlLocalIndexForGuardedSuite;
    use LocalIndexFixtures;

    private const PEER = 'pgsql_testing_peer';

    private const TABLES = [
        DatabaseScientificRecordStore::RECORDS_TABLE,
        DatabaseScientificRecordStore::GENERATIONS_TABLE,
        DatabaseScientificRecordStore::STATES_TABLE,
    ];

    private const WORKER_LOCK_WAIT_DEADLINE_SECONDS = 20.0;

    private const WORKER_FINISH_DEADLINE_SECONDS = 60.0;

    private static bool $migrated = false;

    /** @var list<array{process: resource, stdout: resource, stderr: resource, pid: int}> */
    private array $workers = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertDisposablePostgresTestDatabase();
        $this->bindLocalIndexToGuardedPgsqlTesting();
        if (! self::$migrated) {
            Artisan::call('migrate:fresh', ['--force' => true]);
            self::$migrated = true;
        }
        config(['database.connections.'.self::PEER => config('database.connections.pgsql_testing')]);
        Http::preventStrayRequests();
        $this->deleteStoreRows();
    }

    protected function tearDown(): void
    {
        while (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        foreach ($this->workers as $worker) {
            if (proc_get_status($worker['process'])['running']) {
                proc_terminate($worker['process'], 9);
            }
            fclose($worker['stdout']);
            fclose($worker['stderr']);
            proc_close($worker['process']);
        }
        $this->workers = [];
        $this->deleteStoreRows();
        DB::purge(self::PEER);
        parent::tearDown();
    }

    public function test_the_schema_enforces_identity_and_lifecycle_and_holds_no_tenant_data(): void
    {
        $columns = Schema::getColumnListing(DatabaseScientificRecordStore::RECORDS_TABLE);
        sort($columns);
        $this->assertSame(
            ['changed_at', 'content_hash', 'created_at', 'datestamp', 'first_generation', 'generation', 'id', 'lifecycle_state', 'original_identifier', 'provenance', 'record', 'source_key', 'tombstone_reason', 'updated_at'],
            $columns,
            'global reference data of public sources: no tenant, organization or user column',
        );

        $row = ['source_key' => 'dataset_example', 'original_identifier' => 'R-1', 'lifecycle_state' => 'deleted', 'generation' => 1, 'first_generation' => 1, 'provenance' => '{}', 'tombstone_reason' => 'fixture', 'changed_at' => '2026-10-15T09:00:00+00:00'];
        DB::table(DatabaseScientificRecordStore::RECORDS_TABLE)->insert($row);
        $this->assertSqlState('23505', fn () => DB::table(DatabaseScientificRecordStore::RECORDS_TABLE)->insert($row));
        $this->assertSqlState('23514', fn () => DB::table(DatabaseScientificRecordStore::RECORDS_TABLE)->insert(['original_identifier' => 'R-2', 'lifecycle_state' => 'active'] + $row));
        $this->assertSqlState('23514', fn () => DB::table(DatabaseScientificRecordStore::RECORDS_TABLE)->insert(['original_identifier' => 'R-3', 'tombstone_reason' => null] + $row));
        $this->assertSqlState('23514', fn () => DB::table(DatabaseScientificRecordStore::RECORDS_TABLE)->insert(['original_identifier' => 'R-4', 'generation' => 0, 'first_generation' => 0] + $row));
        $this->assertSqlState('23505', fn () => DB::table(DatabaseScientificRecordStore::GENERATIONS_TABLE)->insert(array_fill(0, 2, ['source_key' => 'dataset_example', 'generation' => 1, 'mode' => 'snapshot', 'counts' => '{}', 'committed_at' => 'x'])));
        $this->assertSqlState('23514', fn () => DB::table(DatabaseScientificRecordStore::GENERATIONS_TABLE)->insert(['source_key' => 'dataset_example', 'generation' => 1, 'mode' => 'partial', 'counts' => '{}', 'committed_at' => 'x']));
    }

    public function test_records_committed_by_one_process_are_recovered_by_a_fresh_process_and_a_fresh_store(): void
    {
        $ingested = $this->runWorker(['op' => 'snapshot', 'fixture' => 'bulk_dataset_snapshot_v1.jsonl']);

        $this->assertSame([LocalIngestionReport::COMPLETED, 1], [$ingested['status'], $ingested['generation']]);
        $this->assertSame(['inserted' => 2, 'updated' => 0, 'unchanged' => 0, 'deleted' => 0, 'withdrawn' => 1, 'stale' => 0], $ingested['counts']);

        $found = $this->runWorker(['op' => 'search', 'query' => 'barley irrigation']);
        $this->assertSame([ScientificSourceSearchOutcome::STATUS_SUCCESS, null, 1], [$found['status'], $found['error'], $found['index_generation']]);
        $this->assertSame(['R-1'], array_column($found['results'], 'identifier'));
        $this->assertSame('10.12345/bulk.1', $found['results'][0]['doi']);
        $this->assertSame(BulkDatasetReader::class, $found['results'][0]['adapter']);
        $this->assertSame('bulk_dataset', $found['results'][0]['access_family']);
        $this->assertSame('permitted', $found['results'][0]['licensing']['usages']['metadata_storage']);
        $this->assertSame('unresolved', $found['results'][0]['licensing']['usages']['full_text_storage']);
        $this->assertSame([ScientificSourceSearchOutcome::STATUS_EMPTY, []], [$this->runWorker(['op' => 'search', 'query' => 'retracted nitrogen'])['status'], $this->runWorker(['op' => 'search', 'query' => 'retracted nitrogen'])['results']]);

        $store = $this->freshStore();
        $active = $store->get('dataset_example', 'R-1');
        $this->assertSame(LocalRecordState::ACTIVE, $active?->state);
        $this->assertSame(ScientificRecordGenerationMerge::contentHash($active->result), $active->contentHash);
        $this->assertSame('R-1', $active->result->primaryProvenance()?->originalIdentifier);
        $this->assertSame('record_id', $active->provenance['original_identifier_field']);
        $this->assertSame(['Researcher, Ada', 'Scientist, Ben'], $active->result->authors);
        $this->assertSame(1, $active->generation);
        $withdrawn = $store->get('dataset_example', 'R-3');
        $this->assertSame([LocalRecordState::WITHDRAWN, 'flagged_withdrawn_in_dataset', null], [$withdrawn?->state, $withdrawn->tombstoneReason, $withdrawn->result]);
        $this->assertSame(1, $store->generation('dataset_example'));
        $this->assertSame(1, DB::table(DatabaseScientificRecordStore::GENERATIONS_TABLE)->where('source_key', 'dataset_example')->count());
    }

    public function test_re_ingestion_is_idempotent_and_a_changed_snapshot_updates_and_withdraws_in_place(): void
    {
        $this->runWorker(['op' => 'snapshot', 'fixture' => 'bulk_dataset_snapshot_v1.jsonl']);
        $before = $this->recordRows();

        $again = $this->runWorker(['op' => 'snapshot', 'fixture' => 'bulk_dataset_snapshot_v1.jsonl']);
        $this->assertSame(['inserted' => 0, 'updated' => 0, 'unchanged' => 3, 'deleted' => 0, 'withdrawn' => 0, 'stale' => 0], $again['counts']);
        $this->assertSame($before, $this->recordRows(), 'an identical snapshot rewrites nothing');

        $changed = $this->runWorker(['op' => 'snapshot', 'fixture' => 'bulk_dataset_snapshot_v2.jsonl']);
        $this->assertSame(['inserted' => 1, 'updated' => 1, 'unchanged' => 0, 'deleted' => 0, 'withdrawn' => 1, 'stale' => 0], $changed['counts']);
        $this->assertSame(3, $changed['generation']);
        $this->assertSame(4, DB::table(DatabaseScientificRecordStore::RECORDS_TABLE)->count(), 'one row per identity, never a duplicate');

        $after = $this->recordRows();
        $this->assertSame($before['R-1']['id'], $after['R-1']['id']);
        $this->assertNotSame($before['R-1']['content_hash'], $after['R-1']['content_hash']);
        $this->assertSame([1, 3], [$after['R-1']['first_generation'], $after['R-1']['generation']]);
        $this->assertSame(['withdrawn', ScientificRecordGenerationMerge::ABSENT_FROM_SNAPSHOT, null], [$after['R-2']['lifecycle_state'], $after['R-2']['tombstone_reason'], $after['R-2']['record']]);

        $this->assertSame(['R-1'], array_column($this->runWorker(['op' => 'search', 'query' => 'barley revised'])['results'], 'identifier'));
        $this->assertSame([], $this->runWorker(['op' => 'search', 'query' => 'soil moisture'])['results']);
        $this->assertSame(['R-4'], array_column($this->runWorker(['op' => 'search', 'query' => 'wheat rust'])['results'], 'identifier'));
    }

    public function test_the_same_provider_identifier_in_two_sources_stays_two_records(): void
    {
        $store = $this->freshStore();
        foreach (['dataset_example' => 'Barley in source one', 'dataset_other' => 'Wheat in source two'] as $source => $title) {
            $generation = $store->begin($source, ScientificRecordStore::MODE_INCREMENTAL);
            $store->stageUpsert($generation, self::fixtureRecord($source, 'R-1', $title), '2026-01-01');
            $store->commit($generation, self::now());
        }

        $reopened = $this->freshStore();
        $this->assertSame('Barley in source one', $reopened->get('dataset_example', 'R-1')?->result?->title);
        $this->assertSame('Wheat in source two', $reopened->get('dataset_other', 'R-1')?->result?->title);
        $this->assertSame(2, DB::table(DatabaseScientificRecordStore::RECORDS_TABLE)->where('original_identifier', 'R-1')->count());
        $this->assertSame([1, 1], [$reopened->generation('dataset_example'), $reopened->generation('dataset_other')]);
    }

    public function test_an_older_version_or_tombstone_never_overwrites_a_newer_committed_one(): void
    {
        $store = $this->freshStore();
        $generation = $store->begin('dataset_example', ScientificRecordStore::MODE_INCREMENTAL);
        $store->stageUpsert($generation, self::fixtureRecord('dataset_example', 'R-9', 'Sorghum grain quality, 2026 edition'), '2026-05-01');
        $store->stageTombstone($generation, 'R-10', LocalRecordState::DELETED, 'oai_pmh_deleted_header', '2026-02-01');
        $store->commit($generation, self::now());

        $older = $this->freshStore();
        $generation = $older->begin('dataset_example', ScientificRecordStore::MODE_INCREMENTAL);
        $older->stageUpsert($generation, self::fixtureRecord('dataset_example', 'R-9', 'Sorghum grain quality, 2025 edition'), '2026-01-01');
        $older->stageUpsert($generation, self::fixtureRecord('dataset_example', 'R-10', 'Resurrected by an older copy'), '2026-01-15');
        $commit = $older->commit($generation, self::now());

        $this->assertSame(2, $commit->stale);
        $reopened = $this->freshStore();
        $this->assertSame('Sorghum grain quality, 2026 edition', $reopened->get('dataset_example', 'R-9')?->result?->title);
        $this->assertSame(LocalRecordState::DELETED, $reopened->get('dataset_example', 'R-10')?->state);
    }

    public function test_a_rejected_snapshot_changes_no_row_and_no_generation(): void
    {
        $this->runWorker(['op' => 'snapshot', 'fixture' => 'bulk_dataset_snapshot_v1.jsonl']);
        $records = $this->recordRows();
        $states = DB::table(DatabaseScientificRecordStore::STATES_TABLE)->get()->all();

        $rejected = $this->runWorker(['op' => 'snapshot', 'fixture' => 'bulk_dataset_malformed.jsonl']);
        $missing = $this->runWorker(['op' => 'snapshot', 'fixture' => 'missing.jsonl']);

        $this->assertSame([LocalIngestionReport::SNAPSHOT_REJECTED, 'malformed_lines', 1], [$rejected['status'], $rejected['failure'], $rejected['generation']]);
        $this->assertSame([LocalIngestionReport::SNAPSHOT_REJECTED, 'file_unreadable'], [$missing['status'], $missing['failure']]);
        $this->assertSame($records, $this->recordRows());
        $this->assertEquals($states, DB::table(DatabaseScientificRecordStore::STATES_TABLE)->get()->all());
        $this->assertSame(1, DB::table(DatabaseScientificRecordStore::GENERATIONS_TABLE)->count());
    }

    public function test_a_process_killed_inside_its_commit_leaves_no_trace_and_the_next_run_commits(): void
    {
        $this->runWorker(['op' => 'snapshot', 'fixture' => 'bulk_dataset_snapshot_v1.jsonl']);
        $records = $this->recordRows();

        $worker = $this->startWorker(['op' => 'hold', 'fixture' => 'bulk_dataset_snapshot_v2.jsonl']);
        $held = $this->readLine($worker);
        $this->assertSame(['held', 2], [$held['outcome'], $held['generation']], 'the worker saw its own uncommitted generation');
        $this->assertSame($records, $this->recordRows(), 'uncommitted changes are invisible to other connections');
        $this->assertSame(1, $this->freshStore()->generation('dataset_example'));

        proc_terminate($worker['process'], 9);
        $this->awaitExit($worker);
        $this->awaitBackendGone($worker['pid']);

        $this->assertSame($records, $this->recordRows());
        $this->assertSame(1, $this->freshStore()->generation('dataset_example'));
        $this->assertSame(1, DB::table(DatabaseScientificRecordStore::GENERATIONS_TABLE)->count());

        $next = $this->runWorker(['op' => 'snapshot', 'fixture' => 'bulk_dataset_snapshot_v2.jsonl']);
        $this->assertSame([LocalIngestionReport::COMPLETED, 2], [$next['status'], $next['generation']]);
        $this->assertSame(['inserted' => 1, 'updated' => 1, 'unchanged' => 0, 'deleted' => 0, 'withdrawn' => 1, 'stale' => 0], $next['counts']);
    }

    public function test_an_interrupted_harvest_resumes_in_a_new_process_from_the_durable_checkpoint(): void
    {
        $paused = $this->runWorker(['op' => 'harvest', 'responses' => ['oai_pmh_list_records_page.xml'], 'max_pages' => 1]);

        $this->assertSame([LocalIngestionReport::PAUSED, 1], [$paused['status'], $paused['generation']]);
        $this->assertSame([HarvestCheckpoint::IN_PROGRESS, 'agri-2026-page-2', null], [$paused['checkpoint']['state'], $paused['checkpoint_token'], $paused['checkpoint']['high_water_mark']]);
        $durable = $this->freshStore()->checkpoint('repository_example');
        $this->assertSame([HarvestCheckpoint::IN_PROGRESS, 'agri-2026-page-2', 1], [$durable?->state, $durable->resumptionToken, $durable->pagesCommitted]);

        $resumed = $this->runWorker(['op' => 'harvest', 'responses' => ['oai_pmh_last_page.xml']]);

        $this->assertSame([LocalIngestionReport::COMPLETED, 2], [$resumed['status'], $resumed['generation']]);
        $this->assertSame('agri-2026-page-2', $resumed['requests'][0]['resumptionToken'] ?? null, 'the new process continued at the stored token');
        $this->assertSame([HarvestCheckpoint::COMPLETE, '2026-10-15T08:00:00Z', 2], [$resumed['checkpoint']['state'], $resumed['checkpoint']['high_water_mark'], $resumed['checkpoint']['pages_committed']]);
        $store = $this->freshStore();
        $this->assertSame(LocalRecordState::DELETED, $store->get('repository_example', 'oai:repository.example.org:102')?->state);
        $this->assertSame('2026-03-02', $store->get('repository_example', 'oai:repository.example.org:101')?->datestamp);
    }

    public function test_a_competing_commit_waits_for_the_source_lock_and_a_stale_base_is_refused(): void
    {
        $this->runWorker(['op' => 'snapshot', 'fixture' => 'bulk_dataset_snapshot_v1.jsonl']);
        $mine = $this->freshStore();
        $staged = $mine->begin('dataset_example', ScientificRecordStore::MODE_INCREMENTAL);
        $mine->stageUpsert($staged, self::fixtureRecord('dataset_example', 'R-2', 'A stale rewrite of R-2'), null);

        DB::beginTransaction();
        DB::table(DatabaseScientificRecordStore::STATES_TABLE)->where('source_key', 'dataset_example')->lockForUpdate()->first();
        $worker = $this->startWorker(['op' => 'snapshot', 'fixture' => 'bulk_dataset_snapshot_v2.jsonl']);
        $this->awaitWorkerBlockedByThisConnection($worker);
        DB::rollBack();
        $competitor = $this->finishWorker($worker);

        $this->assertSame([LocalIngestionReport::COMPLETED, 2], [$competitor['status'], $competitor['generation']]);
        try {
            $mine->commit($staged, self::now());
            $this->fail('A generation staged against a superseded base was committed.');
        } catch (LocalStoreFailure $failure) {
            $this->assertSame(LocalStoreFailure::CONCURRENT_GENERATION, $failure->reason);
        }
        $mine->discard($staged);
        $reopened = $this->freshStore();
        $this->assertSame(2, $reopened->generation('dataset_example'));
        $this->assertSame([LocalRecordState::WITHDRAWN, ScientificRecordGenerationMerge::ABSENT_FROM_SNAPSHOT], [$reopened->get('dataset_example', 'R-2')?->state, $reopened->get('dataset_example', 'R-2')->tombstoneReason], 'the stale writer did not resurrect R-2');
    }

    public function test_restricted_content_is_not_persisted_and_permitted_content_is(): void
    {
        $this->runWorker(['op' => 'snapshot', 'fixture' => 'bulk_dataset_snapshot_v1.jsonl']);
        $restricted = (string) DB::table(DatabaseScientificRecordStore::RECORDS_TABLE)->where('original_identifier', 'R-1')->value('record');
        $this->runWorker(['op' => 'snapshot', 'source' => 'dataset_licensed', 'fixture' => 'bulk_dataset_snapshot_v1.jsonl', 'full_text' => true]);
        $permitted = (string) DB::table(DatabaseScientificRecordStore::RECORDS_TABLE)->where('source_key', 'dataset_licensed')->where('original_identifier', 'R-1')->value('record');

        $this->assertStringNotContainsString('Three seasons of barley field trials', $restricted);
        $this->assertNull(json_decode($restricted, true)['abstract']);
        $this->assertNull(json_decode($restricted, true)['raw_metadata']);
        $this->assertContains('abstract_not_retained_licence', json_decode($restricted, true)['provenance'][0]['transformations']);
        $this->assertStringContainsString('Three seasons of barley field trials', $permitted);
        $this->assertSame([], $this->runWorker(['op' => 'search', 'query' => 'field trials'])['results'], 'text that may not be stored is not searchable');
        $this->assertSame(['R-1'], array_column($this->runWorker(['op' => 'search', 'source' => 'dataset_licensed', 'query' => 'field trials'])['results'], 'identifier'));
    }

    public function test_store_faults_and_corrupt_rows_fail_the_search_instead_of_returning_empty(): void
    {
        $this->runWorker(['op' => 'snapshot', 'fixture' => 'bulk_dataset_snapshot_v1.jsonl']);
        config(['database.connections.pgsql_unreachable' => ['port' => 1] + config('database.connections.pgsql_testing')]);
        $unreachable = new LocalIndexScientificSourceAdapter(self::fixtureBulkProfile(), new LocalScientificIndex(), new DatabaseScientificRecordStore(app('db'), 'pgsql_unreachable'));

        $fault = $unreachable->search('barley');
        $this->assertSame([ScientificSourceSearchOutcome::STATUS_FAILED, LocalStoreFailure::STORE_UNAVAILABLE, []], [$fault->status, $fault->error, $fault->results]);

        $record = json_decode((string) DB::table(DatabaseScientificRecordStore::RECORDS_TABLE)->where('original_identifier', 'R-1')->value('record'), true);
        $record['title'] = 'Tampered title';
        DB::table(DatabaseScientificRecordStore::RECORDS_TABLE)->where('original_identifier', 'R-1')->update(['record' => json_encode($record)]);

        $corrupt = $this->runWorker(['op' => 'search', 'query' => 'barley']);
        $this->assertSame([ScientificSourceSearchOutcome::STATUS_FAILED, LocalStoreFailure::RECORD_CORRUPT, []], [$corrupt['status'], $corrupt['error'], $corrupt['results']]);
        $this->assertSame([ScientificSourceSearchOutcome::STATUS_UNAVAILABLE, 'local_index_not_built'], array_values(array_intersect_key($this->runWorker(['op' => 'search', 'source' => 'never_ingested', 'query' => 'barley']), ['status' => 1, 'error' => 1])));
    }

    public function test_an_index_rebuilt_from_the_store_equals_the_incrementally_maintained_one(): void
    {
        $store = $this->freshStore();
        $incremental = new LocalScientificIndex();
        $ingestion = new LocalIndexIngestionService($store, $incremental, new LocalRecordRetentionPolicy(), new OaiPmhHarvester(), new BulkDatasetReader());
        $ingestion->ingestSnapshot(self::fixtureBulkProfile(), self::fixturePath('bulk_dataset_snapshot_v1.jsonl'), self::now());
        $ingestion->ingestSnapshot(self::fixtureBulkProfile(), self::fixturePath('bulk_dataset_snapshot_v2.jsonl'), self::now());

        $rebuilt = new LocalScientificIndex();
        ['generation' => $generation, 'records' => $records] = $this->freshStore()->readActive('dataset_example');
        $rebuilt->rebuild('dataset_example', $records, $generation);

        $this->assertSame([2, 2, 2], [$generation, $incremental->generation('dataset_example'), $rebuilt->generation('dataset_example')]);
        $this->assertSame($incremental->documentCount('dataset_example'), $rebuilt->documentCount('dataset_example'));
        foreach (['barley', 'barley revised', 'wheat rust', 'soil moisture', 'retracted', 'irrigation deficit'] as $query) {
            $this->assertSame(self::hitKeys($incremental, $query), self::hitKeys($rebuilt, $query), $query);
        }
    }

    public function test_search_never_writes_and_never_stores_the_question(): void
    {
        $this->runWorker(['op' => 'snapshot', 'fixture' => 'bulk_dataset_snapshot_v1.jsonl']);
        $snapshot = [$this->recordRows(), DB::table(DatabaseScientificRecordStore::STATES_TABLE)->get()->all(), DB::table(DatabaseScientificRecordStore::GENERATIONS_TABLE)->count()];

        $this->runWorker(['op' => 'search', 'query' => 'confidential farm question about barley']);
        $this->runWorker(['op' => 'search', 'query' => 'barley']);

        $this->assertEquals($snapshot, [$this->recordRows(), DB::table(DatabaseScientificRecordStore::STATES_TABLE)->get()->all(), DB::table(DatabaseScientificRecordStore::GENERATIONS_TABLE)->count()]);
        foreach (self::TABLES as $table) {
            $this->assertSame(0, DB::table($table)->whereRaw('CAST(row_to_json('.$table.') AS text) LIKE ?', ['%confidential farm question%'])->count(), $table);
        }
    }

    private function freshStore(): DatabaseScientificRecordStore
    {
        return new DatabaseScientificRecordStore(app('db'));
    }

    /** @return list<string> */
    private static function hitKeys(LocalScientificIndex $index, string $query): array
    {
        return array_map(static fn (array $hit): string => $hit['record']->recordKey(), $index->search('dataset_example', $query, 10));
    }

    /** @return array<string, array<string, mixed>> source rows of dataset_example keyed by identifier */
    private function recordRows(): array
    {
        $rows = [];
        foreach (DB::table(DatabaseScientificRecordStore::RECORDS_TABLE)->where('source_key', 'dataset_example')->orderBy('id')->get() as $row) {
            $rows[(string) $row->original_identifier] = (array) $row;
        }

        return $rows;
    }

    private function assertSqlState(string $expected, callable $statement): void
    {
        DB::beginTransaction();
        try {
            $statement();
            $this->fail("Expected SQLSTATE {$expected}.");
        } catch (QueryException $e) {
            $this->assertSame($expected, $e->errorInfo[0] ?? null);
        } finally {
            DB::rollBack();
        }
    }

    private function assertDisposablePostgresTestDatabase(): void
    {
        $expected = (string) getenv('DB_TEST_DATABASE');
        $this->assertSame('pgsql_testing', config('database.default'), 'PostgreSQL suite must run on pgsql_testing.');
        $this->assertSame('pgsql', DB::connection()->getDriverName(), 'PostgreSQL suite must not fall back to another driver.');
        $current = (string) DB::selectOne('SELECT current_database() AS name')->name;
        $this->assertSame($expected, $current);
        $this->assertStringEndsWith('_test', $current);
        $this->assertNotContains($current, ['wsa_enterprise']);
    }

    private function deleteStoreRows(): void
    {
        foreach (self::TABLES as $table) {
            DB::connection('pgsql_testing')->table($table)->delete();
        }
    }

    /**
     * @param  array<string, mixed>  $job
     * @return array<string, mixed>
     */
    private function runWorker(array $job): array
    {
        $result = $this->finishWorker($this->startWorker($job));
        $this->assertSame('returned', $result['outcome'] ?? null, 'worker threw: '.json_encode($result));

        return $result;
    }

    /**
     * @param  array<string, mixed>  $job
     * @return array{process: resource, stdout: resource, stderr: resource, pid: int}
     */
    private function startWorker(array $job): array
    {
        $process = proc_open(
            [PHP_BINARY, base_path('tests/Pgsql/bin/scientific-record-store-worker.php'), json_encode($job)],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
        );
        $this->assertIsResource($process);
        fclose($pipes[0]);

        $line = fgets($pipes[1]);
        if (! is_string($line) || preg_match('/^pid (\d+)$/', trim($line), $match) !== 1) {
            $this->fail('Record store worker did not start: '.trim((string) $line.stream_get_contents($pipes[2])));
        }
        $worker = ['process' => $process, 'stdout' => $pipes[1], 'stderr' => $pipes[2], 'pid' => (int) $match[1]];
        $this->workers[] = $worker;

        return $worker;
    }

    /**
     * @param  array{process: resource, stdout: resource, stderr: resource, pid: int}  $worker
     * @return array<string, mixed>
     */
    private function readLine(array $worker): array
    {
        $line = fgets($worker['stdout']);
        $decoded = is_string($line) ? json_decode($line, true) : null;
        if (! is_array($decoded)) {
            $this->fail('Record store worker gave no result: '.trim((string) $line.stream_get_contents($worker['stderr'])));
        }

        return $decoded;
    }

    /**
     * Reads the outcome within a deadline and requires a clean exit, so a hung or crashed worker
     * fails the test instead of being read as an outcome.
     *
     * @param  array{process: resource, stdout: resource, stderr: resource, pid: int}  $worker
     * @return array<string, mixed>
     */
    private function finishWorker(array $worker): array
    {
        $deadline = microtime(true) + self::WORKER_FINISH_DEADLINE_SECONDS;
        $output = '';
        stream_set_blocking($worker['stdout'], false);
        // Do not wait for feof(): on Docker/Windows pipes a finished worker can leave
        // stdout open until proc_close, which hung the prior EXIT-09 rerun for minutes.
        while (microtime(true) < $deadline) {
            $chunk = fread($worker['stdout'], 65536);
            if (is_string($chunk) && $chunk !== '') {
                $output .= $chunk;
                if (str_contains($output, "\n")) {
                    break;
                }
            }
            $status = proc_get_status($worker['process']);
            if (! $status['running'] && (str_contains($output, "\n") || trim($output) !== '')) {
                break;
            }
            if (! $status['running'] && trim($output) === '') {
                $this->fail('Record store worker exited without a JSON result: '.stream_get_contents($worker['stderr']));
            }
            usleep(10_000);
        }
        if (! str_contains($output, "\n") && trim($output) === '') {
            proc_terminate($worker['process'], 9);
            $this->fail('Record store worker did not finish within the deadline.');
        }
        $exit = $this->awaitExit($worker);
        $this->assertSame(0, $exit, 'worker exit code; stderr: '.stream_get_contents($worker['stderr']));
        $line = strtok($output, "\n");
        $decoded = is_string($line) ? json_decode(trim($line), true) : null;
        if (! is_array($decoded)) {
            $this->fail('Record store worker printed no JSON result: '.$output);
        }

        return $decoded;
    }

    /** @param  array{process: resource, stdout: resource, stderr: resource, pid: int}  $worker */
    private function awaitExit(array $worker): int
    {
        $deadline = microtime(true) + self::WORKER_FINISH_DEADLINE_SECONDS;
        do {
            $status = proc_get_status($worker['process']);
            if (! $status['running']) {
                return $status['signaled'] ? -$status['termsig'] : $status['exitcode'];
            }
            usleep(10_000);
        } while (microtime(true) < $deadline);
        $this->fail('Record store worker did not exit.');
    }

    /** Waits until PostgreSQL itself has ended the killed worker's backend, which rolls its transaction back. */
    private function awaitBackendGone(int $pid): void
    {
        $deadline = microtime(true) + self::WORKER_LOCK_WAIT_DEADLINE_SECONDS;
        do {
            if ((int) DB::connection(self::PEER)->selectOne('SELECT count(*) AS n FROM pg_stat_activity WHERE pid = ?', [$pid])->n === 0) {
                return;
            }
            usleep(10_000);
        } while (microtime(true) < $deadline);
        $this->fail("The killed worker's backend {$pid} is still connected.");
    }

    /**
     * Polls PostgreSQL's own lock-wait graph; proceeds only when this connection blocks the worker.
     *
     * @param  array{process: resource, stdout: resource, stderr: resource, pid: int}  $worker
     */
    private function awaitWorkerBlockedByThisConnection(array $worker): void
    {
        $self = (int) DB::selectOne('SELECT pg_backend_pid() AS pid')->pid;
        $deadline = microtime(true) + self::WORKER_LOCK_WAIT_DEADLINE_SECONDS;
        do {
            $blocked = DB::connection(self::PEER)->selectOne('SELECT ? = ANY(pg_blocking_pids(?)) AS blocked', [$self, $worker['pid']])->blocked;
            if ($blocked) {
                return;
            }
            if (! proc_get_status($worker['process'])['running']) {
                $this->fail('Record store worker finished without waiting for the lock: '.stream_get_contents($worker['stdout']));
            }
            usleep(10_000);
        } while (microtime(true) < $deadline);
        $this->fail('Record store worker was never blocked by this connection.');
    }

    private static function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-10-15T09:00:00+00:00');
    }
}
