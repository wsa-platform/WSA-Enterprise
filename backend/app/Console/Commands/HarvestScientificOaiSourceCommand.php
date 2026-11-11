<?php

namespace App\Console\Commands;

use App\Services\Agriculture\Research\Onboarding\CurrentMembershipNearReadySeatCatalog;
use App\Services\Agriculture\Research\Onboarding\NextGateEligibleSeatCatalog;
use App\Services\Agriculture\Research\Search\Family\Bulk\BulkDatasetReader;
use App\Services\Agriculture\Research\Search\Family\OaiPmh\OaiPmhHarvester;
use App\Services\Agriculture\Research\Search\Family\OaiPmh\OaiPmhSourceProfile;
use App\Services\Agriculture\Research\Search\LocalIndex\InMemoryScientificRecordStore;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalIndexIngestionService;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalIndexScientificSourceAdapter;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalRecordRetentionPolicy;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalScientificIndex;
use App\Services\Agriculture\Research\Search\LocalIndex\ScientificRecordStore;
use DateTimeImmutable;
use Illuminate\Console\Command;

/**
 * Bounded OAI-PMH harvest into the LocalIndex store (ADR-023 §8.40–§8.41).
 *
 * Does not activate seats. --memory keeps durable DatabaseScientificRecordStore untouched
 * (verification / environments without DB). Default uses the bound ScientificRecordStore.
 */
final class HarvestScientificOaiSourceCommand extends Command
{
    protected $signature = 'scientific:harvest-oai
        {source_key : Runtime sourceKey (e.g. organic_eprints)}
        {--max-pages=1 : Maximum ListRecords pages this run (loop protection)}
        {--memory : Use in-memory store (no durable DB write)}
        {--probe-query= : Optional LocalIndex search after harvest to prove records are searchable}';

    protected $description = 'Harvest one OAI-PMH scientific source into LocalIndex (bounded; does not activate seats)';

    public function handle(ScientificRecordStore $durableStore): int
    {
        $sourceKey = strtolower(trim((string) $this->argument('source_key')));
        $maxPages = max(1, min(50, (int) $this->option('max-pages')));
        $useMemory = (bool) $this->option('memory');

        $profile = $this->resolveOaiProfile($sourceKey);
        if ($profile === null) {
            $this->error("No OAI-PMH profile for source_key '{$sourceKey}'.");

            return self::FAILURE;
        }

        $store = $useMemory ? new InMemoryScientificRecordStore() : $durableStore;
        $index = new LocalScientificIndex();
        $ingestion = new LocalIndexIngestionService(
            $store,
            $index,
            new LocalRecordRetentionPolicy(),
            new OaiPmhHarvester(),
            new BulkDatasetReader(),
        );

        $this->info("Harvesting '{$sourceKey}' max_pages={$maxPages} store=".($useMemory ? 'memory' : 'durable'));

        try {
            $report = $ingestion->harvestOaiPmh($profile, new DateTimeImmutable('now'), $maxPages);
        } catch (\Throwable $e) {
            $this->error('Harvest failed: '.$e::class);
            // Never echo exception messages (may contain URLs with tokens).

            return self::FAILURE;
        }

        $payload = [
            'source_key' => $sourceKey,
            'store' => $useMemory ? 'memory' : 'durable',
            'max_pages' => $maxPages,
            'status' => $report->status,
            'pages' => $report->pages,
            'generation' => $report->generation,
            'counts' => $report->counts,
            'failure' => $report->failure,
            'checkpoint_state' => $report->checkpoint?->state,
            'activated' => false,
            'note' => 'Harvest does not register or activate the seat in ScientificSourceCatalog.',
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
                'probe_first_source_key' => $outcome->results[0]->sourceKey ?? null,
                'probe_first_identifier' => isset($outcome->results[0]) ? (string) $outcome->results[0]->sourceIdentifier : null,
                'probe_first_url' => $outcome->results[0]->canonicalUrl ?? null,
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        }

        return in_array($report->status, ['completed', 'paused'], true) ? self::SUCCESS : self::FAILURE;
    }

    private function resolveOaiProfile(string $sourceKey): ?OaiPmhSourceProfile
    {
        foreach ([CurrentMembershipNearReadySeatCatalog::all(), NextGateEligibleSeatCatalog::all()] as $seats) {
            foreach ($seats as $seat) {
                if ($seat->sourceKey !== $sourceKey) {
                    continue;
                }
                $profile = $seat->familyProfile;
                if ($profile instanceof OaiPmhSourceProfile) {
                    return $profile;
                }
            }
        }

        return null;
    }
}
