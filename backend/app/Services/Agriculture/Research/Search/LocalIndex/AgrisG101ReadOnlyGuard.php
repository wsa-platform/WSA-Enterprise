<?php

namespace App\Services\Agriculture\Research\Search\LocalIndex;

use PDO;
use Throwable;

/**
 * Proves operational AGRIS LocalIndex SQLite is opened read-only and unchanged.
 * Does not ingest, persist, rebuild FTS, or activate search.
 */
final class AgrisG101ReadOnlyGuard
{
    /**
     * @return array{
     *   path: string,
     *   exists: bool,
     *   size_before: int|null,
     *   mtime_before: int|null,
     *   size_after: int|null,
     *   mtime_after: int|null,
     *   unchanged: bool,
     *   readonly_open_ok: bool,
     *   query_only_pragma: bool|null,
     *   write_attempt_blocked: bool|null,
     *   error: ?string
     * }
     */
    public function probe(string $dbPath): array
    {
        $out = [
            'path' => $dbPath,
            'exists' => is_file($dbPath),
            'size_before' => is_file($dbPath) ? filesize($dbPath) : null,
            'mtime_before' => is_file($dbPath) ? filemtime($dbPath) : null,
            'size_after' => null,
            'mtime_after' => null,
            'unchanged' => false,
            'readonly_open_ok' => false,
            'query_only_pragma' => null,
            'write_attempt_blocked' => null,
            'error' => null,
        ];

        if (! $out['exists']) {
            $out['error'] = 'missing_sqlite';

            return $out;
        }

        try {
            $pdo = new PDO('sqlite:'.$dbPath, null, null, [
                PDO::SQLITE_ATTR_OPEN_FLAGS => PDO::SQLITE_OPEN_READONLY,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5,
            ]);
            $pdo->exec('PRAGMA query_only = ON');
            $out['readonly_open_ok'] = true;
            $mode = $pdo->query('PRAGMA query_only')->fetchColumn();
            $out['query_only_pragma'] = ((string) $mode === '1' || $mode === 1 || $mode === true);

            $writeBlocked = false;
            try {
                $pdo->exec("CREATE TABLE IF NOT EXISTS _agris_g101_ro_guard_should_fail (id INTEGER)");
            } catch (Throwable) {
                $writeBlocked = true;
            }
            $out['write_attempt_blocked'] = $writeBlocked;

            // Touch-free generation read.
            $pdo->query("SELECT generation FROM scientific_source_states WHERE source_key='agris' LIMIT 1");
        } catch (Throwable $e) {
            $out['error'] = $e->getMessage();
        }

        clearstatcache(true, $dbPath);
        $out['size_after'] = filesize($dbPath);
        $out['mtime_after'] = filemtime($dbPath);
        $out['unchanged'] = $out['size_before'] === $out['size_after']
            && $out['mtime_before'] === $out['mtime_after'];

        return $out;
    }
}
