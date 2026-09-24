<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatDeveloperPortalAdapter;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatDeveloperPortalTokenManager;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatEvidenceType;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatRuntimePolicy;
use App\Services\Agriculture\Intelligence\Registry\AgriculturalProviderRegistry;
use App\Services\Agriculture\Research\AgriculturalResearchAgent;
use App\Services\Agriculture\Research\ResearchPlanner;
use App\Services\Agriculture\Research\Search\ScientificEvidenceModality;
use App\Services\Agriculture\Research\Search\ScientificSourceAdapterRegistry;
use App\Services\Agriculture\Research\Search\ScientificSourceSelector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Tests\Unit\Agriculture\Intelligence\FaoStat\FaoStatPhase4Fixtures;

/**
 * FAOSTAT is a first-class Stage 3 scientific provider. Not Library. Not a bypass.
 */
class ScientificResearchFaostatFirstClassContractTest extends TestCase
{
    use RefreshDatabase;
    use FaoStatPhase4Fixtures;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        app(FaoStatDeveloperPortalTokenManager::class)->reset();
        config([
            'agricultural_intelligence.faostat.enabled' => true,
            'agricultural_intelligence.faostat.base_url' => 'https://faostatservices.fao.org/api/v1',
            'agricultural_intelligence.faostat.allowed_host' => 'faostatservices.fao.org',
            'agricultural_intelligence.faostat.username' => 'portal-user',
            'agricultural_intelligence.faostat.password' => 'portal-pass',
            'agricultural_intelligence.faostat.lang' => 'en',
            'agricultural_intelligence.faostat.allowed_domains' => ['QCL'],
            'agricultural_intelligence.fao.enabled' => false,
            'agricultural_intelligence.orchestrator_enabled' => false,
        ]);
        $this->app->forgetInstance(ScientificSourceAdapterRegistry::class);
        $this->app->forgetInstance(AgriculturalProviderRegistry::class);
        Organization::create(['name' => 'WSA Demo', 'slug' => 'wsa-demo', 'is_active' => true]);
    }

    public function test_faostat_is_registered_and_selected_as_stage3_provider(): void
    {
        $this->assertTrue(FaoStatRuntimePolicy::isEnabled());
        $this->assertSame('fao_stat', FaoStatRuntimePolicy::canonicalSourceKey());
        $this->assertSame(FaoStatDeveloperPortalAdapter::class, FaoStatRuntimePolicy::canonicalAdapterClass());

        $registry = app(ScientificSourceAdapterRegistry::class);
        $this->assertContains('fao_stat', $registry->registeredSourceKeys());
        $this->assertInstanceOf(FaoStatDeveloperPortalAdapter::class, $registry->get('fao_stat'));

        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'What was the production quantity of wheat in Italy in 2022?',
            'force_execute' => true,
        ]);
        $sources = app(ScientificSourceSelector::class)->selectSources($plan);
        $this->assertContains('fao_stat', $sources);
        $this->assertContains('openalex', $sources);
        $this->assertSame('fao_stat', $sources[0]);
    }

    public function test_statistical_home_path_reaches_composer_without_library(): void
    {
        Http::fake($this->authAnd([
            'faostatservices.fao.org/api/v1/en/codes/*' => Http::response([
                'data' => [
                    ['code' => '106', 'label' => 'Italy'],
                    ['code' => '15', 'label' => 'Wheat'],
                    ['code' => '2510', 'label' => 'Production Quantity'],
                    ['code' => '5510', 'label' => 'Production'],
                    ['code' => '2022', 'label' => '2022'],
                ],
            ], 200),
            'faostatservices.fao.org/api/v1/en/data/*' => Http::response([
                'metadata' => ['output_type' => 'OBJECTS'],
                'data' => [$this->italyWheatRow()],
            ], 200),
            'api.openalex.org/works*' => Http::response(['results' => []], 200),
            'api.crossref.org/works*' => Http::response(['message' => ['items' => []]], 200),
            'api.semanticscholar.org/graph/v1/paper/search*' => Http::response(['data' => []], 200),
        ]));

        $payload = app(AgriculturalResearchAgent::class)->conductResearch(1, [
            'query' => 'What was the production quantity of wheat in Italy in 2022?',
            'force_execute' => true,
        ]);

        $this->assertSame('library_search_separated', $payload['discovery']['reason'] ?? null);
        $this->assertSame([], $payload['discovery']['discoverers_used'] ?? ['missing']);
        $this->assertFalse((bool) ($payload['discovery']['performed'] ?? true));
        $this->assertArrayNotHasKey('universal_orchestrator', $payload);
        $this->assertSame('removed', $payload['observability']['legacy_post_processing'] ?? null);

        $encoded = json_encode($payload) ?: '';
        $this->assertStringNotContainsString('library_keyword', $encoded);
        $this->assertStringNotContainsString('library_rag', $encoded);
        $this->assertStringNotContainsString('library_structured', $encoded);
        $this->assertStringNotContainsString('library_crop_files', $encoded);
        $this->assertStringNotContainsString('AgriculturalScientificKnowledgeEngine', $encoded);
        $this->assertStringNotContainsString('google.com/search', $encoded);

        $search = is_array($payload['scientific_search'] ?? null) ? $payload['scientific_search'] : [];
        $this->assertContains('fao_stat', $search['selected_sources'] ?? []);
        $this->assertContains('fao_stat', $search['attempted_sources'] ?? []);
        $this->assertNotContains('fao_stat', $search['failed_sources'] ?? []);

        $results = $search['results'] ?? $search['deduplicated_results'] ?? [];
        $this->assertIsArray($results);
        $stat = collect($results)->first(
            static fn ($row): bool => is_array($row) && ($row['source'] ?? $row['source_key'] ?? null) === 'fao_stat',
        );
        $this->assertIsArray($stat, 'FAOSTAT must produce a ScientificSearchResult');
        $this->assertSame(
            FaoStatEvidenceType::DIRECT_STATISTICAL_EVIDENCE,
            $stat['relevance_metadata']['evidence_type']
                ?? $stat['relevanceMetadata']['evidence_type']
                ?? null,
        );

        $validationItems = $payload['scientific_validation']['validated_evidence']
            ?? $payload['scientific_validation']['validatedEvidence']
            ?? [];
        $this->assertNotEmpty($validationItems);
        $item = $validationItems[0];
        $this->assertSame(ScientificEvidenceModality::DIRECT_STATISTICAL, $item['quality_factors']['evidence_modality'] ?? $item['qualityFactors']['evidence_modality'] ?? null);
        $this->assertSame('direct', $item['quality_factors']['evidence_directness'] ?? $item['qualityFactors']['evidence_directness'] ?? null);

        $bag = $item['quality_factors']['faostat']
            ?? $item['quality_factors']['observation']
            ?? $item['source_attribution']['faostat']
            ?? $item['source_attribution']['observation']
            ?? [];
        $this->assertIsArray($bag);
        $this->assertSame('Italy', $bag['area'] ?? $bag['location'] ?? null);
        $this->assertSame('Wheat', $bag['item'] ?? $bag['entity'] ?? null);
        $this->assertSame('2022', (string) ($bag['year'] ?? ''));
        $this->assertSame('6609520', (string) ($bag['value'] ?? ''));
        $this->assertSame('t', $bag['unit'] ?? null);
        $this->assertSame('Production', $bag['element'] ?? $bag['property'] ?? null);

        $this->assertSame(5, $payload['stage']);
        $this->assertArrayHasKey('answer', $payload);
        $this->assertArrayHasKey('answer_candidates', $payload);
        $this->assertFalse((bool) ($payload['answer_candidate_selection']['confidence_exposed'] ?? true));
        $this->assertTrue((bool) ($payload['answer_candidate_selection']['directness_unchanged'] ?? false));
    }
}
