<?php

namespace App\Services\Agriculture\Research\Onboarding;

/**
 * How well one onboarding dimension of a seat is evidenced.
 *
 * A reconciliation vocabulary, not a Cap v2 capability state: documentary evidence can
 * at most make a dimension PARTIALLY_EVIDENCED. VERIFIED needs a verification that the
 * owning authority recorded (identity binding, endpoint test, accepted licence).
 */
enum OnboardingEvidenceStatus: string
{
    case VERIFIED = 'VERIFIED';
    case PARTIALLY_EVIDENCED = 'PARTIALLY_EVIDENCED';
    case UNVERIFIED = 'UNVERIFIED';
    case UNAVAILABLE = 'UNAVAILABLE';
    case BLOCKED = 'BLOCKED';
}
