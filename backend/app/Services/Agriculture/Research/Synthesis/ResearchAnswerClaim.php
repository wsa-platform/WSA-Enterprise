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
     * @param  list<string>  $sourceKeys  Runtime source_key values that actually support this claim
     * @param  list<array<string, mixed>>  $attributions  Claim→Evidence→Source→Citation rows
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
        public readonly ?string $questionClaimId = null,
        public readonly array $sourceKeys = [],
        public readonly array $attributions = [],
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'claim_id' => $this->claimId,
            'claim_text' => $this->claimText,
            'evidence_ids' => $this->evidenceIds,
            'source_ids' => $this->sourceIds,
            'source_keys' => $this->sourceKeys,
            'attributions' => $this->attributions,
            'validation_status' => $this->validationStatus,
            'claim_relationship' => $this->claimRelationship,
            'confidence' => $this->confidence,
            'numerical_values' => $this->numericalValues,
            'limitations' => $this->limitations,
            'conditions' => $this->conditions,
            'question_claim_id' => $this->questionClaimId,
        ];
    }
}
