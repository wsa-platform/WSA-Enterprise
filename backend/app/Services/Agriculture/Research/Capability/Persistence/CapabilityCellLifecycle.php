<?php

namespace App\Services\Agriculture\Research\Capability\Persistence;

/**
 * Cap-native cell fact lifecycle (ADR-023 §8.16.8 CPD-B).
 *
 * Not IC ACTIVE/SUPERSEDED. CURRENT is explicit — never derived from timestamps.
 */
enum CapabilityCellLifecycle: string
{
    case CURRENT = 'CURRENT';
    case HISTORICAL = 'HISTORICAL';
}
