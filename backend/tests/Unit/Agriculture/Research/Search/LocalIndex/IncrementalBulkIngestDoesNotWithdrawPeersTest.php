<?php

namespace Tests\Unit\Agriculture\Research\Search\LocalIndex;

use App\Services\Agriculture\Research\Search\Family\Bulk\BulkDatasetReader;
use App\Services\Agriculture\Research\Search\Family\Bulk\BulkDatasetSourceProfile;
use App\Services\Agriculture\Research\Search\Family\OaiPmh\OaiPmhHarvester;
use App\Services\Agriculture\Research\Search\LocalIndex\InMemoryScientificRecordStore;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalIndexIngestionService;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalRecordRetentionPolicy;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalScientificIndex;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceAccessFamily;
use DateTimeImmutable;
use Tests\Support\LocalIndexFixtures;
use Tests\TestCase;

/**
 * Chunked incremental ingest must not withdraw peers absent from the current batch.
 */
class IncrementalBulkIngestDoesNotWithdrawPeersTest extends TestCase
{
    use LocalIndexFixtures;

    public function test_incremental_second_chunk_preserves_first_chunk_records(): void
    {
        $dir = storage_path('framework/testing/incremental_bulk');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $chunk1 = $dir.'/chunk1.jsonl';
        $chunk2 = $dir.'/chunk2.jsonl';
        file_put_contents($chunk1, json_encode(['id' => 'a1', 'title' => 'Alpha Mentha leaf', 'abstract' => 'a', 'url' => 'https://example.test/a1'])."\n");
        file_put_contents($chunk2, json_encode(['id' => 'b2', 'title' => 'Beta wheat grain', 'abstract' => 'b', 'url' => 'https://example.test/b2'])."\n");

        $profile = new BulkDatasetSourceProfile(
            sourceKey: 'dr_duke',
            displayName: 'Dr. Duke test',
            family: ScientificSourceAccessFamily::STATIC_DATASET,
            format: BulkDatasetSourceProfile::FORMAT_JSONL,
            fieldPaths: [
                'identifier' => 'id',
                'title' => 'title',
                'abstract' => 'abstract',
                'url' => 'url',
            ],
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

        $now = new DateTimeImmutable('2026-10-18T18:00:00+00:00');
        $r1 = $ingestion->ingestIncrementalBulk($profile, $chunk1, $now);
        $this->assertSame('completed', $r1->status);
        $this->assertSame(1, $r1->counts['inserted'] ?? 0);

        $r2 = $ingestion->ingestIncrementalBulk($profile, $chunk2, $now->modify('+1 minute'));
        $this->assertSame('completed', $r2->status);
        $this->assertSame(1, $r2->counts['inserted'] ?? 0);
        $this->assertSame(0, $r2->counts['withdrawn'] ?? 0);

        $active = $store->readActive('dr_duke');
        $ids = array_map(static fn ($rec) => (string) $rec->result->sourceIdentifier, $active['records']);
        sort($ids);
        $this->assertSame(['a1', 'b2'], $ids);

        @unlink($chunk1);
        @unlink($chunk2);
    }
}
