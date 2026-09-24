<?php

namespace App\Services\Agriculture\Research\Synthesis;

/**
 * Phase-5 Unit A — answer-statement traceability to a QuestionClaim.
 *
 * Semantic/domain layer only — not a rendering system.
 */
final class AnswerStatementTrace
{
    /**
     * @param  list<string>  $evidenceIds
     * @param  list<string>  $sourceIds
     * @param  list<string>  $limitations
     * @param  list<string>  $accuracyOutcomes  B3 accuracy_* codes reported after the gate; empty when B3 did not reject. Does not change `status`.
     */
    public function __construct(
        public readonly string $statementId,
        public readonly string $questionClaimId,
        public readonly string $status,
        public readonly ?string $statementText,
        public readonly string $aggregateClaimRelationship,
        public readonly array $evidenceIds,
        public readonly array $sourceIds,
        public readonly bool $answerEligible,
        public readonly array $limitations = [],
        public readonly array $accuracyOutcomes = [],
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'statement_id' => $this->statementId,
            'question_claim_id' => $this->questionClaimId,
            'status' => $this->status,
            'statement_text' => $this->statementText,
            'aggregate_claim_relationship' => $this->aggregateClaimRelationship,
            'evidence_ids' => $this->evidenceIds,
            'source_ids' => $this->sourceIds,
            'answer_eligible' => $this->answerEligible,
            'limitations' => $this->limitations,
            'accuracy_outcomes' => $this->accuracyOutcomes,
        ];
    }
}
