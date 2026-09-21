<?php

namespace Tests\Feature;

use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use App\Services\Agriculture\Research\Validation\EvidenceVerificationLayer;
use Tests\TestCase;

/**
 * Phase 4 Unit B — EvidenceVerificationLayer ownership.
 */
class Phase4EvidenceVerificationContractTest extends TestCase
{
    public function test_evl_maps_directness_to_verification_label(): void
    {
        $evl = app(EvidenceVerificationLayer::class);
        $this->assertSame(
            EvidenceVerificationLayer::LABEL_DIRECT,
            $evl->toVerificationLabel(ScientificEvidenceDirectnessAssessor::DIRECT),
        );
        $this->assertSame(
            EvidenceVerificationLayer::LABEL_SUPPORTED,
            $evl->toVerificationLabel(ScientificEvidenceDirectnessAssessor::SUPPORTING),
        );
        $this->assertSame(
            EvidenceVerificationLayer::LABEL_SUPPORTED,
            $evl->toVerificationLabel(ScientificEvidenceDirectnessAssessor::SUPPORTED),
        );
    }

    public function test_primary_citation_eligibility_is_direct_only(): void
    {
        $evl = app(EvidenceVerificationLayer::class);
        $this->assertTrue($evl->isPrimaryCitationEligible(ScientificEvidenceDirectnessAssessor::DIRECT));
        $this->assertFalse($evl->isPrimaryCitationEligible(ScientificEvidenceDirectnessAssessor::SUPPORTING));
        $this->assertFalse($evl->isPrimaryCitationEligible(ScientificEvidenceDirectnessAssessor::BACKGROUND));
        $this->assertFalse($evl->isPrimaryCitationEligible(ScientificEvidenceDirectnessAssessor::IRRELEVANT));
    }

    public function test_evl_assess_does_not_emit_ranker_or_disposition_fields(): void
    {
        $plan = $this->plan('What irrigation methods improve wheat yield?');
        $refined = app(EvidenceVerificationLayer::class)->assess(
            $plan,
            'Wheat drip irrigation yield response',
            'Wheat drip irrigation improved grain yield in multi-year field trials.',
            '10.9999/evl2',
            null,
        );

        $this->assertArrayHasKey('directness', $refined);
        $this->assertArrayHasKey('verification_label', $refined);
        $this->assertArrayNotHasKey('relevanceScore', $refined);
        $this->assertArrayNotHasKey('score_role', $refined);
        $this->assertArrayNotHasKey('evidence_lifecycle_disposition', $refined);
        $this->assertArrayNotHasKey('evidence_sufficient', $refined);
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
