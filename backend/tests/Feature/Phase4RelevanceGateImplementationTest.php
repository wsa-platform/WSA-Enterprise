<?php

namespace Tests\Feature;

use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Search\ScientificEvidenceRelevanceGate;
use App\Services\Agriculture\Research\Validation\CatalogSafeLandSignals;
use Tests\TestCase;

/**
 * Phase 4 Unit C — RelevanceGate implementation (candidate relevance only).
 */
class Phase4RelevanceGateImplementationTest extends TestCase
{
    public function test_relevant_wheat_irrigation_passes_gate(): void
    {
        $assessment = app(ScientificEvidenceRelevanceGate::class)->assess(
            $this->plan('What irrigation methods improve wheat yield?'),
            'Wheat drip irrigation yield response',
            'Wheat drip irrigation improved grain yield in multi-year field trials.',
            '10.9999/rel-ok',
            null,
        );

        $this->assertTrue($assessment['relevant']);
        $this->assertSame([], $assessment['rejection_reasons']);
        $this->assertArrayNotHasKey('claim_relationship', $assessment);
        $this->assertArrayNotHasKey('evidence_sufficient', $assessment);
        $this->assertArrayNotHasKey('evidence_lifecycle_disposition', $assessment);
    }

    public function test_irrelevant_domain_is_rejected_with_reason_not_claim_conflict(): void
    {
        $assessment = app(ScientificEvidenceRelevanceGate::class)->assess(
            $this->plan('What irrigation methods improve wheat yield?'),
            'National banking reforms and tourism GDP',
            'Nationwide macroeconomic inventory of banking indicators.',
            '10.9999/rel-bad',
            null,
        );

        $this->assertFalse($assessment['relevant']);
        $this->assertNotEmpty($assessment['rejection_reasons']);
        $this->assertNotContains('claim_conflicting', $assessment['rejection_reasons']);
        $this->assertArrayNotHasKey('claim_relationship', $assessment);
    }

    public function test_named_entity_surface_can_satisfy_entity_requirement(): void
    {
        $plan = $this->plan(
            'What are production practices for Atlantis wheat?',
            subject: ['type' => 'named_entity', 'value' => 'Atlantis', 'label' => 'Atlantis'],
            cropId: null,
            constraints: [
                'entity_dependent' => true,
                'named_entity_surface' => 'Atlantis',
                'question_type' => 'mechanism',
            ],
        );

        $assessment = app(ScientificEvidenceRelevanceGate::class)->assess(
            $plan,
            'Atlantis wheat production practices in irrigated systems',
            'Atlantis cultivar wheat irrigation and production practices for arid regions.',
            null,
            null,
        );

        $this->assertTrue($assessment['entity_matched'] || $assessment['relevant']);
    }

    public function test_catalog_safe_land_signals_do_not_require_catalog_wip(): void
    {
        $this->assertTrue(CatalogSafeLandSignals::hasInventoryContent(
            'nationwide inventory of soil types and land classes'
        ));
        $this->assertTrue(CatalogSafeLandSignals::isMethodologyOnlyWithoutInventory(
            'random forest neural network classification model for remote sensing'
        ));
        $this->assertFalse(CatalogSafeLandSignals::isMethodologyOnlyWithoutInventory(
            'nationwide inventory of soil types and land classification'
        ));
    }

    /**
     * @param  array<string, mixed>|null  $subject
     * @param  array<string, mixed>  $constraints
     */
    private function plan(
        string $question,
        ?array $subject = null,
        ?string $cropId = 'wheat',
        array $constraints = [],
    ): KnowledgeQueryPlan {
        $subject ??= ['type' => 'crop', 'value' => 'wheat', 'label' => 'wheat'];
        $query = new AgriculturalKnowledgeQuery(
            originalQuestion: $question,
            normalizedQuestion: $question,
            language: 'en',
            agriculturalDomain: 'agronomy',
            subject: $subject,
            crop: $cropId,
            cropId: $cropId,
            scientificName: null,
            topic: 'irrigation',
            subtopic: null,
            requestedInformation: ['mechanism'],
            constraints: array_merge([
                'answer_language' => 'en',
                'question_type' => 'mechanism',
            ], $constraints),
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
