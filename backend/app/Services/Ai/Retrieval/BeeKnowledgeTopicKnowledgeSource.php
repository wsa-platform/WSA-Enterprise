<?php

namespace App\Services\Ai\Retrieval;

use App\Models\BeeKnowledgeTopic;

class BeeKnowledgeTopicKnowledgeSource implements KnowledgeSourceInterface
{
    public function sourceType(): string
    {
        return 'bee_knowledge_topics';
    }

    public function search(int $organizationId, array $keywords, int $limit): array
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
                        ->orWhere('summary_key', $like, $pattern);
                    if ($like === 'ilike') {
                        $builder->orWhereRaw('coalesce(tags::text, \'\') ilike ?', [$pattern]);
                    } else {
                        $builder->orWhere('tags', $like, $pattern);
                    }
                }
            })
            ->limit($limit)
            ->get(['id', 'slug', 'category', 'title_key', 'summary_key', 'tags', 'metadata']);

        $hits = [];
        foreach ($topics as $topic) {
            $tags = is_array($topic->tags) ? implode(' ', $topic->tags) : '';
            $haystacks = [
                'title' => (string) $topic->slug.' '.(string) $topic->title_key,
                'summary' => (string) $topic->summary_key.' '.(string) $topic->category,
                'content' => $tags,
            ];
            $score = KeywordKnowledgeRetriever::score($keywords, $haystacks);
            if ($score <= 0) {
                continue;
            }

            $metadata = is_array($topic->metadata) ? $topic->metadata : [];
            $snippet = trim(implode(' ', array_filter([
                (string) $topic->slug,
                (string) $topic->category,
                $tags,
                (string) $topic->title_key,
                (string) $topic->summary_key,
            ])));

            $hits[] = new AiRetrievalHit(
                sourceType: $this->sourceType(),
                sourceId: (int) $topic->id,
                title: (string) $topic->slug,
                content: $snippet,
                score: $score,
                metadata: [
                    'category' => $topic->category,
                    'rag_ready' => (bool) ($metadata['rag_ready'] ?? false),
                ],
            );
        }

        return $hits;
    }
}
