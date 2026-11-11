<?php

namespace App\Console\Commands;

use App\Services\Agriculture\Research\Onboarding\CurrentMembershipNearReadySeatCatalog;
use App\Services\Agriculture\Research\Onboarding\NextGateEligibleSeatCatalog;
use App\Services\Agriculture\Research\Search\Family\Bulk\BulkDatasetSourceProfile;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalIndexIngestionService;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalIndexScientificSourceAdapter;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalIndexSearchEngine;
use App\Services\Agriculture\Research\Search\LocalIndex\ScientificRecordStore;
use DateTimeImmutable;
use Illuminate\Console\Command;

/**
 * Snapshot ingest of a local bulk/static dataset into LocalIndex (ADR-023 §8.40–§8.41).
 *
 * Never downloads. Does not activate seats.
 */
final class IngestScientificBulkSourceCommand extends Command
{
    protected $signature = 'scientific:ingest-bulk
        {source_key : Runtime sourceKey (e.g. agris, dr_duke)}
        {path : Absolute local path to jsonl/csv dataset}
        {--mode=snapshot : snapshot (withdraw absent peers) or incremental (upsert only; safe for chunks)}
        {--probe-query= : Optional LocalIndex search after ingest}';

    protected $description = 'Ingest one bulk/static scientific dataset into LocalIndex (does not activate seats)';

    public function handle(ScientificRecordStore $store, LocalIndexSearchEngine $index, LocalIndexIngestionService $ingestion): int
    {
        $sourceKey = strtolower(trim((string) $this->argument('source_key')));
        $path = (string) $this->argument('path');
        $mode = strtolower(trim((string) $this->option('mode')));
        if (! in_array($mode, ['snapshot', 'incremental'], true)) {
            $this->error("--mode must be snapshot or incremental; got '{$mode}'.");

            return self::FAILURE;
        }
        $profile = $this->resolveBulkProfile($sourceKey);
        if ($profile === null) {
            $this->error("No bulk/static profile for source_key '{$sourceKey}'.");

            return self::FAILURE;
        }

        $this->info("Ingesting '{$sourceKey}' from local path (store=durable mode={$mode})");

        try {
            $report = $mode === 'incremental'
                ? $ingestion->ingestIncrementalBulk($profile, $path, new DateTimeImmutable('now'))
                : $ingestion->ingestSnapshot($profile, $path, new DateTimeImmutable('now'));
        } catch (\Throwable) {
            $this->error('Ingest failed: exception');

            return self::FAILURE;
        }

        $this->line(json_encode([
            'source_key' => $sourceKey,
            'mode' => $mode,
            'status' => $report->status,
            'generation' => $report->generation,
            'counts' => $report->counts,
            'failure' => $report->failure,
            'activated' => false,
            'note' => 'Ingest does not register or activate the seat in ScientificSourceCatalog. incremental never withdraws peers absent from this batch.',
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        $probe = trim((string) $this->option('probe-query'));
        if ($probe !== '') {
            $adapter = new LocalIndexScientificSourceAdapter($profile, $index, $store);
            $outcome = $adapter->search($probe, 5);
            $this->line(json_encode([
                'probe_query_len' => strlen($probe),
                'probe_status' => $outcome->status,
                'probe_error' => $outcome->error,
                'probe_result_count' => count($outcome->results),
                'probe_first_source_key' => $outcome->results[0]->sourceKey ?? null,
                'probe_first_identifier' => isset($outcome->results[0]) ? (string) $outcome->results[0]->sourceIdentifier : null,
                'probe_first_url' => $outcome->results[0]->canonicalUrl ?? null,
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        }

        return $report->status === 'completed' ? self::SUCCESS : self::FAILURE;
    }

    private function resolveBulkProfile(string $sourceKey): ?BulkDatasetSourceProfile
    {
        foreach ([CurrentMembershipNearReadySeatCatalog::all(), NextGateEligibleSeatCatalog::all()] as $seats) {
            foreach ($seats as $seat) {
                if ($seat->sourceKey !== $sourceKey) {
                    continue;
                }
                $profile = $seat->familyProfile;
                if ($profile instanceof BulkDatasetSourceProfile) {
                    return $profile;
                }
            }
        }

        return null;
    }
}
