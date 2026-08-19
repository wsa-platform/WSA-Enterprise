<?php

namespace App\Services\Ai\Retrieval;

use DateTimeInterface;

final class AiKnowledgeDocument
{
    /**
     * Normalized knowledge record for retrieval. Never includes prompts, API keys, or URLs.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public readonly string $sourceType,
        public readonly int $sourceId,
        public readonly ?int $organizationId,
        public readonly string $title,
        public readonly string $summary,
        public readonly string $body,
        public readonly string $searchableText,
        public readonly ?DateTimeInterface $updatedAt,
        public readonly bool $visible,
        public readonly array $metadata = [],
    ) {}
}
