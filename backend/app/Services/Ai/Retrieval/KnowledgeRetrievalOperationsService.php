<?php

namespace App\Services\Ai\Retrieval;

use App\Models\AiUsageRecord;
use App\Models\LibraryItem;
use App\Services\Ai\AiErrorSanitizer;
use App\Services\Ai\Embeddings\EmbeddingConfig;
use App\Services\Audit\AuditService;
use DateTimeInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class KnowledgeRetrievalOperationsService
{
    public const TELEMETRY_MAX_LIMIT = 100;

    public const TELEMETRY_DEFAULT_LIMIT = 25;

    public const TELEMETRY_MAX_RANGE_DAYS = 90;

    public function __construct(
        private KnowledgeRetrievalConfig $config,
        private KnowledgeSemanticIndexInterface $semanticIndex,
        private KnowledgeSemanticIndexSync $semanticSync,
        private KnowledgeIndexer $indexer,
        private KnowledgeRetrievalHealthService $healthSummary,
        private KnowledgeRetrievalQualityService $quality,
        private KnowledgeIngestionService $ingestion,
        private AuditService $audit,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function health(int $organizationId): array
    {
        $retrievalAvailable = (bool) config('ai.retrieval.enabled', true);
        $ingestionAvailable = true;
        $semanticAvailable = $this->semanticAvailable();
        $vectorStoreAvailable = $this->vectorStoreAvailable();
        $embeddingProviderAvailable = $this->embeddingProviderAvailable();
        $effective = $this->config->strategy();
        $needsSemantic = in_array($effective, ['semantic', 'hybrid'], true);
        $status = 'healthy';
        if (! $retrievalAvailable && ! $vectorStoreAvailable) {
            $status = 'unavailable';
        } elseif (! $retrievalAvailable) {
            $status = 'unavailable';
        } elseif ($this->config->configuredStrategyIsInvalid() || ($needsSemantic && ! $semanticAvailable)) {
            $status = 'degraded';
        } elseif ((bool) config('ai.retrieval.semantic_enabled', true) && (! $embeddingProviderAvailable || ! $vectorStoreAvailable)) {
            $status = 'degraded';
        }

        $knowledge = null;
        try {
            $knowledge = $this->healthSummary->summary($organizationId);
        } catch (\Throwable $exception) {
            Log::warning('AI operator health knowledge summary failed', [
                'organization_id' => $organizationId,
                'message' => AiErrorSanitizer::logMessage($exception),
            ]);
        }

        return $this->withoutSecrets([
            'status' => $status,
            'strategy' => $effective,
            'retrieval_available' => $retrievalAvailable,
            'semantic_available' => $semanticAvailable,
            'ingestion_available' => $ingestionAvailable,
            'vector_store_available' => $vectorStoreAvailable,
            'embedding_provider_available' => $embeddingProviderAvailable,
            'pgvector_available' => $this->pgvectorAvailable(),
            'semantic_backend' => 'vector',
            'organization_id' => $organizationId,
            'knowledge' => $knowledge,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function strategy(): array
    {
        $configured = strtolower(trim((string) config('ai.retrieval.strategy', 'keyword')));
        $valid = in_array($configured, KnowledgeRetrievalConfig::STRATEGIES, true);
        $semanticAvailable = $this->semanticAvailable();

        return $this->withoutSecrets([
            'configured_strategy' => $valid ? $configured : 'invalid',
            'effective_strategy' => $this->config->strategy(),
            'configured_strategy_valid' => $valid,
            'keyword_enabled' => true,
            'semantic_enabled' => $semanticAvailable,
            'hybrid_enabled' => $semanticAvailable,
            'fallback_enabled' => true,
            'retrieval_enabled' => (bool) config('ai.retrieval.enabled', true),
            'semantic_backend' => 'vector',
            'vector_store_available' => $this->vectorStoreAvailable(),
            'embedding_provider_available' => $this->embeddingProviderAvailable(),
            'pgvector_available' => $this->pgvectorAvailable(),
            'embedding_provider' => app(EmbeddingConfig::class)->provider(),
            'embedding_model' => app(EmbeddingConfig::class)->model(),
            'embedding_dimensions' => app(EmbeddingConfig::class)->dimensions(),
            'similarity_threshold' => app(EmbeddingConfig::class)->similarityThreshold(),
            'weights' => [
                'keyword' => $this->config->keywordWeight(),
                'semantic' => $this->config->semanticWeight(),
                'freshness' => $this->config->freshnessWeight(),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function qualitySummary(int $organizationId): array
    {
        try {
            $summary = $this->quality->summary($organizationId);
        } catch (\Throwable $exception) {
            Log::warning('AI operator quality summary failed', [
                'organization_id' => $organizationId,
                'message' => AiErrorSanitizer::logMessage($exception),
            ]);

            return $this->withoutSecrets([
                'organization_id' => $organizationId,
                'status' => 'unavailable',
                'semantic_available' => $this->semanticAvailable(),
                'semantic_backend' => 'vector',
            ]);
        }

        $summary['status'] = 'ok';
        $summary['total_retrieval_requests'] = (int) $summary['success_count']
            + (int) $summary['zero_result_count']
            + (int) $summary['error_count']
            + (int) $summary['fallback_count'];
        $summary['semantic_backend'] = 'vector';
        $summary['semantic_available'] = $this->semanticAvailable();
        $summary['average_retrieval_duration_ms'] = $this->averageDuration($organizationId);

        return $this->withoutSecrets($summary);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function telemetrySummary(int $organizationId, array $filters, ?DateTimeInterface $now = null): array
    {
        $now = Carbon::parse($now ?? now());
        $limit = max(1, min(self::TELEMETRY_MAX_LIMIT, (int) ($filters['limit'] ?? self::TELEMETRY_DEFAULT_LIMIT)));
        $from = isset($filters['from']) ? Carbon::parse($filters['from']) : $now->copy()->subDays(7);
        $to = isset($filters['to']) ? Carbon::parse($filters['to']) : $now;
        if ($from->gt($to)) {
            throw ValidationException::withMessages(['from' => ['The from date must be before the to date.']]);
        }
        $rangeDays = (int) floor(abs($to->getTimestamp() - $from->getTimestamp()) / 86400);
        if ($rangeDays > self::TELEMETRY_MAX_RANGE_DAYS) {
            throw ValidationException::withMessages(['to' => ['The date range may not exceed 90 days.']]);
        }

        $query = AiUsageRecord::withoutGlobalScopes()
            ->where('organization_id', $organizationId)
            ->whereNotNull('retrieval')
            ->whereBetween('created_at', [$from, $to]);
        if (! empty($filters['strategy'])) {
            $query->where('retrieval->retrieval_strategy', $filters['strategy']);
        }
        if (! empty($filters['status'])) {
            $query->where('retrieval->retrieval_status', $filters['status']);
        }

        $items = [];
        foreach ($query->orderByDesc('id')->limit($limit)->get(['id', 'organization_id', 'created_at', 'retrieval']) as $record) {
            $telemetry = is_array($record->retrieval) ? $record->retrieval : [];
            $items[] = $this->withoutSecrets([
                'id' => $record->id,
                'organization_id' => $record->organization_id,
                'created_at' => optional($record->created_at)?->toIso8601String(),
                'retrieval_status' => $telemetry['retrieval_status'] ?? null,
                'retrieval_strategy' => $telemetry['retrieval_strategy'] ?? 'keyword',
                'candidate_count' => $telemetry['candidate_count'] ?? null,
                'returned_count' => $telemetry['returned_count'] ?? null,
                'retrieval_duration_ms' => $telemetry['retrieval_duration_ms'] ?? null,
                'source_types' => $telemetry['source_types'] ?? [],
                'fallback_reason' => $telemetry['fallback_reason'] ?? null,
                'embedding_provider' => $telemetry['embedding_provider'] ?? null,
                'embedding_model' => $telemetry['embedding_model'] ?? null,
                'embedding_duration_ms' => $telemetry['embedding_duration_ms'] ?? null,
                'vector_search_duration_ms' => $telemetry['vector_search_duration_ms'] ?? null,
                'similarity_threshold' => $telemetry['similarity_threshold'] ?? null,
                'semantic_result_count' => $telemetry['semantic_result_count'] ?? null,
            ]);
        }

        return $this->withoutSecrets([
            'organization_id' => $organizationId,
            'from' => $from->toIso8601String(),
            'to' => $to->toIso8601String(),
            'limit' => $limit,
            'count' => count($items),
            'items' => $items,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function ingest(int $organizationId, array $payload, int $userId): array
    {
        $payload['source_type'] = $payload['source_type'] ?? 'library_items';
        if (($payload['source_type'] ?? '') !== 'library_items') {
            throw ValidationException::withMessages([
                'source_type' => ['Operator ingestion is limited to tenant library items.'],
            ]);
        }
        if (! isset($payload['content']) && isset($payload['body'])) {
            $payload['content'] = $payload['body'];
        }

        $result = $this->ingestion->ingestLibraryItem($organizationId, $payload, $this->actorId($userId));
        $item = LibraryItem::query()->find($result->sourceId);
        $this->audit->record(
            'ai.knowledge.ingested',
            $organizationId,
            $this->actorId($userId),
            $item,
            null,
            ['source_type' => $result->sourceType, 'source_id' => $result->sourceId, 'action' => $result->action],
        );

        return $this->ingestionPayload($result);
    }

    /**
     * @return array<string, mixed>
     */
    public function reindex(int $organizationId, int $sourceId, int $userId): array
    {
        $item = $this->ownedLibraryItem($organizationId, $sourceId);
        $document = $this->indexer->fromLibraryItem($item);
        $semanticAvailable = $this->semanticAvailable();
        $indexed = 0;
        $skipped = 0;
        $failed = 0;
        $removed = 0;
        $fallback = null;
        $status = 'ok';

        if ($this->semanticIndex instanceof VectorKnowledgeSemanticIndex) {
            $outcome = $this->semanticIndex->indexDocument($document);
            match ($outcome->status) {
                'indexed' => $indexed = 1,
                'skipped' => $skipped = 1,
                'removed' => $removed = 1,
                default => $failed = 1,
            };
            $fallback = $outcome->fallbackReason;
        } else {
            $this->semanticSync->syncLibraryItem($item);
        }

        $semanticIndexed = false;
        if ($semanticAvailable && $document->visible) {
            try {
                $semanticIndexed = $this->semanticIndex->fingerprint('library_items', (int) $item->id) !== null;
            } catch (\Throwable $exception) {
                Log::warning('AI semantic fingerprint check failed', [
                    'source_id' => $item->id,
                    'message' => AiErrorSanitizer::logMessage($exception),
                ]);
            }
        }
        if (! $semanticAvailable) {
            $status = 'degraded';
            $fallback = $fallback ?? 'semantic_unavailable';
        } elseif ($document->visible && ! $semanticIndexed) {
            $status = 'degraded';
            $fallback = $fallback ?? 'semantic_error';
            $failed = max($failed, 1);
        }
        $this->audit->record(
            'ai.knowledge.reindexed',
            $organizationId,
            $this->actorId($userId),
            $item,
            null,
            ['source_id' => $item->id, 'status' => $status],
        );

        return $this->withoutSecrets([
            'source_type' => 'library_items',
            'source_id' => (int) $item->id,
            'keyword_indexed' => true,
            'semantic_indexed' => $semanticIndexed,
            'status' => $status,
            'fallback_reason' => $fallback,
            'total' => 1,
            'indexed' => $indexed,
            'skipped' => $skipped,
            'failed' => $failed,
            'semantic_skipped' => $skipped,
            'semantic_failed' => $failed,
            'removed' => $removed,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function publish(int $organizationId, int $sourceId, int $userId): array
    {
        $result = $this->setPublication($organizationId, $sourceId, 'published', $userId);
        $this->audit->record(
            'ai.knowledge.published',
            $organizationId,
            $this->actorId($userId),
            LibraryItem::query()->find($result->sourceId),
            null,
            ['source_id' => $result->sourceId],
        );

        return $this->ingestionPayload($result);
    }

    /**
     * @return array<string, mixed>
     */
    public function unpublish(int $organizationId, int $sourceId, int $userId): array
    {
        $result = $this->setPublication($organizationId, $sourceId, 'draft', $userId);
        $this->audit->record(
            'ai.knowledge.unpublished',
            $organizationId,
            $this->actorId($userId),
            LibraryItem::query()->find($result->sourceId),
            null,
            ['source_id' => $result->sourceId],
        );

        return $this->ingestionPayload($result);
    }

    private function setPublication(int $organizationId, int $sourceId, string $status, int $userId): KnowledgeIngestionResult
    {
        $item = $this->ownedLibraryItem($organizationId, $sourceId);

        return $this->ingestion->ingestLibraryItem($organizationId, [
            'slug' => $item->slug,
            'title' => $item->title,
            'title_ar' => $item->title_ar,
            'summary' => $item->summary,
            'content' => $item->content,
            'source' => $item->source,
            'publication_status' => $status,
        ], $this->actorId($userId));
    }

    private function actorId(int $userId): ?int
    {
        return $userId > 0 ? $userId : null;
    }

    private function ownedLibraryItem(int $organizationId, int $sourceId): LibraryItem
    {
        $item = LibraryItem::query()
            ->where('organization_id', $organizationId)
            ->where('id', $sourceId)
            ->first();
        if ($item === null) {
            throw (new ModelNotFoundException)->setModel(LibraryItem::class, [$sourceId]);
        }

        return $item;
    }

    /**
     * @return array<string, mixed>
     */
    private function ingestionPayload(KnowledgeIngestionResult $result): array
    {
        return $this->withoutSecrets([
            'action' => $result->action,
            'source_type' => $result->sourceType,
            'source_id' => $result->sourceId,
            'slug' => $result->slug,
        ]);
    }

    private function semanticAvailable(): bool
    {
        try {
            return $this->semanticIndex->isAvailable();
        } catch (\Throwable $exception) {
            Log::warning('AI semantic availability check failed', [
                'message' => AiErrorSanitizer::logMessage($exception),
            ]);

            return false;
        }
    }

    private function vectorStoreAvailable(): bool
    {
        return $this->semanticIndex instanceof VectorKnowledgeSemanticIndex
            ? $this->semanticIndex->vectorStoreAvailable()
            : $this->semanticAvailable();
    }

    private function embeddingProviderAvailable(): bool
    {
        return $this->semanticIndex instanceof VectorKnowledgeSemanticIndex
            ? $this->semanticIndex->embeddingProviderAvailable()
            : $this->semanticAvailable();
    }

    private function pgvectorAvailable(): bool
    {
        return $this->semanticIndex instanceof VectorKnowledgeSemanticIndex
            && $this->semanticIndex->pgvectorAvailable();
    }

    private function averageDuration(int $organizationId): ?float
    {
        try {
            $records = AiUsageRecord::withoutGlobalScopes()
                ->where('organization_id', $organizationId)
                ->whereNotNull('retrieval')
                ->where('created_at', '>=', now()->subDays(self::TELEMETRY_MAX_RANGE_DAYS))
                ->orderByDesc('id')
                ->limit(500)
                ->get(['retrieval']);
            $total = 0;
            $count = 0;
            foreach ($records as $record) {
                $telemetry = is_array($record->retrieval) ? $record->retrieval : [];
                if (! array_key_exists('retrieval_duration_ms', $telemetry)) {
                    continue;
                }
                $total += max(0, (int) $telemetry['retrieval_duration_ms']);
                $count++;
            }

            return $count === 0 ? null : round($total / $count, 4);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function withoutSecrets(array $payload): array
    {
        $blocked = ['api_key', 'openai_api_key', 'authorization', 'password', 'secret', 'token', 'app_key', 'connection'];
        foreach ($payload as $key => $value) {
            $normalized = strtolower((string) $key);
            foreach ($blocked as $needle) {
                if (str_contains($normalized, $needle)) {
                    unset($payload[$key]);

                    continue 2;
                }
            }
            if (is_array($value)) {
                $payload[$key] = $this->withoutSecrets($value);
            }
            if (is_string($value) && (str_contains($value, 'sk-') || str_contains(strtolower($value), 'bearer '))) {
                unset($payload[$key]);
            }
        }

        return $payload;
    }
}
