<?php

namespace App\Services\Agriculture\Research\Projection;

/**
 * C9 fidelity taxonomy — fidelity of source-native representation vs immutable CSQ.
 *
 * Not evidence quality, Directness, claim_relation, sufficiency, or confidence.
 */
enum ProjectionFidelityClass: string
{
    case EXACT = 'EXACT';
    case COMPRESSED = 'COMPRESSED';
    case APPROXIMATED = 'APPROXIMATED';
    case UNRESOLVED = 'UNRESOLVED';
    case OMITTED = 'OMITTED';
    case UNSUPPORTED = 'UNSUPPORTED';

    /**
     * Higher = worse material representation loss for aggregation.
     */
    public function lossRank(): int
    {
        return match ($this) {
            self::EXACT => 0,
            self::COMPRESSED => 1,
            self::APPROXIMATED => 2,
            self::UNRESOLVED => 3,
            self::OMITTED => 4,
            self::UNSUPPORTED => 5,
        };
    }

    public static function worst(self $a, self $b): self
    {
        return $a->lossRank() >= $b->lossRank() ? $a : $b;
    }
}
