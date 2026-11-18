<?php

namespace App\Console\Commands;

use App\Services\Agriculture\Research\Onboarding\CurrentMembershipNearReadySeatCatalog;
use App\Services\Agriculture\Research\Onboarding\NextGateEligibleSeatCatalog;
use App\Services\Agriculture\Research\Search\Family\Bulk\BulkDatasetSourceProfile;
use App\Services\Agriculture\Research\Search\LocalIndex\AgrisCandidateIngestCoordinator;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalIndexIngestionService;
use App\Services\Agriculture\Research\Search\LocalIndex\ScientificRecordStore;
use DateTimeImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Resume-safe incremental ingest of projected AGRIS JSONL packages into LocalIndex.
 *
 * Never downloads. Never uses snapshot mode (does not withdraw historical peers).
 * Rev 10.23: candidate store generations advance while search FTS stays pinned at GEN79;
 * separate FTS checkpoint; FTS failure never re-ingests.
 */
final class IngestAgrisProjectedPackagesCommand extends Command
{
    protected $signature = 'scientific:ingest-agris-projected
        {--manifest= : Path to harvest_manifest.v10.19.local-only.json}
        {--checkpoint= : Resume ingest checkpoint JSON path}
        {--fts-checkpoint= : Separate FTS checkpoint JSON path}
        {--chunk-size=2000 : Records per incremental persist chunk}
        {--max-packages=0 : Stop after N newly completed packages (0 = all remaining)}
        {--activate-search : Rebuild search FTS to current store after eligible ingest complete}
        {--dry-run : List eligible packages without writing}';

    protected $description = 'Incremental LocalIndex ingest of projected AGRIS JSONL (candidate mode; no download)';

    public function handle(
        LocalIndexIngestionService $ingestion,
        ScientificRecordStore $store,
        AgrisCandidateIngestCoordinator $coordinator,
    ): int {
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

        $ftsCheckpointPath = (string) $this->option('fts-checkpoint');
        if ($ftsCheckpointPath === '') {
            $ftsCheckpointPath = dirname($checkpointPath).DIRECTORY_SEPARATOR.'agris_fts_checkpoint.v10.23.json';
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
            'search_pin_generation' => AgrisCandidateIngestCoordinator::SEARCH_PIN_GENERATION,
            'captured_at' => (new DateTimeImmutable('now'))->format(DATE_ATOM),
        ];

        $checkpoint = $this->loadCheckpoint($checkpointPath, $baseline);
        $ftsCheckpoint = $this->loadFtsCheckpoint($ftsCheckpointPath, $baseline);
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
            'eligible_package_ids' => array_column($eligible, 'package_id'),
            'chunk_size' => $chunkSize,
            'dry_run' => $dryRun,
            'mode' => 'candidate_incremental',
            'search_pin_generation' => AgrisCandidateIngestCoordinator::SEARCH_PIN_GENERATION,
            'no_download' => true,
            'fts_checkpoint' => $ftsCheckpointPath,
        ]);

        if ($dryRun) {
            return self::SUCCESS;
        }

        // Auto-complete BRY if prior partial already consumed all projected records (persist done; FTS pending).
        // Runs only after dry-run gate so --dry-run cannot mutate checkpoints.
        if (isset($checkpoint['partial']['AGRIS.ODS.BRY'])) {
            $brySkip = (int) ($checkpoint['partial']['AGRIS.ODS.BRY']['skip_records'] ?? 0);
            $bryProjected = 2771;
            foreach ($manifest['providers'] as $provider) {
                if (($provider['status'] ?? null) === 'projected' && str_contains((string) ($provider['jsonl'] ?? ''), 'AGRIS.ODS.BRY')) {
                    $bryProjected = (int) ($provider['records'] ?? 2771);
                    break;
                }
            }
            if ($brySkip >= $bryProjected && ! isset($checkpoint['completed_packages']['AGRIS.ODS.BRY'])) {
                unset($checkpoint['partial']['AGRIS.ODS.BRY']);
                $checkpoint['completed_packages']['AGRIS.ODS.BRY'] = [
                    'catalog_url' => 'https://agris.fao.org/ods/AGRIS.ODS.BRY.xml',
                    'projected_records' => $bryProjected,
                    'records_read' => $brySkip,
                    'chunks' => (int) ceil($brySkip / max(1, $chunkSize)),
                    'counts' => ['note' => 'persisted_via_prior_partial_chunks'],
                    'generation_after' => $store->generation('agris'),
                    'completed_at' => (new DateTimeImmutable('now'))->format(DATE_ATOM),
                    'persist_status' => 'completed',
                    'fts_status' => 'pending',
                ];
                $ftsCheckpoint['batches']['AGRIS.ODS.BRY'] = [
                    'status' => 'fts_pending',
                    'pending' => true,
                    'store_generation' => $store->generation('agris'),
                    'search_pin_generation' => AgrisCandidateIngestCoordinator::SEARCH_PIN_GENERATION,
                    'at' => (new DateTimeImmutable('now'))->format(DATE_ATOM),
                ];
                $this->writeCheckpoint($checkpointPath, $checkpoint);
                $this->writeCheckpoint($ftsCheckpointPath, $ftsCheckpoint);
                $this->emit(['package_id' => 'AGRIS.ODS.BRY', 'status' => 'persist_completed_from_partial', 'fts_status' => 'pending']);
                $eligible = array_values(array_filter(
                    $eligible,
                    static fn (array $pkg): bool => ($pkg['package_id'] ?? null) !== 'AGRIS.ODS.BRY',
                ));
            }
        }

        $newly = 0;
        foreach ($eligible as $pkg) {
            if ($maxPackages > 0 && $newly >= $maxPackages) {
                break;
            }

            $skip = (int) ($checkpoint['partial'][$pkg['package_id']]['skip_records'] ?? 0);
            $started = microtime(true);
            try {
                $outcome = $coordinator->ingestPackageBatch(
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

            $result = $outcome['persist'];
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
                'persist_status' => 'completed',
                'fts_status' => $outcome['fts']['pending'] ? 'pending' : ($outcome['fts']['status'] ?? 'unknown'),
            ];
            $checkpoint['last_package'] = $pkg['package_id'];
            $checkpoint['last_progress_at'] = (new DateTimeImmutable('now'))->format(DATE_ATOM);
            $checkpoint['active_count_after'] = $this->activeCount();
            $checkpoint['generation_after'] = $store->generation('agris');
            $this->writeCheckpoint($checkpointPath, $checkpoint);

            $ftsCheckpoint['batches'][$pkg['package_id']] = [
                'status' => $outcome['fts']['pending'] ? 'fts_pending' : 'fts_ok',
                'pending' => (bool) ($outcome['fts']['pending'] ?? false),
                'failure' => $outcome['fts']['failure'] ?? null,
                'store_generation' => $report->generation,
                'search_pin_generation' => AgrisCandidateIngestCoordinator::SEARCH_PIN_GENERATION,
                'at' => (new DateTimeImmutable('now'))->format(DATE_ATOM),
            ];
            $ftsCheckpoint['last_batch'] = $pkg['package_id'];
            $ftsCheckpoint['updated_at'] = (new DateTimeImmutable('now'))->format(DATE_ATOM);
            $this->writeCheckpoint($ftsCheckpointPath, $ftsCheckpoint);

            $newly++;
            $this->emit([
                'package_id' => $pkg['package_id'],
                'status' => 'persist_completed',
                'records_read' => $result['records_read'],
                'chunks' => $result['chunks'],
                'counts' => $report->counts,
                'generation' => $report->generation,
                'active_count' => $checkpoint['active_count_after'],
                'fts' => $outcome['fts'],
                'elapsed_ms' => $checkpoint['completed_packages'][$pkg['package_id']]['elapsed_ms'],
            ]);
        }

        $remaining = count($eligible) - $newly;
        $summary = [
            'phase' => 'summary',
            'newly_completed' => $newly,
            'total_completed' => count($checkpoint['completed_packages']),
            'remaining_eligible' => max(0, $remaining),
            'generation' => $store->generation('agris'),
            'active_count' => $this->activeCount(),
            'baseline_generation' => $baseline['generation'],
            'search_pin_generation' => AgrisCandidateIngestCoordinator::SEARCH_PIN_GENERATION,
            'historical_gen76_preserved' => true,
            'fts_pending_batches' => count(array_filter($ftsCheckpoint['batches'] ?? [], static fn ($b) => ! empty($b['pending']))),
        ];

        if ((bool) $this->option('activate-search') && $remaining <= 0 && ($summary['fts_pending_batches'] ?? 0) >= 0) {
            $activation = $coordinator->activateSearchToCurrentStore();
            $summary['activation'] = $activation;
            if (($activation['status'] ?? null) === 'completed') {
                foreach ($ftsCheckpoint['batches'] as $id => $batch) {
                    $ftsCheckpoint['batches'][$id]['pending'] = false;
                    $ftsCheckpoint['batches'][$id]['status'] = 'fts_activated';
                }
                $ftsCheckpoint['activated_at'] = (new DateTimeImmutable('now'))->format(DATE_ATOM);
                $ftsCheckpoint['activated_store_generation'] = $activation['store_generation'];
                $this->writeCheckpoint($ftsCheckpointPath, $ftsCheckpoint);
            }
        }

        $this->emit($summary);

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
            'revision' => '10.23',
            'mode' => 'candidate_incremental',
            'no_download' => true,
            'baseline' => $baseline,
            'completed_packages' => [],
            'partial' => [],
            'failures' => [],
            'created_at' => (new DateTimeImmutable('now'))->format(DATE_ATOM),
        ];
    }

    /** @return array<string, mixed> */
    private function loadFtsCheckpoint(string $path, array $baseline): array
    {
        if (is_file($path)) {
            $decoded = json_decode((string) file_get_contents($path), true);
            if (is_array($decoded)) {
                $decoded['batches'] = is_array($decoded['batches'] ?? null) ? $decoded['batches'] : [];

                return $decoded;
            }
        }

        return [
            'schema_id' => 'wsa.agris_fts_checkpoint.v1',
            'revision' => '10.23',
            'search_pin_generation' => AgrisCandidateIngestCoordinator::SEARCH_PIN_GENERATION,
            'baseline' => $baseline,
            'batches' => [],
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
