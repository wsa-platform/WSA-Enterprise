<?php

namespace App\Services\Agriculture\Research\Capability;

/**
 * Cap v2 capability states — FROZEN.
 *
 * STALE / LIVE / FULL / COMPLETE / SUPPORTED / UNKNOWN are not Cap v2 states.
 * GO-1 SUPPORTED/UNKNOWN remain historical (see HistoricalGo1CapabilityState).
 */
enum CapabilityState: string
{
    case VERIFIED = 'VERIFIED';
    case PARTIAL = 'PARTIAL';
    case UNVERIFIED = 'UNVERIFIED';
    case UNAVAILABLE = 'UNAVAILABLE';
    case NOT_APPLICABLE = 'NOT_APPLICABLE';

    /**
     * Restrictiveness for mandatory AND / seat-override conflicts.
     * Higher = more restrictive for verified automation eligibility inputs.
     * NOT_APPLICABLE is not ranked here — it is handled separately.
     */
    public function restrictivenessRank(): int
    {
        return match ($this) {
            self::VERIFIED => 0,
            self::PARTIAL => 1,
            self::UNVERIFIED => 2,
            self::UNAVAILABLE => 3,
            self::NOT_APPLICABLE => -1,
        };
    }

    /**
     * Seat override vs shared cell: more restrictive state wins (Cap Design §6).
     */
    public static function moreRestrictive(self $a, self $b): self
    {
        if ($a === self::NOT_APPLICABLE) {
            return $b;
        }
        if ($b === self::NOT_APPLICABLE) {
            return $a;
        }

        return $a->restrictivenessRank() >= $b->restrictivenessRank() ? $a : $b;
    }

    public function maySatisfyVerifiedAutomation(): bool
    {
        return $this === self::VERIFIED;
    }

    public function maySupportConditionalOnly(): bool
    {
        return $this === self::PARTIAL;
    }

    public function cannotProduceVerifiedAutomation(): bool
    {
        return $this === self::UNVERIFIED || $this === self::UNAVAILABLE;
    }
}
