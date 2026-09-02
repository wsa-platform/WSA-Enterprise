<?php

namespace App\Services\Agriculture\Research\Validation;

/**
 * Explicit Stage 4 evidence validation status progression.
 */
final class EvidenceValidationStatus
{
    public const DISCOVERED = 'discovered';

    public const METADATA_VALID = 'metadata_valid';

    public const SOURCE_IDENTITY_VALID = 'source_identity_valid';

    public const SCIENTIFICALLY_TRUSTWORTHY = 'scientifically_trustworthy';

    public const EVIDENCE_USABLE = 'evidence_usable';

    public const REJECTED = 'rejected';

    /** @return list<string> */
    public static function all(): array
    {
        return [
            self::DISCOVERED,
            self::METADATA_VALID,
            self::SOURCE_IDENTITY_VALID,
            self::SCIENTIFICALLY_TRUSTWORTHY,
            self::EVIDENCE_USABLE,
            self::REJECTED,
        ];
    }

    public static function isTerminal(string $status): bool
    {
        return in_array($status, [self::EVIDENCE_USABLE, self::REJECTED], true);
    }
}
