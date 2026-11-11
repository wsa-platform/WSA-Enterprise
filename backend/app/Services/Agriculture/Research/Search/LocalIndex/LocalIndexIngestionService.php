<?php

namespace App\Services\Agriculture\Research\Search\LocalIndex;

use App\Services\Agriculture\Research\Search\Family\Bulk\BulkDatasetReader;
use App\Services\Agriculture\Research\Search\Family\Bulk\BulkDatasetSourceProfile;
use App\Services\Agriculture\Research\Search\Family\OaiPmh\OaiPmhHarvester;
use App\Services\Agriculture\Research\Search\Family\OaiPmh\OaiPmhSourceProfile;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;
use DateTimeImmutable;

/**
 * Moves harvested and bulk-loaded records into the local store and index (ADR-023 §8.40).
 *
 * OAI-PMH: each page is committed with its checkpoint, so an interrupted harvest resumes at the
 * next page; a rejected resumption token restarts the window once, and re-harvested records are
 * idempotent. The high-water mark advances only when a list completes.
 * Datasets: one complete snapshot replaces the source's records in one generation; an
 * incomplete read changes nothing.
 * The licence is checked before any request or read.
 */
final class LocalIndexIngestionService
{
    public const BAD_RESUMPTION_TOKEN = 'badResumptionToken';

    private const COUNTS = ['inserted', 'updated', 'unchanged', 'deleted', 'withdrawn', 'stale'];

    public function __construct(
        private readonly ScientificRecordStore $store,
        private readonly LocalIndexSearchEngine $index,
        private readonly LocalRecordRetentionPolicy $policy,
        private readonly OaiPmhHarvester $harvester,
        private readonly BulkDatasetReader $reader,
    ) {}

    public function harvestOaiPmh(OaiPmhSourceProfile $profile, DateTimeImmutable $now, int $maxPages = 100): LocalIngestionReport
    {
        if ($maxPages < 1) {
            throw new LocalIndexInvariantViolation('A harvest needs a page budget of at least 1.');
        }
        $key = $profile->sourceKey;
        $previous = $this->store->checkpoint($key);
        $refusal = $this->policy->sourceRefusal($profile->licensing);
        if ($refusal !== null) {
            return new LocalIngestionReport($key, LocalIngestionReport::LICENCE_REFUSED, generation: $this->store->generation($key), failure: $refusal, checkpoint: $previous);
        }

        $resuming = $previous?->state === HarvestCheckpoint::IN_PROGRESS;
        $token = $resuming ? $previous->resumptionToken : null;
        $from = $resuming ? $previous->windowFrom : self::fromHighWaterMark($previous?->highWaterMark, $profile->datestampGranularity);
        $startedAt = $resuming ? $previous->harvestStartedAt : null;
        $highWaterMark = $previous?->highWaterMark;
        $pagesCommitted = $resuming ? $previous->pagesCommitted : 0;
        $counts = array_fill_keys(self::COUNTS, 0);
        $refusals = [];
        $restarted = false;
        $pages = 0;

        while ($pages < $maxPages) {
            $page = $this->harvester->listRecords($profile, $token === null ? $from : null, null, $token);
            $pages++;
            if ($page->status === $page::STATUS_FAILED) {
                if ($token !== null && ! $restarted && in_array(self::BAD_RESUMPTION_TOKEN, $page->errorCodes, true)) {
                    $restarted = true;
                    $token = null;

                    continue;
                }

                return $this->report($key, LocalIngestionReport::FAILED, $pages, $counts, $refusals, $page->error ?? 'harvest_failed', $restarted);
            }

            $startedAt ??= $page->responseDate ?? $now->format('Y-m-d\TH:i:s\Z');
            $done = $page->resumptionToken === null;
            $checkpoint = new HarvestCheckpoint(
                sourceKey: $key,
                state: $done ? HarvestCheckpoint::COMPLETE : HarvestCheckpoint::IN_PROGRESS,
                resumptionToken: $page->resumptionToken,
                windowFrom: $done ? null : $from,
                harvestStartedAt: $done ? null : $startedAt,
                highWaterMark: $done ? $startedAt : $highWaterMark,
                pagesCommitted: $pagesCommitted + 1,
                updatedAt: $now->format(DATE_ATOM),
            );
            $commit = $this->commit($key, ScientificRecordStore::MODE_INCREMENTAL, $page->records, $page->deletedIdentifiers, LocalRecordState::DELETED, 'oai_pmh_deleted_header', $now, $checkpoint, $refusals);
            $pagesCommitted++;
            foreach (self::COUNTS as $count) {
                $counts[$count] += $commit->toArray()[$count];
            }
            if ($done) {
                return $this->report($key, LocalIngestionReport::COMPLETED, $pages, $counts, $refusals, null, $restarted);
            }
            $token = $page->resumptionToken;
        }

        return $this->report($key, LocalIngestionReport::PAUSED, $pages, $counts, $refusals, null, $restarted);
    }

    public function ingestSnapshot(BulkDatasetSourceProfile $profile, string $path, DateTimeImmutable $now): LocalIngestionReport
    {
        $key = $profile->sourceKey;
        $refusal = $this->policy->sourceRefusal($profile->licensing);
        if ($refusal !== null) {
            return new LocalIngestionReport($key, LocalIngestionReport::LICENCE_REFUSED, generation: $this->store->generation($key), failure: $refusal);
        }
        $snapshot = $this->reader->read($profile, $path, $now->format(DATE_ATOM));
        if (! $snapshot->isComplete()) {
            return new LocalIngestionReport($key, LocalIngestionReport::SNAPSHOT_REJECTED, generation: $this->store->generation($key), failure: $snapshot->failure ?? $snapshot->status);
        }

        $refusals = [];
        $commit = $this->commit($key, ScientificRecordStore::MODE_SNAPSHOT, $snapshot->records, $snapshot->withdrawnIdentifiers, LocalRecordState::WITHDRAWN, 'flagged_withdrawn_in_dataset', $now, null, $refusals);
        $counts = array_intersect_key($commit->toArray(), array_flip(self::COUNTS));

        return new LocalIngestionReport($key, LocalIngestionReport::COMPLETED, 1, $commit->generation, $counts, $refusals);
    }

    /**
     * Incremental bulk upsert: inserts/updates records from a local file without withdrawing
     * active peers absent from this batch. Safe for chunked full-corpus indexing.
     * Explicit withdrawn flags in the file are still applied as WITHDRAWN tombstones.
     */
    public function ingestIncrementalBulk(BulkDatasetSourceProfile $profile, string $path, DateTimeImmutable $now): LocalIngestionReport
    {
        $key = $profile->sourceKey;
        $refusal = $this->policy->sourceRefusal($profile->licensing);
        if ($refusal !== null) {
            return new LocalIngestionReport($key, LocalIngestionReport::LICENCE_REFUSED, generation: $this->store->generation($key), failure: $refusal);
        }
        $snapshot = $this->reader->read($profile, $path, $now->format(DATE_ATOM));
        if (! $snapshot->isComplete()) {
            return new LocalIngestionReport($key, LocalIngestionReport::SNAPSHOT_REJECTED, generation: $this->store->generation($key), failure: $snapshot->failure ?? $snapshot->status);
        }

        $refusals = [];
        $commit = $this->commit(
            $key,
            ScientificRecordStore::MODE_INCREMENTAL,
            $snapshot->records,
            $snapshot->withdrawnIdentifiers,
            LocalRecordState::WITHDRAWN,
            'flagged_withdrawn_in_dataset',
            $now,
            null,
            $refusals,
        );
        $counts = array_intersect_key($commit->toArray(), array_flip(self::COUNTS));

        return new LocalIngestionReport($key, LocalIngestionReport::COMPLETED, 1, $commit->generation, $counts, $refusals);
    }

    /** Rebuilds the source's index from the store, e.g. after an index version change or a restart. */
    public function rebuildIndex(string $sourceKey): void
    {
        ['generation' => $generation, 'records' => $records] = $this->store->readActive($sourceKey);
        $this->index->rebuild($sourceKey, $records, $generation);
    }

    /**
     * @param  list<ScientificSearchResult>  $records
     * @param  list<string>  $tombstones
     * @param  array<string, int>  $refusals
     */
    private function commit(
        string $key,
        string $mode,
        array $records,
        array $tombstones,
        LocalRecordState $tombstoneState,
        string $tombstoneReason,
        DateTimeImmutable $now,
        ?HarvestCheckpoint $checkpoint,
        array &$refusals,
    ): LocalStoreCommit {
        $generation = $this->store->begin($key, $mode);
        try {
            foreach ($records as $record) {
                $datestamp = $record->rawMetadata[$key]['header']['datestamp'] ?? null;
                ['record' => $kept, 'refusal' => $refusal] = $this->policy->retain($record);
                if ($kept === null) {
                    $refusals[$refusal] = ($refusals[$refusal] ?? 0) + 1;

                    continue;
                }
                $this->store->stageUpsert($generation, $kept, is_string($datestamp) ? $datestamp : null);
            }
            foreach ($tombstones as $identifier) {
                $this->store->stageTombstone($generation, $identifier, $tombstoneState, $tombstoneReason);
            }
            $commit = $this->store->commit($generation, $now, $checkpoint);
        } catch (\Throwable $exception) {
            $this->store->discard($generation);

            throw $exception;
        }
        $this->index->synchronize($commit);

        return $commit;
    }

    /**
     * @param  array<string, int>  $counts
     * @param  array<string, int>  $refusals
     */
    private function report(string $key, string $status, int $pages, array $counts, array $refusals, ?string $failure, bool $restarted): LocalIngestionReport
    {
        return new LocalIngestionReport(
            sourceKey: $key,
            status: $status,
            pages: $pages,
            generation: $this->store->generation($key),
            counts: $counts,
            refusals: $refusals,
            failure: $failure,
            restartedWindow: $restarted,
            checkpoint: $this->store->checkpoint($key),
        );
    }

    private static function fromHighWaterMark(?string $highWaterMark, string $granularity): ?string
    {
        if ($highWaterMark === null || preg_match('/^\d{4}-\d{2}-\d{2}(T\d{2}:\d{2}:\d{2}Z)?$/', $highWaterMark) !== 1) {
            return null;
        }
        if ($granularity === OaiPmhSourceProfile::GRANULARITY_DAY) {
            return substr($highWaterMark, 0, 10);
        }

        return strlen($highWaterMark) === 10 ? $highWaterMark.'T00:00:00Z' : $highWaterMark;
    }
}
