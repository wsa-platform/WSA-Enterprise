<?php

namespace App\Services\Agriculture\Research\Capability;

/**
 * GO-1 historical capability vocabulary from SOURCE-CAPABILITY-CONTRACT.md.
 *
 * HISTORICAL ONLY — must never be treated as Cap v2 via silent equivalence.
 * There is intentionally no toCapV2() / fromCapV2() mapper on this type.
 */
enum HistoricalGo1CapabilityState: string
{
    case SUPPORTED = 'SUPPORTED';
    case PARTIALLY_SUPPORTED = 'PARTIALLY_SUPPORTED';
    case UNSUPPORTED = 'UNSUPPORTED';
    case UNKNOWN = 'UNKNOWN';
}
