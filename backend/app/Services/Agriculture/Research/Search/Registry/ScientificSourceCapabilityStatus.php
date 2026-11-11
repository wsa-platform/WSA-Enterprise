<?php

namespace App\Services\Agriculture\Research\Search\Registry;

/**
 * Status of one runtime capability declaration (ADR-023 §8.35).
 *
 * Only VERIFIED_SUPPORTED with unexpired evidence, and the owner-approved legacy operational
 * basis for search, authorize an operation. Every other status denies it.
 * These are runtime-registration statuses, not Cap v2 states (which stay seat-scoped).
 */
enum ScientificSourceCapabilityStatus: string
{
    case VERIFIED_SUPPORTED = 'verified_supported';
    case VERIFIED_UNSUPPORTED = 'verified_unsupported';
    case NOT_VERIFIED = 'not_verified';
    case NOT_TESTED = 'not_tested';
    case TEMPORARILY_UNAVAILABLE = 'temporarily_unavailable';
    case ACCESS_DENIED = 'access_denied';
    case AUTHENTICATION_REQUIRED = 'authentication_required';
    case LICENCE_UNRESOLVED = 'licence_unresolved';
    case LEGACY_OPERATIONAL_BASIS = 'legacy_operational_basis';

    public function isVerified(): bool
    {
        return $this === self::VERIFIED_SUPPORTED || $this === self::VERIFIED_UNSUPPORTED;
    }
}
