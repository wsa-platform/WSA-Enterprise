<?php

namespace Tests\Feature;

use App\Services\Agriculture\Research\Synthesis\AnswerComposer;
use App\Services\Agriculture\Research\Synthesis\QuestionClaimSynthesisContract;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use Tests\TestCase;

class Phase5AnswerStatementTraceabilityTest extends TestCase
{
    use Phase5UnitATestFixtures;

    public function test_answer_statement_traces_to_question_claim_and_evidence(): void
    {
        $plan = $this->phase5Plan();
        $evidence = [$this->phase5Evidence('e-1', ClaimEvidenceRelationship::SUPPORTED)];
        $matrix = (new QuestionClaimSynthesisContract)->build(
            $plan,
            $this->phase5Validation($evidence, true),
            $evidence,
        );

        $this->assertNotEmpty($matrix['answer_statement_traces']);
        $trace = $matrix['answer_statement_traces'][0];
        $this->assertSame('qc-1', $trace['question_claim_id']);
        $this->assertSame('as-1', $trace['statement_id']);
        $this->assertSame(['e-1'], $trace['evidence_ids']);
        $this->assertSame(['src-e-1'], $trace['source_ids']);
        $this->assertTrue($trace['answer_eligible']);
        $this->assertSame(ClaimEvidenceRelationship::SUPPORTED, $trace['aggregate_claim_relationship']);
    }

    public function test_composer_embeds_phase5_unit_a_matrix(): void
    {
        $plan = $this->phase5Plan();
        $report = app(AnswerComposer::class)->compose(
            $plan,
            $this->phase5Validation([
                $this->phase5Evidence('e-compose-1', ClaimEvidenceRelationship::SUPPORTED),
            ], true),
        );

        $this->assertTrue((bool) ($report->observability['phase5_unit_a'] ?? false));
        $this->assertArrayHasKey('question_claims', $report->observability);
        $this->assertArrayHasKey('answer_statement_traces', $report->observability);
        $this->assertSame('qc-1', $report->observability['question_claims'][0]['claim_id'] ?? null);
        if ($report->claims !== []) {
            $this->assertSame('qc-1', $report->claims[0]->questionClaimId);
        }
    }

    public function test_r5_ownership_not_moved_into_composer_matrix(): void
    {
        $plan = $this->phase5Plan();
        $matrix = (new QuestionClaimSynthesisContract)->build(
            $plan,
            $this->phase5Validation([], false),
            [],
        );

        $this->assertArrayNotHasKey('should_persist', $matrix);
        $this->assertArrayNotHasKey('library_save', $matrix);
        $this->assertFalse($matrix['answer_statement_traces'][0]['answer_eligible']);
    }
}
