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
 * Chunked JSONL incremental ingest must cover a large file without withdrawing peers.
 */
class ChunkedIncrementalBulkIngestTest extends TestCase
{
    use LocalIndexFixtures;

    public function test_chunked_ingest_covers_all_records_and_preserves_prior_peers(): void
    {
        $dir = storage_path('framework/testing/chunked_bulk');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $prior = $dir.'/prior.jsonl';
        $large = $dir.'/large.jsonl';
        file_put_contents($prior, json_encode(['id' => 'legacy-1', 'title' => 'Legacy wheat study', 'url' => 'https://example.test/legacy'])."\n");
        $lines = [];
        for ($i = 1; $i <= 12; $i++) {
            $lines[] = json_encode(['id' => 'n'.$i, 'title' => "Record {$i} irrigation", 'url' => "https://example.test/n{$i}"]);
        }
        file_put_contents($large, implode("\n", $lines)."\n");

        $profile = new BulkDatasetSourceProfile(
            sourceKey: 'agris',
            displayName: 'AGRIS test',
            family: ScientificSourceAccessFamily::BULK_DATASET,
            format: BulkDatasetSourceProfile::FORMAT_JSONL,
            fieldPaths: [
                'identifier' => 'id',
                'title' => 'title',
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

        $now = new DateTimeImmutable('2026-11-14T18:00:00+00:00');
        $priorReport = $ingestion->ingestIncrementalBulk($profile, $prior, $now);
        $this->assertSame('completed', $priorReport->status);

        $chunked = $ingestion->ingestIncrementalBulkChunked($profile, $large, $now->modify('+1 minute'), 5);
        $this->assertSame('completed', $chunked['report']->status);
        $this->assertSame(3, $chunked['chunks']);
        $this->assertSame(12, $chunked['records_read']);
        $this->assertSame(0, $chunked['report']->counts['withdrawn'] ?? 0);

        $active = $store->readActive('agris');
        $ids = array_map(static fn ($rec) => (string) $rec->result->sourceIdentifier, $active['records']);
        sort($ids);
        $expected = array_merge(['legacy-1'], array_map(static fn (int $i) => 'n'.$i, range(1, 12)));
        sort($expected);
        $this->assertSame($expected, $ids);

        @unlink($prior);
        @unlink($large);
    }

    public function test_jsonl_slice_does_not_fail_on_window_limit(): void
    {
        $dir = storage_path('framework/testing/chunked_bulk');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $path = $dir.'/slice.jsonl';
        file_put_contents($path, implode("\n", [
            json_encode(['id' => 'a', 'title' => 'A']),
            json_encode(['id' => 'b', 'title' => 'B']),
            json_encode(['id' => 'c', 'title' => 'C']),
        ])."\n");

        $profile = new BulkDatasetSourceProfile(
            sourceKey: 'agris',
            displayName: 'AGRIS test',
            family: ScientificSourceAccessFamily::BULK_DATASET,
            format: BulkDatasetSourceProfile::FORMAT_JSONL,
            fieldPaths: ['identifier' => 'id', 'title' => 'title'],
            licensing: self::fixtureLicensing(),
            maxRecords: 100,
        );

        $reader = new BulkDatasetReader();
        $slice = $reader->readJsonlSlice($profile, $path, 0, 2);
        $this->assertTrue($slice->isComplete());
        $this->assertCount(2, $slice->records);
        $this->assertNull($slice->failure);

        $slice2 = $reader->readJsonlSlice($profile, $path, 2, 2);
        $this->assertTrue($slice2->isComplete());
        $this->assertCount(1, $slice2->records);

        @unlink($path);
    }
}
