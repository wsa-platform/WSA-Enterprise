<?php

namespace App\Services\Ai\Rag;

use App\Services\Ai\Retrieval\AiRetrievalHit;

final class RagResult
{
    /**
     * @param  list<AiRetrievalHit>  $hits
     * @param  list<array<string, mixed>>  $citations
     * @param  array<string, mixed>  $telemetry
     */
    public function __construct(
        public readonly array $hits,
        public readonly string $context,
        public readonly array $citations,
        public readonly array $telemetry = [],
        public readonly bool $failed = false,
    ) {}

    public function isEmpty(): bool
    {
        return $this->hits === [] || $this->citations === [];
    }
}
