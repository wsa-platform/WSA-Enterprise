<?php

namespace Tests\Unit\Agriculture\Research\Synthesis;

use App\Services\Agriculture\Research\CanonicalScientificQuestion;
use App\Services\Agriculture\Research\Synthesis\ExplicitScientificIndependentAnswerProvider;
use App\Services\Agriculture\Research\Synthesis\ScientificIndependentAnswer;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ExplicitScientificIndependentAnswerProviderTest extends TestCase
{
    public function test_provides_explicit_answers_without_claim_or_evidence_inference(): void
    {
        $answer = new ScientificIndependentAnswer(
            answer: 'Explicit independent answer.',
            statementIds: ['as-1'],
            evidenceIds: ['e-1'],
            sourceIds: ['s-1'],
            resultId: 'result-1',
        );

        $provider = new ExplicitScientificIndependentAnswerProvider([$answer]);
        $source = $provider->provide($this->question());

        $this->assertSame([$answer], $source->answers());
    }

    public function test_empty_provider_yields_empty_source(): void
    {
        $provider = new ExplicitScientificIndependentAnswerProvider();
        $source = $provider->provide($this->question());

        $this->assertSame([], $source->answers());
    }

    public function test_preserves_order_and_traceability_without_deduplication_or_ranking(): void
    {
        $first = new ScientificIndependentAnswer(
            answer: 'First.',
            statementIds: ['as-1'],
            evidenceIds: ['e-1'],
            sourceIds: ['s-1'],
            resultId: 'result-1',
        );
        $second = new ScientificIndependentAnswer(
            answer: 'Second.',
            statementIds: ['as-2'],
            evidenceIds: ['e-2'],
            sourceIds: ['s-2'],
            resultId: 'result-2',
        );

        $provider = new ExplicitScientificIndependentAnswerProvider([$first, $second, $first]);
        $source = $provider->provide($this->question());

        $this->assertSame([$first, $second, $first], $source->answers());
        $this->assertSame(['e-1'], $source->answers()[0]->evidenceIds);
        $this->assertSame(['s-2'], $source->answers()[1]->sourceIds);
    }

    public function test_rejects_non_answer_objects(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ExplicitScientificIndependentAnswerProvider([
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
