<?php

namespace App\Services\Agriculture\Research\Capability;

/**
 * Aggregated AND evaluation class for Capability Decision (Design §20).
 *
 * Does not encode Path eligibility_state or Path status.
 */
enum CapabilityAndResultClass: string
{
    case ALL_VERIFIED = 'ALL_VERIFIED';
    case HAS_PARTIAL = 'HAS_PARTIAL';
    case HAS_UNVERIFIED = 'HAS_UNVERIFIED';
    case HAS_UNAVAILABLE = 'HAS_UNAVAILABLE';
}
