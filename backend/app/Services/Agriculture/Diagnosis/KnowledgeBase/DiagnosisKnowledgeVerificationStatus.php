<?php

namespace App\Services\Agriculture\Diagnosis\KnowledgeBase;

/**
 * Separates RAW/UNVERIFIED ingestion from VERIFIED diagnosis knowledge.
 */
final class DiagnosisKnowledgeVerificationStatus
{
    public const RAW_UNVERIFIED = 'raw_unverified';

    public const VERIFIED = 'verified';

    /** @return list<string> */
    public static function all(): array
    {
        return [
            self::RAW_UNVERIFIED,
            self::VERIFIED,
        ];
    }

    public static function isValid(string $value): bool
    {
        return in_array($value, self::all(), true);
    }
}
