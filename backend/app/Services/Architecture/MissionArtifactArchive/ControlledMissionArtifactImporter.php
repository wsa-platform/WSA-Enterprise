<?php

namespace App\Services\Architecture\MissionArtifactArchive;

/**
 * Bounded, resumable discovery+import of mission docs under allowlisted roots.
 * Never rewrites source files. Never archives secrets.
 */
final class ControlledMissionArtifactImporter
{
    public function __construct(
        private readonly FilesystemMissionArtifactArchive $archive,
        private readonly MissionArtifactSecretFilter $secrets = new MissionArtifactSecretFilter(),
        private readonly MissionArtifactClassifier $classifier = new MissionArtifactClassifier(),
    ) {}

    /**
     * @param  list<string>  $roots  absolute directories
     * @return array<string, mixed>
     */
    public function inventoryAndImport(
        array $roots,
        string $manifestPath,
        string $checkpointPath,
        int $maxFiles = 80,
        bool $dryRun = false,
    ): array {
        $this->archive->ensureSchema();
        $checkpoint = $this->readJson($checkpointPath) ?? [
            'completed_paths' => [],
            'cursor' => 0,
        ];
        $completed = is_array($checkpoint['completed_paths'] ?? null) ? $checkpoint['completed_paths'] : [];

        $candidates = [];
        foreach ($roots as $root) {
            if (! is_dir($root)) {
                continue;
            }
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
            );
            foreach ($iterator as $file) {
                if (! $file instanceof \SplFileInfo || ! $file->isFile()) {
                    continue;
                }
                $path = $file->getPathname();
                $rel = $this->relativeTo($root, $path);
                $ext = strtolower($file->getExtension());
                if (! in_array($ext, ['json', 'md', 'markdown', 'txt', 'aho', 'xml'], true)) {
                    continue;
                }
                // Skip enormous blobs in controlled first pass
                if ($file->getSize() > 5_000_000) {
                    $candidates[] = [
                        'path' => $path,
                        'root' => $root,
                        'relative' => $rel,
                        'status' => 'skipped_too_large',
                        'bytes' => $file->getSize(),
                    ];
                    continue;
                }
                $excluded = $this->secrets->isExcluded($path);
                $class = $this->classifier->classify($path, $rel);
                $candidates[] = [
                    'path' => $path,
                    'root' => $root,
                    'relative' => $rel,
                    'bytes' => $file->getSize(),
                    'sha256' => hash_file('sha256', $path) ?: null,
                    'excluded' => $excluded,
                    'classification' => $class,
                    'status' => $excluded ? 'excluded_secret' : 'eligible',
                ];
            }
        }

        usort($candidates, static fn ($a, $b) => strcmp($a['relative'], $b['relative']));

        $manifest = [
            'schema_id' => 'wsa.mission_artifact_import_manifest.v1',
            'contract_version' => FilesystemMissionArtifactArchive::CONTRACT_VERSION,
            'dry_run' => $dryRun,
            'roots' => $roots,
            'discovered' => count($candidates),
            'eligible' => count(array_filter($candidates, static fn ($c) => ($c['status'] ?? '') === 'eligible')),
            'excluded_secret' => count(array_filter($candidates, static fn ($c) => ($c['status'] ?? '') === 'excluded_secret')),
            'skipped_too_large' => count(array_filter($candidates, static fn ($c) => ($c['status'] ?? '') === 'skipped_too_large')),
            'aho_discovered' => count(array_filter($candidates, static fn ($c) => ($c['classification']['format'] ?? '') === 'aho')),
            'aho_schema_supported' => false,
            'aho_note' => 'No .aho files or AHO schema found in repository inspection; format reserved but extraction unsupported.',
            'max_files' => $maxFiles,
            'items' => [],
            'totals' => [
                'attempted' => 0,
                'archived' => 0,
                'unchanged' => 0,
                'failed' => 0,
                'excluded' => 0,
            ],
            'completed' => false,
            'next_cursor' => (int) ($checkpoint['cursor'] ?? 0),
        ];

        if ($dryRun) {
            $manifest['items'] = array_slice($candidates, 0, 200);
            $manifest['completed'] = true;
            $this->writeJson($manifestPath, $manifest);

            return $manifest;
        }

        // Always rescan from index 0. New files can sort before a prior cursor tip;
        // idempotency is enforced by completed_paths + findByOriginalPath, not by cursor alone.
        $i = 0;
        $attempted = 0;
        while ($attempted < $maxFiles && $i < count($candidates)) {
            $c = $candidates[$i];
            $i++;
            $path = $c['path'];
            if (in_array($path, $completed, true)) {
                continue;
            }
            if (($c['status'] ?? '') !== 'eligible') {
                $manifest['items'][] = $c;
                if (($c['status'] ?? '') === 'excluded_secret') {
                    $manifest['totals']['excluded']++;
                }
                continue;
            }
            $attempted++;
            // Prefer stable repo-relative identity; also short-circuit if this absolute path was archived under a prior identity scheme.
            $rootName = basename(rtrim(str_replace('\\', '/', (string) ($c['root'] ?? dirname($path))), '/'));
            $ingestionId = 'sse:'.$rootName.'/'.str_replace('\\', '/', $c['relative']).'|sha256:'.($c['sha256'] ?? '');
            $existingPath = $this->archive->findByOriginalPath($path);
            if ($existingPath !== null) {
                $result = [
                    'status' => 'unchanged',
                    'archive_id' => $existingPath->archiveId,
                    'record' => $existingPath,
                ];
            } else {
                $result = $this->archive->ingestFile(
                    $path,
                    $ingestionId,
                    'controlled_import',
                    $c['classification']['mission_id'] ?? '2026-10-17-SOURCE-EXPANSION-MISSION',
                    null,
                    [],
                    $rootName.'/'.$c['relative'],
                );
            }
            $entry = array_merge($c, [
                'import_status' => $result['status'],
                'archive_id' => $result['archive_id'] ?? null,
                'reason' => $result['reason'] ?? null,
            ]);
            $manifest['items'][] = $entry;
            $manifest['totals']['attempted'] = $attempted;
            if ($result['status'] === 'archived') {
                $manifest['totals']['archived']++;
                $completed[] = $path;
            } elseif ($result['status'] === 'unchanged') {
                $manifest['totals']['unchanged']++;
                $completed[] = $path;
            } elseif ($result['status'] === 'excluded_secret') {
                $manifest['totals']['excluded']++;
            } else {
                $manifest['totals']['failed']++;
            }
            $checkpoint['cursor'] = $i;
            $checkpoint['completed_paths'] = array_values(array_unique($completed));
            $this->writeJson($checkpointPath, $checkpoint);
        }

        $manifest['completed'] = $i >= count($candidates);
        $manifest['next_cursor'] = $i;
        $manifest['pending_eligible'] = max(0, $manifest['eligible'] - count($completed));
        $this->writeJson($manifestPath, $manifest);
        $this->writeJson($checkpointPath, [
            'completed_paths' => array_values(array_unique($completed)),
            'cursor' => $i,
        ]);

        return $manifest;
    }

    private function relativeTo(string $root, string $path): string
    {
        $root = rtrim(str_replace('\\', '/', $root), '/');
        $path = str_replace('\\', '/', $path);
        if (str_starts_with($path, $root.'/')) {
            return substr($path, strlen($root) + 1);
        }

        return basename($path);
    }

    /** @return array<string, mixed>|null */
    private function readJson(string $path): ?array
    {
        if (! is_file($path)) {
            return null;
        }
        $raw = file_get_contents($path);
        if ($raw === false) {
            return null;
        }
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : null;
    }

    /** @param  array<string, mixed>  $data */
    private function writeJson(string $path, array $data): void
    {
        $dir = dirname($path);
        if (! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir)) {
            throw new \RuntimeException('Cannot write manifest directory');
        }
        file_put_contents(
            $path,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );
    }
}
