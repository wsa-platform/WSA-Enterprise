<?php

namespace App\Console\Commands;

use App\Services\Agriculture\Research\Onboarding\CurrentMembershipNearReadySeatCatalog;
use App\Services\Agriculture\Research\Onboarding\NextGateEligibleSeatCatalog;
use App\Services\Agriculture\Research\Search\Family\Bulk\BulkDatasetSourceProfile;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalIndexIngestionService;
use App\Services\Agriculture\Research\Search\LocalIndex\ScientificRecordStore;
use DateTimeImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Resume-safe incremental ingest of projected AGRIS JSONL packages into LocalIndex.
 *
 * Never downloads. Never uses snapshot mode (does not withdraw historical peers).
 * Protects Generation 76 baseline by incremental upserts only.
 */
final class IngestAgrisProjectedPackagesCommand extends Command
{
    protected $signature = 'scientific:ingest-agris-projected
        {--manifest= : Path to harvest_manifest.v10.19.local-only.json}
        {--checkpoint= : Resume checkpoint JSON path}
        {--chunk-size=5000 : Records per incremental commit}
        {--max-packages=0 : Stop after N newly completed packages (0 = all remaining)}
        {--defer-index-sync : Rebuild FTS once per package instead of per chunk}
        {--dry-run : List eligible packages without writing}';

    protected $description = 'Incremental LocalIndex ingest of projected AGRIS JSONL (no download, no snapshot withdraw)';

    public function handle(LocalIndexIngestionService $ingestion, ScientificRecordStore $store): int
    {
        $manifestPath = (string) $this->option('manifest');
        $checkpointPath = (string) $this->option('checkpoint');
        if ($manifestPath === '' || $checkpointPath === '') {
            $this->error('--manifest and --checkpoint are required');

            return self::FAILURE;
        }
        if (! is_file($manifestPath)) {
            $this->error('Manifest not found: '.$manifestPath);

            return self::FAILURE;
        }

        $manifest = json_decode((string) file_get_contents($manifestPath), true);
        if (! is_array($manifest) || ! isset($manifest['providers']) || ! is_array($manifest['providers'])) {
            $this->error('Manifest missing providers[]');

            return self::FAILURE;
        }

        $profile = $this->resolveAgrisProfile();
        if ($profile === null) {
            $this->error('AGRIS bulk profile not found in seat catalogs');

            return self::FAILURE;
        }

        $baseline = [
            'generation' => $store->generation('agris'),
            'active_count' => $this->activeCount(),
            'captured_at' => (new DateTimeImmutable('now'))->format(DATE_ATOM),
        ];

        $checkpoint = $this->loadCheckpoint($checkpointPath, $baseline);
        $chunkSize = max(1, min(50_000, (int) $this->option('chunk-size')));
        $maxPackages = max(0, (int) $this->option('max-packages'));
        $dryRun = (bool) $this->option('dry-run');

        $eligible = [];
        foreach ($manifest['providers'] as $provider) {
            if (! is_array($provider) || ($provider['status'] ?? null) !== 'projected') {
                continue;
            }
            $jsonl = (string) ($provider['jsonl'] ?? '');
            if ($jsonl === '' || ! is_file($jsonl)) {
                continue;
            }
            $packageId = pathinfo($jsonl, PATHINFO_FILENAME);
            if (isset($checkpoint['completed_packages'][$packageId])) {
                continue;
            }
            $eligible[] = [
                'package_id' => $packageId,
                'jsonl' => $jsonl,
                'catalog_url' => $provider['url'] ?? null,
                'license' => $provider['license'] ?? null,
                'projected_records' => (int) ($provider['records'] ?? 0),
            ];
        }

        $this->emit([
            'phase' => 'plan',
            'baseline' => $baseline,
            'already_completed' => count($checkpoint['completed_packages']),
            'eligible_remaining' => count($eligible),
            'chunk_size' => $chunkSize,
            'dry_run' => $dryRun,
            'mode' => 'incremental_only',
            'no_download' => true,
        ]);

        if ($dryRun) {
            return self::SUCCESS;
        }

        $newly = 0;
        foreach ($eligible as $pkg) {
            if ($maxPackages > 0 && $newly >= $maxPackages) {
                break;
            }

            $skip = (int) ($checkpoint['partial'][$pkg['package_id']]['skip_records'] ?? 0);
            $started = microtime(true);
            try {
                $result = $ingestion->ingestIncrementalBulkChunked(
                    $profile,
                    $pkg['jsonl'],
                    new DateTimeImmutable('now'),
                    $chunkSize,
                    $skip,
                    function (int $chunks, int $offset, $chunkReport) use (&$checkpoint, $checkpointPath, $pkg): void {
                        $checkpoint['partial'][$pkg['package_id']] = [
                            'skip_records' => $offset,
                            'chunks' => $chunks,
                            'generation' => $chunkReport->generation,
                            'at' => (new DateTimeImmutable('now'))->format(DATE_ATOM),
                        ];
                        $checkpoint['last_package'] = $pkg['package_id'];
                        $checkpoint['last_progress_at'] = (new DateTimeImmutable('now'))->format(DATE_ATOM);
                        $this->writeCheckpoint($checkpointPath, $checkpoint);
                        $this->emit([
                            'package_id' => $pkg['package_id'],
                            'status' => 'chunk_progress',
                            'chunks' => $chunks,
                            'skip_records' => $offset,
                            'generation' => $chunkReport->generation,
                            'chunk_counts' => $chunkReport->counts,
                        ]);
                    },
                    deferIndexSync: (bool) $this->option('defer-index-sync'),
                );
            } catch (\Throwable $e) {
                $checkpoint['failures'][$pkg['package_id']] = [
                    'error' => 'exception',
                    'message' => $e->getMessage(),
                    'at' => (new DateTimeImmutable('now'))->format(DATE_ATOM),
                ];
                $this->writeCheckpoint($checkpointPath, $checkpoint);
                $this->error(json_encode([
                    'package_id' => $pkg['package_id'],
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ], JSON_UNESCAPED_SLASHES));

                return self::FAILURE;
            }

            $report = $result['report'];
            if ($report->status !== 'completed') {
                $checkpoint['partial'][$pkg['package_id']] = [
                    'skip_records' => $result['skip_resume'],
                    'failure' => $report->failure,
                    'at' => (new DateTimeImmutable('now'))->format(DATE_ATOM),
                ];
                $this->writeCheckpoint($checkpointPath, $checkpoint);
                $this->error(json_encode([
                    'package_id' => $pkg['package_id'],
                    'status' => $report->status,
                    'failure' => $report->failure,
                    'skip_resume' => $result['skip_resume'],
                ], JSON_UNESCAPED_SLASHES));

                return self::FAILURE;
            }

            unset($checkpoint['partial'][$pkg['package_id']], $checkpoint['failures'][$pkg['package_id']]);
            $checkpoint['completed_packages'][$pkg['package_id']] = [
                'catalog_url' => $pkg['catalog_url'],
                'projected_records' => $pkg['projected_records'],
                'records_read' => $result['records_read'],
                'chunks' => $result['chunks'],
                'counts' => $report->counts,
                'generation_after' => $report->generation,
                'elapsed_ms' => (int) round((microtime(true) - $started) * 1000),
                'completed_at' => (new DateTimeImmutable('now'))->format(DATE_ATOM),
            ];
            $checkpoint['last_package'] = $pkg['package_id'];
            $checkpoint['last_progress_at'] = (new DateTimeImmutable('now'))->format(DATE_ATOM);
            $checkpoint['active_count_after'] = $this->activeCount();
            $checkpoint['generation_after'] = $store->generation('agris');
            $this->writeCheckpoint($checkpointPath, $checkpoint);
            $newly++;

            $this->emit([
                'package_id' => $pkg['package_id'],
                'status' => 'completed',
                'records_read' => $result['records_read'],
                'chunks' => $result['chunks'],
                'counts' => $report->counts,
                'generation' => $report->generation,
                'active_count' => $checkpoint['active_count_after'],
                'elapsed_ms' => $checkpoint['completed_packages'][$pkg['package_id']]['elapsed_ms'],
            ]);
        }

        $remaining = count($eligible) - $newly;
        $this->emit([
            'phase' => 'summary',
            'newly_completed' => $newly,
            'total_completed' => count($checkpoint['completed_packages']),
            'remaining_eligible' => max(0, $remaining),
            'generation' => $store->generation('agris'),
            'active_count' => $this->activeCount(),
            'baseline_generation' => $baseline['generation'],
            'baseline_active_count' => $baseline['active_count'],
            'historical_gen76_preserved' => $store->generation('agris') >= 76 && $this->activeCount() >= (int) $baseline['active_count'],
        ]);

        return self::SUCCESS;
    }

    /** @param  array<string, mixed>  $payload */
    private function emit(array $payload): void
    {
        $this->line(json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        if (function_exists('ob_get_level') && ob_get_level() > 0) {
            @ob_flush();
        }
        flush();
    }

    private function resolveAgrisProfile(): ?BulkDatasetSourceProfile
    {
        foreach ([NextGateEligibleSeatCatalog::all(), CurrentMembershipNearReadySeatCatalog::all()] as $seats) {
            foreach ($seats as $seat) {
                if ($seat->sourceKey !== 'agris') {
                    continue;
                }
                if ($seat->familyProfile instanceof BulkDatasetSourceProfile) {
                    return $seat->familyProfile;
                }
            }
        }

        return null;
    }

    private function activeCount(): int
    {
        try {
            return (int) DB::connection((string) config('agricultural_intelligence.local_index.connection', 'scientific_records'))
                ->table('scientific_source_records')
                ->where('source_key', 'agris')
                ->where('lifecycle_state', 'active')
                ->count();
        } catch (\Throwable) {
            return -1;
        }
    }

    /** @return array<string, mixed> */
    private function loadCheckpoint(string $path, array $baseline): array
    {
        if (is_file($path)) {
            $decoded = json_decode((string) file_get_contents($path), true);
            if (is_array($decoded) && isset($decoded['completed_packages']) && is_array($decoded['completed_packages'])) {
                $decoded['partial'] = is_array($decoded['partial'] ?? null) ? $decoded['partial'] : [];
                $decoded['failures'] = is_array($decoded['failures'] ?? null) ? $decoded['failures'] : [];

                return $decoded;
            }
        }

        return [
            'schema_id' => 'wsa.agris_projected_ingest_checkpoint.v1',
            'revision' => '10.21',
            'mode' => 'incremental_only',
            'no_download' => true,
            'baseline' => $baseline,
            'completed_packages' => [],
            'partial' => [],
            'failures' => [],
            'created_at' => (new DateTimeImmutable('now'))->format(DATE_ATOM),
        ];
    }

    /** @param  array<string, mixed>  $checkpoint */
    private function writeCheckpoint(string $path, array $checkpoint): void
    {
        $dir = dirname($path);
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $checkpoint['updated_at'] = (new DateTimeImmutable('now'))->format(DATE_ATOM);
        file_put_contents(
            $path,
            json_encode($checkpoint, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT).PHP_EOL,
        );
    }
}
