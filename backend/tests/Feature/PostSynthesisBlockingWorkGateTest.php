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
use App\Services\Agriculture\Research\AgriculturalScientificKnowledgeEngine;
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

        $this->bindSearchAndValidation($query, sufficient: true);

        $pipeline = Mockery::mock(ScientificSourceDiscoveryPipeline::class);
        $pipeline->shouldNotReceive('discoverMissingSections');
        $this->app->instance(ScientificSourceDiscoveryPipeline::class, $pipeline);

        $openAlex = Mockery::mock(OpenAlexScientificClient::class);
        $openAlex->shouldNotReceive('searchWorks');
        $this->app->instance(OpenAlexScientificClient::class, $openAlex);

        $crossRef = Mockery::mock(CrossRefScientificClient::class);
        $crossRef->shouldNotReceive('searchWorks');
        $this->app->instance(CrossRefScientificClient::class, $crossRef);

        $engine = Mockery::mock(AgriculturalScientificKnowledgeEngine::class);
        $engine->shouldNotReceive('execute');
        $this->app->instance(AgriculturalScientificKnowledgeEngine::class, $engine);

        $mcp = Mockery::mock(McpToolClientInterface::class);
        $mcp->shouldNotReceive('callTool');
        $this->rebindMcpClient($mcp);

        $payload = app(AgriculturalResearchAgent::class)->conductResearch(1, [
            'query' => $query,
            'organization_id' => 1,
            'force_execute' => true,
        ]);

        $this->assertSame('scientific_generated', $payload['status']);
        $this->assertTrue((bool) ($payload['research_metadata']['evidence_sufficient'] ?? false));
        $this->assertNotEmpty($payload['answer'] ?? null);
        $this->assertNotEmpty($payload['citations'] ?? []);
        $this->assertNotEmpty($payload['claims'] ?? []);
        $this->assertSame(5, $payload['stage']);
        $this->assertFalse((bool) ($payload['discovery']['performed'] ?? true));
        $this->assertSame('sufficient_scientific_result', $payload['discovery']['reason'] ?? null);
        $this->assertSame('skipped', $payload['observability']['legacy_post_processing'] ?? null);
        $this->assertTrue((bool) ($payload['library_persistence']['performed'] ?? false));
        $this->assertArrayHasKey('scientific_search', $payload);
        $this->assertArrayHasKey('scientific_validation', $payload);
        $this->assertArrayHasKey('confidence', $payload);
        $this->assertArrayHasKey('plan', $payload);
        $this->assertArrayHasKey('research', $payload);
    }

    public function test_insufficient_scientific_result_keeps_legacy_fallback(): void
    {
        $query = 'unanswered generic agronomy topic without usable evidence';

        $this->bindSearchAndValidation($query, sufficient: false);

        $pipeline = Mockery::mock(ScientificSourceDiscoveryPipeline::class);
        $pipeline->shouldReceive('discoverMissingSections')->once()->andReturn([
            'sections' => [],
            'discoverers_used' => [],
            'external_discoverers_used' => [],
            'library_discoverers_used' => [],
            'retrieval_failed' => false,
        ]);
        $this->app->instance(ScientificSourceDiscoveryPipeline::class, $pipeline);

        $mcp = Mockery::mock(McpToolClientInterface::class);
        $mcp->shouldReceive('callTool')->once()->andReturn([
            'is_error' => false,
            'text' => '',
            'structured' => ['results' => [[
                'title' => 'General agronomy note',
                'url' => 'https://example.com/agronomy',
                'snippet' => 'General field practices',
            ]]],
            'error' => null,
        ]);
        $this->rebindMcpClient($mcp);

        $payload = app(AgriculturalResearchAgent::class)->conductResearch(1, [
            'query' => $query,
            'organization_id' => 1,
            'force_execute' => true,
        ]);

        $this->assertNotTrue(($payload['research_metadata']['evidence_sufficient'] ?? false) === true);
        $this->assertNotSame('skipped', $payload['observability']['legacy_post_processing'] ?? null);
        $this->assertNotSame('sufficient_scientific_result', $payload['discovery']['reason'] ?? null);
        $this->assertArrayHasKey('discovery', $payload);
        $this->assertArrayHasKey('research', $payload);
        $this->assertArrayHasKey('universal_orchestrator', $payload);
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
}
