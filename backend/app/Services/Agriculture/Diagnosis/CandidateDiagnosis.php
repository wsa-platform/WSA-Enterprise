<?php

namespace App\Services\Agriculture\Diagnosis;

/**
 * Ranked candidate diagnosis with explainable confidence.
 */
final class CandidateDiagnosis
{
    /**
     * @param  list<DiagnosisEvidence>  $evidence
     * @param  list<string>  $differentialNotes
     */
    public function __construct(
        public readonly string $id,
        public readonly string $commonName,
        public readonly ?string $scientificName,
        public readonly bool $scientificNameVerified,
        public readonly float $confidenceScore,
        public readonly string $confidenceBand,
        public readonly int $rank,
        public readonly string $rationale,
        public readonly array $evidence = [],
        public readonly array $differentialNotes = [],
        public readonly ?string $category = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'common_name' => $this->commonName,
            'scientific_name' => $this->scientificNameVerified ? $this->scientificName : null,
            'scientific_name_verified' => $this->scientificNameVerified,
            'confidence_score' => round($this->confidenceScore, 4),
            'confidence_band' => $this->confidenceBand,
            'rank' => $this->rank,
            'rationale' => $this->rationale,
            'evidence' => array_map(
                static fn (DiagnosisEvidence $item): array => $item->toArray(),
                $this->evidence,
            ),
            'differential_notes' => $this->differentialNotes,
            'category' => $this->category,
        ];
    }
}
