<?php

namespace App\Services\Ai\Retrieval;

use App\Models\LibraryItem;

class LibraryItemKnowledgeSource implements KnowledgeSourceInterface
{
    public function __construct(
        private KnowledgeIndexer $indexer,
        private KnowledgeRanker $ranker,
    ) {}

    public function sourceType(): string
    {
        return 'library_items';
    }

    public function search(int $organizationId, array $keywords, int $limit, string $query = ''): array
    {
        if ($keywords === [] || $limit < 1) {
            return [];
        }

        $like = LibraryItem::query()->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        $items = LibraryItem::query()
            ->where('organization_id', $organizationId)
            ->where('publication_status', 'published')
            ->where(function ($builder) use ($keywords, $like): void {
                foreach ($keywords as $keyword) {
                    $pattern = '%'.KeywordKnowledgeRetriever::escapeLike($keyword).'%';
                    $builder->orWhere('title', $like, $pattern)
                        ->orWhere('title_ar', $like, $pattern)
                        ->orWhere('summary', $like, $pattern)
                        ->orWhere('summary_ar', $like, $pattern)
                        ->orWhere('content', $like, $pattern)
                        ->orWhere('content_ar', $like, $pattern)
                        ->orWhere('slug', $like, $pattern);
                }
            })
            ->limit($limit)
            ->get([
                'id', 'organization_id', 'slug', 'title', 'title_ar', 'summary', 'summary_ar',
                'content', 'content_ar', 'publication_status', 'updated_at',
            ]);

        $hits = [];
        foreach ($items as $item) {
            $document = $this->indexer->fromLibraryItem($item);
            if (! $document->visible) {
                continue;
            }
            $score = $this->ranker->score($query, $keywords, $document);
            if ($score <= 0) {
                continue;
            }

            $hits[] = new AiRetrievalHit(
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

        return $hits;
    }
}
