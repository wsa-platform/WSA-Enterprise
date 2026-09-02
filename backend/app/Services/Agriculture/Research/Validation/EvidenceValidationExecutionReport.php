<?php

namespace App\Services\Agriculture\Research\Validation;

/**
 * Structured Stage 4 validation execution report.
 */
final class EvidenceValidationExecutionReport
{
    /**
     * @param  list<ScientificEvidenceItem>  $validatedEvidence
     * @param  list<ScientificEvidenceItem>  $rejectedEvidence
     * @param  list<string>  $validatorsUsed
     * @param  array<string, int>  $qualityDistribution
     * @param  array<string, mixed>  $searchSummary
     * @param  array<string, mixed>  $observability
     */
    public function __construct(
        public readonly string $status,
        public readonly array $validatedEvidence,
        public readonly array $rejectedEvidence,
        public readonly int $sourcesReceived,
        public readonly int $validatedCount,
        public readonly int $rejectedCount,
        public readonly int $duplicateCount,
        public readonly int $conflictingCount,
        public readonly bool $evidenceSufficient,
        public readonly array $validatorsUsed,
        public readonly array $qualityDistribution,
        public readonly array $searchSummary,
        public readonly array $observability,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'stage' => 4,
            'validation' => [
                'performed' => true,
                'stage' => 4,
                'sources_received' => $this->sourcesReceived,
                'validated_count' => $this->validatedCount,
                'rejected_count' => $this->rejectedCount,
                'duplicate_count' => $this->duplicateCount,
                'conflicting_count' => $this->conflictingCount,
                'evidence_sufficient' => $this->evidenceSufficient,
                'validators_used' => $this->validatorsUsed,
                'quality_distribution' => $this->qualityDistribution,
            ],
            'validated_evidence' => array_map(
                static fn (ScientificEvidenceItem $item): array => $item->toArray(),
                $this->validatedEvidence,
            ),
            'rejected_evidence' => array_map(
                static fn (ScientificEvidenceItem $item): array => $item->toArray(),
                $this->rejectedEvidence,
            ),
            'observability' => $this->observability,
            'search_summary' => $this->searchSummary,
            'synthesis' => [
                'performed' => false,
                'reason' => 'stage_4_validation_only',
            ],
            'library_persistence' => [
                'performed' => false,
                'reason' => 'stage_4_validation_only',
            ],
        ];
    }
}
