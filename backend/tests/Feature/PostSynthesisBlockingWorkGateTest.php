<?php

namespace Tests\Feature;

use App\Contracts\Agriculture\McpToolClientInterface;
use App\Contracts\Agriculture\WebSearchProviderInterface;
use App\Models\Organization;
use App\Services\Agriculture\CrossRefScientificClient;
use App\Services\Agriculture\Intelligence\Adapters\Web\FreeSearchMcpAdapter;
use App\Services\Agriculture\Intelligence\Orchestration\UniversalAnswerOrchestrator;
use App\Services\Agriculture\OpenAlexScientificClient;
use App\Services\Agriculture\Research\AgriculturalResearchAgent;
use App\Services\Agriculture\Research\Search\AgriculturalScientificSearchService;
use App\Services\Agriculture\Research\Synthesis\AnswerComposer;
use App\Services\Agriculture\Research\Synthesis\AnswerSynthesisExecutionReport;
use App\Services\Agriculture\Research\Synthesis\ResearchAnswerCitation;
use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use App\Services\Agriculture\Research\Search\ScientificSearchExecutionReport;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;
use App\Services\Agriculture\Research\Validation\AgriculturalScientificValidationService;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use App\Services\Agriculture\Research\Validation\EvidenceValidationExecutionReport;
use App\Services\Agriculture\Research\Validation\EvidenceValidationStatus;
use App\Services\Agriculture\Research\Validation\ScientificEvidenceItem;
use App\Services\Agriculture\ScientificSourceDiscoveryPipeline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * Post-synthesis quality gate: sufficient Stage 5 results must not start a second
 * blocking discovery/MCP wave. Insufficient results keep the legacy fallback.
 */
class PostSynthesisBlockingWorkGateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Organization::create(['name' => 'WSA Demo', 'slug' => 'wsa-demo']);
        $this->enableBlockingProviders();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_sufficient_scientific_result_skips_legacy_discovery_and_mcp(): void
    {
        $query = 'wheat cultivation practices in dryland agriculture systems';

        $this->bindP4cHomePipeline($this->p4cSynthesisReport([
            'researchMetadata' => [
                'evidence_sufficient' => true,
                'direct_evidence_gate' => 'PASSED',
                'sufficiency_mode' => 'sufficient_direct_evidence',
                'supporting_evidence_count' => 0,
            ],
        ]), expectLegacyExecute: false);

        $pipeline = Mockery::mock(ScientificSourceDiscoveryPipeline::class);
        $pipeline->shouldNotReceive('discoverMissingSections');
        $this->app->instance(ScientificSourceDiscoveryPipeline::class, $pipeline);

        $openAlex = Mockery::mock(OpenAlexScientificClient::class);
        $openAlex->shouldNotReceive('searchWorks');
        $this->app->instance(OpenAlexScientificClient::class, $openAlex);

        $crossRef = Mockery::mock(CrossRefScientificClient::class);
        $crossRef->shouldNotReceive('searchWorks');
        $this->app->instance(CrossRefScientificClient::class, $crossRef);

        $payload = app(AgriculturalResearchAgent::class)->conductResearch(1, [
            'query' => $query,
            'organization_id' => 1,
            'force_execute' => true,
        ]);

        $this->assertSame('scientific_generated', $payload['status']);
        $this->assertTrue((bool) ($payload['research_metadata']['evidence_sufficient'] ?? false));
        $this->assertSame('PASSED', $payload['research_metadata']['direct_evidence_gate'] ?? null);
        $this->assertNotEmpty($payload['answer'] ?? null);
        $this->assertNotEmpty($payload['citations'] ?? []);
        $this->assertSame(5, $payload['stage']);
        $this->assertFalse((bool) ($payload['discovery']['performed'] ?? true));
        $this->assertSame('library_search_separated', $payload['discovery']['reason'] ?? null);
        $this->assertSame('removed', $payload['observability']['legacy_post_processing'] ?? null);
        $this->assertArrayHasKey('scientific_search', $payload);
        $this->assertArrayHasKey('scientific_validation', $payload);
        $this->assertArrayHasKey('confidence', $payload);
        $this->assertArrayHasKey('plan', $payload);
        $this->assertArrayHasKey('research', $payload);
    }

    public function test_insufficient_scientific_result_does_not_run_library_or_mcp(): void
    {
        $query = 'unanswered generic agronomy topic without usable evidence';

        $this->bindSearchAndValidation($query, sufficient: false);

        $pipeline = Mockery::mock(ScientificSourceDiscoveryPipeline::class);
        $pipeline->shouldNotReceive('discoverMissingSections');
        $this->app->instance(ScientificSourceDiscoveryPipeline::class, $pipeline);

        $mcp = Mockery::mock(McpToolClientInterface::class);
        $mcp->shouldNotReceive('callTool');
        $this->rebindMcpClient($mcp);

        $payload = app(AgriculturalResearchAgent::class)->conductResearch(1, [
            'query' => $query,
            'organization_id' => 1,
            'force_execute' => true,
        ]);

        $this->assertNotTrue(($payload['research_metadata']['evidence_sufficient'] ?? false) === true);
        $this->assertSame('removed', $payload['observability']['legacy_post_processing'] ?? null);
        $this->assertSame('library_search_separated', $payload['discovery']['reason'] ?? null);
        $this->assertArrayHasKey('discovery', $payload);
        $this->assertArrayHasKey('research', $payload);
        $this->assertArrayNotHasKey('universal_orchestrator', $payload);
    }

    public function test_home_direct_passed_gate_skips_legacy_execute(): void
    {
        $this->bindP4cHomePipeline($this->p4cSynthesisReport([
            'researchMetadata' => [
                'evidence_sufficient' => true,
                'direct_evidence_gate' => 'PASSED',
                'sufficiency_mode' => 'sufficient_direct_evidence',
                'supporting_evidence_count' => 0,
            ],
        ]), expectLegacyExecute: false);

        $payload = app(AgriculturalResearchAgent::class)->conductResearch(1, [
            'query' => 'wheat cultivation practices in dryland agriculture systems',
            'organization_id' => 1,
            'force_execute' => true,
        ]);

        $this->assertSame('scientific_generated', $payload['status']);
        $this->assertSame('PASSED', $payload['research_metadata']['direct_evidence_gate'] ?? null);
        $this->assertSame('removed', $payload['observability']['legacy_post_processing'] ?? null);
        $this->assertSame('generic_research', $payload['plan']['intent'] ?? null);
    }

    public function test_home_supporting_only_does_not_run_library_execute(): void
    {
        $this->bindP4cHomePipeline($this->p4cSynthesisReport([
            'status' => 'synthesis_completed_partial',
            'researchMetadata' => [
                'evidence_sufficient' => true,
                'direct_evidence_gate' => 'SUPPORTED_ANSWER_ELIGIBLE',
                'sufficiency_mode' => 'supporting_only',
                'supporting_evidence_count' => 2,
            ],
        ]), expectLegacyExecute: false);

        $payload = app(AgriculturalResearchAgent::class)->conductResearch(1, [
            'query' => 'wheat cultivation practices in dryland agriculture systems',
            'organization_id' => 1,
            'force_execute' => true,
        ]);

        $this->assertSame('removed', $payload['observability']['legacy_post_processing'] ?? null);
    }

    public function test_home_insufficient_direct_evidence_does_not_run_library_execute(): void
    {
        $this->bindP4cHomePipeline($this->p4cSynthesisReport([
            'status' => 'insufficient_evidence',
            'researchMetadata' => [
                'evidence_sufficient' => false,
                'direct_evidence_gate' => 'INSUFFICIENT_DIRECT_EVIDENCE',
                'sufficiency_mode' => 'insufficient_direct_evidence',
                'supporting_evidence_count' => 1,
            ],
        ]), expectLegacyExecute: false);

        $payload = app(AgriculturalResearchAgent::class)->conductResearch(1, [
            'query' => 'wheat cultivation practices in dryland agriculture systems',
            'organization_id' => 1,
            'force_execute' => true,
        ]);

        $this->assertSame('removed', $payload['observability']['legacy_post_processing'] ?? null);
    }

    public function test_home_empty_citations_do_not_run_library_execute(): void
    {
        $this->bindP4cHomePipeline($this->p4cSynthesisReport([
            'citations' => [],
            'researchMetadata' => [
                'evidence_sufficient' => true,
                'direct_evidence_gate' => 'PASSED',
                'sufficiency_mode' => 'sufficient_direct_evidence',
                'supporting_evidence_count' => 0,
            ],
        ]), expectLegacyExecute: false);

        $payload = app(AgriculturalResearchAgent::class)->conductResearch(1, [
            'query' => 'wheat cultivation practices in dryland agriculture systems',
            'organization_id' => 1,
            'force_execute' => true,
        ]);

        $this->assertSame('removed', $payload['observability']['legacy_post_processing'] ?? null);
        $this->assertSame([], $payload['discovery']['discoverers_used'] ?? ['missing']);
    }

    public function test_home_empty_answer_does_not_run_library_execute(): void
    {
        $this->bindP4cHomePipeline($this->p4cSynthesisReport([
            'answer' => '',
            'researchMetadata' => [
                'evidence_sufficient' => true,
                'direct_evidence_gate' => 'PASSED',
                'sufficiency_mode' => 'sufficient_direct_evidence',
                'supporting_evidence_count' => 0,
            ],
        ]), expectLegacyExecute: false);

        $payload = app(AgriculturalResearchAgent::class)->conductResearch(1, [
            'query' => 'wheat cultivation practices in dryland agriculture systems',
            'organization_id' => 1,
            'force_execute' => true,
        ]);

        $this->assertSame('removed', $payload['observability']['legacy_post_processing'] ?? null);
    }

    public function test_home_synthesis_not_performed_does_not_run_library_execute(): void
    {
        $this->bindP4cHomePipeline($this->p4cSynthesisReport([
            'performed' => false,
            'researchMetadata' => [
                'evidence_sufficient' => true,
                'direct_evidence_gate' => 'PASSED',
                'sufficiency_mode' => 'sufficient_direct_evidence',
                'supporting_evidence_count' => 0,
            ],
        ]), expectLegacyExecute: false);

        $payload = app(AgriculturalResearchAgent::class)->conductResearch(1, [
            'query' => 'wheat cultivation practices in dryland agriculture systems',
            'organization_id' => 1,
            'force_execute' => true,
        ]);

        $this->assertSame('removed', $payload['observability']['legacy_post_processing'] ?? null);
    }

    public function test_crop_direct_passed_gate_skips_legacy_execute(): void
    {
        $this->bindP4cHomePipeline($this->p4cSynthesisReport([
            'researchMetadata' => [
                'evidence_sufficient' => true,
                'direct_evidence_gate' => 'PASSED',
                'sufficiency_mode' => 'sufficient_direct_evidence',
                'supporting_evidence_count' => 0,
            ],
        ]), expectLegacyExecute: false);

        $payload = app(AgriculturalResearchAgent::class)->conductResearch(1, [
            'query' => 'sweet potato farming needs',
            'selected_crop_id' => 'sweet-potato',
            'selected_crop_name' => 'Sweet potato',
            'knowledge_option' => 'farming-needs',
            'organization_id' => 1,
            'force_execute' => true,
        ]);

        $this->assertSame('crop_profile', $payload['research_agent']['plan']['intent'] ?? null);
        $this->assertTrue((bool) ($payload['library']['legacy_discovery_skipped'] ?? false));
        $this->assertSame([], $payload['library']['discoverers_used'] ?? null);
    }

    private function enableBlockingProviders(): void
    {
        config([
            'agricultural_intelligence.orchestrator_enabled' => true,
            'agricultural_intelligence.enrich_legacy_synthesis' => true,
            'agricultural_intelligence.mcp.free_search.enabled' => true,
            'agricultural_intelligence.mcp.free_search.command' => 'uvx',
            'agricultural_intelligence.mcp.free_search.arguments' => 'free-search-mcp',
            'agricultural_intelligence.web_search.enabled' => false,
        ]);
    }

    private function rebindMcpClient(McpToolClientInterface $client): void
    {
        $this->app->instance(McpToolClientInterface::class, $client);
        $this->app->forgetInstance(FreeSearchMcpAdapter::class);
        $this->app->forgetInstance(WebSearchProviderInterface::class);
        $this->app->forgetInstance(UniversalAnswerOrchestrator::class);
    }

    private function bindSearchAndValidation(string $query, bool $sufficient): void
    {
        $result = new ScientificSearchResult(
            sourceKey: 'openalex',
            sourceIdentifier: 'https://openalex.org/Wgate',
            title: 'Wheat cultivation practices in dryland agriculture systems',
            authors: ['Dr Researcher'],
            publicationYear: 2023,
            doi: '10.1000/gate-wheat',
            canonicalUrl: 'https://doi.org/10.1000/gate-wheat',
            abstract: 'Wheat cultivation practices in dryland agriculture systems improve yield under limited rainfall.',
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
            failedSources: [],
            emptySources: $sufficient ? [] : ['openalex'],
            sourceOutcomes: [],
            results: $sufficient ? [$result] : [],
            deduplicatedResults: $sufficient ? [$result] : [],
            planSummary: [],
            internetFirst: true,
            searchQueries: [$query],
        ));
        $this->app->instance(AgriculturalScientificSearchService::class, $search);

        $item = new ScientificEvidenceItem(
            evidenceId: 'ev-gate-wheat',
            sourceId: 'src-gate-wheat',
            sourceKey: 'openalex',
            sourceType: 'university_research',
            publicationTitle: 'Wheat cultivation practices in dryland agriculture systems',
            authors: ['Dr Researcher'],
            institution: 'University of Agriculture',
            journal: 'Journal of Agronomy',
            doi: '10.1000/gate-wheat',
            url: 'https://doi.org/10.1000/gate-wheat',
            publicationYear: 2023,
            retrievedAt: now()->toIso8601String(),
            agriculturalDomain: 'field_crops',
            claimTopic: 'cultivation',
            evidenceText: 'Wheat cultivation practices in dryland agriculture systems improve yield under limited rainfall.',
            validationStatus: EvidenceValidationStatus::EVIDENCE_USABLE,
            validationFailures: [],
            claimRelationship: ClaimEvidenceRelationship::SUPPORTED,
            confidence: 0.86,
            qualityScore: 82.0,
            qualityFactors: [
                'not_scientific_certainty' => true,
                'evidence_directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
            ],
            sourceAttribution: [
                'organization' => 'University of Agriculture',
                'source_type' => 'university_research',
                'evidence_directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
            ],
            cropOrEntity: 'wheat',
        );

        $validation = Mockery::mock(AgriculturalScientificValidationService::class);
        $validation->shouldReceive('validate')->once()->andReturn(new EvidenceValidationExecutionReport(
            status: $sufficient ? 'validation_completed' : 'validation_insufficient',
            validatedEvidence: $sufficient ? [$item] : [],
            rejectedEvidence: [],
            sourcesReceived: $sufficient ? 1 : 0,
            validatedCount: $sufficient ? 1 : 0,
            rejectedCount: 0,
            duplicateCount: 0,
            conflictingCount: 0,
            evidenceSufficient: $sufficient,
            validatorsUsed: [],
            qualityDistribution: [],
            searchSummary: [],
            observability: [],
        ));
        $this->app->instance(AgriculturalScientificValidationService::class, $validation);
    }

    private function bindP4cHomePipeline(AnswerSynthesisExecutionReport $synthesis, bool $expectLegacyExecute): void
    {
        $this->bindSearchAndValidation('wheat cultivation practices in dryland agriculture systems', sufficient: true);

        $composer = Mockery::mock(AnswerComposer::class);
        $composer->shouldReceive('compose')->once()->andReturn($synthesis);
        $this->app->instance(AnswerComposer::class, $composer);

        $mcp = Mockery::mock(McpToolClientInterface::class);
        $mcp->shouldNotReceive('callTool');
        $this->rebindMcpClient($mcp);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function p4cSynthesisReport(array $overrides): AnswerSynthesisExecutionReport
    {
        $metadata = array_merge([
            'evidence_sufficient' => true,
            'direct_evidence_gate' => 'PASSED',
            'sufficiency_mode' => 'sufficient_direct_evidence',
            'supporting_evidence_count' => 0,
        ], is_array($overrides['researchMetadata'] ?? null) ? $overrides['researchMetadata'] : []);

        $citations = array_key_exists('citations', $overrides)
            ? $overrides['citations']
            : [$this->p4cCitation()];

        return new AnswerSynthesisExecutionReport(
            status: (string) ($overrides['status'] ?? 'synthesis_completed'),
            performed: (bool) ($overrides['performed'] ?? true),
            answer: array_key_exists('answer', $overrides) ? $overrides['answer'] : 'Direct wheat cultivation answer.',
            conciseSummary: 'Direct wheat cultivation answer.',
            detailedExplanation: 'Direct wheat cultivation answer.',
            keyFindings: ['Wheat cultivation is documented.'],
            claims: [],
            citations: $citations,
            evidenceReferences: [],
            confidence: 0.8,
            limitations: [],
            uncertainty: null,
            conflicts: [],
            language: 'en',
            researchMetadata: $metadata,
            observability: [],
        );
    }

    private function p4cCitation(): ResearchAnswerCitation
    {
        return new ResearchAnswerCitation(
            citationId: 'c-p4c-wheat',
            sourceId: 'src-p4c-wheat',
            evidenceId: 'ev-p4c-wheat',
            title: 'Wheat cultivation practices in dryland agriculture systems',
            authors: ['Dr Researcher'],
            organization: 'University of Agriculture',
            journal: 'Journal of Agronomy',
            doi: '10.1000/gate-wheat',
            url: 'https://doi.org/10.1000/gate-wheat',
            publicationYear: 2023,
            sourceType: 'university_research',
        );
    }
}
