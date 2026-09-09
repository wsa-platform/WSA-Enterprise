<?php

namespace Tests\Unit\Agriculture\Intelligence;

use App\Contracts\Agriculture\WebSearchProviderInterface;
use App\Services\Agriculture\Intelligence\Adapters\Execution\OctoPusExecutionAdapter;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStatScientificSourceAdapter;
use App\Services\Agriculture\Intelligence\Adapters\Web\ConfigurableWebSearchProvider;
use App\Services\Agriculture\Intelligence\Contracts\ProviderHealthState;
use App\Services\Agriculture\Intelligence\DTO\ProviderQueryInput;
use App\Services\Agriculture\Intelligence\DTO\WebSearchOutcome;
use App\Services\Agriculture\Intelligence\Health\ProviderHealthChecker;
use App\Services\Agriculture\Intelligence\Registry\AgriculturalProviderRegistry;
use App\Services\Agriculture\Research\Search\ScientificSourceSearchOutcome;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProviderRegistryAndAdaptersTest extends TestCase
{
    public function test_registry_lists_core_provider_families(): void
    {
        $registry = app(AgriculturalProviderRegistry::class);
        $ids = $registry->ids();

        $this->assertContains('web_search', $ids);
        $this->assertContains('openalex', $ids);
        $this->assertContains('crossref', $ids);
        $this->assertContains('semantic_scholar', $ids);
        $this->assertContains('fao_stat', $ids);
        $this->assertContains('open_meteo', $ids);
        $this->assertContains('agrisignal_mcp', $ids);
        $this->assertContains('agriculture_mcp', $ids);
        $this->assertContains('greensense', $ids);
        $this->assertContains('huggingface_disease', $ids);
        $this->assertContains('field_sense', $ids);
        $this->assertContains('octopus_execution', $ids);
    }

    public function test_web_search_not_configured_without_key(): void
    {
        config([
            'agricultural_intelligence.web_search.enabled' => true,
            'agricultural_intelligence.web_search.api_key' => '',
            'agricultural_intelligence.web_search.endpoint' => '',
        ]);

        $provider = app(WebSearchProviderInterface::class);
        $this->assertFalse($provider->isConfigured());
        $outcome = $provider->search('irrigation scheduling');
        $this->assertSame(WebSearchOutcome::STATUS_NOT_CONFIGURED, $outcome->status);
        $this->assertSame('NOT_CONFIGURED', $outcome->error);
    }

    public function test_web_search_success_with_mocked_http(): void
    {
        config([
            'agricultural_intelligence.web_search.enabled' => true,
            'agricultural_intelligence.web_search.api_key' => 'test-key',
            'agricultural_intelligence.web_search.endpoint' => 'https://web.search.test/v1',
        ]);

        Http::fake([
            'web.search.test/*' => Http::response([
                'results' => [
                    ['title' => 'Web hit', 'url' => 'https://example.com/w', 'snippet' => 'About 15 C nights'],
                ],
            ], 200),
        ]);

        $outcome = app(ConfigurableWebSearchProvider::class)->search('climate nights');
        $this->assertSame(WebSearchOutcome::STATUS_SUCCESS, $outcome->status);
        $this->assertCount(1, $outcome->results);
        $this->assertSame('web', $outcome->results[0]['evidence_family']);
    }

    public function test_fao_skips_without_valid_code_and_never_fabricates(): void
    {
        config(['agricultural_intelligence.fao.enabled' => true]);
        $adapter = app(FaoStatScientificSourceAdapter::class);
        $outcome = $adapter->search('production statistics', 5, []);
        $this->assertSame(ScientificSourceSearchOutcome::STATUS_EMPTY, $outcome->status);
        $this->assertSame('no_valid_fao_code', $outcome->error);
    }

    public function test_fao_success_empty_and_failure_isolation(): void
    {
        config([
            'agricultural_intelligence.fao.enabled' => true,
            'agricultural_intelligence.fao.base_url' => 'https://fenixservices.fao.org/faostat/api/v1',
        ]);

        Http::fake([
            'fenixservices.fao.org/*/data/QCL*' => Http::sequence()
                ->push(['data' => [[
                    'item' => 'Wheat',
                    'Area' => 'World',
                    'Year' => 2020,
                    'Value' => 100,
                    'Unit' => 'tonnes',
                    'Element' => 'Production',
                    'itemCode' => '15',
                ]]], 200)
                ->push(['data' => []], 200)
                ->push(['error' => 'boom'], 503),
        ]);

        $adapter = app(FaoStatScientificSourceAdapter::class);

        $ok = $adapter->search('wheat', 5, ['item_code' => '15']);
        $this->assertSame(ScientificSourceSearchOutcome::STATUS_SUCCESS, $ok->status);
        $this->assertNotEmpty($ok->results);

        $empty = $adapter->search('wheat', 5, ['item_code' => '15']);
        $this->assertSame(ScientificSourceSearchOutcome::STATUS_EMPTY, $empty->status);

        $fail = $adapter->search('wheat', 5, ['item_code' => '15']);
        $this->assertSame(ScientificSourceSearchOutcome::STATUS_FAILED, $fail->status);
    }

    public function test_octopus_blocked_without_license(): void
    {
        config([
            'agricultural_intelligence.octopus.enabled' => true,
            'agricultural_intelligence.octopus.endpoint' => 'https://octopus.test/run',
            'agricultural_intelligence.octopus.license_status' => 'unknown',
        ]);

        $adapter = app(OctoPusExecutionAdapter::class);
        $health = $adapter->health();
        $this->assertSame(ProviderHealthState::BLOCKED, $health->state);

        $result = $adapter->retrieve(new ProviderQueryInput(query: 'run tool'));
        $this->assertSame('blocked', $result->status);
    }

    public function test_provider_health_checker_isolates_failures(): void
    {
        $statuses = app(ProviderHealthChecker::class)->checkAll();
        $this->assertNotEmpty($statuses);
        foreach ($statuses as $status) {
            $this->assertNotEmpty($status->providerId);
            $this->assertNotEmpty($status->state);
        }
    }

    public function test_disease_adapter_not_configured_without_endpoint(): void
    {
        $registry = app(AgriculturalProviderRegistry::class);
        $gs = $registry->get('greensense');
        $this->assertNotNull($gs);
        $result = $gs->retrieve(new ProviderQueryInput(query: 'leaf spots'));
        $this->assertSame('not_configured', $result->status);
    }
}
