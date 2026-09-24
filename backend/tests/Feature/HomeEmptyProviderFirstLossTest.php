<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Services\Agriculture\Research\AgriculturalResearchAgent;
use App\Services\Agriculture\Research\Persistence\KnowledgePersistenceExecutionReport;
use App\Services\Agriculture\Research\Persistence\ScientificKnowledgePersistenceService;
use App\Services\Agriculture\Research\Search\AgriculturalScientificSearchService;
use App\Services\Agriculture\Research\Search\ScientificSearchExecutionReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class HomeEmptyProviderFirstLossTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_empty_deduplicated_results_become_no_search_results_before_composer_answer(): void
    {
        Organization::create(['name' => 'WSA Demo', 'slug' => 'wsa-demo', 'is_active' => true]);
        config(['agricultural_intelligence.orchestrator_enabled' => false]);

        $search = Mockery::mock(AgriculturalScientificSearchService::class);
        $search->shouldReceive('search')->once()->andReturn(new ScientificSearchExecutionReport(
            status: 'search_empty',
            searchQuery: 'wheat irrigation',
            selectedSources: ['openalex'],
            attemptedSources: ['openalex'],
            successfulSources: [],
            failedSources: [],
            emptySources: ['openalex'],
            sourceOutcomes: [],
            results: [],
            deduplicatedResults: [],
            planSummary: [],
            internetFirst: true,
        ));
        $this->app->instance(AgriculturalScientificSearchService::class, $search);

        $persist = Mockery::mock(ScientificKnowledgePersistenceService::class);
        $persist->shouldReceive('persist')->once()->andReturn(new KnowledgePersistenceExecutionReport(
            status: 'skipped',
            performed: false,
            libraryItemId: null,
            slug: null,
            action: 'skipped',
            provenance: null,
            observability: [],
        ));
        $this->app->instance(ScientificKnowledgePersistenceService::class, $persist);

        $payload = app(AgriculturalResearchAgent::class)->conductResearch(1, [
            'query' => 'wheat irrigation scheduling',
            'force_execute' => true,
        ]);

        $this->assertSame('no_search_results', $payload['scientific_validation']['status'] ?? null);
        $this->assertNotSame('', trim((string) ($payload['answer'] ?? $payload['concise_summary'] ?? '')));
        $this->assertSame([], $payload['citations'] ?? []);
    }
}
