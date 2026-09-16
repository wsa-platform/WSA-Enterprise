<?php

namespace App\Services\Agriculture\Research\Search;

/**
 * Bounded Stage 3 wall-clock budget. Independent of nginx FastCGI timeout.
 */
final class ScientificSearchTimeBudget
{
    public function __construct(
        private readonly int $deadlineHrtime,
        public readonly int $seconds,
    ) {}

    public static function start(): self
    {
        $seconds = self::configuredSeconds();
        $budget = new self(hrtime(true) + ($seconds * 1_000_000_000), $seconds);
        $budget->bind();

        return $budget;
    }

    public static function configuredSeconds(): int
    {
        return max(8, min(90, (int) config('agricultural_intelligence.search_time_budget_seconds', 45)));
    }

    public function bind(): void
    {
        app()->instance('scientific.search_time_budget', $this);
    }

    public static function current(): ?self
    {
        if (! app()->bound('scientific.search_time_budget')) {
            return null;
        }

        $budget = app('scientific.search_time_budget');

        return $budget instanceof self ? $budget : null;
    }

    public function remaining(): bool
    {
        return $this->remainingSeconds() > 0.0;
    }

    public function remainingSeconds(): float
    {
        $leftNs = $this->deadlineHrtime - hrtime(true);
        if ($leftNs <= 0) {
            return 0.0;
        }

        return $leftNs / 1_000_000_000;
    }
}
