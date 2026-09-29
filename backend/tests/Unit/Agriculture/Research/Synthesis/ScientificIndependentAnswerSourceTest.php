<?php

namespace Tests\Unit\Agriculture\Research\Synthesis;

use App\Services\Agriculture\Research\Synthesis\ScientificIndependentAnswer;
use App\Services\Agriculture\Research\Synthesis\ScientificIndependentAnswerSource;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ScientificIndependentAnswerSourceTest extends TestCase
{
    public function test_accepts_only_explicit_independent_answers(): void
    {
        $answer = new ScientificIndependentAnswer(
            answer: 'Wheat growth is optimized near the documented temperature range.',
            statementIds: ['as-1'],
            evidenceIds: ['e-1', 'e-2'],
            sourceIds: ['s-1'],
            resultId: 'result-1',
        );

        $source = ScientificIndependentAnswerSource::fromExplicitAnswers([$answer]);

        $this->assertSame([$answer], $source->answers());
    }

    public function test_preserves_explicit_order_and_traceability(): void
    {
        $first = new ScientificIndependentAnswer(
            answer: 'First independent answer.',
            statementIds: ['as-1'],
            evidenceIds: ['e-1'],
            sourceIds: ['s-1'],
            resultId: 'result-1',
        );
        $second = new ScientificIndependentAnswer(
            answer: 'Second independent answer.',
            statementIds: ['as-2'],
            evidenceIds: ['e-2'],
            sourceIds: ['s-2'],
            resultId: 'result-2',
        );

        $source = new ScientificIndependentAnswerSource([$first, $second]);

        $this->assertSame([$first, $second], $source->answers());
        $this->assertSame(['as-1'], $source->answers()[0]->statementIds);
        $this->assertSame(['e-2'], $source->answers()[1]->evidenceIds);
    }

    public function test_rejects_claim_or_evidence_arrays_instead_of_answer_objects(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ScientificIndependentAnswerSource([
            [
                'answer' => 'Derived from a claim.',
                'statement_ids' => ['as-1'],
                'evidence_ids' => ['e-1'],
                'source_ids' => ['s-1'],
                'result_id' => 'result-1',
            ],
        ]);
    }

    public function test_does_not_deduplicate_or_rank_explicit_answers(): void
    {
        $answer = new ScientificIndependentAnswer(
            answer: 'Same explicit answer.',
            statementIds: ['as-1'],
            evidenceIds: ['e-1'],
            sourceIds: ['s-1'],
            resultId: 'result-1',
        );

        $source = new ScientificIndependentAnswerSource([$answer, $answer]);

        $this->assertCount(2, $source->answers());
    }

    public function test_empty_source_is_valid_and_has_no_inferred_answer(): void
    {
        $source = new ScientificIndependentAnswerSource([]);

        $this->assertSame([], $source->answers());
    }
}
