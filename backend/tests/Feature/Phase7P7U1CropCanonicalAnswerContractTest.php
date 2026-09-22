<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\ResearchFeedbackRecord;
use App\Services\Agriculture\Research\CropCanonicalStage5Response;
use App\Services\Agriculture\Research\Feedback\PositiveResearchFeedbackService;
use App\Services\Agriculture\Research\Persistence\ScientificKnowledgePersistenceService;
use App\Services\Agriculture\Research\ResearchPlanner;
use App\Services\Agriculture\Research\Synthesis\AnswerSynthesisExecutionReport;
use App\Services\Agriculture\Research\Synthesis\ResearchAnswerCitation;
use App\Services\Agriculture\Research\Synthesis\ResearchAnswerClaim;
use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use App\Services\Agriculture\Research\Validation\EvidenceValidationExecutionReport;
use App\Services\Agriculture\Research\Validation\EvidenceValidationStatus;
use App\Services\Agriculture\Research\Validation\ScientificEvidenceItem;
use Database\Seeders\FieldCropCultivationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Phase 7 P7-U1 / STRUCT-03 — Crop dual-emit: Stage 5 canonical at root + legacy compatibility.
 */
class Phase7P7U1CropCanonicalAnswerContractTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Organization::create(['name' => 'WSA Demo', 'slug' => 'wsa-demo', 'is_active' => true]);
        config(['wsa.public_organization_slug' => 'wsa-demo']);
        Http::fake([
            'api.openalex.org/works*' => Http::response(['results' => []], 200),
            'api.crossref.org/works*' => Http::response(['message' => ['items' => []]], 200),
            'api.semanticscholar.org/*' => Http::response(['data' => []], 200),
        ]);
    }

    public function test_a_dual_emit_promotes_stage5_fields_matching_same_synthesis_report(): void
    {
        $synthesis = $this->makeSynthesisReport(
            status: 'completed',
            answer: 'Wheat requires well-drained soils.',
            language: 'en',
            limitations: ['limited_geo_coverage'],
            conflicts: [['type' => 'claim_conflict', 'detail' => 'irrigation amounts differ']],
            questionClaimId: 'qc-wheat-1',
            citationUrl: 'https://example.org/wheat-source',
        );

        $legacy = [
            'crop' => ['id' => 'wheat', 'name' => 'Wheat'],
            'title' => 'Crop cultivation and needs for Wheat',
            'load_state' => 'library_complete',
            'sections' => [['key' => 'soil', 'title' => 'Soil', 'content' => 'legacy', 'source' => null, 'verified' => true]],
            'references' => [['title' => 'Legacy ref', 'url' => 'https://library.example/ref']],
            'library' => ['item_id' => 1, 'slug' => 'wheat', 'reused_existing' => true, 'missing_sections_filled' => []],
            'research_agent' => [
                'stage' => 5,
                'synthesis' => $synthesis->toArray(),
            ],
        ];

        $response = CropCanonicalStage5Response::dualEmit($legacy, $synthesis);
        $canonical = CropCanonicalStage5Response::canonicalClientFields($synthesis);

        // A — root canonical answer matches Stage 5 source (same report instance serialization).
        $this->assertSame($canonical['answer'], $response['answer']);
        $this->assertSame($canonical['concise_summary'], $response['concise_summary']);
        $this->assertSame($canonical['claims'], $response['claims']);
        $this->assertSame($canonical['citations'], $response['citations']);
        $this->assertSame($canonical['confidence'], $response['confidence']);
        $this->assertSame($canonical['limitations'], $response['limitations']);
        $this->assertSame($canonical['language'], $response['language']);
        $this->assertSame($canonical['status'], $response['status']);
        $this->assertSame($canonical['conflicts'], $response['conflicts']);
        $this->assertSame($canonical['uncertainty'], $response['uncertainty']);
        $this->assertSame($canonical['evidence_references'], $response['evidence_references']);
        $this->assertSame($canonical['key_findings'], $response['key_findings']);
        $this->assertSame($canonical['detailed_explanation'], $response['detailed_explanation']);
        $this->assertSame($canonical['research_metadata'], $response['research_metadata']);

        // Nested research_agent.synthesis remains the same Stage 5 payload.
        $this->assertSame($synthesis->toArray(), $response['research_agent']['synthesis']);
        $this->assertSame($response['answer'], $response['research_agent']['synthesis']['answer']);
        $this->assertSame($response['claims'], $response['research_agent']['synthesis']['claims']);
    }

    public function test_b_legacy_compatibility_fields_preserved(): void
    {
        $synthesis = $this->makeSynthesisReport(status: 'completed', answer: 'Answer', language: 'ar');
        $legacy = [
            'crop' => ['id' => 'wheat', 'name' => 'القمح'],
            'title' => 'زراعة واحتياجات محصول القمح',
            'load_state' => 'library_complete',
            'sections' => [['key' => 'soil', 'title' => 'التربة', 'content' => 'x', 'source' => null, 'verified' => true]],
            'references' => [],
            'library' => ['item_id' => 9, 'slug' => 'wheat', 'reused_existing' => true, 'missing_sections_filled' => []],
            'service_option' => 'farming-needs',
        ];

        $response = CropCanonicalStage5Response::dualEmit($legacy, $synthesis);

        $this->assertSame($legacy['sections'], $response['sections']);
        $this->assertSame($legacy['title'], $response['title']);
        $this->assertSame($legacy['load_state'], $response['load_state']);
        $this->assertSame($legacy['crop'], $response['crop']);
        $this->assertSame($legacy['references'], $response['references']);
        $this->assertSame($legacy['library'], $response['library']);
        $this->assertSame('farming-needs', $response['service_option']);
    }

    public function test_d_conflicts_visible_at_canonical_root(): void
    {
        $conflicts = [['claim_id' => 'c1', 'relationship' => 'conflicts']];
        $synthesis = $this->makeSynthesisReport(
            status: 'conflicted',
            answer: null,
            language: 'en',
            conflicts: $conflicts,
            limitations: ['conflicting_evidence'],
        );

        $response = CropCanonicalStage5Response::dualEmit([
            'sections' => [],
            'load_state' => 'insufficient_verified_sources',
            'crop' => ['id' => 'wheat'],
            'title' => 't',
            'references' => [],
            'library' => ['item_id' => null],
        ], $synthesis);

        $this->assertSame($conflicts, $response['conflicts']);
        $this->assertSame('conflicted', $response['status']);
        $this->assertContains('conflicting_evidence', $response['limitations']);
        // Legacy load_state remains independently.
        $this->assertSame('insufficient_verified_sources', $response['load_state']);
    }

    public function test_e_insufficient_evidence_status_and_limitations_survive(): void
    {
        $synthesis = $this->makeSynthesisReport(
            status: 'insufficient_evidence',
            answer: 'Insufficient evidence for a factual crop answer.',
            language: 'en',
            limitations: ['insufficient_validated_evidence_for_question_claim'],
            questionClaimId: 'qc-1',
        );

        $response = CropCanonicalStage5Response::dualEmit([
            'sections' => [],
            'load_state' => 'insufficient_verified_sources',
            'crop' => ['id' => 'wheat'],
            'title' => 't',
            'references' => [],
            'library' => ['item_id' => null],
        ], $synthesis);

        $this->assertSame('insufficient_evidence', $response['status']);
        $this->assertSame(
            ['insufficient_validated_evidence_for_question_claim'],
            $response['limitations']
        );
        $this->assertSame('qc-1', $response['claims'][0]['question_claim_id'] ?? null);
    }

    public function test_f_language_equals_synthesis_language_not_ui_locale(): void
    {
        app()->setLocale('fr');
        $synthesis = $this->makeSynthesisReport(status: 'completed', answer: 'Respuesta', language: 'es');
        $response = CropCanonicalStage5Response::dualEmit(['sections' => [], 'load_state' => 'x', 'crop' => [], 'title' => 't', 'references' => [], 'library' => []], $synthesis);

        $this->assertSame('es', $response['language']);
        $this->assertSame('es', $synthesis->language);
        $this->assertNotSame(app()->getLocale(), $response['language']);
    }

    public function test_g_citation_urls_remain_direct_original_sources(): void
    {
        $url = 'https://doi.org/10.1000/wheat.example';
        $synthesis = $this->makeSynthesisReport(
            status: 'completed',
            answer: 'A',
            language: 'en',
            citationUrl: $url,
            citationDoi: '10.1000/wheat.example',
        );
        $response = CropCanonicalStage5Response::dualEmit(['sections' => [], 'load_state' => 'x', 'crop' => [], 'title' => 't', 'references' => [], 'library' => []], $synthesis);

        $this->assertSame($url, $response['citations'][0]['url'] ?? null);
        $this->assertSame('10.1000/wheat.example', $response['citations'][0]['doi'] ?? null);
        $this->assertStringNotContainsString('google.com', (string) ($response['citations'][0]['url'] ?? ''));
        $this->assertSame('ev-1', $response['citations'][0]['evidence_id'] ?? null);
        $this->assertSame('cite-1', $response['citations'][0]['citation_id'] ?? null);
    }

    public function test_h_question_claim_and_evidence_identity_intact(): void
    {
        $synthesis = $this->makeSynthesisReport(
            status: 'completed',
            answer: 'A',
            language: 'en',
            questionClaimId: 'qc-42',
            citationUrl: 'https://example.org/a',
        );
        $response = CropCanonicalStage5Response::dualEmit(['sections' => [], 'load_state' => 'x', 'crop' => [], 'title' => 't', 'references' => [], 'library' => []], $synthesis);

        $this->assertSame('qc-42', $response['claims'][0]['question_claim_id']);
        $this->assertSame(['ev-1'], $response['claims'][0]['evidence_ids']);
        $this->assertSame('claim-1', $response['claims'][0]['claim_id']);
        $this->assertSame('ev-1', $response['citations'][0]['evidence_id']);
    }

    public function test_http_crop_profile_dual_emits_stage5_and_legacy(): void
    {
        $this->seed(FieldCropCultivationSeeder::class);

        $response = $this->getJson('/api/v1/public/field-crops/farming-needs-profile?'.http_build_query([
            'organization' => 'wsa-demo',
            'selected_crop_id' => 'wheat',
            'selected_crop_name' => 'القمح',
            'knowledge_option' => 'farming-needs',
        ]));

        $response->assertOk();
        $payload = $response->json();

        // B — legacy compatibility
        $this->assertArrayHasKey('sections', $payload);
        $this->assertNotEmpty($payload['sections']);
        $this->assertArrayHasKey('title', $payload);
        $this->assertArrayHasKey('load_state', $payload);
        $this->assertArrayHasKey('crop', $payload);
        $this->assertArrayHasKey('references', $payload);
        $this->assertArrayHasKey('library', $payload);
        $this->assertSame('wheat', $payload['crop']['id'] ?? null);

        // A — Stage 5 nested remains and root mirrors nested synthesis when present
        $this->assertArrayHasKey('research_agent', $payload);
        $this->assertIsArray($payload['research_agent']['synthesis'] ?? null);
        $nested = $payload['research_agent']['synthesis'];

        foreach (['status', 'answer', 'claims', 'citations', 'confidence', 'limitations', 'language'] as $field) {
            $this->assertArrayHasKey($field, $payload, "Crop root missing canonical field: {$field}");
            $this->assertSame(
                $nested[$field],
                $payload[$field],
                "Root {$field} must match nested Stage 5 synthesis"
            );
        }

        // C — same synthesis (root answer equals nested)
        $this->assertSame($nested['answer'], $payload['answer']);
        $this->assertSame($nested['claims'], $payload['claims']);
        $this->assertSame($nested['citations'], $payload['citations']);
    }

    public function test_i_home_query_keeps_top_level_stage5_without_crop_legacy_shape(): void
    {
        $response = $this->postJson('/api/v1/public/research-agent/query', [
            'organization' => 'wsa-demo',
            'query' => 'What is the average wheat yield in Egypt?',
        ]);

        $response->assertOk();
        $payload = $response->json();

        // Home remains Stage-5-oriented at root (answer/status/confidence contract).
        $this->assertArrayHasKey('status', $payload);
        $this->assertTrue(
            array_key_exists('answer', $payload)
            || array_key_exists('concise_summary', $payload)
            || array_key_exists('limitations', $payload)
            || array_key_exists('citations', $payload),
            'Home response must retain Stage 5 client fields at root'
        );

        // J — must not accidentally become Crop legacy profile.
        $this->assertArrayNotHasKey('service_option', $payload);
        $this->assertFalse(
            isset($payload['crop']['id']) && isset($payload['sections']) && is_array($payload['sections']) && ($payload['load_state'] ?? null) === 'library_complete',
            'Home must not receive Crop library-complete legacy envelope'
        );
    }

    public function test_k_r5_insufficient_crop_synthesis_still_not_auto_persisted(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'selected_crop_id' => 'wheat',
            'selected_crop_name' => 'Wheat',
            'knowledge_option' => 'farming-needs',
        ]);
        $this->assertTrue($plan->toAgriculturalResearchPlan()->isCropProfileIntent());

        $synthesis = $this->makeSynthesisReport(
            status: 'insufficient_evidence',
            answer: 'Insufficient evidence for a factual crop answer.',
            language: 'en',
            limitations: ['insufficient_validated_evidence_for_question_claim'],
            questionClaimId: 'qc-1',
            confidence: 0.0,
        );

        // Dual-emit must not alter persistence decision inputs — same synthesis object.
        $dual = CropCanonicalStage5Response::dualEmit([
            'sections' => [],
            'load_state' => 'insufficient_verified_sources',
            'crop' => ['id' => 'wheat'],
            'title' => 't',
            'references' => [],
            'library' => ['item_id' => null],
        ], $synthesis);
        $this->assertSame('insufficient_evidence', $dual['status']);

        $validation = new EvidenceValidationExecutionReport(
            status: 'validation_completed',
            validatedEvidence: [$this->evidenceItem('e1')],
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
    }

    public function test_l_r7_positive_only_unchanged(): void
    {
        $service = app(PositiveResearchFeedbackService::class);

        $negative = $service->record([
            'polarity' => 'negative',
            'research_source' => 'crop',
            'question' => 'wheat?',
        ]);
        $this->assertFalse($negative['persisted']);
        $this->assertSame('negative_feedback_not_persisted', $negative['reason'] ?? null);

        $positive = $service->record([
            'polarity' => 'positive',
            'research_source' => 'crop',
            'question' => 'wheat?',
            'what_worked' => 'clear answer',
        ]);
        $this->assertTrue($positive['persisted']);
        $this->assertSame(1, ResearchFeedbackRecord::query()->count());
    }

    /**
     * @param  list<string>  $limitations
     * @param  list<array<string, mixed>>  $conflicts
     */
    private function makeSynthesisReport(
        string $status,
        ?string $answer,
        string $language,
        array $limitations = [],
        array $conflicts = [],
        ?string $questionClaimId = 'qc-1',
        ?string $citationUrl = 'https://example.org/source',
        ?string $citationDoi = null,
        float $confidence = 0.72,
    ): AnswerSynthesisExecutionReport {
        $claims = [];
        if ($questionClaimId !== null) {
            $claims[] = new ResearchAnswerClaim(
                claimId: 'claim-1',
                claimText: (string) $answer,
                evidenceIds: ['ev-1'],
                sourceIds: ['src-1'],
                validationStatus: $status,
                claimRelationship: $conflicts === []
                    ? ClaimEvidenceRelationship::SUPPORTED
                    : ClaimEvidenceRelationship::CONFLICTING,
                confidence: $confidence,
                numericalValues: [],
                limitations: $limitations,
                conditions: null,
                questionClaimId: $questionClaimId,
            );
        }

        $citations = [];
        if ($citationUrl !== null) {
            $citations[] = new ResearchAnswerCitation(
                citationId: 'cite-1',
                sourceId: 'src-1',
                evidenceId: 'ev-1',
                title: 'Source',
                authors: ['Author'],
                organization: 'Org',
                journal: null,
                doi: $citationDoi,
                url: $citationUrl,
                publicationYear: 2020,
                sourceType: 'journal',
            );
        }

        return new AnswerSynthesisExecutionReport(
            status: $status,
            performed: true,
            answer: $answer,
            conciseSummary: $answer,
            detailedExplanation: $answer,
            keyFindings: $answer !== null ? [$answer] : [],
            claims: $claims,
            citations: $citations,
            evidenceReferences: [['evidence_id' => 'ev-1', 'title' => 'Source']],
            confidence: $confidence,
            limitations: $limitations,
            uncertainty: $status === 'insufficient_evidence' ? 'insufficient_evidence' : null,
            conflicts: $conflicts,
            language: $language,
            researchMetadata: ['direct_evidence_gate' => $status === 'completed' ? 'PASSED' : 'FAILED'],
            observability: ['composer' => 'test'],
        );
    }

    private function evidenceItem(string $id): ScientificEvidenceItem
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
            doi: '10.9999/p7u1-'.$id,
            url: 'https://example.test/p7u1/'.$id,
            publicationYear: 2021,
            retrievedAt: '2026-09-22T00:00:00+00:00',
            agriculturalDomain: 'field_crops',
            claimTopic: 'cultivation',
            evidenceText: 'Wheat cultivation practices for dryland systems.',
            validationStatus: EvidenceValidationStatus::EVIDENCE_USABLE,
            validationFailures: [],
            claimRelationship: ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE,
            confidence: 0.2,
            qualityScore: 20.0,
            qualityFactors: [
                'evidence_directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
                'answer_eligible' => false,
            ],
            sourceAttribution: [
                'evidence_directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
            ],
            cropOrEntity: 'wheat',
        );
    }
}
