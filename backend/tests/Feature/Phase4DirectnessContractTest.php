<?php

namespace Tests\Feature;

use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use App\Services\Agriculture\Research\Validation\EvidenceVerificationLayer;
use Tests\TestCase;

/**
 * Phase 4 Unit B — Directness + EVL refine ownership (R6).
 */
class Phase4DirectnessContractTest extends TestCase
{
    public function test_directness_labels_are_not_claim_relation_values(): void
    {
        $directnessLabels = [
            ScientificEvidenceDirectnessAssessor::DIRECT,
            ScientificEvidenceDirectnessAssessor::SUPPORTING,
            ScientificEvidenceDirectnessAssessor::BACKGROUND,
            ScientificEvidenceDirectnessAssessor::RELATED,
            ScientificEvidenceDirectnessAssessor::IRRELEVANT,
            ScientificEvidenceDirectnessAssessor::GEOGRAPHIC_MISMATCH,
        ];
        foreach ($directnessLabels as $label) {
            $this->assertNotContains($label, ClaimEvidenceRelationship::all());
        }
    }

    public function test_assessor_and_evl_emit_directness_axis_only(): void
    {
        $plan = $this->plan('What irrigation methods improve wheat yield?');
        $title = 'Wheat drip irrigation yield response';
        $abstract = 'Wheat drip irrigation improved grain yield in multi-year field trials.';

        $base = app(ScientificEvidenceDirectnessAssessor::class)->assess($plan, $title, $abstract, null, null);
        $refined = app(EvidenceVerificationLayer::class)->assess($plan, $title, $abstract, null, null);

        $this->assertArrayHasKey('directness', $base);
        $this->assertArrayHasKey('directness', $refined);
        $this->assertArrayHasKey('verification_label', $refined);
        $this->assertSame(
            app(EvidenceVerificationLayer::class)->toVerificationLabel($refined['directness']),
            $refined['verification_label'],
        );
        $this->assertArrayNotHasKey('claim_relationship', $base);
        $this->assertArrayNotHasKey('claim_relationship', $refined);
        $this->assertArrayNotHasKey('evidence_lifecycle_disposition', $refined);
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
