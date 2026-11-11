<?php

namespace App\Services\Agriculture\Research\Search\Contract;

/**
 * Declared retry policy of an adapter (adapter contract v2, ADR-023 §8.36).
 *
 * Deterministic (no jitter) so that decisions are reproducible in tests and observability.
 * A retry is never planned past the remaining search budget.
 */
final readonly class ScientificSourceRetryPolicy
{
    /** Seconds kept free after the delay for the retried request itself. */
    public const MIN_SECONDS_FOR_RETRIED_REQUEST = 1.0;

    public function __construct(
        public int $maxAttempts,
        public int $baseDelayMs,
        public int $maxDelayMs,
    ) {
        if ($maxAttempts < 1) {
            throw new ScientificSourceContractViolation("Retry policy maxAttempts must be at least 1; got {$maxAttempts}.");
        }
        if ($baseDelayMs < 0 || $maxDelayMs < $baseDelayMs) {
            throw new ScientificSourceContractViolation(
                "Retry policy delays must satisfy 0 <= baseDelayMs <= maxDelayMs; got {$baseDelayMs} and {$maxDelayMs}."
            );
        }
    }

    /** One attempt only: the behaviour of every v1 adapter at the orchestrator level. */
    public static function none(): self
    {
        return new self(1, 0, 0);
    }

    public function decide(
        ScientificSourceFailureCategory $category,
        int $attemptsMade,
        ?float $retryAfterSeconds = null,
        ?float $remainingBudgetSeconds = null,
    ): ScientificSourceRetryDecision {
        if (! $category->isRetryable()) {
            return ScientificSourceRetryDecision::stop(ScientificSourceRetryDecision::REASON_NOT_RETRYABLE);
        }
        if ($attemptsMade >= $this->maxAttempts) {
            return ScientificSourceRetryDecision::stop(ScientificSourceRetryDecision::REASON_ATTEMPTS_EXHAUSTED);
        }

        if ($retryAfterSeconds !== null && $retryAfterSeconds >= 0) {
            $delayMs = (int) ceil($retryAfterSeconds * 1000);
            if ($delayMs > $this->maxDelayMs) {
                return ScientificSourceRetryDecision::stop(ScientificSourceRetryDecision::REASON_RETRY_AFTER_TOO_LONG);
            }
        } else {
            $delayMs = min($this->maxDelayMs, $this->baseDelayMs * (2 ** max(0, $attemptsMade - 1)));
        }

        if ($remainingBudgetSeconds !== null
            && $delayMs / 1000 + self::MIN_SECONDS_FOR_RETRIED_REQUEST > $remainingBudgetSeconds) {
            return ScientificSourceRetryDecision::stop(ScientificSourceRetryDecision::REASON_INSUFFICIENT_BUDGET);
        }

        return ScientificSourceRetryDecision::retryAfter($delayMs);
    }

    /** @return array{max_attempts: int, base_delay_ms: int, max_delay_ms: int} */
    public function toArray(): array
    {
        return [
            'max_attempts' => $this->maxAttempts,
            'base_delay_ms' => $this->baseDelayMs,
            'max_delay_ms' => $this->maxDelayMs,
        ];
    }
}
