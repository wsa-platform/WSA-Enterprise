<?php

namespace Tests\Feature;

use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use App\Services\Agriculture\Research\Synthesis\QuestionClaimSynthesisContract;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use Tests\TestCase;

class Phase5ConflictingClaimContractTest extends TestCase
{
    use Phase5UnitATestFixtures;

    public function test_conflicting_claim_preserves_conflict_status(): void
    {
        $plan = $this->phase5Plan();
        $evidence = [
            $this->phase5Evidence(
                'e-conflict',
                ClaimEvidenceRelationship::CONFLICTING,
                ScientificEvidenceDirectnessAssessor::DIRECT,
                true,
            ),
        ];

        $matrix = (new QuestionClaimSynthesisContract)->build(
            $plan,
            $this->phase5Validation($evidence, false),
            $evidence,
        );

        $this->assertSame('conflict', $matrix['answer_statement_traces'][0]['status']);
        $this->assertSame(
            ClaimEvidenceRelationship::CONFLICTING,
            $matrix['answer_statement_traces'][0]['aggregate_claim_relationship'],
        );
        $this->assertFalse($matrix['answer_statement_traces'][0]['answer_eligible']);
        $this->assertNotSame(
            ClaimEvidenceRelationship::SUPPORTED,
            $matrix['answer_statement_traces'][0]['aggregate_claim_relationship'],
        );
    }
}
