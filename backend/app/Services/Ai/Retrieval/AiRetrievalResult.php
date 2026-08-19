<?php

namespace App\Services\Ai\Retrieval;

final class AiRetrievalResult
{
    /**
     * @param  list<AiRetrievalHit>  $hits
     * @param  array<string, mixed>  $telemetry
     */
    public function __construct(
        public readonly array $hits,
        public readonly string $context,
        public readonly array $telemetry = [],
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

    /** @param  array<string, mixed>  $telemetry */
    public static function empty(array $telemetry = []): self
    {
        return new self([], '', array_merge([
            'candidate_count' => 0,
            'returned_count' => 0,
            'retrieval_duration_ms' => 0,
            'source_types' => [],
            'freshness_distribution' => ['fresh' => 0, 'stale' => 0, 'unknown' => 0],
            'retrieval_status' => 'empty',
        ], $telemetry));
    }
}
