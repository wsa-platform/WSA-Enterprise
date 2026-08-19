<?php

namespace App\Services\Ai\Retrieval;

use App\Models\BeeKnowledgeTopic;

class BeeKnowledgeBodyBackfillService
{
    public function __construct(private KnowledgeTextNormalizer $normalizer) {}

    public function backfillMissingBodies(?int $limit = null): BeeKnowledgeBackfillResult
    {
        $query = BeeKnowledgeTopic::query()->orderBy('id');
        if ($limit !== null) {
            $query->limit(max(1, $limit));
        }

        $updated = [];
        $skipped = [];
        $unchanged = [];

        foreach ($query->get() as $topic) {
            $outcome = $this->backfillTopic($topic);
            match ($outcome) {
                'updated' => $updated[] = (int) $topic->id,
                'skipped' => $skipped[] = (int) $topic->id,
                default => $unchanged[] = (int) $topic->id,
            };
        }

        return new BeeKnowledgeBackfillResult(
            updated: count($updated),
            skipped: count($skipped),
            unchanged: count($unchanged),
            updatedIds: $updated,
            skippedIds: $skipped,
            unchangedIds: $unchanged,
        );
    }

    public function backfillTopic(BeeKnowledgeTopic $topic): string
    {
        $existing = $this->normalizer->clean((string) ($topic->body ?? ''));
        if ($existing !== '') {
            return 'unchanged';
        }

        $body = $this->composeFromAuthoritativeFields($topic);
        if ($body === null) {
            return 'skipped';
        }

        $topic->body = $body;
        $topic->save();

        return 'updated';
    }

    public function composeFromAuthoritativeFields(BeeKnowledgeTopic $topic): ?string
    {
        $category = $this->normalizer->clean((string) $topic->category);
        $titleKey = $this->normalizer->clean((string) $topic->title_key);
        $summaryKey = $this->normalizer->clean((string) ($topic->summary_key ?? ''));
        $tags = is_array($topic->tags) ? array_values(array_filter(array_map(
            fn (mixed $tag): string => is_string($tag) || is_numeric($tag) ? $this->normalizer->clean((string) $tag) : '',
            $topic->tags,
        ))) : [];

        if ($category === '' && $titleKey === '' && $summaryKey === '' && $tags === []) {
            return null;
        }

        $lines = [];
        $slug = $this->normalizer->clean((string) $topic->slug);
        if ($slug !== '') {
            $lines[] = 'Catalog topic: '.$slug;
        }
        if ($category !== '') {
            $lines[] = 'Category: '.$category;
        }
        if ($titleKey !== '') {
            $lines[] = 'Title key: '.$titleKey;
        }
        if ($summaryKey !== '') {
            $lines[] = 'Summary key: '.$summaryKey;
        }
        if ($tags !== []) {
            $lines[] = 'Tags: '.implode(', ', $tags);
        }

        $body = $this->normalizer->clean(implode("\n", $lines));

        return $body === '' ? null : $body;
    }
}
