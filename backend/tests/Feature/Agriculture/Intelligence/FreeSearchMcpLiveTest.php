<?php

namespace Tests\Feature\Agriculture\Intelligence;

use App\Services\Agriculture\Intelligence\Adapters\Web\FreeSearchMcpAdapter;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Live MCP smoke — skipped unless FREE_SEARCH_MCP_LIVE=1 and uvx is available.
 */
#[Group('live-mcp')]
class FreeSearchMcpLiveTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (getenv('FREE_SEARCH_MCP_LIVE') !== '1' && ($_ENV['FREE_SEARCH_MCP_LIVE'] ?? '') !== '1') {
            $this->markTestSkipped('Live MCP smoke disabled (set FREE_SEARCH_MCP_LIVE=1).');
        }
    }

    public function test_live_keyless_search_via_adapter(): void
    {
        config([
            'agricultural_intelligence.mcp.free_search.enabled' => true,
            'agricultural_intelligence.mcp.free_search.command' => env('FREE_SEARCH_MCP_COMMAND', 'uvx'),
            'agricultural_intelligence.mcp.free_search.arguments' => env('FREE_SEARCH_MCP_ARGUMENTS', 'free-search-mcp'),
            'agricultural_intelligence.mcp.free_search.timeout' => 60000,
        ]);

        $started = microtime(true);
        $adapter = app(FreeSearchMcpAdapter::class);
        $this->assertTrue($adapter->isConfigured());
        $outcome = $adapter->search('soil pH irrigation agriculture', 5);
        $elapsedMs = (int) round((microtime(true) - $started) * 1000);

        $this->assertContains($outcome->status, [
            $outcome::STATUS_SUCCESS,
            $outcome::STATUS_EMPTY,
            $outcome::STATUS_FAILED,
        ]);
        if ($outcome->status === $outcome::STATUS_SUCCESS) {
            $this->assertNotEmpty($outcome->results);
            $this->assertNotEmpty($outcome->results[0]['url'] ?? $outcome->results[0]['title'] ?? null);
        }

        fwrite(STDERR, sprintf(
            "LIVE_MCP status=%s count=%d elapsed_ms=%d error=%s\n",
            $outcome->status,
            count($outcome->results),
            $elapsedMs,
            (string) ($outcome->error ?? 'none'),
        ));
    }
}
