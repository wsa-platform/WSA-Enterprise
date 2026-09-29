<?php

namespace Tests\Unit\Agriculture\Research\Synthesis;

use App\Services\Agriculture\Research\Synthesis\ScientificIndependentAnswer;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ScientificIndependentAnswerTest extends TestCase
{
    public function test_accepts_explicit_answer_and_preserves_traceability(): void
    {
        $answer = new ScientificIndependentAnswer(
            answer: 'Wheat growth is optimized near the documented temperature range.',
            statementIds: ['as-1', 'as-2'],
            evidenceIds: ['e-1', 'e-2'],
            sourceIds: ['s-1'],
            resultId: 'result-1',
        );

        $this->assertSame('Wheat growth is optimized near the documented temperature range.', $answer->answer);
        $this->assertSame(['as-1', 'as-2'], $answer->statementIds);
        $this->assertSame(['e-1', 'e-2'], $answer->evidenceIds);
        $this->assertSame(['s-1'], $answer->sourceIds);
        $this->assertSame('result-1', $answer->resultId);
        $this->assertSame([
            'answer' => 'Wheat growth is optimized near the documented temperature range.',
            'statement_ids' => ['as-1', 'as-2'],
            'evidence_ids' => ['e-1', 'e-2'],
            'source_ids' => ['s-1'],
            'result_id' => 'result-1',
        ], $answer->toArray());
    }

    public function test_rejects_empty_answer(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ScientificIndependentAnswer(
            answer: '   ',
            statementIds: ['as-1'],
            evidenceIds: ['e-1'],
            sourceIds: ['s-1'],
            resultId: 'result-1',
        );
    }

    public function test_rejects_empty_result_id(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ScientificIndependentAnswer(
            answer: 'Explicit answer.',
            statementIds: ['as-1'],
            evidenceIds: ['e-1'],
            sourceIds: ['s-1'],
            resultId: '',
        );
    }
}
