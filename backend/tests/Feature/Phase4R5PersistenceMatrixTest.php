<?php

namespace Tests\Feature;

use App\Models\LibraryItem;
use App\Models\Organization;
use App\Services\Agriculture\Research\Home\HomeEvidenceLifecycleDisposition;
use App\Services\Agriculture\Research\Persistence\ScientificKnowledgePersistenceService;
use App\Services\Agriculture\Research\ResearchPlanner;
use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use App\Services\Agriculture\Research\Synthesis\AnswerSynthesisExecutionReport;
use App\Services\Agriculture\Research\Synthesis\ResearchAnswerCitation;
use App\Services\Agriculture\Research\Synthesis\ResearchAnswerClaim;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use App\Services\Agriculture\Research\Validation\EvidenceValidationExecutionReport;
use App\Services\Agriculture\Research\Validation\EvidenceValidationStatus;
use App\Services\Agriculture\Research\Validation\ScientificEvidenceItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 4 — R5 Library-save matrix (validation sufficiency gates persistence).
 */
class Phase4R5PersistenceMatrixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Organization::create(['name' => 'WSA Demo', 'slug' => 'wsa-demo']);
    }

    public function test_case1_sufficient_verified_with_citations_can_save(): void
    {
        $plan = $this->homePlan();
        $item = $this->usableItem('r5-ok');
        $validation = $this->validation([$item], evidenceSufficient: true);
        $synthesis = $this->synthesis(
            status: 'synthesis_completed',
            claims: [$this->claim('c1', $item)],
            citations: [$this->citation($item)],
        );

        $result = app(ScientificKnowledgePersistenceService::class)->persist(1, $plan, $synthesis, $validation);

        $this->assertTrue($result->performed);
        $this->assertNotNull($result->libraryItemId);
    }

    public function test_case2_insufficient_evidence_never_auto_saves(): void
    {
        $plan = $this->homePlan();
        $item = $this->usableItem('r5-insuf');
        $validation = $this->validation([$item], evidenceSufficient: false);
        $synthesis = $this->synthesis(
            status: 'synthesis_completed',
            claims: [$this->claim('c1', $item)],
            citations: [$this->citation($item)],
        );

        $result = app(ScientificKnowledgePersistenceService::class)->persist(1, $plan, $synthesis, $validation);

        $this->assertFalse($result->performed);
        $this->assertSame('insufficiently_verified', $result->status);
    }

    public function test_case3_no_citations_never_saves(): void
    {
        $plan = $this->homePlan();
        $item = $this->usableItem('r5-nocite');
        $validation = $this->validation([$item], evidenceSufficient: true);
        $synthesis = $this->synthesis(
            status: 'synthesis_completed',
            claims: [$this->claim('c1', $item)],
            citations: [],
        );

        $result = app(ScientificKnowledgePersistenceService::class)->persist(1, $plan, $synthesis, $validation);

        $this->assertFalse($result->performed);
        $this->assertSame('nothing_to_persist', $result->status);
    }

    public function test_case4_no_validated_evidence_never_saves(): void
    {
        $plan = $this->homePlan();
        $validation = $this->validation([], evidenceSufficient: true);
        $synthesis = $this->synthesis(
            status: 'synthesis_completed',
            claims: [$this->claim('c1', $this->usableItem('ghost'))],
            citations: [$this->citation($this->usableItem('ghost'))],
        );

        $result = app(ScientificKnowledgePersistenceService::class)->persist(1, $plan, $synthesis, $validation);

        $this->assertFalse($result->performed);
        $this->assertSame('nothing_to_persist', $result->status);
    }

    public function test_case5_insufficient_synthesis_status_never_saves(): void
    {
        $plan = $this->homePlan();
        $item = $this->usableItem('r5-status');
        $validation = $this->validation([$item], evidenceSufficient: true);
        $synthesis = $this->synthesis(
            status: 'insufficient_evidence',
            claims: [$this->claim('c1', $item)],
            citations: [$this->citation($item)],
            metadata: [
                'evidence_lifecycle_disposition' => HomeEvidenceLifecycleDisposition::INSUFFICIENT_FOR_SYNTHESIS,
            ],
        );

        $result = app(ScientificKnowledgePersistenceService::class)->persist(1, $plan, $synthesis, $validation);

        $this->assertFalse($result->performed);
        $this->assertSame('insufficiently_verified', $result->status);
    }

    private function homePlan()
    {
        return app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'What irrigation methods improve wheat yield?',
        ]);
    }

    /**
     * @param  list<ScientificEvidenceItem>  $items
     */
    private function validation(array $items, bool $evidenceSufficient): EvidenceValidationExecutionReport
    {
        return new EvidenceValidationExecutionReport(
            status: $items === [] ? 'no_valid_evidence' : 'validation_completed',
            validatedEvidence: $items,
            rejectedEvidence: [],
            sourcesReceived: max(1, count($items)),
            validatedCount: count($items),
            rejectedCount: 0,
            duplicateCount: 0,
            conflictingCount: 0,
            evidenceSufficient: $evidenceSufficient,
            validatorsUsed: [],
            qualityDistribution: [],
            searchSummary: ['search_status' => 'search_completed'],
            observability: [],
        );
    }

    /**
     * @param  list<ResearchAnswerClaim>  $claims
     * @param  list<ResearchAnswerCitation>  $citations
     * @param  array<string, mixed>  $metadata
     */
    private function synthesis(
        string $status,
        array $claims,
        array $citations,
        array $metadata = [],
    ): AnswerSynthesisExecutionReport {
        return new AnswerSynthesisExecutionReport(
            status: $status,
            performed: true,
            answer: 'Answer body',
            conciseSummary: 'Summary',
            detailedExplanation: '',
            keyFindings: ['Finding'],
            claims: $claims,
            citations: $citations,
            evidenceReferences: [],
            confidence: 0.7,
            limitations: [],
            uncertainty: null,
            conflicts: [],
            language: 'en',
            researchMetadata: $metadata,
            observability: [],
        );
    }

    private function usableItem(string $id): ScientificEvidenceItem
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
            doi: '10.9999/r5-'.$id,
            url: 'https://example.test/r5/'.$id,
            publicationYear: 2021,
            retrievedAt: '2026-09-21T00:00:00+00:00',
            agriculturalDomain: 'field_crops',
            claimTopic: 'irrigation',
            evidenceText: 'Wheat drip irrigation improved yield in field trials.',
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

    private function claim(string $id, ScientificEvidenceItem $item): ResearchAnswerClaim
    {
        return new ResearchAnswerClaim(
            claimId: $id,
            claimText: $item->evidenceText ?? 'claim',
            evidenceIds: [$item->evidenceId],
            sourceIds: [$item->sourceId],
            validationStatus: EvidenceValidationStatus::EVIDENCE_USABLE,
            claimRelationship: ClaimEvidenceRelationship::SUPPORTED,
            confidence: 0.8,
        );
    }

    private function citation(ScientificEvidenceItem $item): ResearchAnswerCitation
    {
        return new ResearchAnswerCitation(
            citationId: 'cite-'.$item->evidenceId,
            sourceId: $item->sourceId,
            evidenceId: $item->evidenceId,
            title: $item->publicationTitle,
            authors: $item->authors,
            organization: $item->institution,
            journal: $item->journal,
            doi: $item->doi,
            url: $item->url,
            publicationYear: $item->publicationYear,
            sourceType: $item->sourceType,
        );
    }
}