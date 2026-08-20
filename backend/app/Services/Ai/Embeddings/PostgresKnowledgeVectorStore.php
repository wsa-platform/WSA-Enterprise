<?php

namespace App\Services\Ai\Embeddings;

use App\Models\BeeKnowledgeTopic;
use App\Models\KnowledgeEmbedding;
use App\Models\LibraryItem;
use App\Services\Ai\AiErrorSanitizer;
use App\Services\Ai\Retrieval\AiKnowledgeDocument;
use App\Services\Ai\Retrieval\AiRetrievalHit;
use App\Services\Ai\Retrieval\KnowledgeIndexer;
use App\Services\Ai\Retrieval\KnowledgeRanker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class PostgresKnowledgeVectorStore
{
    private bool $lastUsedAnn = false;

    public function __construct(
        private EmbeddingConfig $config,
        private KnowledgeIndexer $indexer,
        private KnowledgeRanker $ranker,
        private EmbeddingVectorValidator $validator,
        private PgvectorSchema $schema,
    ) {}

    public function isAvailable(): bool
    {
        try {
            return Schema::hasTable('knowledge_embeddings');
        } catch (\Throwable) {
            return false;
        }
    }

    public function pgvectorAvailable(): bool
    {
        return $this->schema->extensionAvailable();
    }

    public function hnswAvailable(): bool
    {
        return $this->schema->hnswAvailable();
    }

    public function annAvailable(): bool
    {
        return $this->config->annEnabled()
            && $this->config->distanceMetric() === 'cosine'
            && $this->schema->annReady();
    }

    public function lastUsedAnn(): bool
    {
        return $this->lastUsedAnn;
    }

    public function nativeVectorAvailable(): bool
    {
        return $this->schema->nativeColumnAvailable();
    }

    /**
     * @param  list<float>  $vector
     */
    public function upsert(AiKnowledgeDocument $document, array $vector, string $model, int $dimensions, string $contentHash): KnowledgeEmbedding
    {
        $vector = $this->validator->assert($vector, $dimensions);
        $attributes = [
            'organization_id' => $document->organizationId,
            'embedding' => $vector,
            'embedding_model' => $model,
            'embedding_dimensions' => $dimensions,
            'content_hash' => $contentHash,
            'indexed_at' => now(),
        ];

        $existing = KnowledgeEmbedding::query()
            ->where('source_type', $document->sourceType)
            ->where('source_id', $document->sourceId)
            ->first();
        if ($existing !== null) {
            $existing->fill($attributes);
            $existing->save();
            $this->syncNativeVector($existing, $vector);

            return $existing;
        }

        $created = KnowledgeEmbedding::query()->create(array_merge($attributes, [
            'source_type' => $document->sourceType,
            'source_id' => $document->sourceId,
        ]));
        $this->syncNativeVector($created, $vector);

        return $created;
    }

    public function find(string $sourceType, int $sourceId): ?KnowledgeEmbedding
    {
        return KnowledgeEmbedding::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->first();
    }

    public function remove(string $sourceType, int $sourceId): void
    {
        KnowledgeEmbedding::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->delete();
    }

    /**
     * @param  list<float>  $queryVector
     * @return list<AiRetrievalHit>
     */
    public function search(int $organizationId, array $queryVector, int $limit, string $model): array
    {
        $limit = max(1, $limit);
        $this->lastUsedAnn = false;
        if ($this->annAvailable()) {
            try {
                return $this->searchNativeAnn($organizationId, $queryVector, $limit, $model);
            } catch (\Throwable $exception) {
                Log::warning('AI native ANN search failed; using JSON vector fallback', [
                    'organization_id' => $organizationId,
                    'message' => AiErrorSanitizer::logMessage($exception),
                ]);
            }
        }

        return $this->searchJsonFallback($organizationId, $queryVector, $limit, $model);
    }

    /**
     * @return list<KnowledgeEmbedding>
     */
    public function orphaned(): array
    {
        $orphans = [];
        foreach (KnowledgeEmbedding::query()->orderBy('id')->limit(500)->get() as $row) {
            if ($row->source_type === 'library_items') {
                $exists = DB::table('library_items')->where('id', $row->source_id)->exists();
            } elseif ($row->source_type === 'bee_knowledge_topics') {
                $exists = DB::table('bee_knowledge_topics')->where('id', $row->source_id)->exists();
            } else {
                $exists = false;
            }
            if (! $exists) {
                $orphans[] = $row;
            }
        }

        return $orphans;
    }

    public function purgeOrphans(): int
    {
        $count = 0;
        foreach ($this->orphaned() as $row) {
            $row->delete();
            $count++;
        }

        return $count;
    }

    /**
     * @param  list<float>  $queryVector
     * @return list<AiRetrievalHit>
     */
    private function searchNativeAnn(int $organizationId, array $queryVector, int $limit, string $model): array
    {
        $queryVector = $this->validator->assert($queryVector, $this->config->dimensions());
        $literal = PgvectorLiteral::format($queryVector);
        $threshold = $this->config->similarityThreshold();
        $rows = DB::select(PgvectorAnnQuery::searchSql(), [
            $literal,
            $model,
            $this->config->dimensions(),
            $organizationId,
            $organizationId,
            $threshold,
            $limit,
        ]);
        $this->lastUsedAnn = true;

        $libraryIds = [];
        $beeIds = [];
        foreach ($rows as $row) {
            if ((string) $row->source_type === 'library_items') {
                $libraryIds[] = (int) $row->source_id;
            } elseif ((string) $row->source_type === 'bee_knowledge_topics') {
                $beeIds[] = (int) $row->source_id;
            }
        }
        $library = $libraryIds === []
            ? collect()
            : LibraryItem::query()->whereIn('id', array_values(array_unique($libraryIds)))->get()->keyBy('id');
        $bee = $beeIds === []
            ? collect()
            : BeeKnowledgeTopic::query()->whereIn('id', array_values(array_unique($beeIds)))->get()->keyBy('id');

        $hits = [];
        foreach ($rows as $row) {
            $score = round((float) $row->score, 6);
            if ($score < $threshold) {
                continue;
            }
            $embedding = new KnowledgeEmbedding;
            $embedding->id = (int) $row->id;
            $embedding->source_type = (string) $row->source_type;
            $embedding->source_id = (int) $row->source_id;
            $embedding->organization_id = $row->organization_id !== null ? (int) $row->organization_id : null;
            $embedding->embedding_model = (string) $row->embedding_model;
            $embedding->embedding_dimensions = (int) $row->embedding_dimensions;
            $document = null;
            if ($embedding->source_type === 'library_items') {
                $item = $library->get($embedding->source_id);
                $document = $item instanceof LibraryItem ? $this->indexer->fromLibraryItem($item) : null;
            } elseif ($embedding->source_type === 'bee_knowledge_topics') {
                $topic = $bee->get($embedding->source_id);
                $document = $topic instanceof BeeKnowledgeTopic ? $this->indexer->fromBeeTopic($topic) : null;
            }
            $hits[] = $this->toHit($embedding, $score, $document);
        }

        return $this->ranker->sort($hits);
    }

    /**
     * @param  list<float>  $queryVector
     * @return list<AiRetrievalHit>
     */
    private function searchJsonFallback(int $organizationId, array $queryVector, int $limit, string $model): array
    {
        $threshold = $this->config->similarityThreshold();
        $rows = $this->eligibleRows($organizationId, $this->config->dimensions(), $model);
        $hits = [];
        foreach ($rows as $row) {
            $vector = is_array($row->embedding) ? $row->embedding : [];
            try {
                $vector = $this->validator->assert($vector, (int) $row->embedding_dimensions);
            } catch (\Throwable) {
                continue;
            }
            $score = CosineSimilarity::score($queryVector, $vector);
            if ($score < $threshold) {
                continue;
            }
            $hits[] = $this->toHit($row, $score);
        }

        return array_slice($this->ranker->sort($hits), 0, $limit);
    }

    /**
     * @return list<KnowledgeEmbedding>
     */
    private function eligibleRows(int $organizationId, int $dimensions, string $model): array
    {
        return KnowledgeEmbedding::query()
            ->where('embedding_dimensions', $dimensions)
            ->where('embedding_model', $model)
            ->where(function ($query) use ($organizationId): void {
                $query->where(function ($library) use ($organizationId): void {
                    $library->where('source_type', 'library_items')
                        ->where('organization_id', $organizationId)
                        ->whereExists(function ($exists) use ($organizationId): void {
                            $exists->selectRaw('1')
                                ->from('library_items')
                                ->whereColumn('library_items.id', 'knowledge_embeddings.source_id')
                                ->where('library_items.organization_id', $organizationId)
                                ->where('library_items.publication_status', 'published');
                        });
                })->orWhere(function ($bee): void {
                    $bee->where('source_type', 'bee_knowledge_topics')
                        ->whereNull('organization_id')
                        ->whereExists(function ($exists): void {
                            $exists->selectRaw('1')
                                ->from('bee_knowledge_topics')
                                ->whereColumn('bee_knowledge_topics.id', 'knowledge_embeddings.source_id')
                                ->where('bee_knowledge_topics.is_active', true);
                        });
                });
            })
            ->orderBy('id')
            ->limit($this->config->maxScan())
            ->get()
            ->all();
    }

    /**
     * @param  list<float>  $vector
     */
    private function syncNativeVector(KnowledgeEmbedding $row, array $vector): void
    {
        if (! $this->annAvailable() && ! $this->schema->nativeColumnAvailable()) {
            return;
        }
        try {
            $this->schema->writeNativeVector((int) $row->id, $vector);
        } catch (\Throwable $exception) {
            Log::warning('AI native vector write failed; JSON embedding was stored', [
                'source_type' => $row->source_type,
                'source_id' => $row->source_id,
                'message' => AiErrorSanitizer::logMessage($exception),
            ]);
        }
    }

    private function toHit(KnowledgeEmbedding $row, float $score, ?AiKnowledgeDocument $document = null): AiRetrievalHit
    {
        $document ??= $this->documentFor($row);

        return new AiRetrievalHit(
            sourceType: $row->source_type,
            sourceId: (int) $row->source_id,
            title: $document?->title ?? ($row->source_type.':'.$row->source_id),
            content: $document !== null ? $this->indexer->excerpt($document) : '',
            score: $score,
            metadata: [
                'semantic_score' => $score,
                'retrieval_strategy' => 'semantic',
                'embedding_model' => $row->embedding_model,
                'ann_used' => $this->lastUsedAnn,
            ],
            organizationId: $row->organization_id !== null ? (int) $row->organization_id : null,
            updatedAt: $document?->updatedAt,
        );
    }

    private function documentFor(KnowledgeEmbedding $row): ?AiKnowledgeDocument
    {
        if ($row->source_type === 'library_items') {
            $item = LibraryItem::query()->find($row->source_id);

            return $item !== null ? $this->indexer->fromLibraryItem($item) : null;
        }
        if ($row->source_type === 'bee_knowledge_topics') {
            $topic = BeeKnowledgeTopic::query()->find($row->source_id);

            return $topic !== null ? $this->indexer->fromBeeTopic($topic) : null;
        }

        return null;
    }
}
