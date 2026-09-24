<?php

namespace Tests\Feature;

use App\Services\Agriculture\Research\AgriculturalResearchAgent;
use App\Services\Agriculture\Research\Synthesis\AnswerComposer;
use App\Services\Agriculture\Research\Synthesis\AnswerSynthesisExecutionReport;
use App\Services\Agriculture\Research\Synthesis\ResearchAnswerCitation;
use App\Services\Agriculture\Research\Synthesis\ResearchAnswerClaim;
use App\Services\Agriculture\Research\Synthesis\ScientificAnswerCandidatePresenter;
use App\Services\Agriculture\Research\Search\AgriculturalScientificSearchService;
use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use App\Services\Agriculture\Research\Search\ScientificSearchExecutionReport;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;
use App\Services\Agriculture\Research\Validation\AgriculturalScientificValidationService;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use App\Services\Agriculture\Research\Validation\EvidenceValidationExecutionReport;
use App\Services\Agriculture\Research\Validation\EvidenceValidationStatus;
use App\Services\Agriculture\Research\Validation\ScientificEvidenceItem;
use App\Services\Agriculture\ScientificSourceDiscoveryPipeline;
use App\Contracts\Agriculture\McpToolClientInterface;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * Architectural contract: Scientific Research has zero Library Search dependency.
 */
class ScientificResearchLibrarySeparationContractTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Organization::create(['name' => 'WSA Demo', 'slug' => 'wsa-demo']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_research_agent_source_has_zero_library_search_dependency(): void
    {
        $source = file_get_contents((new \ReflectionClass(AgriculturalResearchAgent::class))->getFileName());
        $this->assertIsString($source);
        foreach ([
            'library_keyword',
            'library_structured',
            'library_rag',
            'library_crop_files',
            'LibraryKeywordSectionDiscoverer',
            'LibraryStructuredSectionDiscoverer',
            'LibraryRagSectionDiscoverer',
            'LibraryCropFilesSectionDiscoverer',
            'discoverMissingSections',
            'shouldRunLegacyPostProcessing',
            'maybeEnrichWithUniversalOrchestrator',
            'knowledgeEngine->execute',
        ] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $source, $forbidden);
        }
    }

    public function test_pipeline_no_longer_registers_library_discoverers(): void
    {
        $order = app(ScientificSourceDiscoveryPipeline::class)->discovererOrder();
        $this->assertNotContains('library_keyword', $order);
        $this->assertNotContains('library_structured', $order);
        $this->assertNotContains('library_rag', $order);
        $this->assertNotContains('library_crop_files', $order);
    }

    public function test_home_and_crop_paths_never_call_library_search_or_mcp(): void
    {
        $cases = [
            ['label' => 'home_sufficient_direct', 'sufficient' => true, 'crop' => false],
            ['label' => 'home_insufficient', 'sufficient' => false, 'crop' => false],
            ['label' => 'home_supporting_only', 'sufficient' => false, 'crop' => false],
            ['label' => 'home_empty_provider', 'sufficient' => false, 'crop' => false],
            ['label' => 'home_provider_failure', 'sufficient' => false, 'crop' => false],
            ['label' => 'home_statistical', 'sufficient' => true, 'crop' => false, 'query' => 'regional cereal production statistics and yield trends'],
            ['label' => 'home_multilingual', 'sufficient' => true, 'crop' => false, 'query' => 'الاحتياجات الزراعية لمحصول حقلي'],
            ['label' => 'crop_sufficient_direct', 'sufficient' => true, 'crop' => true],
            ['label' => 'crop_insufficient', 'sufficient' => false, 'crop' => true],
        ];

        foreach ($cases as $case) {
            $this->resetApplicationBindings();
            $this->bindScientificPipeline($case['sufficient']);
            $this->forbidLibrarySearchAndMcp();

            $input = [
                'query' => $case['query'] ?? ($case['crop']
                    ? 'sweet potato farming needs'
                    : 'wheat cultivation practices in dryland agriculture systems'),
                'organization_id' => 1,
                'force_execute' => true,
            ];
            if ($case['crop']) {
                $input['selected_crop_id'] = 'sweet-potato';
                $input['selected_crop_name'] = 'Sweet potato';
                $input['knowledge_option'] = 'farming-needs';
            }

            $payload = app(AgriculturalResearchAgent::class)->conductResearch(1, $input);
            $this->assertSame([], $payload['discovery']['discoverers_used'] ?? ['missing'], $case['label']);
            $this->assertFalse((bool) ($payload['discovery']['performed'] ?? true), $case['label']);
            $this->assertSame('library_search_separated', $payload['discovery']['reason'] ?? null, $case['label']);
            $this->assertArrayHasKey('stage_timings', $payload, $case['label']);
            $this->assertArrayHasKey('answer_candidates', $payload, $case['label']);
            $this->assertArrayNotHasKey('universal_orchestrator', $payload, $case['label']);
        }
    }

    public function test_candidates_use_fifty_percent_threshold_without_changing_directness(): void
    {
        $synthesis = new AnswerSynthesisExecutionReport(
            status: 'synthesis_completed',
            performed: true,
            answer: 'Primary scientific answer.',
            conciseSummary: 'Primary scientific answer.',
            detailedExplanation: 'Primary scientific answer.',
            keyFindings: [],
            claims: [
                $this->claim('c-high', 'High confidence claim.', 0.91),
                $this->claim('c-mid', 'Mid confidence claim.', 0.64),
                $this->claim('c-edge', 'Threshold claim.', 0.52),
                $this->claim('c-low', 'Below threshold claim.', 0.49),
            ],
            citations: [$this->citation()],
            evidenceReferences: [],
            confidence: 0.8,
            limitations: [],
            uncertainty: null,
            conflicts: [],
            language: 'en',
            researchMetadata: [
                'direct_evidence_gate' => 'PASSED',
                'evidence_sufficient' => true,
            ],
            observability: [],
        );

        $presented = ScientificAnswerCandidatePresenter::fromSynthesis($synthesis);
        $answers = array_column($presented['answer_candidates'], 'answer');
        $this->assertSame([
            'High confidence claim.',
            'Mid confidence claim.',
            'Threshold claim.',
        ], $answers);
        $this->assertNotContains('Below threshold claim.', $answers);
        $this->assertFalse($presented['answer_candidate_selection']['confidence_exposed']);
        $this->assertTrue($presented['answer_candidate_selection']['directness_unchanged']);
        $this->assertSame('PASSED', $synthesis->researchMetadata['direct_evidence_gate']);
    }

    public function test_supporting_confidence_cap_cannot_create_presentable_direct_candidates(): void
    {
        $synthesis = new AnswerSynthesisExecutionReport(
            status: 'insufficient_evidence',
            performed: true,
            answer: 'Insufficient direct scientific evidence was found for a definitive answer.',
            conciseSummary: null,
            detailedExplanation: null,
            keyFindings: [],
            claims: [
                $this->claim('c-support', 'Supporting-only claim stays below presentation.', 0.42),
            ],
            citations: [],
            evidenceReferences: [],
            confidence: 0.42,
            limitations: [],
            uncertainty: null,
            conflicts: [],
            language: 'en',
            researchMetadata: [
                'direct_evidence_gate' => 'INSUFFICIENT_DIRECT_EVIDENCE',
                'sufficiency_mode' => 'supporting_only',
            ],
            observability: [],
        );

        $presented = ScientificAnswerCandidatePresenter::fromSynthesis($synthesis);
        $this->assertSame([], $presented['answer_candidates']);
        $this->assertSame('INSUFFICIENT_DIRECT_EVIDENCE', $synthesis->researchMetadata['direct_evidence_gate']);
    }

    public function test_library_search_implementations_are_deleted(): void
    {
        foreach ([
            'Services/Agriculture/Discoverers/LibraryKeywordSectionDiscoverer.php',
            'Services/Agriculture/Discoverers/LibraryStructuredSectionDiscoverer.php',
            'Services/Agriculture/Discoverers/LibraryRagSectionDiscoverer.php',
            'Services/Agriculture/Discoverers/LibraryCropFilesSectionDiscoverer.php',
            'Services/Agriculture/Research/AgriculturalScientificKnowledgeEngine.php',
            'Services/Agriculture/Research/AgriculturalResearchResult.php',
        ] as $relative) {
            $this->assertFileDoesNotExist(app_path($relative), $relative);
        }
    }

    private function forbidLibrarySearchAndMcp(): void
    {
        $pipeline = Mockery::mock(ScientificSourceDiscoveryPipeline::class);
        $pipeline->shouldNotReceive('discoverMissingSections');
        $this->app->instance(ScientificSourceDiscoveryPipeline::class, $pipeline);

        $mcp = Mockery::mock(McpToolClientInterface::class);
        $mcp->shouldNotReceive('callTool');
        $this->app->instance(McpToolClientInterface::class, $mcp);
    }

    private function bindScientificPipeline(bool $sufficient): void
    {
        $query = 'wheat cultivation practices in dryland agriculture systems';
        $result = new ScientificSearchResult(
            sourceKey: 'openalex',
            sourceIdentifier: 'https://openalex.org/Wsep',
            title: $query,
            authors: ['Dr Researcher'],
            publicationYear: 2023,
            doi: '10.1000/sep-wheat',
            canonicalUrl: 'https://doi.org/10.1000/sep-wheat',
            abstract: $query.' improve yield under limited rainfall.',
            journal: 'Journal of Agronomy',
            foundBySources: ['openalex'],
        );

        $search = Mockery::mock(AgriculturalScientificSearchService::class);
        $search->shouldReceive('search')->once()->andReturn(new ScientificSearchExecutionReport(
            status: $sufficient ? 'search_completed' : 'search_empty',
            searchQuery: $query,
            selectedSources: ['openalex'],
            attemptedSources: ['openalex'],
            successfulSources: $sufficient ? ['openalex'] : [],
            failedSources: $sufficient ? [] : ['openalex'],
            emptySources: $sufficient ? [] : ['openalex'],
            sourceOutcomes: [],
            results: $sufficient ? [$result] : [],
            deduplicatedResults: $sufficient ? [$result] : [],
            planSummary: ['provider_duration_ms' => ['openalex' => 12]],
            internetFirst: true,
            searchQueries: [$query],
        ));
        $this->app->instance(AgriculturalScientificSearchService::class, $search);

        $item = new ScientificEvidenceItem(
            evidenceId: 'ev-sep',
            sourceId: 'src-sep',
            sourceKey: 'openalex',
            sourceType: 'university_research',
            publicationTitle: $query,
            authors: ['Dr Researcher'],
            institution: 'University of Agriculture',
            journal: 'Journal of Agronomy',
            doi: '10.1000/sep-wheat',
            url: 'https://doi.org/10.1000/sep-wheat',
            publicationYear: 2023,
            retrievedAt: now()->toIso8601String(),
            agriculturalDomain: 'field_crops',
            claimTopic: 'cultivation',
            evidenceText: $query.' improve yield under limited rainfall.',
            validationStatus: $sufficient ? EvidenceValidationStatus::EVIDENCE_USABLE : EvidenceValidationStatus::REJECTED,
            validationFailures: [],
            claimRelationship: $sufficient ? ClaimEvidenceRelationship::SUPPORTED : ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE,
            confidence: $sufficient ? 0.86 : 0.1,
            qualityScore: $sufficient ? 82.0 : 10.0,
            qualityFactors: [
                'not_scientific_certainty' => true,
                'evidence_directness' => $sufficient
                    ? ScientificEvidenceDirectnessAssessor::DIRECT
                    : ScientificEvidenceDirectnessAssessor::SUPPORTING,
            ],
            sourceAttribution: [
                'organization' => 'University of Agriculture',
                'source_type' => 'university_research',
                'evidence_directness' => $sufficient
                    ? ScientificEvidenceDirectnessAssessor::DIRECT
                    : ScientificEvidenceDirectnessAssessor::SUPPORTING,
            ],
            cropOrEntity: 'wheat',
        );

        $validation = Mockery::mock(AgriculturalScientificValidationService::class);
        $validation->shouldReceive('validate')->once()->andReturn(new EvidenceValidationExecutionReport(
            status: $sufficient ? 'validation_completed' : 'validation_insufficient',
            validatedEvidence: $sufficient ? [$item] : [],
            rejectedEvidence: $sufficient ? [] : [$item],
            sourcesReceived: 1,
            validatedCount: $sufficient ? 1 : 0,
            rejectedCount: $sufficient ? 0 : 1,
            duplicateCount: 0,
            conflictingCount: 0,
            evidenceSufficient: $sufficient,
            validatorsUsed: [],
            qualityDistribution: [],
            searchSummary: [],
            observability: [],
        ));
        $this->app->instance(AgriculturalScientificValidationService::class, $validation);

        $composer = Mockery::mock(AnswerComposer::class);
        $composer->shouldReceive('compose')->once()->andReturn(new AnswerSynthesisExecutionReport(
            status: $sufficient ? 'synthesis_completed' : 'insufficient_evidence',
            performed: true,
            answer: $sufficient ? 'Direct wheat cultivation answer.' : 'Insufficient direct scientific evidence was found for a definitive answer.',
            conciseSummary: $sufficient ? 'Direct wheat cultivation answer.' : null,
            detailedExplanation: null,
            keyFindings: $sufficient ? ['Wheat cultivation is documented.'] : [],
            claims: $sufficient ? [$this->claim('c-sep', 'Direct wheat cultivation answer.', 0.8)] : [],
            citations: $sufficient ? [$this->citation()] : [],
            evidenceReferences: [],
            confidence: $sufficient ? 0.8 : 0.0,
            limitations: [],
            uncertainty: null,
            conflicts: [],
            language: 'en',
            researchMetadata: [
                'evidence_sufficient' => $sufficient,
                'direct_evidence_gate' => $sufficient ? 'PASSED' : 'INSUFFICIENT_DIRECT_EVIDENCE',
            ],
            observability: [],
        ));
        $this->app->instance(AnswerComposer::class, $composer);
    }

    private function claim(string $id, string $text, float $confidence): ResearchAnswerClaim
    {
        return new ResearchAnswerClaim(
            claimId: $id,
            claimText: $text,
            evidenceIds: ['ev-sep'],
            sourceIds: ['src-sep'],
            validationStatus: 'validated',
            claimRelationship: ClaimEvidenceRelationship::SUPPORTED,
            confidence: $confidence,
        );
    }

    private function citation(): ResearchAnswerCitation
    {
        return new ResearchAnswerCitation(
            citationId: 'c-sep',
            sourceId: 'src-sep',
            evidenceId: 'ev-sep',
            title: 'Wheat cultivation practices in dryland agriculture systems',
            authors: ['Dr Researcher'],
            organization: 'University of Agriculture',
            journal: 'Journal of Agronomy',
            doi: '10.1000/sep-wheat',
            url: 'https://doi.org/10.1000/sep-wheat',
            publicationYear: 2023,
            sourceType: 'university_research',
        );
    }

    private function resetApplicationBindings(): void
    {
        $this->app->forgetInstance(AgriculturalResearchAgent::class);
        $this->app->forgetInstance(AgriculturalScientificSearchService::class);
        $this->app->forgetInstance(AgriculturalScientificValidationService::class);
        $this->app->forgetInstance(AnswerComposer::class);
        $this->app->forgetInstance(ScientificSourceDiscoveryPipeline::class);
        $this->app->forgetInstance(McpToolClientInterface::class);
    }
}
