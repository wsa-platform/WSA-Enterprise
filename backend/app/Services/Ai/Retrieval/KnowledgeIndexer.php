<?php

namespace App\Services\Ai\Retrieval;

use App\Models\BeeKnowledgeTopic;
use App\Models\LibraryItem;

class KnowledgeIndexer
{
    public function __construct(private KnowledgeTextNormalizer $normalizer) {}

    public function fromLibraryItem(LibraryItem $item): AiKnowledgeDocument
    {
        $title = $this->normalizer->clean((string) ($item->title ?: $item->title_ar ?: $item->slug));
        $summary = $this->normalizer->clean((string) ($item->summary ?: $item->summary_ar));
        $body = $this->normalizer->clean((string) ($item->content ?: $item->content_ar));
        $searchable = $this->normalizer->searchable(implode(' ', array_filter([
            $title,
            (string) $item->title_ar,
            $summary,
            (string) $item->summary_ar,
            $body,
            (string) $item->content_ar,
            (string) $item->slug,
        ])));

        return new AiKnowledgeDocument(
            sourceType: 'library_items',
            sourceId: (int) $item->id,
            organizationId: (int) $item->organization_id,
            title: $title,
            summary: $summary,
            body: $body,
            searchableText: $searchable,
            updatedAt: $item->updated_at,
            visible: $item->publication_status === 'published',
            metadata: ['slug' => $item->slug],
        );
    }

    public function fromBeeTopic(BeeKnowledgeTopic $topic): AiKnowledgeDocument
    {
        $title = $this->normalizer->clean((string) ($topic->slug ?: $topic->title_key));
        $summary = $this->normalizer->clean((string) ($topic->summary_key ?: $topic->category));
        $body = $this->normalizer->clean((string) ($topic->body ?? ''));
        $tags = is_array($topic->tags) ? implode(' ', $topic->tags) : '';
        $searchable = $this->normalizer->searchable(implode(' ', array_filter([
            $title,
            $summary,
            $body,
            $tags,
            (string) $topic->title_key,
            (string) $topic->category,
        ])));
        $metadata = is_array($topic->metadata) ? $topic->metadata : [];

        return new AiKnowledgeDocument(
            sourceType: 'bee_knowledge_topics',
            sourceId: (int) $topic->id,
            organizationId: null,
            title: $title,
            summary: $summary,
            body: $body,
            searchableText: $searchable,
            updatedAt: $topic->updated_at,
            visible: (bool) $topic->is_active,
            metadata: [
                'category' => $topic->category,
                'rag_ready' => (bool) ($metadata['rag_ready'] ?? false),
            ],
        );
    }

    public function excerpt(AiKnowledgeDocument $document): string
    {
        $max = max(1, (int) config('ai.retrieval.max_excerpt_characters', 400));
        $preferred = $document->body !== '' ? $document->body : $document->summary;

        return $this->normalizer->excerpt($preferred, $max);
    }
}
