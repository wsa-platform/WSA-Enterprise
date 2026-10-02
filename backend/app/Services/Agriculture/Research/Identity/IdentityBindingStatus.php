<?php

namespace App\Services\Agriculture\Research\Identity;

/**
 * FS-01-ID identity binding decision status (persistence/domain vocabulary).
 *
 * Not Cap v2 state. Not D-10 activation_state. Not Path eligibility/status.
 * Not Membership membership_status.
 */
enum IdentityBindingStatus: string
{
    case CANDIDATE = 'CANDIDATE';
    case BOUND = 'BOUND';
    case UNBOUND = 'UNBOUND';
    case CONFLICT = 'CONFLICT';
    case DEFERRED = 'DEFERRED';
}
