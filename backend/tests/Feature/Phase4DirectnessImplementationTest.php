<?php

namespace Tests\Feature;

use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use App\Services\Agriculture\Research\Validation\EvidenceVerificationLayer;
use Tests\TestCase;

/**
 * Phase 4 Unit C — Directness implementation (R6 axis independence).
 */
class Phase4DirectnessImplementationTest extends TestCase
{
    public function test_direct_wheat_irrigation_can_be_direct_or_supporting(): void
    {
        $out = app(ScientificEvidenceDirectnessAssessor::class)->assess(
            $this->plan('What irrigation methods improve wheat yield?'),
            'Wheat drip irrigation yield response',
            'Wheat drip irrigation improved grain yield in multi-year field trials under arid conditions.',
            '10.9999/dir-ok',
            null,
        );

        $this->assertContains($out['directness'], [
            ScientificEvidenceDirectnessAssessor::DIRECT,
            ScientificEvidenceDirectnessAssessor::SUPPORTING,
            ScientificEvidenceDirectnessAssessor::RELATED,
            ScientificEvidenceDirectnessAssessor::BACKGROUND,
        ]);
        $this->assertArrayHasKey('reasons', $out);
        $this->assertNotContains($out['directness'], ClaimEvidenceRelationship::all());
    }

    public function test_method_only_land_classification_is_not_direct(): void
    {
        $plan = $this->plan(
            'What are the types of agricultural land in Egypt?',
            constraints: [
                'scientific_sense' => 'land_classification',
                'required_evidence_type' => 'classification_or_types_inventory',
                'question_type' => 'classification',
            ],
            location: 'Egypt',
            topic: 'land_classification',
        );

        $out = app(ScientificEvidenceDirectnessAssessor::class)->assess(
            $plan,
            'Random forest land classification model using remote sensing',
            'Machine learning neural network CNN classification algorithm without soil type inventory.',
            '10.9999/dir-ml',
            null,
        );

        $this->assertNotSame(ScientificEvidenceDirectnessAssessor::DIRECT, $out['directness']);
        $this->assertContains($out['directness'], [
            ScientificEvidenceDirectnessAssessor::RELATED,
            ScientificEvidenceDirectnessAssessor::BACKGROUND,
            ScientificEvidenceDirectnessAssessor::IRRELEVANT,
            ScientificEvidenceDirectnessAssessor::SUPPORTING,
        ]);
    }

    public function test_evl_preserves_reason_traceability_on_refine(): void
    {
        $plan = $this->plan(
            'What are the types of agricultural land in Egypt?',
            constraints: [
                'scientific_sense' => 'land_classification',
                'required_evidence_type' => 'classification_or_types_inventory',
                'question_type' => 'classification',
            ],
            location: 'Egypt',
            topic: 'land_classification',
        );

        $refined = app(EvidenceVerificationLayer::class)->assess(
            $plan,
            'Greenhouse gerbera cultivation practices',
            'Protected cultivation of gerbera roses in hydroponic greenhouses without soil types inventory.',
            '10.9999/dir-off',
            null,
        );

        $this->assertArrayHasKey('directness', $refined);
        $this->assertArrayHasKey('verification_label', $refined);
        $this->assertArrayHasKey('reasons', $refined);
        $this->assertIsArray($refined['reasons']);
        $this->assertArrayNotHasKey('evidence_lifecycle_disposition', $refined);
        $this->assertArrayNotHasKey('evidence_sufficient', $refined);
    }

    /**
     * @param  array<string, mixed>  $constraints
     */
    private function plan(
        string $question,
        array $constraints = [],
        ?string $location = null,
        string $topic = 'irrigation',
    ): KnowledgeQueryPlan {
        $query = new AgriculturalKnowledgeQuery(
            originalQuestion: $question,
            normalizedQuestion: $question,
            language: 'en',
            agriculturalDomain: 'agronomy',
            subject: ['type' => 'crop', 'value' => 'wheat', 'label' => 'wheat'],
            crop: 'wheat',
            cropId: 'wheat',
            scientificName: null,
            topic: $topic,
            subtopic: null,
            requestedInformation: ['mechanism'],
            constraints: array_merge([
                'answer_language' => 'en',
                'question_type' => 'mechanism',
            ], $constraints),
            location: $location,
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
            topics: [$topic],
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
