<?php

namespace Tests\Unit\Agriculture\Research\Search\LocalIndex;

use App\Services\Agriculture\Research\Search\LocalIndex\LocalIndexIngestionService;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalIndexSearchEngine;
use App\Services\Agriculture\Research\Search\LocalIndex\ScientificRecordStore;
use App\Services\Agriculture\Research\Search\LocalIndex\SqliteFtsScientificIndex;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\Support\DatabaseSafety;
use Tests\TestCase;

/**
 * Rev 11.0.14: --dry-run must list eligible packages and must not mutate checkpoints.
 * Uses an isolated scientific_records SQLite file — never the operational LocalIndex.
 */
class IngestAgrisProjectedDryRunSafetyTest extends TestCase
{
    private string $dbPath = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->dbPath = storage_path('framework/testing/agris_dry_run_r1114_'.uniqid('', true).'.sqlite');
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

    public function test_dry_run_emits_eligible_ids_without_writing_checkpoints(): void
    {
        $dir = storage_path('framework/testing/agris_dry_run_fixtures_r1114');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $jsonlA = $dir.'/AGRIS.ODS.AAA.jsonl';
        $jsonlB = $dir.'/AGRIS.ODS.BBB.jsonl';
        file_put_contents($jsonlA, json_encode(['id' => 'a1', 'title' => 'A', 'url' => 'https://example.test/a'])."\n");
        file_put_contents($jsonlB, json_encode(['id' => 'b1', 'title' => 'B', 'url' => 'https://example.test/b'])."\n");

        $manifest = $dir.'/manifest.json';
        file_put_contents($manifest, json_encode([
            'providers' => [
                [
                    'status' => 'projected',
                    'jsonl' => $jsonlA,
                    'url' => 'https://agris.fao.org/ods/AGRIS.ODS.AAA.xml',
                    'records' => 1,
                ],
                [
                    'status' => 'projected',
                    'jsonl' => $jsonlB,
                    'url' => 'https://agris.fao.org/ods/AGRIS.ODS.BBB.xml',
                    'records' => 1,
                ],
            ],
        ], JSON_UNESCAPED_SLASHES));

        $checkpoint = $dir.'/ingest_checkpoint.json';
        $ftsCheckpoint = $dir.'/fts_checkpoint.json';
        $checkpointPayload = [
            'schema_id' => 'wsa.agris_projected_ingest_checkpoint.v1',
            'revision' => '10.23',
            'completed_packages' => [
                'AGRIS.ODS.AAA' => [
                    'persist_status' => 'completed',
                    'completed_at' => '2026-11-17T00:00:00+00:00',
                ],
            ],
            'partial' => [
                'AGRIS.ODS.BRY' => [
                    'skip_records' => 2771,
                    'chunks' => 3,
                ],
            ],
            'failures' => [],
            'updated_at' => '2026-11-17T00:00:00+00:00',
        ];
        file_put_contents($checkpoint, json_encode($checkpointPayload, JSON_PRETTY_PRINT));
        file_put_contents($ftsCheckpoint, json_encode([
            'schema_id' => 'wsa.agris_fts_checkpoint.v1',
            'revision' => '10.23',
            'batches' => [],
            'updated_at' => '2026-11-17T00:00:00+00:00',
        ], JSON_PRETTY_PRINT));

        $beforeCheckpoint = (string) file_get_contents($checkpoint);
        $beforeFts = (string) file_get_contents($ftsCheckpoint);

        $exit = Artisan::call('scientific:ingest-agris-projected', [
            '--manifest' => $manifest,
            '--checkpoint' => $checkpoint,
            '--fts-checkpoint' => $ftsCheckpoint,
            '--chunk-size' => 10,
            '--max-packages' => 5,
            '--dry-run' => true,
        ]);

        $this->assertSame(0, $exit);
        $output = Artisan::output();
        $this->assertStringContainsString('"dry_run":true', $output);
        $this->assertStringContainsString('AGRIS.ODS.BBB', $output);
        $this->assertStringContainsString('"eligible_remaining":1', $output);
        $this->assertSame($beforeCheckpoint, (string) file_get_contents($checkpoint), 'dry-run must not mutate ingest checkpoint (including BRY auto-complete)');
        $this->assertSame($beforeFts, (string) file_get_contents($ftsCheckpoint), 'dry-run must not mutate FTS checkpoint');

        @unlink($jsonlA);
        @unlink($jsonlB);
        @unlink($manifest);
        @unlink($checkpoint);
        @unlink($ftsCheckpoint);
    }
}
