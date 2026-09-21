<?php

namespace Tests\Feature;

use App\Services\Agriculture\Research\Home\HomeEvidenceLifecycleDisposition;
use App\Services\Agriculture\Research\ResearchPlanner;
use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use App\Services\Agriculture\Research\Validation\EvidenceValidationExecutionReport;
use Tests\TestCase;

/**
 * Phase 4 Unit C — cross-component ownership integration.
 */
class Phase4ValidationOwnershipIntegrationTest extends TestCase
{
    public function test_home_disposition_owner_unchanged_by_evl(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'What irrigation methods improve wheat yield?',
        ]);
        $validation = new EvidenceValidationExecutionReport(
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
            searchSummary: ['search_status' => 'no_results'],
            observability: [],
        );

        $meta = (new HomeEvidenceLifecycleDisposition)->classify($plan, $validation, 0);
        $this->assertSame(
            HomeEvidenceLifecycleDisposition::NO_RESULTS_RETRIEVED,
            $meta['evidence_lifecycle_disposition'] ?? null,
        );
    }

    public function test_crop_disposition_remains_noop(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'farming needs',
            'selected_crop_id' => 'wheat',
            'selected_crop_name' => 'Wheat',
            'knowledge_option' => 'farming-needs',
        ]);
        $this->assertTrue($plan->toAgriculturalResearchPlan()->isCropProfileIntent());
        $this->assertSame([], (new HomeEvidenceLifecycleDisposition)->classify(
            $plan,
            new EvidenceValidationExecutionReport(
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
                searchSummary: [],
                observability: [],
            ),
            0,
        ));
    }

    public function test_claim_relation_vocabulary_still_independent(): void
    {
        foreach ([
            ScientificEvidenceDirectnessAssessor::DIRECT,
            ScientificEvidenceDirectnessAssessor::SUPPORTING,
            ScientificEvidenceDirectnessAssessor::BACKGROUND,
            ScientificEvidenceDirectnessAssessor::RELATED,
            ScientificEvidenceDirectnessAssessor::IRRELEVANT,
        ] as $label) {
            $this->assertNotContains($label, ClaimEvidenceRelationship::all());
        }
    }
}
