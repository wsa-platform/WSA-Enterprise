<?php

namespace App\Services\Agriculture\Research\Synthesis;

use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use App\Services\Agriculture\Research\Validation\EvidenceValidationExecutionReport;
use App\Services\Agriculture\Research\Validation\ScientificEvidenceItem;

/**
 * Phase-5 Unit A — Question → Claims → Evidence → AnswerStatement traces.
 *
 * Composer may embed the resulting matrix; it does not own Phase-4 axes or R5.
 */
final class QuestionClaimSynthesisContract
{
    public function __construct(
        private QuestionClaimExtractor $extractor = new QuestionClaimExtractor,
        private QuestionClaimEvidenceMapper $mapper = new QuestionClaimEvidenceMapper,
    ) {}

    /**
     * @param  list<ScientificEvidenceItem>  $evidenceItems
     * @return array{
     *     question_claims: list<array<string, mixed>>,
     *     claim_evidence_bindings: list<array<string, mixed>>,
     *     answer_statement_traces: list<array<string, mixed>>,
     *     matrix_version: string
     * }
     */
    public function build(
        KnowledgeQueryPlan $plan,
        EvidenceValidationExecutionReport $validationReport,
        array $evidenceItems = [],
    ): array {
        $questionClaims = $this->extractor->extract($plan);
        $items = $evidenceItems !== []
            ? array_values($evidenceItems)
            : array_values($validationReport->validatedEvidence);
        $bindings = $this->mapper->bind($questionClaims, $items);
        $statements = $this->buildAnswerStatements($questionClaims, $bindings, $validationReport);

        return [
            'matrix_version' => 'phase5-unit-a-v1',
            'question_claims' => array_map(
                static fn (QuestionClaim $claim): array => $claim->toArray(),
                $questionClaims,
            ),
            'claim_evidence_bindings' => array_map(
                static fn (QuestionClaimEvidenceBinding $binding): array => $binding->toArray(),
                $bindings,
            ),
            'answer_statement_traces' => array_map(
                static fn (AnswerStatementTrace $trace): array => $trace->toArray(),
                $statements,
            ),
        ];
    }

    /**
     * @param  list<QuestionClaim>  $questionClaims
     * @param  list<QuestionClaimEvidenceBinding>  $bindings
     * @return list<AnswerStatementTrace>
     */
    private function buildAnswerStatements(
        array $questionClaims,
        array $bindings,
        EvidenceValidationExecutionReport $validationReport,
    ): array {
        $traces = [];
        foreach ($questionClaims as $index => $claim) {
            $claimBindings = array_values(array_filter(
                $bindings,
                static fn (QuestionClaimEvidenceBinding $binding): bool => $binding->questionClaimId === $claim->claimId,
            ));
            $aggregate = $this->mapper->aggregateRelationshipForClaim($claim->claimId, $claimBindings);
            $evidenceIds = array_values(array_unique(array_map(
                static fn (QuestionClaimEvidenceBinding $binding): string => $binding->evidenceId,
                $claimBindings,
            )));
            $sourceIds = array_values(array_unique(array_map(
                static fn (QuestionClaimEvidenceBinding $binding): string => $binding->sourceId,
                $claimBindings,
            )));

            $limitations = $claim->limitations;
            $answerEligible = false;
            $status = 'insufficient_evidence';
            $statementText = null;

            if ($claimBindings === [] || $aggregate === ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE) {
                $status = 'insufficient_evidence';
                $limitations[] = 'insufficient_validated_evidence_for_question_claim';
                $answerEligible = false;
                // Preserve claim identity even when unanswered.
                $statementText = null;
            } elseif ($aggregate === ClaimEvidenceRelationship::CONFLICTING) {
                $status = 'conflict';
                $limitations[] = 'conflicting_evidence_for_question_claim';
                $answerEligible = false;
                $statementText = $claim->claimText;
            } elseif ($aggregate === ClaimEvidenceRelationship::PARTIALLY_SUPPORTED) {
                $status = 'partially_supported';
                $limitations[] = 'partial_evidence_support';
                $answerEligible = true;
                $statementText = $claim->claimText;
            } else {
                $status = 'supported';
                $answerEligible = true;
                $statementText = $claim->claimText;
            }

            // R5 remains outside Composer: eligibility here is answer-statement eligibility only.
            if (! $validationReport->evidenceSufficient && $answerEligible) {
                $limitations[] = 'validation_evidence_insufficient';
            }

            $traces[] = new AnswerStatementTrace(
                statementId: 'as-'.($index + 1),
                questionClaimId: $claim->claimId,
                status: $status,
                statementText: $statementText,
                aggregateClaimRelationship: $aggregate,
                evidenceIds: $evidenceIds,
                sourceIds: $sourceIds,
                answerEligible: $answerEligible,
                limitations: array_values(array_unique($limitations)),
            );
        }

        return $traces;
    }
}
