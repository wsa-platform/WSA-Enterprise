<?php

namespace App\Services\Agriculture\Diagnosis\KnowledgeBase;

/**
 * Safety posture for diagnosis knowledge matches (Stage 7).
 */
final class DiagnosisKnowledgeSafetyStatus
{
    public const SAFE = 'safe';

    public const CAUTION = 'caution';

    public const INSUFFICIENT_EVIDENCE = 'insufficient_evidence';

    public const HUMAN_REVIEW_REQUIRED = 'human_review_required';

    /** @return list<string> */
    public static function all(): array
    {
        return [
            self::SAFE,
            self::CAUTION,
            self::INSUFFICIENT_EVIDENCE,
            self::HUMAN_REVIEW_REQUIRED,
        ];
    }

    public static function isValid(string $value): bool
    {
        return in_array($value, self::all(), true);
    }
}
