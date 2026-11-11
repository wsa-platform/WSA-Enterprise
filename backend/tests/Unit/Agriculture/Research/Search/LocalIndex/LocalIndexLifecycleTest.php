<?php

namespace Tests\Unit\Agriculture\Research\Search\LocalIndex;

use App\Services\Agriculture\Research\Search\Contract\ScientificSourceContractViolation;
use App\Services\Agriculture\Research\Search\Contract\ScientificSourceLicensingDeclaration;
use App\Services\Agriculture\Research\Search\Contract\ScientificSourceUsage;
use App\Services\Agriculture\Research\Search\Contract\ScientificSourceUsageStatus;
use App\Services\Agriculture\Research\Search\Family\Bulk\BulkDatasetReader;
use App\Services\Agriculture\Research\Search\Family\Bulk\BulkDatasetSourceProfile;
use App\Services\Agriculture\Research\Search\Family\OaiPmh\OaiPmhHarvester;
use App\Services\Agriculture\Research\Search\Family\OaiPmh\OaiPmhSourceProfile;
use App\Services\Agriculture\Research\Search\Family\ScientificSourceFamilyRecord;
use App\Services\Agriculture\Research\Search\LocalIndex\HarvestCheckpoint;
use App\Services\Agriculture\Research\Search\LocalIndex\InMemoryScientificRecordStore;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalIndexIngestionService;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalIndexInvariantViolation;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalIngestionReport;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalRecordRetentionPolicy;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalRecordState;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalScientificIndex;
use App\Services\Agriculture\Research\Search\LocalIndex\ScientificRecordStore;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceAccessFamily;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;
use DateTimeImmutable;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * ADR-023 §8.40 (R6): the OAI-PMH and bulk/static storage and indexing lifecycle — licence-gated
 * ingestion, staged generations with atomic promotion, idempotent re-ingestion, tombstones,
 * checkpointed resumable harvesting, snapshot withdrawal and a rebuildable versioned index.
 * Offline only: every harvest response is a fixture.
 */
class LocalIndexLifecycleTest extends TestCase
{
    private const BASE_URL = 'https://repository.example.org/oai';

    private InMemoryScientificRecordStore $store;

    private LocalScientificIndex $index;

    private LocalIndexIngestionService $ingestion;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->store = new InMemoryScientificRecordStore();
        $this->index = new LocalScientificIndex();
        $this->ingestion = new LocalIndexIngestionService($this->store, $this->index, new LocalRecordRetentionPolicy(), new OaiPmhHarvester(), new BulkDatasetReader());
    }

    public function test_a_complete_harvest_stores_indexes_and_tombstones_and_sets_the_high_water_mark(): void
    {
        $this->fakeOai(['oai_pmh_list_records_page.xml', 'oai_pmh_last_page.xml']);

        $report = $this->ingestion->harvestOaiPmh(self::oaiProfile(), self::now());

        $this->assertSame(LocalIngestionReport::COMPLETED, $report->status);
        $this->assertSame(2, $report->pages);
        $this->assertSame(['inserted' => 3, 'updated' => 0, 'unchanged' => 0, 'deleted' => 1, 'withdrawn' => 0, 'stale' => 0], $report->counts);
        $this->assertSame(HarvestCheckpoint::COMPLETE, $report->checkpoint?->state);
        $this->assertSame('2026-10-15T08:00:00Z', $report->checkpoint->highWaterMark);
        $this->assertSame(2, $this->store->generation('repository_example'));

        $deleted = $this->store->get('repository_example', 'oai:repository.example.org:102');
        $this->assertSame(LocalRecordState::DELETED, $deleted?->state);
        $this->assertSame('oai_pmh_deleted_header', $deleted->tombstoneReason);
        $this->assertNull($deleted->result);

        $stored = $this->store->get('repository_example', 'oai:repository.example.org:101');
        $this->assertSame('2026-03-02', $stored?->datestamp);
        $this->assertNull($stored->result?->abstract);
        $this->assertNull($stored->result->rawMetadata);
        $this->assertContains('abstract_not_retained_licence', $stored->result->primaryProvenance()?->transformations);
        $this->assertSame(OaiPmhHarvester::class, $stored->provenance['adapter']);

        $this->assertSame(['repository_example|oai:repository.example.org:101'], $this->keys('barley irrigation'));
        $this->assertSame(['repository_example|oai:repository.example.org:105'], $this->keys('wheat rust'));
        $this->assertSame([], $this->keys('barley wheat'));
        $this->assertSame([], $this->keys('field trials'), 'an abstract that may not be stored is not indexed');
    }

    public function test_an_incremental_run_starts_from_the_high_water_mark_at_the_repository_granularity(): void
    {
        $this->fakeOai(['oai_pmh_list_records_page.xml', 'oai_pmh_last_page.xml', 'oai_pmh_no_records_match.xml']);
        $this->ingestion->harvestOaiPmh(self::oaiProfile(), self::now());

        $report = $this->ingestion->harvestOaiPmh(self::oaiProfile(), self::now());

        $this->assertSame(LocalIngestionReport::COMPLETED, $report->status);
        $this->assertSame(0, array_sum($report->counts));
        $last = Http::recorded()->last()[0];
        $this->assertSame('2026-10-15', self::query($last)['from'] ?? null);
        $this->assertArrayNotHasKey('resumptionToken', self::query($last));
    }

    public function test_a_failed_page_keeps_committed_pages_and_the_next_run_resumes_at_the_token(): void
    {
        $this->fakeOai(['oai_pmh_list_records_page.xml', 500, 'oai_pmh_last_page.xml']);

        $failed = $this->ingestion->harvestOaiPmh(self::oaiProfile(), self::now());

        $this->assertSame(LocalIngestionReport::FAILED, $failed->status);
        $this->assertSame('http_error', $failed->failure);
        $this->assertSame(HarvestCheckpoint::IN_PROGRESS, $failed->checkpoint?->state);
        $this->assertSame('agri-2026-page-2', $failed->checkpoint->resumptionToken);
        $this->assertNull($failed->checkpoint->highWaterMark);
        $this->assertNotNull($this->store->get('repository_example', 'oai:repository.example.org:101'));

        $resumed = $this->ingestion->harvestOaiPmh(self::oaiProfile(), self::now());

        $this->assertSame(LocalIngestionReport::COMPLETED, $resumed->status);
        $this->assertSame('agri-2026-page-2', self::query(Http::recorded()->last()[0])['resumptionToken'] ?? null);
        $this->assertSame('2026-10-15T08:00:00Z', $resumed->checkpoint?->highWaterMark);
        $this->assertSame(['inserted' => 1, 'updated' => 0, 'unchanged' => 0, 'deleted' => 0, 'withdrawn' => 0, 'stale' => 0], $resumed->counts);
    }

    public function test_a_rejected_resumption_token_restarts_the_window_once_and_re_harvesting_is_idempotent(): void
    {
        $this->fakeOai(['oai_pmh_list_records_page.xml', 'oai_pmh_bad_resumption_token.xml', 'oai_pmh_list_records_page.xml', 'oai_pmh_last_page.xml']);
        $paused = $this->ingestion->harvestOaiPmh(self::oaiProfile(), self::now(), maxPages: 1);
        $this->assertSame(LocalIngestionReport::PAUSED, $paused->status);

        $report = $this->ingestion->harvestOaiPmh(self::oaiProfile(), self::now());

        $this->assertSame(LocalIngestionReport::COMPLETED, $report->status);
        $this->assertTrue($report->restartedWindow);
        $this->assertSame(['inserted' => 1, 'updated' => 0, 'unchanged' => 3, 'deleted' => 0, 'withdrawn' => 0, 'stale' => 0], $report->counts);
        $requests = Http::recorded()->map(static fn (array $pair): array => self::query($pair[0]))->all();
        $this->assertSame('agri-2026-page-2', $requests[1]['resumptionToken'] ?? null);
        $this->assertArrayNotHasKey('resumptionToken', $requests[2]);
        $this->assertSame(3, $this->index->documentCount('repository_example'));
    }

    public function test_unknown_licence_terms_refuse_ingestion_before_any_request_or_read(): void
    {
        $oai = $this->ingestion->harvestOaiPmh(self::oaiProfile(ScientificSourceLicensingDeclaration::unresolved()), self::now());
        $bulk = $this->ingestion->ingestSnapshot(self::bulkProfile(licensing: ScientificSourceLicensingDeclaration::unresolved()), '/nonexistent/dataset.jsonl', self::now());
        $storageOnlyRefused = $this->ingestion->ingestSnapshot(self::bulkProfile(licensing: self::licensing(storage: ScientificSourceUsageStatus::CONDITIONAL)), '/nonexistent/dataset.jsonl', self::now());

        $this->assertSame([LocalIngestionReport::LICENCE_REFUSED, LocalRecordRetentionPolicy::REFUSED_RETRIEVAL], [$oai->status, $oai->failure]);
        $this->assertSame([LocalIngestionReport::LICENCE_REFUSED, LocalRecordRetentionPolicy::REFUSED_RETRIEVAL], [$bulk->status, $bulk->failure]);
        $this->assertSame([LocalIngestionReport::LICENCE_REFUSED, LocalRecordRetentionPolicy::REFUSED_STORAGE], [$storageOnlyRefused->status, $storageOnlyRefused->failure]);
        Http::assertNothingSent();
        $this->assertSame(0, $this->store->generation('repository_example'));
    }

    public function test_a_complete_snapshot_replaces_the_dataset_and_withdraws_absent_records(): void
    {
        $first = $this->ingestion->ingestSnapshot(self::bulkProfile(), self::fixturePath('bulk_dataset_snapshot_v1.jsonl'), self::now());

        $this->assertSame(LocalIngestionReport::COMPLETED, $first->status);
        $this->assertSame(['inserted' => 2, 'updated' => 0, 'unchanged' => 0, 'deleted' => 0, 'withdrawn' => 1, 'stale' => 0], $first->counts);
        $this->assertSame('flagged_withdrawn_in_dataset', $this->store->get('dataset_example', 'R-3')?->tombstoneReason);
        $this->assertSame('Barley yield under deficit irrigation', $this->store->get('dataset_example', 'R-1')?->result?->title);

        $second = $this->ingestion->ingestSnapshot(self::bulkProfile(), self::fixturePath('bulk_dataset_snapshot_v2.jsonl'), self::now());

        $this->assertSame(['inserted' => 1, 'updated' => 1, 'unchanged' => 0, 'deleted' => 0, 'withdrawn' => 1, 'stale' => 0], $second->counts);
        $this->assertSame('absent_from_complete_snapshot', $this->store->get('dataset_example', 'R-2')?->tombstoneReason);
        $this->assertSame(LocalRecordState::WITHDRAWN, $this->store->get('dataset_example', 'R-3')?->state);
        $this->assertSame([], $this->keys('soil moisture', 'dataset_example'));
        $this->assertSame(['dataset_example|R-1'], $this->keys('barley revised', 'dataset_example'));

        $again = $this->ingestion->ingestSnapshot(self::bulkProfile(), self::fixturePath('bulk_dataset_snapshot_v2.jsonl'), self::now());
        $this->assertSame(['inserted' => 0, 'updated' => 0, 'unchanged' => 2, 'deleted' => 0, 'withdrawn' => 0, 'stale' => 0], $again->counts);
    }

    public function test_an_incomplete_snapshot_leaves_the_active_generation_unchanged(): void
    {
        $this->ingestion->ingestSnapshot(self::bulkProfile(), self::fixturePath('bulk_dataset_snapshot_v1.jsonl'), self::now());
        $before = $this->store->records('dataset_example');

        $malformed = $this->ingestion->ingestSnapshot(self::bulkProfile(), self::fixturePath('bulk_dataset_malformed.jsonl'), self::now());
        $tooLong = $this->ingestion->ingestSnapshot(self::bulkProfile(maxLineBytes: 64), self::fixturePath('bulk_dataset_snapshot_v1.jsonl'), self::now());
        $tooMany = $this->ingestion->ingestSnapshot(self::bulkProfile(maxRecords: 1), self::fixturePath('bulk_dataset_snapshot_v2.jsonl'), self::now());
        $missing = $this->ingestion->ingestSnapshot(self::bulkProfile(), self::fixturePath('missing.jsonl'), self::now());

        $this->assertSame(
            [['snapshot_rejected', 'malformed_lines'], ['snapshot_rejected', 'line_too_long'], ['snapshot_rejected', 'max_records_exceeded'], ['snapshot_rejected', 'file_unreadable']],
            array_map(static fn (LocalIngestionReport $r): array => [$r->status, $r->failure], [$malformed, $tooLong, $tooMany, $missing]),
        );
        $this->assertEquals($before, $this->store->records('dataset_example'));
        $this->assertSame(1, $this->store->generation('dataset_example'));
    }

    public function test_a_dataset_is_read_from_local_storage_only(): void
    {
        foreach (['https://dataset.example.org/dump.jsonl', 'phar:///tmp/archive.phar/data.jsonl', 'php://input'] as $path) {
            try {
                (new BulkDatasetReader())->read(self::bulkProfile(), $path);
                $this->fail("'{$path}' was not refused.");
            } catch (ScientificSourceContractViolation) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_a_csv_dataset_maps_columns_and_splits_authors(): void
    {
        $profile = new BulkDatasetSourceProfile(
            sourceKey: 'csv_example',
            displayName: 'CSV dataset (fixture)',
            family: ScientificSourceAccessFamily::STATIC_DATASET,
            format: BulkDatasetSourceProfile::FORMAT_CSV,
            fieldPaths: ['identifier' => 'id', 'title' => 'title', 'doi' => 'doi', 'authors' => 'authors', 'year' => 'year'],
            authorSeparator: ';',
            licensing: self::licensing(),
        );

        $snapshot = (new BulkDatasetReader())->read($profile, self::fixturePath('bulk_dataset.csv'), '2026-10-15T09:00:00+00:00');

        $this->assertTrue($snapshot->isComplete());
        $this->assertSame(64, strlen((string) $snapshot->contentSha256));
        $this->assertCount(2, $snapshot->records);
        $first = $snapshot->records[0];
        $this->assertSame('Rice blast resistance, a field survey', $first->title);
        $this->assertSame(['Researcher, Ada', 'Scientist, Ben'], $first->authors);
        $this->assertSame('10.12345/csv.1', $first->doi);
        $this->assertSame(2023, $first->publicationYear);
        $this->assertSame('static_dataset', $first->primaryProvenance()?->accessFamily);
        $this->assertSame('id', $first->primaryProvenance()->originalIdentifierField);
        $this->assertNull($snapshot->records[1]->doi);
    }

    public function test_staged_changes_are_invisible_until_commit_and_a_discard_leaves_nothing(): void
    {
        $generation = $this->store->begin('dataset_example', ScientificRecordStore::MODE_INCREMENTAL);
        $this->store->stageUpsert($generation, self::record('R-9', 'Sorghum grain quality'), '2026-01-01');

        $this->assertNull($this->store->get('dataset_example', 'R-9'));
        $this->assertSame(0, $this->store->generation('dataset_example'));
        $this->expectExceptionObject(new LocalIndexInvariantViolation("Source 'dataset_example' already has an open generation."));
        try {
            $this->store->begin('dataset_example', ScientificRecordStore::MODE_SNAPSHOT);
        } finally {
            $this->store->discard($generation);
            $this->assertNull($this->store->get('dataset_example', 'R-9'));
            $this->assertSame([], $this->store->records('dataset_example'));
        }
    }

    public function test_an_older_version_never_overwrites_a_newer_one(): void
    {
        $generation = $this->store->begin('dataset_example', ScientificRecordStore::MODE_INCREMENTAL);
        $this->store->stageUpsert($generation, self::record('R-9', 'Sorghum grain quality, 2026 edition'), '2026-05-01');
        $this->store->commit($generation, self::now());

        $generation = $this->store->begin('dataset_example', ScientificRecordStore::MODE_INCREMENTAL);
        $this->store->stageUpsert($generation, self::record('R-9', 'Sorghum grain quality, 2025 edition'), '2026-01-01');
        $this->store->stageTombstone($generation, 'R-10', LocalRecordState::DELETED, 'oai_pmh_deleted_header', '2026-02-01');
        $commit = $this->store->commit($generation, self::now());

        $this->assertSame(1, $commit->stale);
        $this->assertSame('Sorghum grain quality, 2026 edition', $this->store->get('dataset_example', 'R-9')?->result?->title);
        $this->assertSame(LocalRecordState::DELETED, $this->store->get('dataset_example', 'R-10')?->state);

        $generation = $this->store->begin('dataset_example', ScientificRecordStore::MODE_INCREMENTAL);
        $this->store->stageUpsert($generation, self::record('R-10', 'Resurrected by an older copy'), '2026-01-15');
        $this->assertSame(1, $this->store->commit($generation, self::now())->stale);
        $this->assertSame(LocalRecordState::DELETED, $this->store->get('dataset_example', 'R-10')?->state);
    }

    public function test_a_staged_record_must_belong_to_the_source_and_carry_its_identifier(): void
    {
        $generation = $this->store->begin('other_source', ScientificRecordStore::MODE_INCREMENTAL);

        $this->expectException(LocalIndexInvariantViolation::class);
        $this->store->stageUpsert($generation, self::record('R-1', 'Barley'));
    }

    public function test_the_retention_policy_keeps_only_what_the_licence_permits(): void
    {
        $policy = new LocalRecordRetentionPolicy();
        $withText = self::record('R-1', 'Barley yield', 'An abstract.', self::licensing(fullText: ScientificSourceUsageStatus::PERMITTED));
        $withoutText = self::record('R-1', 'Barley yield', 'An abstract.', self::licensing());
        $conditional = self::record('R-1', 'Barley yield', null, self::licensing(retrieval: ScientificSourceUsageStatus::CONDITIONAL));

        $this->assertSame('An abstract.', $policy->retain($withText)['record']?->abstract);
        $this->assertNotNull($policy->retain($withText)['record']?->rawMetadata);
        $stripped = $policy->retain($withoutText)['record'];
        $this->assertNull($stripped?->abstract);
        $this->assertNull($stripped->rawMetadata);
        $this->assertSame(['abstract_not_retained_licence', 'raw_metadata_not_retained_licence'], $stripped->primaryProvenance()?->transformations);
        $this->assertSame(['record' => null, 'refusal' => LocalRecordRetentionPolicy::REFUSED_RETRIEVAL], $policy->retain($conditional));
    }

    public function test_the_index_is_versioned_rebuildable_and_unicode_aware(): void
    {
        $this->ingestion->ingestSnapshot(self::bulkProfile(licensing: self::licensing(fullText: ScientificSourceUsageStatus::PERMITTED)), self::fixturePath('bulk_dataset_snapshot_v1.jsonl'), self::now());
        $generation = $this->store->begin('dataset_example', ScientificRecordStore::MODE_INCREMENTAL);
        $this->store->stageUpsert($generation, self::record('R-20', 'إنتاجية القمح تحت الري الناقص'), null);
        $this->index->synchronize($this->store->commit($generation, self::now()));
        $incremental = [$this->keys('barley', 'dataset_example'), $this->keys('field trials', 'dataset_example'), $this->keys('القمح', 'dataset_example')];

        $rebuilt = new LocalScientificIndex();
        $rebuilt->rebuild('dataset_example', $this->store->records('dataset_example'), $this->store->generation('dataset_example'));

        $this->assertSame('local_scientific_index.v1', $rebuilt->version());
        $this->assertSame([['dataset_example|R-1'], ['dataset_example|R-1'], ['dataset_example|R-20']], $incremental);
        foreach (['barley', 'field trials', 'القمح', 'retracted'] as $query) {
            $this->assertSame(
                array_map(static fn (array $hit): string => $hit['record']->recordKey(), $this->index->search('dataset_example', $query, 10)),
                array_map(static fn (array $hit): string => $hit['record']->recordKey(), $rebuilt->search('dataset_example', $query, 10)),
                $query,
            );
        }
        $this->assertSame($this->index->generation('dataset_example'), $rebuilt->generation('dataset_example'));
        $this->assertSame([], $this->keys('the of and', 'dataset_example'));
    }

    /** @return list<string> */
    private function keys(string $query, string $source = 'repository_example'): array
    {
        return array_map(static fn (array $hit): string => $hit['record']->recordKey(), $this->index->search($source, $query, 10));
    }

    /** @param  list<string|int>  $responses  fixture names or HTTP status codes */
    private function fakeOai(array $responses): void
    {
        $sequence = Http::sequence();
        foreach ($responses as $response) {
            is_int($response)
                ? $sequence->push('', $response)
                : $sequence->push((string) file_get_contents(self::fixturePath($response)), 200, ['Content-Type' => 'text/xml']);
        }
        Http::fake([self::BASE_URL.'*' => $sequence]);
    }

    /** @return array<string, mixed> */
    private static function query(Request $request): array
    {
        parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

        return $query;
    }

    private static function licensing(
        ScientificSourceUsageStatus $retrieval = ScientificSourceUsageStatus::PERMITTED,
        ScientificSourceUsageStatus $storage = ScientificSourceUsageStatus::PERMITTED,
        ScientificSourceUsageStatus $fullText = ScientificSourceUsageStatus::UNRESOLVED,
    ): ScientificSourceLicensingDeclaration {
        return new ScientificSourceLicensingDeclaration(
            [
                ScientificSourceUsage::RETRIEVAL->value => $retrieval,
                ScientificSourceUsage::METADATA_STORAGE->value => $storage,
                ScientificSourceUsage::FULL_TEXT_STORAGE->value => $fullText,
            ],
            termsReference: 'https://repository.example.org/terms (fixture)',
            evidenceReference: 'test fixture: terms accepted for this test only',
        );
    }

    private static function oaiProfile(?ScientificSourceLicensingDeclaration $licensing = null): OaiPmhSourceProfile
    {
        return new OaiPmhSourceProfile('repository_example', 'Example repository (fixture)', self::BASE_URL, 'agri', $licensing ?? self::licensing());
    }

    private static function bulkProfile(?ScientificSourceLicensingDeclaration $licensing = null, int $maxLineBytes = 65_536, int $maxRecords = 100): BulkDatasetSourceProfile
    {
        return new BulkDatasetSourceProfile(
            sourceKey: 'dataset_example',
            displayName: 'Example dataset (fixture)',
            family: ScientificSourceAccessFamily::BULK_DATASET,
            format: BulkDatasetSourceProfile::FORMAT_JSONL,
            fieldPaths: [
                'identifier' => 'record_id',
                'title' => 'title',
                'abstract' => 'summary',
                'doi' => 'doi',
                'url' => 'landing',
                'published' => 'issued',
                'authors' => 'creators',
                'journal' => 'venue',
                'withdrawn' => 'withdrawn',
            ],
            licensing: $licensing ?? self::licensing(),
            maxRecords: $maxRecords,
            maxLineBytes: $maxLineBytes,
        );
    }

    private static function record(string $identifier, string $title, ?string $abstract = null, ?ScientificSourceLicensingDeclaration $licensing = null): ScientificSearchResult
    {
        return ScientificSourceFamilyRecord::toResult(
            profile: self::bulkProfile(licensing: $licensing),
            rawTitle: $title,
            originalIdentifier: $identifier,
            originalIdentifierField: 'record_id',
            rawDoi: null,
            rawUrl: null,
            rawAbstract: $abstract,
            publishedAt: null,
            year: null,
            authors: [],
            journal: null,
            raw: ['record_id' => $identifier, 'title' => $title],
        );
    }

    private static function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-10-15T09:00:00+00:00');
    }

    private static function fixturePath(string $name): string
    {
        return base_path('tests/Fixtures/ScientificSourceFamilies/'.$name);
    }
}
