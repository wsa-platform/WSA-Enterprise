<?php

namespace App\Services\Agriculture\Research\Projection;

use App\Services\Agriculture\Research\Capability\CapabilityEvidenceFreshness;
use App\Services\Agriculture\Research\Capability\CapabilityState;

/**
 * Per-facet query constraint derived from the facet's Cap `query_constrainable` cell (ADR-023 §8.33).
 *
 * Read-only mapping: assigns no Cap state and carries no query text. A NOT_CONSTRAINABLE
 * facet the Builder still places as plain lexical text is APPROXIMATED with a reason in the
 * facet accounting, never MAPPED.
 */
enum ProjectionFacetConstraintState: string
{
    case CONSTRAINABLE = 'CONSTRAINABLE';
    case NOT_CONSTRAINABLE = 'NOT_CONSTRAINABLE';
    case UNKNOWN = 'UNKNOWN';

    /**
     * VERIFIED is CONSTRAINABLE only with evidence freshness established as CURRENT (§8.33.8, M-1);
     * STALE, UNKNOWN or unestablished freshness (null) yields UNKNOWN. Freshness never changes
     * the mapping of any other state.
     *
     * @param  CapabilityState|null  $state  null when the capability decision resolves no Cap fact (MISSING)
     */
    public static function fromCapability(?CapabilityState $state, ?CapabilityEvidenceFreshness $evidenceFreshness): self
    {
        return match ($state) {
            CapabilityState::VERIFIED => $evidenceFreshness === CapabilityEvidenceFreshness::CURRENT
                ? self::CONSTRAINABLE
                : self::UNKNOWN,
            CapabilityState::UNAVAILABLE, CapabilityState::NOT_APPLICABLE => self::NOT_CONSTRAINABLE,
            CapabilityState::UNVERIFIED, CapabilityState::PARTIAL, null => self::UNKNOWN,
        };
    }
}
