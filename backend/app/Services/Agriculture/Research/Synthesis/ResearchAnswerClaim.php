<?php

namespace App\Services\Agriculture\Research\Synthesis;

/**
 * Claim bound to validated evidence for Stage 5 synthesis.
 */
final class ResearchAnswerClaim
{
    /**
     * @param  list<string>  $evidenceIds
     * @param  list<string>  $sourceIds
     * @param  list<string>  $numericalValues
     * @param  list<string>  $limitations
     */
    public function __construct(
        public readonly string $claimId,
        public readonly string $claimText,
        public readonly array $evidenceIds,
        public readonly array $sourceIds,
        public readonly string $validationStatus,
        public readonly string $claimRelationship,
        public readonly float $confidence,
        public readonly array $numericalValues = [],
        public readonly array $limitations = [],
        public readonly ?string $conditions = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'claim_id' => $this->claimId,
            'claim_text' => $this->claimText,
            'evidence_ids' => $this->evidenceIds,
            'source_ids' => $this->sourceIds,
            'validation_status' => $this->validationStatus,
            'claim_relationship' => $this->claimRelationship,
            'confidence' => $this->confidence,
            'numerical_values' => $this->numericalValues,
            'limitations' => $this->limitations,
            'conditions' => $this->conditions,
        ];
    }
}
