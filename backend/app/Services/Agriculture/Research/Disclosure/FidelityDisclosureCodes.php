<?php

namespace App\Services\Agriculture\Research\Disclosure;

use App\Services\Agriculture\Research\Projection\ProjectionFidelityClass;

/**
 * IU-08 B8 semantic disclosure codes — machine-readable; not UI wording.
 *
 * Meanings are immutable for schema_version 1.
 */
final class FidelityDisclosureCodes
{
    public const AGGREGATE_NON_EXACT = 'C9_AGGREGATE_NON_EXACT';

    public const IDENTITY_CRITICAL_LOSS = 'C9_IDENTITY_CRITICAL_LOSS';

    public const NON_IDENTITY_FACET_LOSS = 'C9_NON_IDENTITY_FACET_LOSS';

    /** @var list<string> */
    public const ALL = [
        self::AGGREGATE_NON_EXACT,
        self::IDENTITY_CRITICAL_LOSS,
        self::NON_IDENTITY_FACET_LOSS,
    ];

    public static function isKnown(string $code): bool
    {
        return in_array($code, self::ALL, true);
    }

    public static function assertKnown(string $code): void
    {
        if (! self::isKnown($code)) {
            throw new FidelityDisclosureInvariantViolation(
                "Unknown C9 fidelity disclosure code [{$code}]; only OD-01 closed codes are allowed."
            );
        }
    }

    public static function assertNonExactClass(ProjectionFidelityClass $class): void
    {
        if ($class === ProjectionFidelityClass::EXACT) {
            throw new FidelityDisclosureInvariantViolation(
                'EXACT must not be emitted as a non-EXACT fidelity disclosure record.'
            );
        }
    }
}
