<?php

namespace App\Console\Commands;

use App\Services\Agriculture\Research\Onboarding\CurrentMembershipNearReadySeatCatalog;
use App\Services\Agriculture\Research\Search\Family\Bulk\BulkDatasetReader;
use App\Services\Agriculture\Research\Search\Family\Bulk\BulkDatasetSourceProfile;
use App\Services\Agriculture\Research\Search\Family\Bulk\OsmiaCrossrefSnapshotBuilder;
use App\Services\Agriculture\Research\Search\Family\OaiPmh\OaiPmhHarvester;
use App\Services\Agriculture\Research\Search\LocalIndex\InMemoryScientificRecordStore;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalIndexIngestionService;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalIndexSearchEngine;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalIndexScientificSourceAdapter;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalRecordRetentionPolicy;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalScientificIndex;
use App\Services\Agriculture\Research\Search\LocalIndex\ScientificRecordStore;
use DateTimeImmutable;
use Illuminate\Console\Command;

/**
 * Fetch OSMIA (G3-05) Crossref metadata → JSONL → LocalIndex ingest (bounded).
 * Does not by itself flip Membership; runtime activation is declared in NearReady catalog.
 */
final class HarvestOsmiaCrossrefCommand extends Command
{
    protected $signature = 'scientific:harvest-osmia-crossref
        {--max-records=125 : Maximum CC BY works to retain}
        {--memory : Use in-memory store (no durable write)}
        {--snapshot= : Optional JSONL output path}
        {--reuse-snapshot : Skip Crossref fetch; ingest an existing JSONL snapshot}
        {--probe-query= : Optional LocalIndex probe after ingest}';

    protected $description = 'Harvest OSMIA journal metadata via Crossref into LocalIndex (CC BY 4.0 only)';

    public function handle(ScientificRecordStore $durableStore): int
    {
        $maxRecords = max(1, min(500, (int) $this->option('max-records')));
        $useMemory = (bool) $this->option('memory');
        $snapshot = trim((string) $this->option('snapshot'));
        if ($snapshot === '') {
            $snapshot = storage_path('app/scientific-snapshots/osmia-crossref.jsonl');
        }

        $seat = CurrentMembershipNearReadySeatCatalog::get('G3-05');
        $profile = $seat?->familyProfile;
        if (! $profile instanceof BulkDatasetSourceProfile) {
            $this->error('G3-05 OSMIA BulkDatasetSourceProfile missing from NearReady catalog.');

            return self::FAILURE;
        }

        $reuseSnapshot = is_file($snapshot) && filesize($snapshot) > 0
            && (bool) $this->option('reuse-snapshot');
        if ($reuseSnapshot) {
            $lines = max(0, count(file($snapshot, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: []) );
            $built = [
                'path' => $snapshot,
                'written' => $lines,
                'skipped_license' => 0,
                'skipped_incomplete' => 0,
                'total_reported' => $lines,
                'pages' => 0,
            ];
            $this->info("Reusing snapshot ({$lines} lines): {$snapshot}");
        } else {
            $builder = new OsmiaCrossrefSnapshotBuilder();
            try {
                $built = $builder->writeJsonl($snapshot, $maxRecords);
            } catch (\Throwable) {
                $this->error('Crossref snapshot build failed.');

                return self::FAILURE;
            }
        }

        $store = $useMemory ? new InMemoryScientificRecordStore() : $durableStore;
        // Durable harvest must sync the container FTS engine (SqliteFts), not an ephemeral
        // in-memory LocalScientificIndex that Stage-3 search never sees.
        $index = $useMemory ? new LocalScientificIndex() : app(LocalIndexSearchEngine::class);
        $ingestion = new LocalIndexIngestionService(
            $store,
            $index,
            new LocalRecordRetentionPolicy(),
            new OaiPmhHarvester(),
            new BulkDatasetReader(),
        );

        try {
            $report = $ingestion->ingestSnapshot($profile, $snapshot, new DateTimeImmutable('now'));
            // commit() already synchronizes the bound index; avoid a second full rebuild
            // that can hang on bind-mounted SQLite FTS under Docker Desktop.
        } catch (\Throwable $e) {
            $reason = $e instanceof \App\Services\Agriculture\Research\Search\LocalIndex\LocalStoreFailure
                ? (string) ($e->reason ?? 'local_store_failure')
                : $e::class;
            $this->error('LocalIndex ingest failed: '.$reason);

            return self::FAILURE;
        }

        $payload = [
            'source_key' => OsmiaCrossrefSnapshotBuilder::SOURCE_KEY,
            'adr_id' => 'G3-05',
            'store' => $useMemory ? 'memory' : 'durable',
            'snapshot' => [
                'path' => $built['path'],
                'written' => $built['written'],
                'skipped_license' => $built['skipped_license'],
                'skipped_incomplete' => $built['skipped_incomplete'],
                'total_reported' => $built['total_reported'],
                'pages' => $built['pages'],
            ],
            'ingest' => [
                'status' => $report->status,
                'generation' => $report->generation,
                'counts' => $report->counts,
                'failure' => $report->failure,
            ],
        ];
        $this->line(json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        $probe = trim((string) $this->option('probe-query'));
        if ($probe !== '') {
            $adapter = new LocalIndexScientificSourceAdapter($profile, $index, $store);
            $outcome = $adapter->search($probe, 5);
            $this->line(json_encode([
                'probe_query_len' => strlen($probe),
                'probe_status' => $outcome->status,
                'probe_error' => $outcome->error,
                'probe_result_count' => count($outcome->results),
                'probe_first_title' => $outcome->results[0]->title ?? null,
                'probe_first_identifier' => isset($outcome->results[0]) ? (string) $outcome->results[0]->sourceIdentifier : null,
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        }

        return in_array($report->status, ['completed', 'paused'], true) && $built['written'] > 0
            ? self::SUCCESS
            : self::FAILURE;
    }
}
