<?php

namespace App\Services\Ai\Retrieval;

use App\Models\LibraryItem;

class LibraryItemKnowledgeSource implements KnowledgeSourceInterface
{
    public function sourceType(): string
    {
        return 'library_items';
    }

    public function search(int $organizationId, array $keywords, int $limit): array
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
            ->get(['id', 'title', 'title_ar', 'summary', 'summary_ar', 'content', 'content_ar', 'slug']);

        $hits = [];
        foreach ($items as $item) {
            $haystacks = [
                'title' => (string) $item->title.' '.(string) $item->title_ar,
                'summary' => (string) $item->summary.' '.(string) $item->summary_ar,
                'content' => (string) $item->content.' '.(string) $item->content_ar.' '.(string) $item->slug,
            ];
            $score = KeywordKnowledgeRetriever::score($keywords, $haystacks);
            if ($score <= 0) {
                continue;
            }

            $snippet = trim((string) ($item->summary ?: $item->summary_ar ?: $item->content ?: $item->content_ar));
            $hits[] = new AiRetrievalHit(
                sourceType: $this->sourceType(),
                sourceId: (int) $item->id,
                title: (string) ($item->title ?: $item->title_ar ?: $item->slug),
                content: $snippet,
                score: $score,
                metadata: ['slug' => $item->slug],
            );
        }

        return $hits;
    }
}
