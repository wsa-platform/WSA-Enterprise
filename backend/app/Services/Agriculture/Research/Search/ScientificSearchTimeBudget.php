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

        return new self(hrtime(true) + ($seconds * 1_000_000_000), $seconds);
    }

    public static function configuredSeconds(): int
    {
        return max(8, min(90, (int) config('agricultural_intelligence.search_time_budget_seconds', 45)));
    }

    public function remaining(): bool
    {
        return hrtime(true) < $this->deadlineHrtime;
    }
}
