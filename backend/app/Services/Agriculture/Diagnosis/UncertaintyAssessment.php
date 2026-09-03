<?php

namespace App\Services\Agriculture\Diagnosis;

/**
 * Structured uncertainty assessment driving additional-info questions.
 */
final class UncertaintyAssessment
{
    /**
     * @param  list<string>  $factors
     * @param  list<string>  $missingSignals
     */
    public function __construct(
        public readonly float $overallUncertainty,
        public readonly array $factors = [],
        public readonly array $missingSignals = [],
        public readonly bool $imageQualityLimited = false,
        public readonly bool $contextLimited = false,
        public readonly bool $differentialAmbiguity = false,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'overall_uncertainty' => round($this->overallUncertainty, 4),
            'factors' => $this->factors,
            'missing_signals' => $this->missingSignals,
            'image_quality_limited' => $this->imageQualityLimited,
            'context_limited' => $this->contextLimited,
            'differential_ambiguity' => $this->differentialAmbiguity,
        ];
    }
}
