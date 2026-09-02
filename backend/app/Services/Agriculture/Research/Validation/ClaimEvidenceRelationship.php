<?php

namespace App\Services\Agriculture\Research\Validation;

/**
 * Generic claim-to-evidence relationship for Stage 4.
 */
final class ClaimEvidenceRelationship
{
    public const SUPPORTED = 'supported';

    public const PARTIALLY_SUPPORTED = 'partially_supported';

    public const CONFLICTING = 'conflicting';

    public const INSUFFICIENT_EVIDENCE = 'insufficient_evidence';

    public const NOT_VALIDATED = 'not_validated';

    /** @return list<string> */
    public static function all(): array
    {
        return [
            self::SUPPORTED,
            self::PARTIALLY_SUPPORTED,
            self::CONFLICTING,
            self::INSUFFICIENT_EVIDENCE,
            self::NOT_VALIDATED,
        ];
    }
}
