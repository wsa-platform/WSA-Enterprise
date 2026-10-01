<?php

namespace App\Services\Agriculture\Research\Projection;

/**
 * Per-facet representation disposition in a Projection (not Cap state, not R6).
 */
enum ProjectionFacetDisposition: string
{
    case MAPPED = 'MAPPED';
    case EXPANDED = 'EXPANDED';
    case COMPRESSED = 'COMPRESSED';
    case APPROXIMATED = 'APPROXIMATED';
    case UNSUPPORTED = 'UNSUPPORTED';
    case OMITTED = 'OMITTED';
    case UNRESOLVED = 'UNRESOLVED';

    public function toFidelityContribution(): ProjectionFidelityClass
    {
        return match ($this) {
            self::MAPPED => ProjectionFidelityClass::EXACT,
            self::EXPANDED => ProjectionFidelityClass::EXACT, // only when exactEquivalence flagged on record
            self::COMPRESSED => ProjectionFidelityClass::COMPRESSED,
            self::APPROXIMATED => ProjectionFidelityClass::APPROXIMATED,
            self::UNSUPPORTED => ProjectionFidelityClass::UNSUPPORTED,
            self::OMITTED => ProjectionFidelityClass::OMITTED,
            self::UNRESOLVED => ProjectionFidelityClass::UNRESOLVED,
        };
    }

    public function isExactSafeDefault(): bool
    {
        return $this === self::MAPPED;
    }
}
