<?php

namespace App\Services\Agriculture\Research\Capability;

/**
 * Evidence freshness metadata (B4) — NOT a Cap v2 state.
 *
 * STALE must never auto-promote capability_state to VERIFIED.
 */
enum CapabilityEvidenceFreshness: string
{
    case CURRENT = 'CURRENT';
    case STALE = 'STALE';
    case UNKNOWN = 'UNKNOWN';
}
