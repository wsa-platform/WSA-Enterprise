<?php

namespace App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat;

/**
 * In-memory consecutive-failure breaker. Does not persist. Does not trip on auth/config errors.
 */
final class FaoStatCircuitBreaker
{
    private int $consecutiveFailures = 0;

    private ?int $openedAtUnix = null;

    public function reset(): void
    {
        $this->consecutiveFailures = 0;
        $this->openedAtUnix = null;
    }

    public function isOpen(): bool
    {
        if ($this->openedAtUnix === null) {
            return false;
        }
        if (time() >= $this->openedAtUnix + $this->openSeconds()) {
            $this->reset();

            return false;
        }

        return true;
    }

    public function recordSuccess(): void
    {
        $this->reset();
    }

    public function recordFailure(string $category): void
    {
        if (! in_array($category, [
            FaoStatErrorCategory::NETWORK_ERROR,
            FaoStatErrorCategory::TIMEOUT,
            FaoStatErrorCategory::UPSTREAM_SERVER_ERROR,
        ], true)) {
            return;
        }
        $this->consecutiveFailures++;
        if ($this->consecutiveFailures >= $this->failureThreshold()) {
            $this->openedAtUnix = time();
        }
    }

    public function consecutiveFailures(): int
    {
        return $this->consecutiveFailures;
    }

    private function failureThreshold(): int
    {
        return max(2, min(10, (int) config('agricultural_intelligence.faostat.circuit_failure_threshold', 5)));
    }

    private function openSeconds(): int
    {
        return max(5, min(120, (int) config('agricultural_intelligence.faostat.circuit_open_seconds', 30)));
    }
}
