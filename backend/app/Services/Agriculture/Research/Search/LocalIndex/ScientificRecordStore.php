<?php

namespace App\Services\Agriculture\Research\Search\LocalIndex;

use App\Services\Agriculture\Research\Search\ScientificSearchResult;
use DateTimeImmutable;

/**
 * Storage port for harvested and bulk-loaded source records (ADR-023 §8.40).
 *
 * Changes are staged in a generation and become visible only on commit, all at once; a
 * discarded generation leaves no trace. A record is identified by sourceKey and the provider's
 * original identifier. Re-staging identical content is a no-op, an update older than the stored
 * datestamp is stale and ignored, and a SNAPSHOT commit withdraws every active record of the
 * source that the snapshot did not contain. The lifecycle rules live in
 * ScientificRecordGenerationMerge so every implementation applies the same ones.
 *
 * A commit returns only after the implementation's own durability guarantee holds; a commit
 * against a generation another writer has meanwhile advanced fails with
 * LocalStoreFailure::CONCURRENT_GENERATION and changes nothing. Storage faults surface as
 * LocalStoreFailure, never as an empty result.
 */
interface ScientificRecordStore
{
    public const MODE_INCREMENTAL = 'incremental';

    public const MODE_SNAPSHOT = 'snapshot';

    /** Opens a generation and returns its id; one open generation per source. */
    public function begin(string $sourceKey, string $mode): string;

    /** The record must carry provenance with the provider's original identifier. */
    public function stageUpsert(string $generationId, ScientificSearchResult $record, ?string $datestamp = null): void;

    public function stageTombstone(string $generationId, string $originalIdentifier, LocalRecordState $state, string $reason, ?string $datestamp = null): void;

    public function commit(string $generationId, DateTimeImmutable $at, ?HarvestCheckpoint $checkpoint = null): LocalStoreCommit;

    public function discard(string $generationId): void;

    public function checkpoint(string $sourceKey): ?HarvestCheckpoint;

    public function get(string $sourceKey, string $originalIdentifier): ?StoredScientificRecord;

    /** @return list<StoredScientificRecord> every stored version, tombstones included */
    public function records(string $sourceKey): array;

    /**
     * The ACTIVE records and the generation they belong to, read consistently, for index rebuilds.
     *
     * @return array{generation: int, records: list<StoredScientificRecord>}
     */
    public function readActive(string $sourceKey): array;

    /**
     * Stream ACTIVE rows for bounded-memory index rebuilds.
     *
     * @return \Generator<int, StoredScientificRecord>
     */
    public function iterateActive(string $sourceKey): \Generator;

    /** The last committed generation of the source; 0 before the first commit. */
    public function generation(string $sourceKey): int;
}
