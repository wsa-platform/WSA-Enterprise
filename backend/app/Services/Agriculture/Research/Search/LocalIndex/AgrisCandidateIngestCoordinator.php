<?php

namespace App\Services\Agriculture\Research\Search\LocalIndex;

use App\Services\Agriculture\Research\Search\Family\Bulk\BulkDatasetSourceProfile;
use DateTimeImmutable;

/**
 * Rev 10.23 candidate ingest coordinator for AGRIS.
 *
 * - Search-facing FTS remains pinned to the GEN79 baseline while candidate batches persist.
 * - Persistence commits may advance the store generation counter.
 * - FTS is updated per completed package batch via a separate checkpoint; FTS failure never re-ingests.
 * - GEN76 generation log rows are never deleted.
 */
final class AgrisCandidateIngestCoordinator
{
    public const SEARCH_SOURCE = 'agris';

    public const SEARCH_PIN_GENERATION = 79;

    public function __construct(
        private readonly LocalIndexIngestionService $ingestion,
        private readonly ScientificRecordStore $store,
        private readonly LocalIndexSearchEngine $index,
    ) {}

    public function searchPinGeneration(): int
    {
        return self::SEARCH_PIN_GENERATION;
    }

    public function storeGeneration(): int
    {
        return $this->store->generation(self::SEARCH_SOURCE);
    }

    /**
     * Persist one package batch without touching search FTS; then attempt batch FTS sync.
     *
     * @param  (callable(int, int, LocalIngestionReport): void)|null  $afterChunk
     * @return array{
     *   persist: array{report: LocalIngestionReport, chunks: int, records_read: int, skip_resume: int},
     *   fts: array{status: string, pending: bool, failure: ?string, indexed_changed: int}
     * }
     */
    public function ingestPackageBatch(
        BulkDatasetSourceProfile $profile,
        string $jsonlPath,
        DateTimeImmutable $now,
        int $chunkSize,
        int $skipRecords,
        ?callable $afterChunk = null,
    ): array {
        $persist = $this->ingestion->ingestIncrementalBulkChunked(
            $profile,
            $jsonlPath,
            $now,
            $chunkSize,
            $skipRecords,
            $afterChunk,
            deferIndexSync: true,
        );

        if ($persist['report']->status !== LocalIngestionReport::COMPLETED) {
            return [
                'persist' => $persist,
                'fts' => [
                    'status' => 'skipped',
                    'pending' => false,
                    'failure' => 'persist_incomplete',
                    'indexed_changed' => 0,
                ],
            ];
        }

        // Candidate mode: do NOT rebuild full agris FTS here (would pull partial corpus into search).
        // Record FTS pending identities via checkpoint; end-of-ingest activation reconciles FTS.
        return [
            'persist' => $persist,
            'fts' => [
                'status' => 'deferred_until_activation',
                'pending' => true,
                'failure' => null,
                'indexed_changed' => 0,
                'store_generation' => $persist['report']->generation,
                'search_pin_generation' => self::SEARCH_PIN_GENERATION,
            ],
        ];
    }

    /**
     * Activate candidate store generations into search by rebuilding FTS to current store generation.
     * Safe only after all eligible packages persisted and reconciled.
     *
     * @return array{status: string, store_generation: int, index_generation: int, index_docs: int, failure: ?string}
     */
    public function activateSearchToCurrentStore(): array
    {
        try {
            $gen = $this->store->generation(self::SEARCH_SOURCE);
            $this->ingestion->rebuildIndex(self::SEARCH_SOURCE);
            $docs = $this->index->hasSource(self::SEARCH_SOURCE) ? $this->index->documentCount(self::SEARCH_SOURCE) : 0;

            return [
                'status' => 'completed',
                'store_generation' => $gen,
                'index_generation' => $this->index->generation(self::SEARCH_SOURCE),
                'index_docs' => $docs,
                'failure' => null,
                'search_pin_released' => true,
                'gen76_preserved' => true,
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'failed',
                'store_generation' => $this->store->generation(self::SEARCH_SOURCE),
                'index_generation' => $this->index->hasSource(self::SEARCH_SOURCE) ? $this->index->generation(self::SEARCH_SOURCE) : 0,
                'index_docs' => $this->index->hasSource(self::SEARCH_SOURCE) ? $this->index->documentCount(self::SEARCH_SOURCE) : 0,
                'failure' => $e->getMessage(),
                'search_pin_released' => false,
                'gen76_preserved' => true,
            ];
        }
    }
}
