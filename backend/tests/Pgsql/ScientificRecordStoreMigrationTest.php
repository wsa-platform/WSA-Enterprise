<?php

namespace Tests\Pgsql;

use App\Services\Agriculture\Research\Search\LocalIndex\DatabaseScientificRecordStore;
use App\Services\Agriculture\Research\Search\LocalIndex\InMemoryScientificRecordStore;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalIndexScientificSourceAdapter;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalScientificIndex;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalStoreFailure;
use App\Services\Agriculture\Research\Search\LocalIndex\ScientificRecordStore;
use App\Services\Agriculture\Research\Search\ScientificSourceSearchOutcome;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\BindsPgsqlLocalIndexForGuardedSuite;
use Tests\Support\LocalIndexFixtures;
use Tests\TestCase;

/**
 * Migration safety for the durable scientific record store (ADR-023 §8.42).
 *
 * Runs only on the disposable PostgreSQL test database. Rollback is exercised on an
 * empty schema after a controlled wipe of store rows — never against preserved data.
 */
final class ScientificRecordStoreMigrationTest extends TestCase
{
    use BindsPgsqlLocalIndexForGuardedSuite;
    use LocalIndexFixtures;

    private const TABLES = [
        DatabaseScientificRecordStore::RECORDS_TABLE,
        DatabaseScientificRecordStore::GENERATIONS_TABLE,
        DatabaseScientificRecordStore::STATES_TABLE,
    ];

    private const MIGRATION = '2026_10_15_220000_create_scientific_source_record_store_tables';

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertDisposablePostgresTestDatabase();
        $this->bindLocalIndexToGuardedPgsqlTesting();
        Artisan::call('migrate:fresh', ['--force' => true]);
    }

    public function test_provider_binds_the_database_store_and_never_the_in_memory_store(): void
    {
        $store = app(ScientificRecordStore::class);

        $this->assertInstanceOf(DatabaseScientificRecordStore::class, $store);
        $this->assertNotInstanceOf(InMemoryScientificRecordStore::class, $store);
    }

    public function test_migration_creates_expected_tables_constraints_and_indexes(): void
    {
        foreach (self::TABLES as $table) {
            $this->assertTrue(Schema::hasTable($table), $table);
        }

        $indexes = collect(DB::select(
            "SELECT indexname, indexdef FROM pg_indexes WHERE schemaname = 'public' AND tablename = ?",
            [DatabaseScientificRecordStore::RECORDS_TABLE],
        ))->pluck('indexdef', 'indexname');
        $this->assertTrue(
            $indexes->contains(fn (string $def): bool => str_contains($def, 'UNIQUE') && str_contains($def, 'source_key') && str_contains($def, 'original_identifier')),
        );

        $checks = collect(DB::select(
            "SELECT conname FROM pg_constraint WHERE contype = 'c' AND conrelid = ?::regclass",
            [DatabaseScientificRecordStore::RECORDS_TABLE],
        ))->pluck('conname');
        $this->assertContains('scientific_source_records_lifecycle_ck', $checks);
        $this->assertContains('scientific_source_records_generation_ck', $checks);
    }

    public function test_migration_is_repeatable_after_safe_rollback_on_empty_store(): void
    {
        foreach (self::TABLES as $table) {
            $this->assertSame(0, DB::table($table)->count(), $table.' must be empty before rollback');
        }

        $this->assertSame(0, Artisan::call('migrate:rollback', ['--path' => 'database/migrations/'.self::MIGRATION.'.php', '--force' => true]));
        foreach (self::TABLES as $table) {
            $this->assertFalse(Schema::hasTable($table), $table);
        }

        $this->assertSame(0, Artisan::call('migrate', ['--path' => 'database/migrations/'.self::MIGRATION.'.php', '--force' => true]));
        foreach (self::TABLES as $table) {
            $this->assertTrue(Schema::hasTable($table), $table);
        }
    }

    public function test_missing_schema_fails_search_instead_of_returning_empty_or_falling_back_to_memory(): void
    {
        foreach (self::TABLES as $table) {
            $this->assertSame(0, DB::table($table)->count(), $table);
        }
        Artisan::call('migrate:rollback', ['--path' => 'database/migrations/'.self::MIGRATION.'.php', '--force' => true]);

        $adapter = new LocalIndexScientificSourceAdapter(
            self::fixtureBulkProfile(),
            app(LocalScientificIndex::class),
            app(ScientificRecordStore::class),
        );
        $outcome = $adapter->search('barley');

        $this->assertSame(ScientificSourceSearchOutcome::STATUS_FAILED, $outcome->status);
        $this->assertSame(LocalStoreFailure::STORE_UNAVAILABLE, $outcome->error);
        $this->assertSame([], $outcome->results);
        $this->assertInstanceOf(DatabaseScientificRecordStore::class, app(ScientificRecordStore::class));
    }

    private function assertDisposablePostgresTestDatabase(): void
    {
        $expected = (string) getenv('DB_TEST_DATABASE');
        $this->assertSame('pgsql_testing', config('database.default'));
        $this->assertSame('pgsql', DB::connection()->getDriverName());
        $current = (string) DB::selectOne('SELECT current_database() AS name')->name;
        $this->assertSame($expected, $current);
        $this->assertStringEndsWith('_test', $current);
        $this->assertNotContains($current, ['wsa_enterprise']);
    }
}
