<?php

namespace Tests\Unit\Agriculture\Research\Synthesis;

use App\Services\Agriculture\Research\CanonicalScientificQuestion;
use App\Services\Agriculture\Research\Synthesis\AnswerSynthesisExecutionReport;
use App\Services\Agriculture\Research\Synthesis\ClaimBoundIndependentAnswerDeriver;
use App\Services\Agriculture\Research\Synthesis\ResearchAnswerClaim;
use App\Services\Agriculture\Research\Synthesis\ScientificMultiAnswerProducer;
use PHPUnit\Framework\TestCase;

final class ClaimBoundIndependentAnswerDeriverTest extends TestCase
{
    public function test_derives_independent_answers_from_distinct_claims_and_produces_multi_answers(): void
    {
        $synthesis = new AnswerSynthesisExecutionReport(
            status: 'answered',
            performed: true,
            answer: 'Primary synthesis answer.',
            conciseSummary: 'Summary',
            detailedExplanation: null,
            keyFindings: [],
            claims: [
                new ResearchAnswerClaim(
                    claimId: 'c1',
                    claimText: 'Wheat growth increases with temperature up to an optimum.',
                    evidenceIds: ['e1'],
                    sourceIds: ['openalex'],
                    validationStatus: 'accepted',
                    claimRelationship: 'supports',
                    confidence: 0.7,
                    questionClaimId: 'qc1',
                ),
                new ResearchAnswerClaim(
                    claimId: 'c2',
                    claimText: 'Above the optimum, heat stress reduces wheat yield.',
                    evidenceIds: ['e2'],
                    sourceIds: ['crossref'],
                    validationStatus: 'accepted',
                    claimRelationship: 'supports',
                    confidence: 0.65,
                    questionClaimId: 'qc2',
                ),
            ],
            citations: [],
            evidenceReferences: [],
            confidence: 0.7,
            limitations: [],
            uncertainty: null,
            conflicts: [],
            language: 'en',
            researchMetadata: [],
            observability: [],
        );

        $independent = (new ClaimBoundIndependentAnswerDeriver)->derive($synthesis);
        $this->assertCount(2, $independent);
        $this->assertSame('Wheat growth increases with temperature up to an optimum.', $independent[0]->answer);
        $this->assertSame(['e1'], $independent[0]->evidenceIds);

        $produced = (new ScientificMultiAnswerProducer)->produce($this->question(), $independent);
        $this->assertCount(2, $produced);
        $this->assertSame(0, $produced[0]->position);
        $this->assertSame(1, $produced[1]->position);
        $this->assertNotSame($produced[0]->answerId, $produced[1]->answerId);
    }

    public function test_does_not_invent_answers_without_evidence(): void
    {
        $synthesis = new AnswerSynthesisExecutionReport(
            status: 'insufficient_evidence',
            performed: false,
            answer: null,
            conciseSummary: null,
            detailedExplanation: null,
            keyFindings: [],
            claims: [],
            citations: [],
            evidenceReferences: [],
            confidence: 0.0,
            limitations: [],
            uncertainty: null,
            conflicts: [],
            language: 'en',
            researchMetadata: [],
            observability: [],
        );

        $this->assertSame([], (new ClaimBoundIndependentAnswerDeriver)->derive($synthesis));
    }

    private function question(): CanonicalScientificQuestion
    {
        return CanonicalScientificQuestion::fromRoleGraph(
            originalQuestion: 'What is the effect of temperature on wheat growth?',
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
                'question_type' => 'mechanism',
                'requested_information' => ['temperature'],
            ],
        );
    }
}
