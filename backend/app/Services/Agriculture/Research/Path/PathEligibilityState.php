<?php

namespace App\Services\Agriculture\Research\Path;

/**
 * B1 — eligibility_state (orthogonal to Path status).
 *
 * Cap-derived permission for a route — not selection, not runtime success.
 */
enum PathEligibilityState: string
{
    case ELIGIBLE = 'ELIGIBLE';
    case CONDITIONAL = 'CONDITIONAL';
    case DEFERRED = 'DEFERRED';
    case INELIGIBLE = 'INELIGIBLE';
}
