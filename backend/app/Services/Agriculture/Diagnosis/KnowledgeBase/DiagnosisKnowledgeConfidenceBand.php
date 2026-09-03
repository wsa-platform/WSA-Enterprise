<?php

namespace App\Services\Agriculture\Diagnosis\KnowledgeBase;

use App\Services\Agriculture\Diagnosis\DiagnosisConfidenceBand;

/**
 * Stage 7 knowledge confidence bands.
 * Maps onto Stage 6 DiagnosisConfidenceBand for CandidateDiagnosis output.
 */
final class DiagnosisKnowledgeConfidenceBand
{
    public const VERY_LOW = 'very_low';

    public const LOW = 'low';

    public const MODERATE = 'moderate';

    public const HIGH = 'high';

    /** @return list<string> */
    public static function all(): array
    {
        return [
            self::VERY_LOW,
            self::LOW,
            self::MODERATE,
            self::HIGH,
        ];
    }

    public static function isValid(string $value): bool
    {
        return in_array($value, self::all(), true);
    }

    public static function fromScore(float $score): string
    {
        $clamped = DiagnosisConfidenceBand::clampImageAloneScore($score);

        return match (true) {
            $clamped >= 0.75 => self::HIGH,
            $clamped >= 0.55 => self::MODERATE,
            $clamped >= 0.30 => self::LOW,
            default => self::VERY_LOW,
        };
    }

    /**
     * Map Stage 7 bands to Stage 6 CandidateDiagnosis confidence bands.
     */
    public static function toStage6Band(string $band): string
    {
        return match ($band) {
            self::HIGH => DiagnosisConfidenceBand::HIGH,
            self::MODERATE => DiagnosisConfidenceBand::MODERATE,
            self::LOW => DiagnosisConfidenceBand::LOW,
            default => DiagnosisConfidenceBand::INSUFFICIENT,
        };
    }
}
