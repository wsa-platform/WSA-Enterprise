<?php

namespace Tests\Feature;

use App\Services\Agriculture\Research\Synthesis\QuestionClaimSynthesisContract;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use Tests\TestCase;

class Phase5InsufficientClaimContractTest extends TestCase
{
    use Phase5UnitATestFixtures;

    public function test_insufficient_preserves_claim_identity_without_fabricating_statement(): void
    {
        $plan = $this->phase5Plan();
        $matrix = (new QuestionClaimSynthesisContract)->build(
            $plan,
            $this->phase5Validation([], false),
            [],
        );

        $this->assertCount(1, $matrix['question_claims']);
        $this->assertSame('qc-1', $matrix['question_claims'][0]['claim_id']);
        $this->assertSame('insufficient_evidence', $matrix['answer_statement_traces'][0]['status']);
        $this->assertNull($matrix['answer_statement_traces'][0]['statement_text']);
        $this->assertContains(
            'insufficient_validated_evidence_for_question_claim',
            $matrix['answer_statement_traces'][0]['limitations'],
        );
        $this->assertFalse($matrix['answer_statement_traces'][0]['answer_eligible']);
        $this->assertSame(
            ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE,
            $matrix['answer_statement_traces'][0]['aggregate_claim_relationship'],
        );
    }
}
