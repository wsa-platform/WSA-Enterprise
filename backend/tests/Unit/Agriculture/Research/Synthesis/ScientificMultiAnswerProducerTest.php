<?php

namespace Tests\Unit\Agriculture\Research\Synthesis;

use App\Services\Agriculture\Research\CanonicalScientificQuestion;
use App\Services\Agriculture\Research\Synthesis\MultiAnswerIdentitySerializer;
use App\Services\Agriculture\Research\Synthesis\ScientificIndependentAnswer;
use App\Services\Agriculture\Research\Synthesis\ScientificMultiAnswerProducer;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ScientificMultiAnswerProducerTest extends TestCase
{
    public function test_empty_input_produces_empty_output(): void
    {
        $producer = new ScientificMultiAnswerProducer();

        $this->assertSame([], $producer->produce($this->question(), []));
    }

    public function test_preserves_order_assigns_position_and_assembles_provenance(): void
    {
        $question = $this->question();
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

        $produced = (new ScientificMultiAnswerProducer())->produce($question, [$first, $second]);
        $identity = new MultiAnswerIdentitySerializer();

        $this->assertCount(2, $produced);
        $this->assertSame(0, $produced[0]->position);
        $this->assertSame(1, $produced[1]->position);
        $this->assertSame('First independent answer.', $produced[0]->answer);
        $this->assertSame('Second independent answer.', $produced[1]->answer);
        $this->assertSame(
            $identity->answerId($question, $first->answer, $first->evidenceIds),
            $produced[0]->answerId,
        );
        $this->assertSame(
            $identity->answerId($question, $second->answer, $second->evidenceIds),
            $produced[1]->answerId,
        );
        $this->assertSame([
            'statement_ids' => ['as-1'],
            'evidence_ids' => ['e-1'],
            'source_ids' => ['s-1'],
            'result_id' => 'result-1',
        ], $produced[0]->provenance);
        $this->assertSame([
            'answer_id' => $produced[1]->answerId,
            'answer' => 'Second independent answer.',
            'result_id' => 'result-2',
            'evidence_ids' => ['e-2'],
            'source_ids' => ['s-2'],
            'position' => 1,
            'provenance' => [
                'statement_ids' => ['as-2'],
                'evidence_ids' => ['e-2'],
                'source_ids' => ['s-2'],
                'result_id' => 'result-2',
            ],
        ], $produced[1]->toArray());
    }

    public function test_duplicate_answer_id_is_first_wins(): void
    {
        $question = $this->question();
        $first = new ScientificIndependentAnswer(
            answer: 'Same canonical answer.',
            statementIds: ['as-1'],
            evidenceIds: ['e-1', 'e-2'],
            sourceIds: ['s-1'],
            resultId: 'result-1',
        );
        $duplicate = new ScientificIndependentAnswer(
            answer: 'Same canonical answer.',
            statementIds: ['as-dup'],
            evidenceIds: ['e-2', 'e-1'],
            sourceIds: ['s-dup'],
            resultId: 'result-dup',
        );
        $third = new ScientificIndependentAnswer(
            answer: 'Different independent answer.',
            statementIds: ['as-3'],
            evidenceIds: ['e-3'],
            sourceIds: ['s-3'],
            resultId: 'result-3',
        );

        $produced = (new ScientificMultiAnswerProducer())->produce($question, [$first, $duplicate, $third]);

        $this->assertCount(2, $produced);
        $this->assertSame('result-1', $produced[0]->resultId);
        $this->assertSame(['as-1'], $produced[0]->provenance['statement_ids']);
        $this->assertSame(0, $produced[0]->position);
        $this->assertSame('result-3', $produced[1]->resultId);
        $this->assertSame(1, $produced[1]->position);
    }

    public function test_rejects_non_independent_answer_objects(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new ScientificMultiAnswerProducer())->produce($this->question(), [
            [
                'answer' => 'claim text',
                'evidence_ids' => ['e-1'],
            ],
        ]);
    }

    private function question(): CanonicalScientificQuestion
    {
        return CanonicalScientificQuestion::fromRoleGraph(
            originalQuestion: 'What is the optimal temperature for wheat growth?',
            language: 'en',
            normalizedForm: 'normalized question',
            researchContext: 'home',
            graph: [
                'entity_surface' => 'wheat',
                'entity_normalized' => 'wheat',
                'entity_canonical_id' => 'wheat',
                'entity_canonical_namespace' => 'taxonomy.crop',
                'entity_resolution' => 'resolved',
                'property_key' => 'temperature',
                'property_surface' => 'temperature',
                'property_of_role' => 'process',
                'relation_type' => 'descriptive',
            ],
            context: [
                'scientific_sense' => 'crop_growth',
                'question_type' => 'range',
                'requested_information' => ['temperature'],
            ],
        );
    }
}
