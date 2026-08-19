<?php

namespace App\Services\Ai\Retrieval;

use App\Models\BeeKnowledgeTopic;

class BeeKnowledgeTopicKnowledgeSource implements KnowledgeSourceInterface
{
    public function __construct(
        private KnowledgeIndexer $indexer,
        private KnowledgeRanker $ranker,
    ) {}

    public function sourceType(): string
    {
        return 'bee_knowledge_topics';
    }

    public function search(int $organizationId, array $keywords, int $limit, string $query = ''): array
    {
        unset($organizationId);

        if ($keywords === [] || $limit < 1) {
            return [];
        }

        $like = BeeKnowledgeTopic::query()->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        $topics = BeeKnowledgeTopic::query()
            ->where('is_active', true)
            ->where(function ($builder) use ($keywords, $like): void {
                foreach ($keywords as $keyword) {
                    $pattern = '%'.KeywordKnowledgeRetriever::escapeLike($keyword).'%';
                    $builder->orWhere('slug', $like, $pattern)
                        ->orWhere('category', $like, $pattern)
                        ->orWhere('title_key', $like, $pattern)
                        ->orWhere('summary_key', $like, $pattern)
                        ->orWhere('body', $like, $pattern);
                    if ($like === 'ilike') {
                        $builder->orWhereRaw('coalesce(tags::text, \'\') ilike ?', [$pattern]);
                    } else {
                        $builder->orWhere('tags', $like, $pattern);
                    }
                }
            })
            ->limit($limit)
            ->get(['id', 'slug', 'category', 'title_key', 'summary_key', 'body', 'tags', 'metadata', 'is_active', 'updated_at']);

        $hits = [];
        foreach ($topics as $topic) {
            $document = $this->indexer->fromBeeTopic($topic);
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
