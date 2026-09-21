<?php

namespace Tests\Feature;

use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Search\ScientificEvidenceRelevanceGate;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Phase 4 Unit B — RelevanceGate ownership: candidate relevance only.
 */
class Phase4RelevanceGateContractTest extends TestCase
{
    public function test_gate_assess_contract_keys(): void
    {
        $plan = $this->plan('What irrigation methods improve wheat yield?');
        $assessment = app(ScientificEvidenceRelevanceGate::class)->assess(
            $plan,
            'Wheat drip irrigation',
            'Wheat drip irrigation improved yield in field trials.',
            null,
            null,
        );

        foreach (['relevant', 'score', 'entity_matched', 'topic_matched', 'rejection_reasons'] as $key) {
            $this->assertArrayHasKey($key, $assessment);
        }
        $this->assertIsBool($assessment['relevant']);
        $this->assertIsArray($assessment['rejection_reasons']);
        $this->assertArrayNotHasKey('claim_relationship', $assessment);
        $this->assertArrayNotHasKey('evidence_sufficient', $assessment);
        $this->assertArrayNotHasKey('evidence_lifecycle_disposition', $assessment);
    }

    public function test_gate_is_not_a_disposition_or_sufficiency_owner(): void
    {
        $method = new ReflectionMethod(ScientificEvidenceRelevanceGate::class, 'assess');
        $this->assertSame('assess', $method->getName());
        $this->assertFalse(method_exists(ScientificEvidenceRelevanceGate::class, 'classifyDisposition'));
        $this->assertFalse(method_exists(ScientificEvidenceRelevanceGate::class, 'isEvidenceSufficient'));
    }

    private function plan(string $question): KnowledgeQueryPlan
    {
        $query = new AgriculturalKnowledgeQuery(
            originalQuestion: $question,
            normalizedQuestion: $question,
            language: 'en',
            agriculturalDomain: 'agronomy',
            subject: ['type' => 'crop', 'value' => 'wheat', 'label' => 'wheat'],
            crop: 'wheat',
            cropId: 'wheat',
            scientificName: null,
            topic: 'irrigation',
            subtopic: null,
            requestedInformation: ['mechanism'],
            constraints: ['answer_language' => 'en', 'question_type' => 'mechanism'],
            location: null,
            researchRequired: true,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            researchIntent: 'scientific_explanation',
        );

        return new KnowledgeQueryPlan(
            normalizedQuery: $query,
            researchIntent: $query->researchIntent,
            agriculturalDomain: 'agronomy',
            subjectEntity: $query->subject,
            topics: ['irrigation'],
            subtopics: [],
            requestedInformation: $query->requestedInformation,
            evidenceRequirements: ['peer_reviewed'],
            sourcePriorities: ['openalex'],
            primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            readyForStage3: true,
        );
    }
}
