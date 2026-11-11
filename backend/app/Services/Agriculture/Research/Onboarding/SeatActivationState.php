<?php

namespace App\Services\Agriculture\Research\Onboarding;

/**
 * D-10 seat activation_state (ADR-023 §8.11.3, §8.11.4).
 *
 * Its own namespace: not Path eligibility, not PathStatus, not a Cap v2 capability
 * state, and not the FAOSTAT runtime activation_state.
 */
enum SeatActivationState: string
{
    case NOT_READY = 'NOT_READY';
    case UNVERIFIED = 'UNVERIFIED';
    case CONDITIONALLY_ELIGIBLE = 'CONDITIONALLY_ELIGIBLE';
    case ELIGIBLE = 'ELIGIBLE';
    case ACTIVE = 'ACTIVE';
    case SUSPENDED = 'SUSPENDED';
    case DEFERRED = 'DEFERRED';

    /** States that claim the seat may be (or is) served at runtime. */
    public function claimsEligibility(): bool
    {
        return match ($this) {
            self::CONDITIONALLY_ELIGIBLE, self::ELIGIBLE, self::ACTIVE => true,
            default => false,
        };
    }
}
