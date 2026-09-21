<?php

namespace Tests\Feature;

use App\Services\Agriculture\Research\Home\HomeEvidenceLifecycleDisposition;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\ResearchPlanner;
use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use App\Services\Agriculture\Research\Synthesis\AnswerComposer;
use App\Services\Agriculture\Research\Synthesis\AnswerSynthesisExecutionReport;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use App\Services\Agriculture\Research\Validation\EvidenceValidationExecutionReport;
use App\Services\Agriculture\Research\Validation\EvidenceValidationStatus;
use App\Services\Agriculture\Research\Validation\ScientificEvidenceItem;
use ReflectionClass;
use Tests\TestCase;

/**
 * Phase 4 — RC-E evidence ownership: disposition single-writer + axis separation.
 */
class Phase4EvidenceOwnershipContractTest extends TestCase
{
    public function test_home_disposition_is_sole_classify_owner_for_composer_delegation(): void
    {
        $plan = $this->homePlan();
        $validation = $this->validationReport(
            items: [],
            rejected: 2,
            sourcesReceived: 2,
            searchStatus: 'search_completed',
            evidenceSufficient: false,
            searchSummaryExtra: [
                'failed_sources' => [],
                'successful_sources' => ['openalex'],
            ],
        );

        $owner = new HomeEvidenceLifecycleDisposition;
        $fromOwner = $owner->classify($plan, $validation, composerEligibleCount: 0);

        $this->assertSame(
            HomeEvidenceLifecycleDisposition::RETRIEVED_BUT_REJECTED,
            $fromOwner['evidence_lifecycle_disposition'] ?? null,
        );
        $this->assertArrayHasKey('lifecycle_status', $fromOwner);
        $this->assertSame($fromOwner['evidence_lifecycle_disposition'], $fromOwner['lifecycle_status']);

        $composer = app(AnswerComposer::class);
        $ref = new ReflectionClass($composer);
        if ($ref->hasMethod('homeEvidenceLifecycleMetadata')) {
            // WIP/compat path: Composer may embed metadata only by delegating to the owner.
            $method = $ref->getMethod('homeEvidenceLifecycleMetadata');
            $method->setAccessible(true);
            $fromComposer = $method->invoke($composer, $plan, $validation, 0, null);
            $this->assertSame($fromOwner, $fromComposer);
        } else {
            // HEAD path: Composer must not redefine disposition independently.
            $this->assertFalse($ref->hasMethod('isHomeGenericResearchPlan'));
        }
    }

    public function test_apply_to_synthesis_is_authoritative_final_writer(): void
    {
        $plan = $this->homePlan();
        $item = $this->item(
            id: 'p4-own-1',
            text: 'Wheat yield under drip irrigation increased in controlled field trials.',
            relationship: ClaimEvidenceRelationship::SUPPORTED,
            directness: ScientificEvidenceDirectnessAssessor::DIRECT,
        );
        $validation = $this->validationReport(
            items: [$item],
            sourcesReceived: 1,
            searchStatus: 'search_completed',
            evidenceSufficient: true,
        );

        $preliminary = new AnswerSynthesisExecutionReport(
            status: 'synthesis_completed',
            performed: true,
            answer: 'Preliminary',
            conciseSummary: 'Preliminary',
            detailedExplanation: '',
            keyFindings: [],
            claims: [],
            citations: [],
            evidenceReferences: [],
            confidence: 0.5,
            limitations: [],
            uncertainty: null,
            conflicts: [],
            language: 'en',
            researchMetadata: [
                'evidence_lifecycle_disposition' => 'composer_used',
                'direct_evidence_gate' => 'INSUFFICIENT_DIRECT_EVIDENCE',
                'usable_evidence_count' => 0,
            ],
            observability: [
                'usable_evidence_count' => 0,
                'evidence_lifecycle_disposition' => 'composer_used',
            ],
        );

        $final = (new HomeEvidenceLifecycleDisposition)->applyToSynthesis($plan, $validation, $preliminary);

        $this->assertSame(
            HomeEvidenceLifecycleDisposition::INSUFFICIENT_DIRECT_SUPPORTING_RETAINED,
            $final->researchMetadata['evidence_lifecycle_disposition'] ?? null,
        );
        $this->assertSame(
            HomeEvidenceLifecycleDisposition::INSUFFICIENT_DIRECT_SUPPORTING_RETAINED,
            $final->observability['evidence_lifecycle_disposition'] ?? null,
        );
        $this->assertSame(
            HomeEvidenceLifecycleDisposition::INSUFFICIENT_DIRECT_SUPPORTING_RETAINED,
            $final->researchMetadata['lifecycle_status'] ?? null,
        );
    }

    public function test_crop_profile_disposition_remains_noop(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'farming needs',
            'selected_crop_id' => 'wheat',
            'selected_crop_name' => 'Wheat',
            'knowledge_option' => 'farming-needs',
        ]);
        $this->assertTrue($plan->toAgriculturalResearchPlan()->isCropProfileIntent());

        $owner = new HomeEvidenceLifecycleDisposition;
        $this->assertSame([], $owner->classify($plan, $this->validationReport(items: []), 0));

        $synthesis = new AnswerSynthesisExecutionReport(
            status: 'synthesis_completed',
            performed: true,
            answer: 'Crop',
            conciseSummary: 'Crop',
            detailedExplanation: '',
            keyFindings: [],
            claims: [],
            citations: [],
            evidenceReferences: [],
            confidence: 0.4,
            limitations: [],
            uncertainty: null,
            conflicts: [],
            language: 'en',
            researchMetadata: ['untouched' => true],
            observability: [],
        );
        $after = $owner->applyToSynthesis($plan, $this->validationReport(items: []), $synthesis);
        $this->assertTrue((bool) ($after->researchMetadata['untouched'] ?? false));
        $this->assertArrayNotHasKey('evidence_lifecycle_disposition', $after->researchMetadata);
    }

    public function test_r6_axes_remain_independent_on_evidence_item(): void
    {
        $item = $this->item(
            id: 'p4-axes-1',
            text: 'Supporting wheat irrigation review.',
            relationship: ClaimEvidenceRelationship::PARTIALLY_SUPPORTED,
            directness: ScientificEvidenceDirectnessAssessor::SUPPORTING,
        );

        $this->assertSame(ClaimEvidenceRelationship::PARTIALLY_SUPPORTED, $item->claimRelationship);
        $this->assertSame(
            ScientificEvidenceDirectnessAssessor::SUPPORTING,
            $item->qualityFactors['evidence_directness'] ?? null,
        );
        $this->assertNotSame($item->claimRelationship, $item->qualityFactors['evidence_directness']);
        $this->assertArrayNotHasKey('evidence_lifecycle_disposition', $item->qualityFactors);
    }

    private function homePlan(): KnowledgeQueryPlan
    {
        return app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'What irrigation methods improve wheat yield?',
        ]);
    }

    /**
     * @param  list<ScientificEvidenceItem>  $items
     * @param  list<ScientificEvidenceItem>  $rejectedEvidence
     * @param  array<string, mixed>  $searchSummaryExtra
     */
    private function validationReport(
        array $items = [],
        int $rejected = 0,
        int $sourcesReceived = 0,
        string $searchStatus = 'search_completed',
        bool $evidenceSufficient = false,
        array $rejectedEvidence = [],
        array $searchSummaryExtra = [],
    ): EvidenceValidationExecutionReport {
        return new EvidenceValidationExecutionReport(
            status: $items === [] ? 'no_valid_evidence' : 'validation_completed',
            validatedEvidence: $items,
            rejectedEvidence: $rejectedEvidence,
            sourcesReceived: $sourcesReceived > 0 ? $sourcesReceived : count($items) + $rejected,
            validatedCount: count($items),
            rejectedCount: $rejected > 0 ? $rejected : count($rejectedEvidence),
            duplicateCount: 0,
            conflictingCount: 0,
            evidenceSufficient: $evidenceSufficient,
            validatorsUsed: [],
            qualityDistribution: [],
            searchSummary: array_merge([
                'search_status' => $searchStatus,
                'failed_sources' => [],
                'successful_sources' => [],
            ], $searchSummaryExtra),
            observability: [],
        );
    }

    private function item(
        string $id,
        string $text,
        string $relationship,
        string $directness,
    ): ScientificEvidenceItem {
        return new ScientificEvidenceItem(
            evidenceId: $id,
            sourceId: 'src-'.$id,
            sourceKey: 'openalex',
            sourceType: 'peer_reviewed_journal',
            publicationTitle: 'Phase4 ownership fixture '.$id,
            authors: ['Fixture'],
            institution: 'Fixture Institution',
            journal: 'Fixture Journal',
            doi: '10.9999/'.$id,
            url: 'https://example.test/'.$id,
            publicationYear: 2020,
            retrievedAt: '2026-09-21T00:00:00+00:00',
            agriculturalDomain: 'field_crops',
            claimTopic: 'irrigation',
            evidenceText: $text,
            validationStatus: EvidenceValidationStatus::EVIDENCE_USABLE,
            validationFailures: [],
            claimRelationship: $relationship,
            confidence: 0.8,
            qualityScore: 80.0,
            qualityFactors: [
                'evidence_directness' => $directness,
                'answer_eligible' => true,
            ],
            sourceAttribution: [
                'evidence_directness' => $directness,
            ],
            cropOrEntity: 'wheat',
        );
    }
}
