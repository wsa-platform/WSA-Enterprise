<?php

namespace Tests\Feature;

use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use App\Services\Agriculture\Research\Search\ScientificResultRanker;
use App\Services\Agriculture\Research\Validation\EvidenceVerificationLayer;
use App\Services\Agriculture\Research\Validation\EvidenceValidationExecutionReport;
use ReflectionClass;
use Tests\TestCase;

/**
 * Phase 4 Unit C — EVL + validation ownership integration.
 */
class Phase4EvidenceVerificationImplementationTest extends TestCase
{
    public function test_evl_does_not_define_disposition_or_sufficiency_api(): void
    {
        $ref = new ReflectionClass(EvidenceVerificationLayer::class);
        $this->assertFalse($ref->hasMethod('classifyDisposition'));
        $this->assertFalse($ref->hasMethod('isEvidenceSufficient'));
        $this->assertFalse($ref->hasMethod('shouldPersist'));
    }

    public function test_geo_mismatch_reason_is_traceable(): void
    {
        $plan = $this->plan(
            'What are the types of agricultural land in Egypt?',
            location: 'Egypt',
            constraints: [
                'scientific_sense' => 'land_classification',
                'question_type' => 'classification',
                'required_evidence_type' => 'classification_or_types_inventory',
            ],
        );

        $refined = app(EvidenceVerificationLayer::class)->assess(
            $plan,
            'Soil types inventory of Brazil',
            'Nationwide inventory of soil types and land classes across Brazil.',
            '10.9999/geo',
            null,
        );

        $this->assertArrayHasKey('reasons', $refined);
        $this->assertIsArray($refined['reasons']);
        if ($refined['directness'] === ScientificEvidenceDirectnessAssessor::GEOGRAPHIC_MISMATCH) {
            $this->assertSame(
                EvidenceVerificationLayer::LABEL_GEOGRAPHIC_MISMATCH,
                $refined['verification_label'],
            );
        }
        $this->assertArrayNotHasKey('evidence_lifecycle_disposition', $refined);
    }

    public function test_validation_report_sufficiency_independent_from_evl_and_ranker(): void
    {
        $report = new EvidenceValidationExecutionReport(
            status: 'no_valid_evidence',
            validatedEvidence: [],
            rejectedEvidence: [],
            sourcesReceived: 0,
            validatedCount: 0,
            rejectedCount: 0,
            duplicateCount: 0,
            conflictingCount: 0,
            evidenceSufficient: false,
            validatorsUsed: [],
            qualityDistribution: [],
            searchSummary: [
                'search_status' => 'no_results',
                'faostat_pipeline_outcome' => ['stage' => 'EMPTY_RESULT'],
            ],
            observability: [],
        );

        $this->assertFalse($report->evidenceSufficient);
        $this->assertSame('EMPTY_RESULT', $report->searchSummary['faostat_pipeline_outcome']['stage'] ?? null);
        $this->assertFalse(method_exists(EvidenceVerificationLayer::class, 'isEvidenceSufficient'));
        $this->assertFalse(method_exists(ScientificResultRanker::class, 'isEvidenceSufficient'));
    }

    /**
     * @param  array<string, mixed>  $constraints
     */
    private function plan(
        string $question,
        ?string $location = null,
        array $constraints = [],
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
            topic: 'land_classification',
            subtopic: null,
            requestedInformation: ['classification'],
            constraints: array_merge([
                'answer_language' => 'en',
                'question_type' => 'classification',
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
            topics: ['land_classification'],
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
