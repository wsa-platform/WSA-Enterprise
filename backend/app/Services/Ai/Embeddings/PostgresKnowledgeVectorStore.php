<?php

namespace App\Services\Ai\Embeddings;

use App\Models\BeeKnowledgeTopic;
use App\Models\KnowledgeEmbedding;
use App\Models\LibraryItem;
use App\Services\Ai\Retrieval\AiKnowledgeDocument;
use App\Services\Ai\Retrieval\AiRetrievalHit;
use App\Services\Ai\Retrieval\KnowledgeIndexer;
use App\Services\Ai\Retrieval\KnowledgeRanker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PostgresKnowledgeVectorStore
{
    private ?bool $pgvectorAvailable = null;

    public function __construct(
        private EmbeddingConfig $config,
        private KnowledgeIndexer $indexer,
        private KnowledgeRanker $ranker,
        private EmbeddingVectorValidator $validator,
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
        if ($this->pgvectorAvailable !== null) {
            return $this->pgvectorAvailable;
        }
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return $this->pgvectorAvailable = false;
        }
        try {
            $row = DB::selectOne("SELECT 1 AS present FROM pg_extension WHERE extname = 'vector'");
            $this->pgvectorAvailable = $row !== null;
        } catch (\Throwable) {
            $this->pgvectorAvailable = false;
        }

        return $this->pgvectorAvailable;
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

            return $existing;
        }

        return KnowledgeEmbedding::query()->create(array_merge($attributes, [
            'source_type' => $document->sourceType,
            'source_id' => $document->sourceId,
        ]));
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

    private function toHit(KnowledgeEmbedding $row, float $score): AiRetrievalHit
    {
        $document = $this->documentFor($row);

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
