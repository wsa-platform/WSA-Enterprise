<?php

namespace App\Services\Ai\Retrieval;

use DateTimeInterface;
use Illuminate\Support\Carbon;

/**
 * Operational freshness classification for AI-09.
 *
 * Ranking still uses KnowledgeRanker (AI-08). Freshness remains a 0–2 point
 * bounded signal and never outranks a title token. Missing timestamps are
 * unknown (ranking score 0). Callers should pass $now in tests.
 */
class KnowledgeFreshnessService
{
    public const FRESH = 'fresh';

    public const STALE = 'stale';

    public const UNKNOWN = 'unknown';

    public const RANKING_MAX = 2.0;

    public function staleAfterDays(): int
    {
        return max(1, (int) config('ai.retrieval.freshness_stale_after_days', 90));
    }

    public function classify(?DateTimeInterface $updatedAt, ?DateTimeInterface $now = null): string
    {
        if ($updatedAt === null) {
            return self::UNKNOWN;
        }

        $now = $this->moment($now);
        $days = $this->ageInDays($updatedAt, $now);

        return $days >= $this->staleAfterDays() ? self::STALE : self::FRESH;
    }

    /**
     * Documents the AI-08 ranking formula. KnowledgeRanker remains the scorer.
     */
    public function rankingScore(?DateTimeInterface $updatedAt, ?DateTimeInterface $now = null): float
    {
        if ($updatedAt === null) {
            return 0.0;
        }

        $now = $this->moment($now);
        $days = $this->ageInDays($updatedAt, $now);
        $ratio = 1 - (min($days, 365) / 365);

        return round(self::RANKING_MAX * max(0, $ratio), 4);
    }

    /**
     * @param  iterable<int, ?DateTimeInterface>  $timestamps
     * @return array{fresh: int, stale: int, unknown: int}
     */
    public function distribution(iterable $timestamps, ?DateTimeInterface $now = null): array
    {
        $counts = [self::FRESH => 0, self::STALE => 0, self::UNKNOWN => 0];
        $now = $this->moment($now);
        foreach ($timestamps as $timestamp) {
            $state = $this->classify($timestamp instanceof DateTimeInterface ? $timestamp : null, $now);
            $counts[$state]++;
        }

        return $counts;
    }

    private function ageInDays(DateTimeInterface $updatedAt, DateTimeInterface $now): int
    {
        $seconds = abs($this->moment($now)->getTimestamp() - Carbon::parse($updatedAt)->getTimestamp());

        return (int) floor($seconds / 86400);
    }

    private function moment(?DateTimeInterface $now): Carbon
    {
        return Carbon::parse($now ?? now());
    }
}
