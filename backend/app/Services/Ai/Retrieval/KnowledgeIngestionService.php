<?php

namespace App\Services\Ai\Retrieval;

use App\Models\BeeKnowledgeTopic;
use App\Models\LibraryItem;
use Illuminate\Validation\ValidationException;

class KnowledgeIngestionService
{
    public function __construct(
        private KnowledgeIngestionValidator $validator,
        private KnowledgeTextNormalizer $normalizer,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function ingest(int $organizationId, array $payload, ?int $ownerUserId = null): KnowledgeIngestionResult
    {
        $type = (string) ($payload['source_type'] ?? 'library_items');
        if ($type === 'bee_knowledge_topics') {
            return $this->ingestBeeTopic($payload);
        }

        return $this->ingestLibraryItem($organizationId, $payload, $ownerUserId);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function ingestLibraryItem(int $organizationId, array $payload, ?int $ownerUserId = null): KnowledgeIngestionResult
    {
        if ($organizationId < 1) {
            throw ValidationException::withMessages([
                'organization_id' => ['Tenant identity is required.'],
            ]);
        }

        $attributes = $this->validator->validateLibrary($organizationId, $payload);
        $existing = LibraryItem::query()
            ->where('organization_id', $organizationId)
            ->where('slug', $attributes['slug'])
            ->first();

        if ($existing !== null && (int) $existing->organization_id !== $organizationId) {
            throw ValidationException::withMessages([
                'organization_id' => ['Cross-tenant knowledge writes are not allowed.'],
            ]);
        }

        $item = $existing ?? new LibraryItem;
        $item->organization_id = $organizationId;
        if ($ownerUserId !== null && ! $item->exists) {
            $item->owner_user_id = $ownerUserId;
        }
        $item->fill($attributes);
        if ($attributes['publication_status'] === 'published' && $item->published_at === null) {
            $item->published_at = now();
        }

        return $this->persistLibrary($item);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function ingestBeeTopic(array $payload): KnowledgeIngestionResult
    {
        $attributes = $this->validator->validateBeeTopic($payload);
        $topic = BeeKnowledgeTopic::query()->where('slug', $attributes['slug'])->first() ?? new BeeKnowledgeTopic;
        $existingMeta = is_array($topic->metadata) ? $topic->metadata : [];
        foreach (['url', 'source_url', 'citation_url', 'href', 'citations'] as $untrusted) {
            unset($existingMeta[$untrusted]);
        }
        $attributes['metadata'] = array_merge($existingMeta, $attributes['metadata'] ?? ['rag_ready' => false]);
        $topic->fill($attributes);

        return $this->persistBee($topic);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function updateLibraryItem(int $organizationId, int $sourceId, array $payload, ?int $ownerUserId = null): KnowledgeIngestionResult
    {
        $item = LibraryItem::query()->where('id', $sourceId)->first();
        if ($item === null || (int) $item->organization_id !== $organizationId) {
            throw ValidationException::withMessages([
                'source_id' => ['Knowledge record was not found in this tenant.'],
            ]);
        }

        $payload['slug'] = $payload['slug'] ?? $item->slug;

        return $this->ingestLibraryItem($organizationId, $payload, $ownerUserId);
    }

    private function persistLibrary(LibraryItem $item): KnowledgeIngestionResult
    {
        $existed = $item->exists;
        if ($existed && $this->dirtyKeys($item) === []) {
            return $this->result('unchanged', 'library_items', (int) $item->id, (string) $item->slug, $item);
        }

        $item->save();

        return $this->result($existed ? 'updated' : 'created', 'library_items', (int) $item->id, (string) $item->slug, $item);
    }

    private function persistBee(BeeKnowledgeTopic $topic): KnowledgeIngestionResult
    {
        $existed = $topic->exists;
        if ($existed && $this->dirtyKeys($topic) === []) {
            return $this->result('unchanged', 'bee_knowledge_topics', (int) $topic->id, (string) $topic->slug, $topic);
        }

        $topic->save();

        return $this->result($existed ? 'updated' : 'created', 'bee_knowledge_topics', (int) $topic->id, (string) $topic->slug, $topic);
    }

    /**
     * @return list<string>
     */
    private function dirtyKeys(LibraryItem|BeeKnowledgeTopic $model): array
    {
        return array_values(array_filter(
            array_keys($model->getDirty()),
            static fn (string $key): bool => ! in_array($key, ['updated_at', 'created_at'], true),
        ));
    }

    private function result(string $action, string $sourceType, int $sourceId, string $slug, LibraryItem|BeeKnowledgeTopic $model): KnowledgeIngestionResult
    {
        $text = $model instanceof LibraryItem
            ? implode(' ', array_filter([
                (string) $model->title,
                (string) $model->summary,
                (string) $model->content,
                (string) $model->slug,
            ]))
            : implode(' ', array_filter([
                (string) $model->slug,
                (string) $model->category,
                (string) $model->title_key,
                (string) $model->summary_key,
                (string) $model->body,
            ]));

        return new KnowledgeIngestionResult(
            action: $action,
            sourceType: $sourceType,
            sourceId: $sourceId,
            slug: $slug,
            searchableTokens: $this->normalizer->tokens($text),
        );
    }
}
