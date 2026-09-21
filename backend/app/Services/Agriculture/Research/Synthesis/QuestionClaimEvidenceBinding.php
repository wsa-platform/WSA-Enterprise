<?php

namespace App\Services\Agriculture\Research\Synthesis;

/**
 * Phase-5 Unit A — binding of one validated evidence item onto a QuestionClaim.
 *
 * Consumes Phase-4 fields only (does not recompute relevance/directness/relation).
 */
final class QuestionClaimEvidenceBinding
{
    /**
     * @param  list<string>  $reasons
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public readonly string $questionClaimId,
        public readonly string $evidenceId,
        public readonly string $sourceId,
        public readonly ?string $sourceUrl,
        public readonly string $claimRelationship,
        public readonly ?string $directness,
        public readonly string $validationStatus,
        public readonly bool $hasConflict,
        public readonly array $reasons = [],
        public readonly array $metadata = [],
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'question_claim_id' => $this->questionClaimId,
            'evidence_id' => $this->evidenceId,
            'source_id' => $this->sourceId,
            'source_url' => $this->sourceUrl,
            'claim_relationship' => $this->claimRelationship,
            'directness' => $this->directness,
            'validation_status' => $this->validationStatus,
            'has_conflict' => $this->hasConflict,
            'reasons' => $this->reasons,
            'metadata' => $this->metadata,
        ];
    }
}
