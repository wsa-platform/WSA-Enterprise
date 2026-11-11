<?php

namespace App\Services\Agriculture\Research\Search\LocalIndex;

/**
 * Application-facing LocalIndex search engine (in-memory or disk-backed FTS).
 */
interface LocalIndexSearchEngine
{
    public function version(): string;

    /** @param  iterable<StoredScientificRecord>  $records */
    public function rebuild(string $sourceKey, iterable $records, int $generation): void;

    public function synchronize(LocalStoreCommit $commit): void;

    public function apply(StoredScientificRecord $record): void;

    public function hasSource(string $sourceKey): bool;

    public function generation(string $sourceKey): int;

    public function documentCount(string $sourceKey): int;

    /**
     * @return list<array{record: StoredScientificRecord, score: int}>
     */
    public function search(string $sourceKey, string $query, int $limit): array;
}
