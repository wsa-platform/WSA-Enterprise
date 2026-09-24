<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\ResearchFeedbackRecord;
use App\Services\Agriculture\CropKnowledgeOptionCatalog;
use App\Services\Agriculture\FieldCropTaxonomyCatalog;
use App\Services\Agriculture\Research\AgriculturalResearchAgent;
use App\Services\Agriculture\Research\Feedback\PositiveResearchFeedbackService;
use App\Services\Agriculture\Research\Persistence\ScientificKnowledgePersistenceService;
use App\Services\Agriculture\Research\ResearchPlanner;
use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use App\Services\Agriculture\Research\Synthesis\AnswerComposer;
use App\Services\Agriculture\Research\Synthesis\AnswerSynthesisExecutionReport;
use App\Services\Agriculture\Research\Synthesis\ResearchAnswerCitation;
use App\Services\Agriculture\Research\Synthesis\ResearchAnswerClaim;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use App\Services\Agriculture\Research\Validation\EvidenceValidationExecutionReport;
use App\Services\Agriculture\Research\Validation\EvidenceValidationStatus;
use App\Services\Agriculture\Research\Validation\ScientificEvidenceItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Phase 6 U6.1 — R5/R7, FE/BE option parity, Stage-5 accuracy consumption, legacy gate.
 *
 * DIRTY-WIP NOTICE: AnswerComposer and taxonomy catalogs may be dirty. R5/R7 services are
 * largely on HEAD; option catalog is committed.
 */
class Phase6U61CropR5R7AndParityContractTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Organization::create([
            'name' => 'WSA Demo',
            'slug' => 'wsa-demo',
            'is_active' => true,
        ]);
        config(['wsa.public_organization_slug' => 'wsa-demo']);
    }

    public function test_i_r5_insufficient_crop_synthesis_is_not_auto_persisted(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'selected_crop_id' => 'wheat',
            'selected_crop_name' => 'Wheat',
            'knowledge_option' => 'farming-needs',
        ]);
        $this->assertTrue($plan->toAgriculturalResearchPlan()->isCropProfileIntent());

        $synthesis = new AnswerSynthesisExecutionReport(
            status: 'insufficient_evidence',
            performed: true,
            answer: 'Insufficient evidence for a factual crop answer.',
            conciseSummary: null,
            detailedExplanation: null,
            keyFindings: [],
            claims: [
                new ResearchAnswerClaim(
                    claimId: 'claim-qc-1',
                    claimText: '',
                    evidenceIds: [],
                    sourceIds: [],
                    validationStatus: 'insufficient_evidence',
                    claimRelationship: ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE,
                    confidence: 0.0,
                    numericalValues: [],
                    limitations: ['insufficient_validated_evidence_for_question_claim'],
                    conditions: null,
                    questionClaimId: 'qc-1',
                ),
            ],
            citations: [
                new ResearchAnswerCitation(
                    citationId: 'c1',
                    sourceId: 'src-1',
                    evidenceId: 'e1',
                    title: 'Fixture',
                    authors: ['Fixture'],
                    organization: null,
                    journal: null,
                    doi: null,
                    url: 'https://example.test/c1',
                    publicationYear: 2020,
                    sourceType: 'peer_reviewed_journal',
                ),
            ],
            evidenceReferences: [],
            confidence: 0.0,
            limitations: [],
            uncertainty: null,
            conflicts: [],
            language: 'en',
            researchMetadata: [],
            observability: [],
        );

        $validation = new EvidenceValidationExecutionReport(
            status: 'validation_completed',
            validatedEvidence: [$this->item('e1')],
            rejectedEvidence: [],
            sourcesReceived: 1,
            validatedCount: 1,
            rejectedCount: 0,
            duplicateCount: 0,
            conflictingCount: 0,
            evidenceSufficient: false,
            validatorsUsed: [],
            qualityDistribution: [],
            searchSummary: [],
            observability: [],
        );

        $report = app(ScientificKnowledgePersistenceService::class)->persist(1, $plan, $synthesis, $validation);
        $this->assertSame('insufficiently_verified', $report->status);
        $this->assertFalse($report->performed);
        $this->assertSame(
            'evidence_not_sufficient_for_library',
            $report->observability['failure_reason'] ?? null
        );
    }

    public function test_i_r5_crop_uses_shared_persistence_service(): void
    {
        $this->assertInstanceOf(
            ScientificKnowledgePersistenceService::class,
            app(ScientificKnowledgePersistenceService::class)
        );
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'selected_crop_id' => 'wheat',
            'selected_crop_name' => 'Wheat',
            'knowledge_option' => 'farming-needs',
        ]);
        $this->assertTrue($plan->toAgriculturalResearchPlan()->isCropProfileIntent());
    }

    public function test_j_r7_positive_crop_source_may_persist_and_negative_does_not(): void
    {
        $service = app(PositiveResearchFeedbackService::class);

        $positive = $service->record([
            'polarity' => PositiveResearchFeedbackService::POLARITY_POSITIVE,
            'question' => 'Wheat farming needs',
            'research_source' => 'crop',
            'what_worked' => 'clear sections',
        ]);
        $this->assertTrue((bool) ($positive['persisted'] ?? false));
        $row = ResearchFeedbackRecord::query()->findOrFail($positive['id']);
        $this->assertSame('crop', $row->research_source);
        $this->assertTrue((bool) ($row->payload['does_not_mutate_scientific_behavior'] ?? false));

        $negative = $service->record([
            'polarity' => PositiveResearchFeedbackService::POLARITY_NEGATIVE,
            'question' => 'Wheat farming needs',
            'research_source' => 'crop',
        ]);
        $this->assertFalse((bool) ($negative['persisted'] ?? true));
        $this->assertSame('negative_feedback_not_persisted', $negative['reason'] ?? null);
        $this->assertSame(1, ResearchFeedbackRecord::query()->count());
    }

    public function test_n_fe_be_option_ids_overlap(): void
    {
        $feOptionIds = ['farming-needs', 'scientific-research', 'industries'];
        foreach ($feOptionIds as $id) {
            $this->assertTrue(CropKnowledgeOptionCatalog::isImplemented($id), $id);
        }
        $beKeys = array_column(CropKnowledgeOptionCatalog::options(), 'key');
        $this->assertSame($feOptionIds, $beKeys);
    }

    public function test_n_be_option_titles_are_multilingual_with_arabic_default(): void
    {
        $options = CropKnowledgeOptionCatalog::options();
        foreach ($options as $option) {
            $this->assertArrayHasKey('title_ar', $option);
            $this->assertArrayHasKey('title_en', $option);
            $this->assertArrayHasKey('title_fr', $option);
            $this->assertArrayHasKey('title_tr', $option);
        }
        $title = CropKnowledgeOptionCatalog::titleFor('farming-needs', 'القمح');
        $this->assertStringContainsString('القمح', $title);
        $this->assertSame(
            CropKnowledgeOptionCatalog::titleFor('farming-needs', 'Wheat', 'ar'),
            CropKnowledgeOptionCatalog::titleFor('farming-needs', 'Wheat')
        );
    }

    public function test_n_core_fe_crop_ids_exist_in_backend_taxonomy(): void
    {
        foreach (['wheat', 'corn', 'rice', 'barley', 'sorghum'] as $cropId) {
            $scientific = FieldCropTaxonomyCatalog::scientificNameFor($cropId);
            $this->assertNotSame('', trim((string) $scientific), $cropId);
        }
    }

    public function test_a_phase5_accuracy_gate_is_reachable_via_composer_on_crop_plan(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'selected_crop_id' => 'wheat',
            'selected_crop_name' => 'Wheat',
            'knowledge_option' => 'farming-needs',
        ]);
        $composer = app(AnswerComposer::class);
        $validation = new EvidenceValidationExecutionReport(
            status: 'validation_completed',
            validatedEvidence: [$this->item('e-acc')],
            rejectedEvidence: [],
            sourcesReceived: 1,
            validatedCount: 1,
            rejectedCount: 0,
            duplicateCount: 0,
            conflictingCount: 0,
            evidenceSufficient: true,
            validatorsUsed: [],
            qualityDistribution: [],
            searchSummary: [],
            observability: [],
        );
        $synthesis = $composer->compose($plan, $validation);
        $this->assertNotSame('', (string) $synthesis->status);
    }

    public function test_k_agent_skips_legacy_when_crop_stage5_is_sufficient(): void
    {
        $agent = app(AgriculturalResearchAgent::class);
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'selected_crop_id' => 'wheat',
            'selected_crop_name' => 'Wheat',
            'knowledge_option' => 'farming-needs',
        ])->toAgriculturalResearchPlan();
        $this->assertTrue($plan->isCropProfileIntent());

        $method = new ReflectionMethod($agent, 'hasSufficientScientificSynthesis');
        $method->setAccessible(true);
        $synthesis = new AnswerSynthesisExecutionReport(
            status: 'scientific_generated',
            performed: true,
            answer: 'Stage 5 answer body',
            conciseSummary: null,
            detailedExplanation: null,
            keyFindings: [],
            claims: [],
            citations: [
                new ResearchAnswerCitation(
                    citationId: 'c1',
                    sourceId: 'src-1',
                    evidenceId: 'e1',
                    title: 'Fixture',
                    authors: ['Fixture'],
                    organization: null,
                    journal: null,
                    doi: null,
                    url: 'https://example.test/c1',
                    publicationYear: 2020,
                    sourceType: 'peer_reviewed_journal',
                ),
            ],
            evidenceReferences: [],
            confidence: 0.9,
            limitations: [],
            uncertainty: null,
            conflicts: [],
            language: 'en',
            researchMetadata: ['direct_evidence_gate' => 'PASSED'],
            observability: [],
        );
        $this->assertTrue($method->invoke($agent, $synthesis));
    }

    private function item(string $id): ScientificEvidenceItem
    {
        return new ScientificEvidenceItem(
            evidenceId: $id,
            sourceId: 'src-'.$id,
            sourceKey: 'openalex',
            sourceType: 'peer_reviewed_journal',
            publicationTitle: 'Wheat fixture '.$id,
            authors: ['Fixture'],
            institution: 'Fixture',
            journal: 'Fixture Journal',
            doi: '10.9999/u61-r5-'.$id,
            url: 'https://example.test/u61-r5/'.$id,
            publicationYear: 2021,
            retrievedAt: '2026-09-22T00:00:00+00:00',
            agriculturalDomain: 'field_crops',
            claimTopic: 'cultivation',
            evidenceText: 'Wheat cultivation practices for dryland systems.',
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
