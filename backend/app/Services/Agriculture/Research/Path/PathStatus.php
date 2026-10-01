<?php

namespace App\Services\Agriculture\Research\Path;

/**
 * B1 — Path lifecycle status (orthogonal to eligibility_state).
 *
 * Not Cap state. Not HTTP/execution outcome (SUCCESS/TIMEOUT/EMPTY_RESULT).
 */
enum PathStatus: string
{
    case SELECTED = 'SELECTED';
    case CONDITIONALLY_SELECTED = 'CONDITIONALLY_SELECTED';
    case DEFERRED_PENDING_CAPABILITY = 'DEFERRED_PENDING_CAPABILITY';
}
