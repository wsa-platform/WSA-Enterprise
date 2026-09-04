<?php

namespace Tests\Feature;

use App\Services\Agriculture\Research\AgriculturalResearchAgent;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\ResearchPlanner;
use App\Services\Agriculture\Research\Search\AgriculturalScientificSearchService;
use App\Services\Agriculture\Research\Search\MultiSourceScientificSearchOrchestrator;
use App\Services\Agriculture\Research\Search\ScientificResultDeduplicator;
use App\Services\Agriculture\Research\Search\ScientificResultNormalizer;
use App\Services\Agriculture\Research\Search\ScientificResultRanker;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;
use App\Services\Agriculture\Research\Search\ScientificSourceSelector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AgriculturalResearchAgentStage3Test extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    private function openAlexWork(string $title, string $abstract, string $doi, string $author = 'Dr A Researcher'): array
    {
        $inverted = [];
        foreach (preg_split('/\s+/', $abstract) as $index => $word) {
            $inverted[$word][] = $index;
        }

        return [
            'id' => 'https://openalex.org/W'.md5($title),
            'display_name' => $title,
            'doi' => 'https://doi.org/'.$doi,
            'publication_year' => 2023,
            'abstract_inverted_index' => $inverted,
            'primary_location' => [
                'landing_page_url' => 'https://doi.org/'.$doi,
                'source' => ['display_name' => 'Journal of Agronomy'],
            ],
            'authorships' => [[
                'author' => ['display_name' => $author],
                'institutions' => [[
                    'display_name' => 'University of Agriculture',
                    'type' => 'education',
                ]],
            ]],
        ];
    }

    /** @return array<string, mixed> */
    private function crossRefWork(string $title, string $abstract, string $doi, string $author = 'Researcher'): array
    {
        return [
            'DOI' => $doi,
            'title' => [$title],
            'abstract' => $abstract,
            'publisher' => 'University of Agriculture',
            'container-title' => ['Journal of Agricultural Science'],
            'issued' => ['date-parts' => [[2022]]],
            'author' => [['given' => $author, 'family' => 'Smith']],
        ];
    }

    /** @return array<string, array{0: string, 1?: string|null}> */
    public static function agriculturalDomainQueriesProvider(): array
    {
        return [
            'wheat cultivation' => ['how to cultivate wheat in dryland systems', 'cultivation'],
            'tomato irrigation' => ['tomato drip irrigation scheduling', 'irrigation'],
            'fertilization' => ['fertilization nutrient management for wheat crops', 'fertilization'],
            'soil' => ['soil fertility improvement practices', 'soil_management'],
            'plant nutrition' => ['plant nutrition micronutrient deficiency cereals', 'plant_nutrition'],
            'plant disease' => ['tomato plant disease blight management', 'disease'],
            'pest management' => ['integrated pest management wheat fields', 'pest'],
            'weed management' => ['weed management in cereal crops', 'general_knowledge'],
            'fruit production' => ['fruit tree orchard production management', 'general_knowledge'],
            'vegetable production' => ['vegetable production greenhouse systems', 'general_knowledge'],
            'ornamental plants' => ['ornamental plant nursery production', 'general_knowledge'],
            'medicinal plants' => ['medicinal aromatic plant cultivation practices', 'cultivation'],
            'beekeeping' => ['beekeeping pollination orchard management', 'beekeeping'],
            'poultry' => ['poultry broiler production nutrition', 'poultry_production'],
            'livestock' => ['livestock animal production husbandry', 'animal_production'],
            'aquaculture' => ['aquaculture fish farming water quality', 'aquaculture'],
            'animal feed' => ['animal feed formulation nutrition', 'feed'],
            'agricultural economics' => ['agricultural economics farm profitability', 'agricultural_economics'],
            'agricultural industry' => ['agricultural industry value chain processing', 'agricultural_industry'],
            'scientific literature' => ['peer reviewed scientific publications sustainable agriculture', 'scientific_literature'],
            'general agriculture' => ['general farming practices smallholders', 'general_knowledge'],
            'arabic query' => ['كيف أزرع القمح؟', 'cultivation'],
            'english query' => ['irrigation scheduling for orchard crops in arid regions', 'irrigation'],
        ];
    }

    /** @dataProvider agriculturalDomainQueriesProvider */
    public function test_stage_3_executes_internet_first_search_for_agricultural_queries(string $query, ?string $expectedIntent = null): void
    {
        Http::fake([
            'api.openalex.org/works*' => Http::response(['results' => [
                $this->openAlexWork(
                    'Agricultural research on '.$query,
                    'Agricultural research findings related to '.$query.' in farming systems.',
                    '10.1000/stage3-'.md5($query),
                ),
            ]], 200),
            'api.crossref.org/works*' => Http::response(['message' => ['items' => []]], 200),
        ]);

        $response = $this->postJson('/api/v1/public/research-agent/search', ['query' => $query]);

        $response->assertOk();
        if ($response->json('status') === 'needs_clarification') {
            $this->fail('Expected Stage 3 search but query was marked needs_clarification: '.$query);
        }
        $response->assertJsonPath('stage', 3);
        $response->assertJsonPath('internet_first', true);
        $response->assertJsonPath('validation.performed', false);
        $this->assertContains('openalex', $response->json('attempted_sources'));
        if ($expectedIntent !== null) {
            $this->assertSame($expectedIntent, $response->json('knowledge_query_plan.research_intent'));
        }
        $this->assertNotEmpty($response->json('results'));
    }

    public function test_ambiguous_query_does_not_execute_scientific_search(): void
    {
        Http::fake();

        $response = $this->postJson('/api/v1/public/research-agent/search', ['query' => 'best fertilizer']);

        $response->assertOk();
        $response->assertJsonPath('status', 'needs_clarification');
        $response->assertJsonPath('scientific_search.performed', false);
        Http::assertNothingSent();
    }

    public function test_incomplete_query_handling(): void
    {
        $report = app(AgriculturalScientificSearchService::class)->search(
            app(ResearchPlanner::class)->planKnowledgeQuery(['query' => 'crop?']),
        );

        $this->assertSame('needs_clarification', $report->status);
        $this->assertSame([], $report->attemptedSources);
    }

    public function test_openalex_success(): void
    {
        Http::fake([
            'api.openalex.org/works*' => Http::response(['results' => [
                $this->openAlexWork(
                    'Wheat cultivation in dryland agriculture',
                    'Wheat cultivation requires seed rate and water management in dryland agriculture.',
                    '10.1000/openalex-success',
                ),
            ]], 200),
            'api.crossref.org/works*' => Http::response(['message' => ['items' => []]], 200),
        ]);

        $report = app(AgriculturalScientificSearchService::class)->search(
            app(ResearchPlanner::class)->planKnowledgeQuery(['query' => 'wheat cultivation dryland agriculture']),
        );

        $this->assertSame('search_completed', $report->status);
        $this->assertContains('openalex', $report->successfulSources);
        $this->assertSame('10.1000/openalex-success', $report->deduplicatedResults[0]->doi);
    }

    public function test_openalex_empty_result(): void
    {
        Http::fake([
            'api.openalex.org/works*' => Http::response(['results' => []], 200),
            'api.crossref.org/works*' => Http::response(['message' => ['items' => []]], 200),
        ]);

        $report = app(AgriculturalScientificSearchService::class)->search(
            app(ResearchPlanner::class)->planKnowledgeQuery(['query' => 'soil fertility management practices']),
        );

        $this->assertContains('openalex', $report->emptySources);
    }

    public function test_openalex_failure(): void
    {
        Http::fake([
            'api.openalex.org/works*' => Http::response(['results' => []], 500),
            'api.crossref.org/works*' => Http::response(['message' => ['items' => [
                $this->crossRefWork(
                    'Soil fertility management fallback',
                    'Soil fertility management practices for sustainable agriculture.',
                    '10.1000/crossref-fallback',
                ),
            ]]], 200),
        ]);

        $report = app(AgriculturalScientificSearchService::class)->search(
            app(ResearchPlanner::class)->planKnowledgeQuery(['query' => 'soil fertility management']),
        );

        $this->assertContains('openalex', $report->failedSources);
        $this->assertContains('crossref', $report->successfulSources);
        $this->assertSame('search_completed', $report->status);
    }

    public function test_crossref_success(): void
    {
        Http::fake([
            'api.openalex.org/works*' => Http::response(['results' => []], 200),
            'api.crossref.org/works*' => Http::response(['message' => ['items' => [
                $this->crossRefWork(
                    'Poultry feed nutrition formulation',
                    'Poultry feed nutrition formulation for broiler production systems.',
                    '10.1000/crossref-success',
                ),
            ]]], 200),
        ]);

        $report = app(AgriculturalScientificSearchService::class)->search(
            app(ResearchPlanner::class)->planKnowledgeQuery(['query' => 'poultry feed nutrition formulation']),
        );

        $this->assertContains('crossref', $report->successfulSources);
        $this->assertSame('10.1000/crossref-success', $report->deduplicatedResults[0]->doi);
    }

    public function test_crossref_empty_result(): void
    {
        Http::fake([
            'api.openalex.org/works*' => Http::response(['results' => []], 200),
            'api.crossref.org/works*' => Http::response(['message' => ['items' => []]], 200),
        ]);

        $emptyReport = app(AgriculturalScientificSearchService::class)->search(
            app(ResearchPlanner::class)->planKnowledgeQuery(['query' => 'beekeeping pollination management']),
        );
        $this->assertContains('crossref', $emptyReport->emptySources);
    }

    public function test_all_sources_failure(): void
    {
        Http::fake([
            'api.openalex.org/works*' => Http::response(['results' => []], 500),
            'api.crossref.org/works*' => Http::response(['message' => ['items' => []]], 500),
        ]);

        $allFailed = app(AgriculturalScientificSearchService::class)->search(
            app(ResearchPlanner::class)->planKnowledgeQuery(['query' => 'aquaculture fish farming water quality']),
        );
        $this->assertSame('all_sources_failed', $allFailed->status);
        $this->assertContains('openalex', $allFailed->failedSources);
        $this->assertContains('crossref', $allFailed->failedSources);
    }

    public function test_one_source_failure_and_another_success(): void
    {
        Http::fake([
            'api.openalex.org/works*' => Http::response(['results' => []], 500),
            'api.crossref.org/works*' => Http::response(['message' => ['items' => [
                $this->crossRefWork(
                    'Livestock animal production husbandry',
                    'Livestock animal production husbandry practices in mixed farming.',
                    '10.1000/livestock',
                ),
            ]]], 200),
        ]);

        $report = app(MultiSourceScientificSearchOrchestrator::class)->execute(
            app(ResearchPlanner::class)->planKnowledgeQuery(['query' => 'livestock animal production husbandry']),
        );

        $this->assertContains('openalex', $report->failedSources);
        $this->assertContains('crossref', $report->successfulSources);
        $this->assertNotEmpty($report->deduplicatedResults);
    }

    public function test_multi_source_normalization_and_no_fabricated_metadata(): void
    {
        Http::fake([
            'api.openalex.org/works*' => Http::response(['results' => [
                $this->openAlexWork(
                    'Agricultural economics farm profitability',
                    'Agricultural economics farm profitability analysis for smallholders.',
                    '10.1000/econ',
                    'Economist One',
                ),
            ]], 200),
            'api.crossref.org/works*' => Http::response(['message' => ['items' => []]], 200),
        ]);

        $result = app(AgriculturalScientificSearchService::class)->search(
            app(ResearchPlanner::class)->planKnowledgeQuery(['query' => 'agricultural economics farm profitability']),
        )->deduplicatedResults[0];

        $this->assertSame('openalex', $result->sourceKey);
        $this->assertSame('10.1000/econ', $result->doi);
        $this->assertSame('Economist One', $result->authors[0]);
        $this->assertNull($result->rawMetadata['fabricated'] ?? null);
        $this->assertStringNotContainsString('fabricated', json_encode($result->toArray()));
    }

    public function test_doi_deduplication_preserves_provenance(): void
    {
        $deduplicator = app(ScientificResultDeduplicator::class);
        $results = $deduplicator->deduplicate([
            new ScientificSearchResult('openalex', 'W1', 'Shared Paper', ['A'], 2023, '10.1000/shared', 'https://doi.org/10.1000/shared', 'abstract one', 'Journal A', ['openalex']),
            new ScientificSearchResult('crossref', 'cr1', 'Shared Paper', ['A'], 2023, '10.1000/shared', 'https://doi.org/10.1000/shared', 'abstract two', 'Journal A', ['crossref']),
        ]);

        $this->assertCount(1, $results);
        $this->assertEqualsCanonicalizing(['openalex', 'crossref'], $results[0]->foundBySources);
    }

    public function test_url_deduplication(): void
    {
        $deduplicator = app(ScientificResultDeduplicator::class);
        $results = $deduplicator->deduplicate([
            new ScientificSearchResult('openalex', 'W2', 'Paper URL', [], 2022, null, 'https://example.org/paper', null, null, ['openalex']),
            new ScientificSearchResult('crossref', 'W2b', 'Paper URL', [], 2022, null, 'https://example.org/paper/', null, null, ['crossref']),
        ]);

        $this->assertCount(1, $results);
    }

    public function test_title_fallback_deduplication_does_not_merge_unrelated_papers(): void
    {
        $deduplicator = app(ScientificResultDeduplicator::class);
        $results = $deduplicator->deduplicate([
            new ScientificSearchResult('openalex', 'W3', 'Tomato irrigation scheduling', [], 2020, null, null, null, null, ['openalex']),
            new ScientificSearchResult('crossref', 'W4', 'Beekeeping pollination management', [], 2020, null, null, null, null, ['crossref']),
        ]);

        $this->assertCount(2, $results);
    }

    public function test_deterministic_ranking_is_not_scientific_validation(): void
    {
        $ranker = app(ScientificResultRanker::class);
        $ranked = $ranker->rank('wheat cultivation', [
            new ScientificSearchResult('openalex', 'W5', 'General agriculture overview', [], 2024, null, null, 'general overview', null, ['openalex']),
            new ScientificSearchResult('crossref', 'W6', 'Wheat cultivation in dryland systems', [], 2019, null, null, 'wheat cultivation dryland', null, ['crossref']),
        ]);

        $this->assertSame('Wheat cultivation in dryland systems', $ranked[0]->title);
        $this->assertTrue($ranked[0]->relevanceMetadata['not_scientific_validation'] ?? false);
    }

    public function test_source_selection_from_knowledge_query_plan(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery(['query' => 'irrigation scheduling for wheat']);
        $sources = app(ScientificSourceSelector::class)->selectSources($plan);

        $this->assertSame(['openalex', 'crossref'], $sources);
        $this->assertSame(KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST, $plan->primaryResearchStrategy);
    }

    public function test_internet_first_execution_ordering(): void
    {
        Http::fake([
            'api.openalex.org/works*' => Http::response(['results' => [
                $this->openAlexWork('Internet first paper', 'Internet first agricultural research abstract.', '10.1000/first'),
            ]], 200),
            'api.crossref.org/works*' => Http::response(['message' => ['items' => []]], 200),
        ]);

        $report = app(AgriculturalScientificSearchService::class)->search(
            app(ResearchPlanner::class)->planKnowledgeQuery(['query' => 'field crop production research']),
        );

        $this->assertTrue($report->internetFirst);
        $this->assertSame(['openalex', 'crossref'], $report->selectedSources);
        $this->assertSame('openalex', $report->attemptedSources[0]);
    }

    public function test_malformed_external_response_handling(): void
    {
        Http::fake([
            'api.openalex.org/works*' => Http::response(['results' => [['display_name' => '']]], 200),
            'api.crossref.org/works*' => Http::response(['message' => ['items' => [['title' => ['']]]]], 200),
        ]);

        $report = app(AgriculturalScientificSearchService::class)->search(
            app(ResearchPlanner::class)->planKnowledgeQuery(['query' => 'plant protection integrated management']),
        );

        $this->assertContains('openalex', $report->emptySources);
        $this->assertContains('crossref', $report->emptySources);
    }

    public function test_rate_limit_and_timeout_handling(): void
    {
        $openAlexCalls = 0;
        Http::fake([
            'api.openalex.org/works*' => function () use (&$openAlexCalls) {
                $openAlexCalls++;

                return Http::response(['results' => []], 429);
            },
            'api.crossref.org/works*' => function (): never {
                throw new ConnectionException('timeout');
            },
        ]);

        $report = app(AgriculturalScientificSearchService::class)->search(
            app(ResearchPlanner::class)->planKnowledgeQuery(['query' => 'agricultural engineering irrigation systems']),
        );

        $this->assertContains('openalex', $report->failedSources);
        $this->assertContains('crossref', $report->failedSources);
        $this->assertSame(1, $openAlexCalls, 'OpenAlex must not retry variants after first 429');
    }

    public function test_structured_research_result_contract(): void
    {
        Http::fake([
            'api.openalex.org/works*' => Http::response(['results' => [
                $this->openAlexWork(
                    'Structured contract paper',
                    'Structured contract abstract for agricultural research.',
                    '10.1000/contract',
                ),
            ]], 200),
            'api.crossref.org/works*' => Http::response(['message' => ['items' => []]], 200),
        ]);

        $payload = app(AgriculturalResearchAgent::class)->searchResearch([
            'query' => 'agricultural research literature review',
        ]);

        $this->assertArrayHasKey('results', $payload);
        $this->assertArrayHasKey('source_outcomes', $payload);
        $this->assertArrayHasKey('attempted_sources', $payload);
        $this->assertArrayHasKey('deduplicated_result_count', $payload);
        $this->assertFalse($payload['validation']['performed']);
    }

    public function test_research_agent_integration_preserves_stage_2_planning(): void
    {
        Http::fake([
            'api.openalex.org/works*' => Http::response(['results' => []], 200),
            'api.crossref.org/works*' => Http::response(['message' => ['items' => []]], 200),
        ]);

        $payload = app(AgriculturalResearchAgent::class)->searchResearch([
            'query' => 'how to cultivate wheat in dryland systems?',
        ]);

        $this->assertArrayHasKey('knowledge_query_plan', $payload);
        $this->assertArrayHasKey('query_understanding', $payload);
        $this->assertSame(3, $payload['stage']);
    }

    public function test_normalizer_openalex_and_crossref_contracts(): void
    {
        $normalizer = app(ScientificResultNormalizer::class);

        $openAlex = $normalizer->fromOpenAlexWork($this->openAlexWork(
            'Normalizer OpenAlex',
            'Normalizer OpenAlex abstract content.',
            '10.1000/norm-oa',
        ));
        $crossRef = $normalizer->fromCrossRefWork($this->crossRefWork(
            'Normalizer Crossref',
            'Normalizer Crossref abstract content.',
            '10.1000/norm-cr',
        ));

        $this->assertNotNull($openAlex);
        $this->assertNotNull($crossRef);
        $this->assertSame('openalex', $openAlex->sourceKey);
        $this->assertSame('crossref', $crossRef->sourceKey);
    }

    public function test_library_is_not_primary_search_engine_in_stage_3_endpoint(): void
    {
        Http::fake([
            'api.openalex.org/works*' => Http::response(['results' => [
                $this->openAlexWork('External only', 'External only abstract.', '10.1000/external-only'),
            ]], 200),
            'api.crossref.org/works*' => Http::response(['message' => ['items' => []]], 200),
        ]);

        $response = $this->postJson('/api/v1/public/research-agent/search', [
            'query' => 'scientific literature on crop rotation',
        ]);

        $response->assertOk();
        $this->assertSame(['openalex', 'crossref'], $response->json('selected_sources'));
        // Multi-query retrieval issues one request per variant × provider (Internet-First only).
        Http::assertSentCount(count($response->json('search_queries') ?? [1]) * 2);
        Http::assertSent(fn ($request): bool => str_contains($request->url(), 'api.openalex.org')
            || str_contains($request->url(), 'api.crossref.org'));
    }
}
