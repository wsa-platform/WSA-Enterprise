<?php

namespace Tests\Unit\Agriculture\Intelligence;

use App\Contracts\Agriculture\McpToolClientInterface;
use App\Contracts\Agriculture\WebSearchProviderInterface;
use App\Services\Agriculture\Intelligence\Adapters\Web\ConfigurableWebSearchProvider;
use App\Services\Agriculture\Intelligence\Adapters\Web\FreeSearchMcpAdapter;
use App\Services\Agriculture\Intelligence\DTO\WebSearchOutcome;
use App\Services\Agriculture\Intelligence\Mcp\LaravelStdioMcpToolClient;
use App\Services\Agriculture\Intelligence\Normalization\FreeSearchMcpResultNormalizer;
use App\Services\Agriculture\Intelligence\Registry\AgriculturalProviderRegistry;
use Tests\TestCase;

class FreeSearchMcpAdapterTest extends TestCase
{
    private function enableMcp(array $overrides = []): void
    {
        config(array_merge([
            'agricultural_intelligence.mcp.free_search.enabled' => true,
            'agricultural_intelligence.mcp.free_search.command' => 'uvx',
            'agricultural_intelligence.mcp.free_search.arguments' => 'free-search-mcp',
            'agricultural_intelligence.mcp.free_search.timeout' => 30000,
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $call
     */
    private function adapterWithCall(array $call): FreeSearchMcpAdapter
    {
        $this->enableMcp();
        $client = new class($call) implements McpToolClientInterface
        {
            public array $lastCall = [];

            public function __construct(private array $response) {}

            public function callTool(string $name, array $arguments): array
            {
                $this->lastCall = ['name' => $name, 'arguments' => $arguments];

                return $this->response;
            }
        };
        $this->app->instance(McpToolClientInterface::class, $client);
        $this->app->forgetInstance(FreeSearchMcpAdapter::class);
        $this->app->forgetInstance(WebSearchProviderInterface::class);

        return app(FreeSearchMcpAdapter::class);
    }

    public function test_implements_web_search_contract(): void
    {
        $this->enableMcp();
        $adapter = $this->adapterWithCall([
            'is_error' => false,
            'text' => '',
            'structured' => ['results' => []],
            'error' => null,
        ]);
        $this->assertInstanceOf(WebSearchProviderInterface::class, $adapter);
        $this->assertSame(FreeSearchMcpAdapter::PROVIDER_ID, $adapter->providerId());
    }

    public function test_disabled_is_not_configured_and_does_not_require_api_key(): void
    {
        config([
            'agricultural_intelligence.mcp.free_search.enabled' => false,
            'agricultural_intelligence.mcp.free_search.command' => 'uvx',
        ]);
        $adapter = app(FreeSearchMcpAdapter::class);
        $this->assertFalse($adapter->isConfigured());
        $outcome = $adapter->search('irrigation scheduling');
        $this->assertSame(WebSearchOutcome::STATUS_NOT_CONFIGURED, $outcome->status);
    }

    public function test_enabled_without_api_key_is_configured(): void
    {
        $this->enableMcp();
        $adapter = $this->adapterWithCall([
            'is_error' => false,
            'text' => '',
            'structured' => ['results' => []],
            'error' => null,
        ]);
        $this->assertTrue($adapter->isConfigured());
    }

    public function test_invalid_command_is_not_configured(): void
    {
        $this->enableMcp(['agricultural_intelligence.mcp.free_search.command' => 'uvx; rm -rf /']);
        $adapter = app(FreeSearchMcpAdapter::class);
        $this->assertFalse($adapter->isConfigured());
    }

    public function test_empty_command_is_not_configured(): void
    {
        $this->enableMcp(['agricultural_intelligence.mcp.free_search.command' => '']);
        $this->assertFalse(app(FreeSearchMcpAdapter::class)->isConfigured());
    }

    public function test_timeout_configuration_accepts_milliseconds(): void
    {
        $this->enableMcp(['agricultural_intelligence.mcp.free_search.timeout' => 30000]);
        $this->assertSame(30.0, app(LaravelStdioMcpToolClient::class)->timeoutSeconds());
    }

    public function test_timeout_configuration_accepts_seconds(): void
    {
        $this->enableMcp(['agricultural_intelligence.mcp.free_search.timeout' => 20]);
        $this->assertSame(20.0, app(LaravelStdioMcpToolClient::class)->timeoutSeconds());
    }

    public function test_arguments_come_from_config_not_query(): void
    {
        $this->enableMcp(['agricultural_intelligence.mcp.free_search.arguments' => 'free-search-mcp']);
        $args = app(LaravelStdioMcpToolClient::class)->arguments();
        $this->assertSame(['free-search-mcp'], $args);
        $this->assertNotContains('soil pH', $args);
    }

    public function test_search_invokes_search_tool_with_query_as_data(): void
    {
        $malicious = '"; rm -rf /" && whoami `whoami` $(whoami) foo; echo injected';
        $adapter = $this->adapterWithCall([
            'is_error' => false,
            'text' => '',
            'structured' => ['results' => [
                ['title' => 'Soil pH guide', 'url' => 'https://example.com/ph', 'snippet' => 'pH 6.5'],
            ]],
            'error' => null,
        ]);
        $outcome = $adapter->search($malicious, 5);
        $this->assertSame(WebSearchOutcome::STATUS_SUCCESS, $outcome->status);

        /** @var McpToolClientInterface&object $client */
        $client = app(McpToolClientInterface::class);
        $this->assertSame('search', $client->lastCall['name']);
        $this->assertSame($malicious, $client->lastCall['arguments']['query']);
        $this->assertSame('json', $client->lastCall['arguments']['format']);
        $this->assertArrayNotHasKey(0, $client->lastCall['arguments']);
    }

    public function test_research_tool_only_when_requested(): void
    {
        $adapter = $this->adapterWithCall([
            'is_error' => false,
            'text' => '',
            'structured' => ['results' => [
                ['title' => 'Irrigation', 'url' => 'https://example.com/i', 'snippet' => 'drip'],
            ]],
            'error' => null,
        ]);
        $adapter->search('irrigation', 3, ['tool' => 'research']);
        $client = app(McpToolClientInterface::class);
        $this->assertSame('research', $client->lastCall['name']);
        $this->assertSame('irrigation', $client->lastCall['arguments']['question']);
    }

    public function test_normalizes_json_results_and_preserves_metadata(): void
    {
        $adapter = $this->adapterWithCall([
            'is_error' => false,
            'text' => '',
            'structured' => ['results' => [[
                'title' => 'Fertilizer guidance',
                'url' => 'https://example.org/fert',
                'snippet' => 'N-P-K ratios',
                'content' => 'Apply based on soil tests',
                'source' => 'Example Org',
                'domain' => 'example.org',
                'published_at' => '2024-03-01',
                'author' => 'A. Agronomist',
                'engine' => 'duckduckgo',
                'rank' => 1,
                'provenance' => 'duckduckgo',
            ]]],
            'error' => null,
        ]);
        $row = $adapter->search('fertilizer guidance')->results[0];
        $this->assertSame('web', $row['evidence_family']);
        $this->assertSame('Fertilizer guidance', $row['title']);
        $this->assertSame('https://example.org/fert', $row['url']);
        $this->assertSame('N-P-K ratios', $row['snippet']);
        $this->assertSame('Apply based on soil tests', $row['content']);
        $this->assertSame('Example Org', $row['source']);
        $this->assertSame('example.org', $row['domain']);
        $this->assertSame('2024-03-01', $row['published_at']);
        $this->assertSame('A. Agronomist', $row['author']);
        $this->assertSame('duckduckgo', $row['engine']);
        $this->assertSame(1, $row['rank']);
        $this->assertSame('duckduckgo', $row['provenance']);
    }

    public function test_missing_optional_fields_stay_null_and_are_not_invented(): void
    {
        $adapter = $this->adapterWithCall([
            'is_error' => false,
            'text' => '',
            'structured' => ['results' => [[
                'title' => 'Pest information',
                'url' => 'https://example.com/pest',
            ]]],
            'error' => null,
        ]);
        $row = $adapter->search('pest information')->results[0];
        $this->assertNull($row['snippet']);
        $this->assertNull($row['content']);
        $this->assertNull($row['source']);
        $this->assertSame('example.com', $row['domain']);
        $this->assertNull($row['published_at']);
        $this->assertNull($row['author']);
        $this->assertNull($row['engine']);
        $this->assertNull($row['rank']);
        $this->assertNull($row['provenance']);
    }

    public function test_missing_title_url_and_snippet_is_dropped(): void
    {
        $adapter = $this->adapterWithCall([
            'is_error' => false,
            'text' => '',
            'structured' => ['results' => [['engine' => 'mojeek']]],
            'error' => null,
        ]);
        $this->assertSame(WebSearchOutcome::STATUS_EMPTY, $adapter->search('livestock management')->status);
    }

    public function test_markdown_fallback_and_json_text_payload(): void
    {
        $md = $this->adapterWithCall([
            'is_error' => false,
            'text' => '[Greenhouse management](https://example.com/gh)',
            'structured' => null,
            'error' => null,
        ]);
        $this->assertSame('https://example.com/gh', $md->search('greenhouse management')->results[0]['url']);

        $jsonText = $this->adapterWithCall([
            'is_error' => false,
            'text' => json_encode(['items' => [[
                'title' => 'Aquaculture',
                'link' => 'https://example.com/aqua',
                'description' => 'water quality',
            ]]]),
            'structured' => null,
            'error' => null,
        ]);
        $this->assertSame('https://example.com/aqua', $jsonText->search('aquaculture')->results[0]['url']);
    }

    public function test_empty_malformed_timeout_crash_and_unavailable(): void
    {
        $empty = $this->adapterWithCall([
            'is_error' => false,
            'text' => '',
            'structured' => ['results' => []],
            'error' => null,
        ]);
        $this->assertSame(WebSearchOutcome::STATUS_EMPTY, $empty->search('crop temperature')->status);

        $malformed = $this->adapterWithCall([
            'is_error' => false,
            'text' => 'not-json-and-no-links',
            'structured' => ['unexpected' => true],
            'error' => null,
        ]);
        $this->assertSame(WebSearchOutcome::STATUS_FAILED, $malformed->search('soil pH')->status);
        $this->assertSame('malformed_response', $malformed->search('soil pH')->error);

        foreach (['timeout' => 'timeout', 'process_unavailable' => 'process_unavailable', 'mcp_failure' => 'mcp_failure'] as $code => $expect) {
            $failed = $this->adapterWithCall([
                'is_error' => true,
                'text' => '',
                'structured' => null,
                'error' => $code,
            ]);
            $outcome = $failed->search('irrigation');
            $this->assertSame(WebSearchOutcome::STATUS_FAILED, $outcome->status);
            $this->assertSame($expect, $outcome->error);
        }
    }

    public function test_binding_is_configuration_driven(): void
    {
        config(['agricultural_intelligence.mcp.free_search.enabled' => false]);
        $this->assertInstanceOf(ConfigurableWebSearchProvider::class, app(WebSearchProviderInterface::class));

        $this->app->forgetInstance(WebSearchProviderInterface::class);
        $this->enableMcp();
        $this->adapterWithCall([
            'is_error' => false,
            'text' => '',
            'structured' => ['results' => []],
            'error' => null,
        ]);
        $this->app->forgetInstance(WebSearchProviderInterface::class);
        $this->assertInstanceOf(FreeSearchMcpAdapter::class, app(WebSearchProviderInterface::class));
    }

    public function test_registry_keeps_scientific_providers_and_selects_mcp_when_enabled(): void
    {
        $this->enableMcp();
        $this->adapterWithCall([
            'is_error' => false,
            'text' => '',
            'structured' => ['results' => []],
            'error' => null,
        ]);
        $this->app->forgetInstance(AgriculturalProviderRegistry::class);
        $ids = app(AgriculturalProviderRegistry::class)->ids();
        $this->assertContains('free_search_mcp', $ids);
        $this->assertContains('semantic_scholar', $ids);
        $this->assertContains('openalex', $ids);
        $this->assertContains('crossref', $ids);
        $this->assertNotContains('potato', $ids);
    }

    public function test_safe_token_rejects_injection(): void
    {
        $adapter = $this->adapterWithCall([
            'is_error' => false, 'text' => '', 'structured' => ['results' => []], 'error' => null,
        ]);
        $this->assertTrue($adapter->isSafeToken('uvx'));
        $this->assertTrue($adapter->isSafeToken('free-search-mcp'));
        $this->assertFalse($adapter->isSafeToken('uvx; rm -rf /'));
        $this->assertFalse($adapter->isSafeToken('&& whoami'));
        $this->assertFalse($adapter->isSafeToken('$(whoami)'));
        $this->assertFalse($adapter->isSafeToken('`whoami`'));
    }
}
