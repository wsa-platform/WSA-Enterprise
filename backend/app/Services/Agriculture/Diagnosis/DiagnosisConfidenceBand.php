<?php

namespace App\Services\Agriculture\Diagnosis;

/**
 * Explainable confidence bands for candidate plant diagnoses.
 * Image-alone analysis never yields absolute certainty.
 */
final class DiagnosisConfidenceBand
{
    public const HIGH = 'high';

    public const MODERATE = 'moderate';

    public const LOW = 'low';

    public const INSUFFICIENT = 'insufficient';

    /** Hard ceiling: vision-only evidence must never claim 100% certainty. */
    public const MAX_IMAGE_ALONE_SCORE = 0.92;

    /** @return list<string> */
    public static function all(): array
    {
        return [
            self::HIGH,
            self::MODERATE,
            self::LOW,
            self::INSUFFICIENT,
        ];
    }

    public static function fromScore(float $score): string
    {
        $clamped = max(0.0, min(self::MAX_IMAGE_ALONE_SCORE, $score));

        return match (true) {
            $clamped >= 0.75 => self::HIGH,
            $clamped >= 0.55 => self::MODERATE,
            $clamped >= 0.30 => self::LOW,
            default => self::INSUFFICIENT,
        };
    }

    public static function clampImageAloneScore(float $score): float
    {
        return max(0.0, min(self::MAX_IMAGE_ALONE_SCORE, $score));
    }
}
