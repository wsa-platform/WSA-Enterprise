<?php

namespace Tests\Unit\Agriculture\Research\Search\LocalIndex;

use App\Services\Agriculture\Research\Search\Family\Bulk\BulkDatasetSourceProfile;
use App\Services\Agriculture\Research\Search\LocalIndex\InMemoryScientificRecordStore;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalIndexIngestionService;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalIndexScientificSourceAdapter;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalRecordRetentionPolicy;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalScientificIndex;
use App\Services\Agriculture\Research\Search\Family\Bulk\BulkDatasetReader;
use App\Services\Agriculture\Research\Search\Family\OaiPmh\OaiPmhHarvester;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceAccessFamily;
use App\Services\Agriculture\Research\Search\ScientificSourceSearchOutcome;
use DateTimeImmutable;
use Tests\Support\LocalIndexFixtures;
use Tests\TestCase;

/**
 * While AGRIS candidate ingest advances the store, search must remain on the pinned FTS generation.
 */
class AgrisCandidateSearchPinTest extends TestCase
{
    use LocalIndexFixtures;

    public function test_candidate_mode_does_not_rebuild_search_index_to_latest_store_generation(): void
    {
        config([
            'agricultural_intelligence.local_index.agris_candidate_mode' => true,
            'agricultural_intelligence.local_index.agris_search_pin_generation' => 1,
        ]);

        $dir = storage_path('framework/testing/agris_pin');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $pinFile = $dir.'/pin.jsonl';
        $extraFile = $dir.'/extra.jsonl';
        file_put_contents($pinFile, json_encode(['id' => 'pin-1', 'title' => 'Pinned wheat irrigation', 'url' => 'https://example.test/pin'])."\n");
        file_put_contents($extraFile, json_encode(['id' => 'extra-1', 'title' => 'Candidate only maize', 'url' => 'https://example.test/extra'])."\n");

        $profile = new BulkDatasetSourceProfile(
            sourceKey: 'agris',
            displayName: 'AGRIS',
            family: ScientificSourceAccessFamily::BULK_DATASET,
            format: BulkDatasetSourceProfile::FORMAT_JSONL,
            fieldPaths: ['identifier' => 'id', 'title' => 'title', 'url' => 'url'],
            licensing: self::fixtureLicensing(),
            maxRecords: 100,
        );

        $store = new InMemoryScientificRecordStore();
        $index = new LocalScientificIndex();
        $ingestion = new LocalIndexIngestionService(
            $store,
            $index,
            new LocalRecordRetentionPolicy(),
            new OaiPmhHarvester(),
            new BulkDatasetReader(),
        );

        $now = new DateTimeImmutable('2026-11-16T12:00:00+00:00');
        $ingestion->ingestIncrementalBulk($profile, $pinFile, $now);
        $this->assertSame(1, $store->generation('agris'));
        $this->assertSame(1, $index->generation('agris'));

        // Candidate persist advances store without syncing search index (defer).
        $ingestion->ingestIncrementalBulkChunked($profile, $extraFile, $now->modify('+1 minute'), 10, 0, null, true);
        $this->assertSame(2, $store->generation('agris'));
        $this->assertSame(1, $index->generation('agris'), 'FTS remains at pin generation');

        $adapter = new LocalIndexScientificSourceAdapter($profile, $index, $store);
        $outcome = $adapter->search('wheat irrigation', 5);
        $this->assertSame(ScientificSourceSearchOutcome::STATUS_SUCCESS, $outcome->status);
        $this->assertSame('pin-1', (string) $outcome->results[0]->sourceIdentifier);
        $this->assertSame(1, $index->generation('agris'), 'adapter must not rebuild to store gen 2 in candidate mode');

        @unlink($pinFile);
        @unlink($extraFile);
    }
}
