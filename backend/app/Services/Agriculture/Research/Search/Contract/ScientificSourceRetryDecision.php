<?php

namespace App\Services\Agriculture\Research\Search\Contract;

final readonly class ScientificSourceRetryDecision
{
    public const REASON_RETRYABLE = 'retryable';

    public const REASON_NOT_RETRYABLE = 'not_retryable';

    public const REASON_ATTEMPTS_EXHAUSTED = 'attempts_exhausted';

    public const REASON_RETRY_AFTER_TOO_LONG = 'retry_after_exceeds_max_delay';

    public const REASON_INSUFFICIENT_BUDGET = 'insufficient_budget';

    private function __construct(
        public bool $retry,
        public int $delayMs,
        public string $reason,
    ) {}

    public static function retryAfter(int $delayMs): self
    {
        return new self(true, $delayMs, self::REASON_RETRYABLE);
    }

    public static function stop(string $reason): self
    {
        return new self(false, 0, $reason);
    }

    /** @return array{retry: bool, delay_ms: int, reason: string} */
    public function toArray(): array
    {
        return ['retry' => $this->retry, 'delay_ms' => $this->delayMs, 'reason' => $this->reason];
    }
}
