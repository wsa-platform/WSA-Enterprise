<?php

namespace App\Services\Ai\Retrieval;

use App\Models\BeeKnowledgeTopic;
use App\Models\LibraryItem;
use DateTimeInterface;

class KnowledgeRetrievalOperations
{
    public function __construct(
        private KeywordKnowledgeRetriever $retriever,
        private KnowledgeIndexer $indexer,
        private KnowledgeRanker $ranker,
        private KnowledgeFreshnessService $freshness,
        private KnowledgeTextNormalizer $normalizer,
    ) {}

    /**
     * Bounded operational retrieval. Unpublished records are returned only when
     * publication_state is explicitly unpublished or all. Normal retrieval stays
     * published-only via the AI-08 retriever.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function retrieve(int $organizationId, string $query = '', array $filters = [], ?DateTimeInterface $now = null): array
    {
        $limit = $this->boundLimit($filters['limit'] ?? null);
        $sourceType = $this->sourceType($filters['source_type'] ?? null);
        $publication = $this->publicationState($filters['publication_state'] ?? 'published');
        $freshnessFilter = $this->freshnessState($filters['freshness'] ?? null);
        $query = $this->normalizer->clean(mb_substr($query, 0, 2000));
        $now = $now ?? now();

        $candidateCount = 0;
        $hits = $publication === 'published' && $query !== ''
            ? $this->fromRetriever($organizationId, $query, $candidateCount)
            : $this->fromIndexedSources($organizationId, $query, $sourceType, $publication);
        if ($publication !== 'published' || $query === '') {
            $candidateCount = count($hits);
        }
        $hits = array_values(array_filter(
            $hits,
            function (AiRetrievalHit $hit) use ($sourceType, $freshnessFilter, $now): bool {
                if ($sourceType !== null && $hit->sourceType !== $sourceType) {
                    return false;
                }
                if ($freshnessFilter !== null && $this->freshness->classify($hit->updatedAt, $now) !== $freshnessFilter) {
                    return false;
                }

                return true;
            },
        ));

        $hits = array_slice($this->ranker->sort($hits), 0, $limit);
        $items = array_map(fn (AiRetrievalHit $hit) => $this->serializeHit($hit, $now), $hits);

        return [
            'organization_id' => $organizationId,
            'query' => $query,
            'candidate_count' => $candidateCount,
            'returned_count' => count($items),
            'source_types' => $this->sourceTypeCounts($items),
            'freshness_distribution' => $this->freshness->distribution(
                array_map(static fn (AiRetrievalHit $hit) => $hit->updatedAt, $hits),
                $now,
            ),
            'hits' => $items,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function inspect(int $organizationId, string $sourceType, int $sourceId, ?DateTimeInterface $now = null): ?array
    {
        if ($sourceType === 'library_items') {
            $item = LibraryItem::query()->where('id', $sourceId)->first();
            if ($item === null || (int) $item->organization_id !== $organizationId) {
                return null;
            }
            $document = $this->indexer->fromLibraryItem($item);

            return $this->serializeDocument($document, $now);
        }

        if ($sourceType === 'bee_knowledge_topics') {
            $topic = BeeKnowledgeTopic::query()->where('id', $sourceId)->first();
            if ($topic === null) {
                return null;
            }

            return $this->serializeDocument($this->indexer->fromBeeTopic($topic), $now);
        }

        return null;
    }

    /**
     * @return array<string, int>
     */
    public function sourceDistribution(int $organizationId): array
    {
        $distribution = [
            'bee_knowledge_topics' => BeeKnowledgeTopic::query()->where('is_active', true)->count(),
            'library_items' => LibraryItem::query()
                ->where('organization_id', $organizationId)
                ->where('publication_status', 'published')
                ->count(),
        ];
        ksort($distribution);

        return $distribution;
    }

    /**
     * @return list<AiRetrievalHit>
     */
    private function fromRetriever(int $organizationId, string $query, int &$candidateCount): array
    {
        $result = $this->retriever->retrieve($organizationId, $query);
        $candidateCount = max(0, (int) ($result->telemetry['candidate_count'] ?? count($result->hits)));

        return $result->hits;
    }

    /**
     * @return list<AiRetrievalHit>
     */
    private function fromIndexedSources(int $organizationId, string $query, ?string $sourceType, string $publication): array
    {
        $keywords = $query === '' ? [] : $this->retriever->keywords($query);
        $limit = max(1, (int) config('ai.retrieval.candidate_limit', 40));
        $hits = [];

        if ($sourceType === null || $sourceType === 'library_items') {
            $library = LibraryItem::query()->where('organization_id', $organizationId);
            $library = match ($publication) {
                'unpublished' => $library->where('publication_status', '!=', 'published'),
                'all' => $library,
                default => $library->where('publication_status', 'published'),
            };
            foreach ($library->orderBy('id')->limit($limit)->get() as $item) {
                $document = $this->indexer->fromLibraryItem($item);
                if ($publication === 'published' && ! $document->visible) {
                    continue;
                }
                $score = $this->operationalScore($query, $keywords, $document);
                if ($keywords !== [] && $score <= 0) {
                    continue;
                }
                $hits[] = $this->hitFromDocument($document, $score);
            }
        }

        if ($sourceType === null || $sourceType === 'bee_knowledge_topics') {
            $bee = BeeKnowledgeTopic::query();
            $bee = match ($publication) {
                'unpublished' => $bee->where('is_active', false),
                'all' => $bee,
                default => $bee->where('is_active', true),
            };
            foreach ($bee->orderBy('id')->limit($limit)->get() as $topic) {
                $document = $this->indexer->fromBeeTopic($topic);
                if ($publication === 'published' && ! $document->visible) {
                    continue;
                }
                $score = $this->operationalScore($query, $keywords, $document);
                if ($keywords !== [] && $score <= 0) {
                    continue;
                }
                $hits[] = $this->hitFromDocument($document, $score);
            }
        }

        return $hits;
    }

    /**
     * @param  list<string>  $keywords
     */
    private function operationalScore(string $query, array $keywords, AiKnowledgeDocument $document): float
    {
        if ($keywords === []) {
            return 1.0;
        }
        if ($document->visible) {
            return $this->ranker->score($query, $keywords, $document);
        }

        return $this->ranker->score($query, $keywords, new AiKnowledgeDocument(
            sourceType: $document->sourceType,
            sourceId: $document->sourceId,
            organizationId: $document->organizationId,
            title: $document->title,
            summary: $document->summary,
            body: $document->body,
            searchableText: $document->searchableText,
            updatedAt: $document->updatedAt,
            visible: true,
            metadata: $document->metadata,
        ));
    }

    private function hitFromDocument(AiKnowledgeDocument $document, float $score): AiRetrievalHit
    {
        return new AiRetrievalHit(
            sourceType: $document->sourceType,
            sourceId: $document->sourceId,
            title: $document->title,
            content: $this->indexer->excerpt($document),
            score: $score,
            metadata: $document->metadata,
            organizationId: $document->organizationId,
            updatedAt: $document->updatedAt,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeHit(AiRetrievalHit $hit, DateTimeInterface $now): array
    {
        return [
            'source_type' => $hit->sourceType,
            'source_id' => $hit->sourceId,
            'organization_id' => $hit->organizationId,
            'title' => $hit->title,
            'excerpt' => $hit->content,
            'score' => $hit->score,
            'freshness' => $this->freshness->classify($hit->updatedAt, $now),
            'updated_at' => $hit->updatedAt?->format(DATE_ATOM),
            'reference' => $hit->sourceType.':'.$hit->sourceId,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeDocument(AiKnowledgeDocument $document, ?DateTimeInterface $now): array
    {
        $now = $now ?? now();

        return [
            'source_type' => $document->sourceType,
            'source_id' => $document->sourceId,
            'organization_id' => $document->organizationId,
            'title' => $document->title,
            'summary' => $document->summary,
            'has_body' => $document->body !== '',
            'visible' => $document->visible,
            'freshness' => $this->freshness->classify($document->updatedAt, $now),
            'updated_at' => $document->updatedAt?->format(DATE_ATOM),
            'tokens' => $this->normalizer->tokens($document->searchableText),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array<string, int>
     */
    private function sourceTypeCounts(array $items): array
    {
        $counts = ['library_items' => 0, 'bee_knowledge_topics' => 0];
        foreach ($items as $item) {
            $type = (string) ($item['source_type'] ?? '');
            if (isset($counts[$type])) {
                $counts[$type]++;
            }
        }
        ksort($counts);

        return $counts;
    }

    private function boundLimit(mixed $limit): int
    {
        $max = max(1, (int) config('ai.retrieval.operations_max_results', 50));
        $requested = $limit === null ? min(20, $max) : (int) $limit;

        return max(1, min($max, $requested));
    }

    private function sourceType(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }
        if (! in_array($value, ['library_items', 'bee_knowledge_topics'], true)) {
            return null;
        }

        return $value;
    }

    private function publicationState(mixed $value): string
    {
        $state = is_string($value) ? strtolower(trim($value)) : 'published';

        return in_array($state, ['published', 'unpublished', 'all'], true) ? $state : 'published';
    }

    private function freshnessState(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        return in_array($value, [
            KnowledgeFreshnessService::FRESH,
            KnowledgeFreshnessService::STALE,
            KnowledgeFreshnessService::UNKNOWN,
        ], true) ? $value : null;
    }
}
