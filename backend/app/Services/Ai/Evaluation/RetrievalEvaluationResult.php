<?php

namespace App\Services\Ai\Evaluation;

final class RetrievalEvaluationResult
{
    /**
     * @param  list<string>  $retrievedIds
     * @param  list<string>  $expectedIds
     * @param  array{k: int, precision: float, recall: float, f1: float, hit: float, mrr: float}  $metrics
     * @param  array<string, mixed>  $telemetry
     */
    public function __construct(
        public readonly string $caseId,
        public readonly string $configuredStrategy,
        public readonly string $effectiveStrategy,
        public readonly int $k,
        public readonly array $retrievedIds,
        public readonly array $expectedIds,
        public readonly array $metrics,
        public readonly ?string $fallbackReason = null,
        public readonly ?string $expectedTopId = null,
        public readonly bool $expectedTopMatched = false,
        public readonly array $telemetry = [],
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'case' => $this->caseId,
            'strategy' => $this->configuredStrategy,
            'effective_strategy' => $this->effectiveStrategy,
            'k' => $this->k,
            'retrieved_ids' => $this->retrievedIds,
            'expected_ids' => $this->expectedIds,
            'precision' => $this->metrics['precision'],
            'recall' => $this->metrics['recall'],
            'f1' => $this->metrics['f1'],
            'hit' => $this->metrics['hit'],
            'mrr' => $this->metrics['mrr'],
            'fallback_reason' => $this->fallbackReason,
            'expected_top_id' => $this->expectedTopId,
            'expected_top_matched' => $this->expectedTopMatched,
        ];
    }
}
