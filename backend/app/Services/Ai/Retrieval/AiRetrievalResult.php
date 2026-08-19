<?php

namespace App\Services\Ai\Retrieval;

final class AiRetrievalResult
{
    /** @param  list<AiRetrievalHit>  $hits */
    public function __construct(
        public readonly array $hits,
        public readonly string $context,
    ) {}

    public function isEmpty(): bool
    {
        return $this->hits === [];
    }

    /** @return list<array<string, mixed>> */
    public function citations(): array
    {
        return array_map(static fn (AiRetrievalHit $hit) => $hit->toCitation(), $this->hits);
    }

    public static function empty(): self
    {
        return new self([], '');
    }
}
