<?php

namespace App\Services\Ai\Retrieval;

use App\Services\Ai\AiErrorSanitizer;
use App\Services\Ai\Embeddings\EmbeddingConfig;
use App\Services\Ai\Embeddings\EmbeddingException;
use App\Services\Ai\Embeddings\EmbeddingProviderInterface;
use App\Services\Ai\Embeddings\KnowledgeEmbeddingHasher;
use App\Services\Ai\Embeddings\PostgresKnowledgeVectorStore;
use App\Services\Ai\Embeddings\VectorIndexOutcome;
use Illuminate\Support\Facades\Log;

class VectorKnowledgeSemanticIndex implements KnowledgeSemanticIndexInterface
{
    /** @var array<string, mixed> */
    private array $lastSearchStats = [];

    public function __construct(
        private KnowledgeRetrievalConfig $retrievalConfig,
        private EmbeddingConfig $embeddingConfig,
        private EmbeddingProviderInterface $embeddings,
        private PostgresKnowledgeVectorStore $store,
        private KnowledgeEmbeddingHasher $hasher,
    ) {}

    public function isAvailable(): bool
    {
        return $this->retrievalConfig->semanticEnabled()
            && $this->embeddings->isAvailable()
            && $this->store->isAvailable();
    }

    public function vectorStoreAvailable(): bool
    {
        return $this->store->isAvailable();
    }

    public function embeddingProviderAvailable(): bool
    {
        return $this->embeddings->isAvailable();
    }

    public function pgvectorAvailable(): bool
    {
        return $this->store->pgvectorAvailable();
    }

    public function index(AiKnowledgeDocument $document): void
    {
        $this->indexDocument($document);
    }

    public function indexDocument(AiKnowledgeDocument $document): VectorIndexOutcome
    {
        if (! $document->visible) {
            $this->remove($document->sourceType, $document->sourceId);

            return VectorIndexOutcome::removed();
        }
        if (! $this->isAvailable()) {
            return VectorIndexOutcome::failed('semantic_unavailable');
        }

        $model = $this->embeddings->model();
        $dimensions = $this->embeddings->dimensions();
        $contentHash = $this->hasher->hash($document);
        $existing = $this->store->find($document->sourceType, $document->sourceId);
        if (
            $existing !== null
            && $existing->content_hash === $contentHash
            && $existing->embedding_model === $model
            && (int) $existing->embedding_dimensions === $dimensions
        ) {
            return VectorIndexOutcome::skipped();
        }

        try {
            $text = $this->embeddingText($document);
            $result = $this->embeddings->embed($text);
            if ($result->dimensions !== $dimensions || $result->model !== $model) {
                if ($result->dimensions !== $dimensions) {
                    throw new EmbeddingException('The embedding dimension does not match the configured size.');
                }
            }
            $this->store->upsert($document, $result->vector, $model, $dimensions, $contentHash);

            return VectorIndexOutcome::indexed($result->durationMs);
        } catch (\Throwable $exception) {
            Log::warning('AI vector indexing failed', [
                'source_type' => $document->sourceType,
                'source_id' => $document->sourceId,
                'organization_id' => $document->organizationId,
                'message' => AiErrorSanitizer::logMessage($exception),
            ]);

            return VectorIndexOutcome::failed('semantic_error');
        }
    }

    /**
     * @param  list<AiKnowledgeDocument>  $documents
     * @return array{total: int, indexed: int, skipped: int, failed: int, removed: int}
     */
    public function indexDocuments(array $documents): array
    {
        $summary = ['total' => count($documents), 'indexed' => 0, 'skipped' => 0, 'failed' => 0, 'removed' => 0];
        $pending = [];
        foreach ($documents as $document) {
            if (! $document instanceof AiKnowledgeDocument) {
                $summary['failed']++;

                continue;
            }
            if (! $document->visible) {
                $this->remove($document->sourceType, $document->sourceId);
                $summary['removed']++;

                continue;
            }
            $existing = $this->store->find($document->sourceType, $document->sourceId);
            $hash = $this->hasher->hash($document);
            if (
                $existing !== null
                && $existing->content_hash === $hash
                && $existing->embedding_model === $this->embeddings->model()
                && (int) $existing->embedding_dimensions === $this->embeddings->dimensions()
            ) {
                $summary['skipped']++;

                continue;
            }
            $pending[] = $document;
        }

        foreach (array_chunk($pending, $this->embeddingConfig->batchSize()) as $chunk) {
            try {
                $texts = array_map(fn (AiKnowledgeDocument $document): string => $this->embeddingText($document), $chunk);
                $results = $this->embeddings->embedMany($texts);
                foreach ($chunk as $offset => $document) {
                    try {
                        $embedding = $results[$offset] ?? null;
                        if ($embedding === null) {
                            $summary['failed']++;

                            continue;
                        }
                        $this->store->upsert(
                            $document,
                            $embedding->vector,
                            $this->embeddings->model(),
                            $this->embeddings->dimensions(),
                            $this->hasher->hash($document),
                        );
                        $summary['indexed']++;
                    } catch (\Throwable $exception) {
                        Log::warning('AI vector batch item failed', [
                            'source_type' => $document->sourceType,
                            'source_id' => $document->sourceId,
                            'message' => AiErrorSanitizer::logMessage($exception),
                        ]);
                        $summary['failed']++;
                    }
                }
            } catch (\Throwable $exception) {
                Log::warning('AI vector batch failed; retrying per document', [
                    'message' => AiErrorSanitizer::logMessage($exception),
                ]);
                foreach ($chunk as $document) {
                    $outcome = $this->indexDocument($document);
                    $summary[$outcome->status === 'indexed' ? 'indexed' : ($outcome->status === 'skipped' ? 'skipped' : 'failed')]++;
                }
            }
        }

        return $summary;
    }

    public function remove(string $sourceType, int $sourceId): void
    {
        $this->store->remove($sourceType, $sourceId);
    }

    public function search(int $organizationId, string $query, int $limit): array
    {
        $started = (int) round(microtime(true) * 1000);
        $this->lastSearchStats = [
            'embedding_provider' => $this->embeddings->name(),
            'embedding_model' => $this->embeddings->model(),
            'similarity_threshold' => $this->embeddingConfig->similarityThreshold(),
            'embedding_duration_ms' => 0,
            'vector_search_duration_ms' => 0,
            'semantic_result_count' => 0,
        ];
        if (! $this->isAvailable()) {
            return [];
        }
        $normalized = trim($query);
        if ($normalized === '') {
            return [];
        }

        $embedStarted = (int) round(microtime(true) * 1000);
        $embedding = $this->embeddings->embed($normalized);
        $embedMs = max(0, ((int) round(microtime(true) * 1000)) - $embedStarted);
        $searchStarted = (int) round(microtime(true) * 1000);
        $hits = $this->store->search(
            $organizationId,
            $embedding->vector,
            min($limit, $this->embeddingConfig->maxCandidates()),
            $embedding->model,
        );
        $searchMs = max(0, ((int) round(microtime(true) * 1000)) - $searchStarted);
        $this->lastSearchStats = [
            'embedding_provider' => $this->embeddings->name(),
            'embedding_model' => $this->embeddings->model(),
            'similarity_threshold' => $this->embeddingConfig->similarityThreshold(),
            'embedding_duration_ms' => $embedMs,
            'vector_search_duration_ms' => $searchMs,
            'semantic_result_count' => count($hits),
            'retrieval_duration_ms' => max(0, ((int) round(microtime(true) * 1000)) - $started),
        ];

        return $hits;
    }

    public function fingerprint(string $sourceType, int $sourceId): ?string
    {
        return $this->store->find($sourceType, $sourceId)?->content_hash;
    }

    /** @return array<string, mixed> */
    public function lastSearchStats(): array
    {
        return $this->lastSearchStats;
    }

    private function embeddingText(AiKnowledgeDocument $document): string
    {
        $max = max(1, (int) config('ai.max_input_characters', 8000));
        $text = trim(implode("\n", array_filter([
            $document->title,
            $document->summary,
            $document->body,
            $document->searchableText,
        ])));

        return mb_substr($text !== '' ? $text : $document->title, 0, $max);
    }
}
