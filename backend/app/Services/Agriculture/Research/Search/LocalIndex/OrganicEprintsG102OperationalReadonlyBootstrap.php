<?php

namespace App\Services\Agriculture\Research\Search\LocalIndex;

use Illuminate\Support\Facades\DB;
use PDO;
use Throwable;

/**
 * Boots an operational Organic Eprints LocalIndex search stack that cannot write.
 *
 * Registers `scientific_records_readonly` (SQLITE_OPEN_READONLY), rebinds
 * ScientificRecordStore + LocalIndexSearchEngine for the request, and enables
 * FTS read_only_search. Does not harvest or persist.
 */
final class OrganicEprintsG102OperationalReadonlyBootstrap
{
    public const CONNECTION = 'scientific_records_readonly';

    public const SOURCE_KEY = 'organic_eprints';

    /**
     * @return array{
     *   ok: bool,
     *   path: string,
     *   connection: string,
     *   store_generation: int|null,
     *   fts_generation: int|null,
     *   fts_docs: int|null,
     *   size_before: int|null,
     *   mtime_before: int|null,
     *   error: ?string
     * }
     */
    public function activate(?string $dbPath = null): array
    {
        $path = $dbPath ?? storage_path('app/scientific_record_store.sqlite');
        $out = [
            'ok' => false,
            'path' => $path,
            'connection' => self::CONNECTION,
            'store_generation' => null,
            'fts_generation' => null,
            'fts_docs' => null,
            'size_before' => is_file($path) ? filesize($path) : null,
            'mtime_before' => is_file($path) ? filemtime($path) : null,
            'error' => null,
        ];

        if (! is_file($path)) {
            $out['error'] = 'operational_sqlite_missing';

            return $out;
        }

        try {
            $probe = new PDO('sqlite:'.$path, null, null, [
                PDO::SQLITE_ATTR_OPEN_FLAGS => PDO::SQLITE_OPEN_READONLY,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 30,
            ]);
            $probe->exec('PRAGMA query_only = ON');
            $storeGen = (int) $probe->query(
                "SELECT generation FROM scientific_source_states WHERE source_key='organic_eprints'"
            )->fetchColumn();
            $ftsGen = (int) $probe->query(
                "SELECT generation FROM scientific_source_fts_meta WHERE source_key='organic_eprints'"
            )->fetchColumn();
            $probe = null;

            if ($storeGen < 1 || $ftsGen < 1) {
                $out['error'] = 'organic_eprints_localindex_not_ready';
                $out['store_generation'] = $storeGen;
                $out['fts_generation'] = $ftsGen;
                $out['fts_docs'] = 0;

                return $out;
            }

            config([
                'database.connections.'.self::CONNECTION => [
                    'driver' => 'sqlite',
                    'database' => $path,
                    'prefix' => '',
                    'foreign_key_constraints' => true,
                    'busy_timeout' => 30000,
                    'options' => [
                        PDO::SQLITE_ATTR_OPEN_FLAGS => PDO::SQLITE_OPEN_READONLY,
                    ],
                ],
                'agricultural_intelligence.local_index.connection' => self::CONNECTION,
                'agricultural_intelligence.local_index.search_backend' => 'fts5',
                'agricultural_intelligence.local_index.read_only_search' => true,
            ]);

            DB::purge(self::CONNECTION);
            DB::connection(self::CONNECTION)->getPdo();
            DB::connection(self::CONNECTION)->statement('PRAGMA query_only = ON');

            app()->forgetInstance(ScientificRecordStore::class);
            app()->forgetInstance(SqliteFtsScientificIndex::class);
            app()->forgetInstance(LocalIndexSearchEngine::class);
            app()->forgetInstance(DatabaseScientificRecordStore::class);

            $store = new DatabaseScientificRecordStore(app('db'), self::CONNECTION);
            $index = new SqliteFtsScientificIndex(app('db'), $store, self::CONNECTION);
            app()->instance(ScientificRecordStore::class, $store);
            app()->instance(DatabaseScientificRecordStore::class, $store);
            app()->instance(SqliteFtsScientificIndex::class, $index);
            app()->instance(LocalIndexSearchEngine::class, $index);

            $writeBlocked = false;
            try {
                DB::connection(self::CONNECTION)->statement('CREATE TABLE _oe_g102_ro_should_fail (id INTEGER)');
            } catch (Throwable) {
                $writeBlocked = true;
            }
            if (! $writeBlocked) {
                $out['error'] = 'readonly_write_not_blocked';

                return $out;
            }

            $out['ok'] = true;
            $out['store_generation'] = $storeGen;
            $out['fts_generation'] = $ftsGen;
            $out['fts_docs'] = 1;
        } catch (Throwable $e) {
            $out['error'] = $e->getMessage();
        }

        return $out;
    }

    /**
     * @param  array{size_before: int|null, mtime_before: int|null, path: string}  $baseline
     * @return array{unchanged: bool, size_after: int|null, mtime_after: int|null}
     */
    public function verifyUnchanged(array $baseline): array
    {
        $path = (string) ($baseline['path'] ?? storage_path('app/scientific_record_store.sqlite'));
        clearstatcache(true, $path);
        $sizeAfter = is_file($path) ? filesize($path) : null;
        $mtimeAfter = is_file($path) ? filemtime($path) : null;

        return [
            'unchanged' => ($baseline['size_before'] ?? null) === $sizeAfter
                && ($baseline['mtime_before'] ?? null) === $mtimeAfter,
            'size_after' => $sizeAfter,
            'mtime_after' => $mtimeAfter,
        ];
    }
}
