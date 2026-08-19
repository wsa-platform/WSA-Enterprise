<?php

namespace App\Services\Ai\Retrieval;

final class AiRetrievalHit
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public readonly string $sourceType,
        public readonly int $sourceId,
        public readonly string $title,
        public readonly string $content,
        public readonly float $score,
        public readonly array $metadata = [],
    ) {}

    /** @return array<string, mixed> */
    public function toCitation(): array
    {
        return [
            'title' => $this->title,
            'reference' => $this->sourceType.':'.$this->sourceId,
            'source_type' => $this->sourceType,
            'source_id' => $this->sourceId,
        ];
    }
}
