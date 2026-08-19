<?php

namespace App\Services\Ai\Retrieval;

use App\Models\BeeKnowledgeTopic;
use App\Models\LibraryItem;

class SemanticKnowledgeCandidateLoader
{
    public function __construct(
        private KnowledgeIndexer $indexer,
        private KnowledgeTextNormalizer $normalizer,
    ) {}

    /**
     * Published/active documents only. Tenant library items plus the platform bee catalog.
     *
     * @return list<AiKnowledgeDocument>
     */
    public function documents(int $organizationId, int $limit, string $query = ''): array
    {
        $limit = max(1, $limit);
        $tokens = $this->normalizer->tokens($query);
        $documents = [];

        $library = LibraryItem::query()
            ->where('organization_id', $organizationId)
            ->where('publication_status', 'published');
        if ($tokens !== []) {
            $library->where(function ($builder) use ($tokens): void {
                $this->matchTokens($builder, $tokens, [
                    'title', 'title_ar', 'summary', 'summary_ar', 'content', 'content_ar', 'slug',
                ]);
            });
        }
        foreach ($library->orderBy('id')->limit($limit)->get([
            'id', 'organization_id', 'slug', 'title', 'title_ar', 'summary', 'summary_ar',
            'content', 'content_ar', 'publication_status', 'updated_at',
        ]) as $item) {
            $document = $this->indexer->fromLibraryItem($item);
            if ($document->visible) {
                $documents[] = $document;
            }
        }

        $bee = BeeKnowledgeTopic::query()->where('is_active', true);
        if ($tokens !== []) {
            $bee->where(function ($builder) use ($tokens): void {
                $this->matchTokens($builder, $tokens, ['slug', 'category', 'title_key', 'summary_key', 'body']);
            });
        }
        foreach ($bee->orderBy('id')->limit($limit)->get([
            'id', 'slug', 'category', 'title_key', 'summary_key', 'body', 'tags', 'metadata', 'is_active', 'updated_at',
        ]) as $topic) {
            $document = $this->indexer->fromBeeTopic($topic);
            if ($document->visible) {
                $documents[] = $document;
            }
        }

        return $documents;
    }

    /**
     * @param  list<string>  $tokens
     * @param  list<string>  $columns
     */
    private function matchTokens($builder, array $tokens, array $columns): void
    {
        $like = $builder->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
        foreach ($tokens as $token) {
            $pattern = '%'.KeywordKnowledgeRetriever::escapeLike($token).'%';
            foreach ($columns as $column) {
                $builder->orWhere($column, $like, $pattern);
            }
        }
    }
}
