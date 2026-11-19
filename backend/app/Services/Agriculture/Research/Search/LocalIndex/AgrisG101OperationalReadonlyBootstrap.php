<?php

namespace App\Services\Agriculture\Research\Search\LocalIndex;

use Illuminate\Support\Facades\DB;
use PDO;
use Throwable;

/**
 * Boots an operational AGRIS LocalIndex search stack that cannot write.
 *
 * Registers `scientific_records_readonly` (SQLITE_OPEN_READONLY), rebinds
 * ScientificRecordStore + LocalIndexSearchEngine for the request, pins AGRIS
 * search generation to avoid adapter rebuild, and enables FTS read_only_search.
 */
final class AgrisG101OperationalReadonlyBootstrap
{
    public const CONNECTION = 'scientific_records_readonly';

    /**
     * @return array{
     *   ok: bool,
     *   path: string,
     *   connection: string,
     *   store_generation: int|null,
     *   fts_generation: int|null,
     *   fts_docs: int|null,
     *   pin_generation: int|null,
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
            'pin_generation' => null,
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
            $storeGen = (int) $probe->query("SELECT generation FROM scientific_source_states WHERE source_key='agris'")->fetchColumn();
            $ftsGen = (int) $probe->query("SELECT generation FROM scientific_source_fts_meta WHERE source_key='agris'")->fetchColumn();
            // Avoid full FTS COUNT on multi-million corpora; presence check is enough for RO search.
            $ftsPresent = (bool) $probe->query(
                "SELECT 1 FROM scientific_source_fts WHERE source_key='agris' LIMIT 1"
            )->fetchColumn();
            $probe = null;

            if (! $ftsPresent || $ftsGen < 1) {
                $out['error'] = 'fts_not_ready_for_readonly_search';
                $out['store_generation'] = $storeGen;
                $out['fts_generation'] = $ftsGen;
                $out['fts_docs'] = $ftsPresent ? 1 : 0;

                return $out;
            }
            $ftsDocs = 1;

            // Pin to FTS generation so LocalIndexScientificSourceAdapter skips rebuild.
            $pin = $ftsGen;

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
                'agricultural_intelligence.local_index.agris_candidate_mode' => true,
                'agricultural_intelligence.local_index.agris_search_pin_generation' => $pin,
            ]);

            DB::purge(self::CONNECTION);
            DB::connection(self::CONNECTION)->getPdo();
            DB::connection(self::CONNECTION)->statement('PRAGMA query_only = ON');

            // Rebind singletons so Stage-3 uses the RO connection.
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

            // Smoke: read-only generation + refuse write.
            $writeBlocked = false;
            try {
                DB::connection(self::CONNECTION)->statement('CREATE TABLE _agris_c12_ro_should_fail (id INTEGER)');
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
            $out['fts_docs'] = $ftsDocs;
            $out['pin_generation'] = $pin;
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
