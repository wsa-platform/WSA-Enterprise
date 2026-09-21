<?php

namespace Tests\Feature;

use App\Services\Agriculture\Research\Home\HomeEvidenceLifecycleDisposition;
use App\Services\Agriculture\Research\ResearchPlanner;
use App\Services\Agriculture\Research\Synthesis\AnswerComposer;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use App\Services\Agriculture\Research\Validation\EvidenceValidationExecutionReport;
use App\Services\Agriculture\Research\Validation\EvidenceValidationStatus;
use App\Services\Agriculture\Research\Validation\ScientificEvidenceItem;
use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use Tests\TestCase;

/**
 * Phase 4 — Home vs Crop evidence disposition boundary.
 */
class Phase4HomeCropEvidenceContractTest extends TestCase
{
    public function test_home_plan_receives_disposition_metadata_from_owner(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'What irrigation methods improve wheat yield?',
        ]);
        $this->assertFalse($plan->toAgriculturalResearchPlan()->isCropProfileIntent());

        $item = $this->item('home-1');
        $validation = $this->validation([$item], true);
        $synthesis = app(AnswerComposer::class)->compose($plan, $validation);
        $decorated = (new HomeEvidenceLifecycleDisposition)->applyToSynthesis($plan, $validation, $synthesis);

        $this->assertArrayHasKey('evidence_lifecycle_disposition', $decorated->researchMetadata);
        $this->assertArrayHasKey('lifecycle_status', $decorated->researchMetadata);
        $this->assertNotSame('', (string) ($decorated->researchMetadata['evidence_lifecycle_disposition'] ?? ''));
    }

    public function test_crop_plan_skips_home_disposition_and_shares_composer_path(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'farming needs',
            'selected_crop_id' => 'wheat',
            'selected_crop_name' => 'Wheat',
            'knowledge_option' => 'farming-needs',
        ]);
        $this->assertTrue($plan->toAgriculturalResearchPlan()->isCropProfileIntent());

        $item = $this->item('crop-1');
        $validation = $this->validation([$item], true);
        $synthesis = app(AnswerComposer::class)->compose($plan, $validation);
        $decorated = (new HomeEvidenceLifecycleDisposition)->applyToSynthesis($plan, $validation, $synthesis);

        $this->assertSame($synthesis->researchMetadata, $decorated->researchMetadata);
        $this->assertArrayNotHasKey('evidence_lifecycle_disposition', $decorated->researchMetadata);
        $this->assertArrayNotHasKey('lifecycle_status', $decorated->researchMetadata);
    }

    /**
     * @param  list<ScientificEvidenceItem>  $items
     */
    private function validation(array $items, bool $sufficient): EvidenceValidationExecutionReport
    {
        return new EvidenceValidationExecutionReport(
            status: 'validation_completed',
            validatedEvidence: $items,
            rejectedEvidence: [],
            sourcesReceived: count($items),
            validatedCount: count($items),
            rejectedCount: 0,
            duplicateCount: 0,
            conflictingCount: 0,
            evidenceSufficient: $sufficient,
            validatorsUsed: [],
            qualityDistribution: [],
            searchSummary: ['search_status' => 'search_completed'],
            observability: [],
        );
    }

    private function item(string $id): ScientificEvidenceItem
    {
        return new ScientificEvidenceItem(
            evidenceId: $id,
            sourceId: 'src-'.$id,
            sourceKey: 'openalex',
            sourceType: 'peer_reviewed_journal',
            publicationTitle: 'Wheat irrigation fixture '.$id,
            authors: ['Fixture'],
            institution: 'Fixture',
            journal: 'Fixture Journal',
            doi: '10.9999/'.$id,
            url: 'https://example.test/'.$id,
            publicationYear: 2021,
            retrievedAt: '2026-09-21T00:00:00+00:00',
            agriculturalDomain: 'field_crops',
            claimTopic: 'irrigation',
            evidenceText: 'Wheat drip irrigation improved yield in multi-year field trials.',
            validationStatus: EvidenceValidationStatus::EVIDENCE_USABLE,
            validationFailures: [],
            claimRelationship: ClaimEvidenceRelationship::SUPPORTED,
            confidence: 0.85,
            qualityScore: 85.0,
            qualityFactors: [
                'evidence_directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
                'answer_eligible' => true,
            ],
            sourceAttribution: [
                'evidence_directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
            ],
            cropOrEntity: 'wheat',
        );
    }
}
