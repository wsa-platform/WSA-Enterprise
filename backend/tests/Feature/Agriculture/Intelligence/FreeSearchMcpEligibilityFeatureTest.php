<?php

namespace Tests\Feature\Agriculture\Intelligence;

use App\Contracts\Agriculture\McpToolClientInterface;
use App\Services\Agriculture\Intelligence\Contracts\AnswerStatus;
use App\Services\Agriculture\Intelligence\Orchestration\UniversalAnswerOrchestrator;
use Tests\TestCase;

class FreeSearchMcpEligibilityFeatureTest extends TestCase
{
    private function bindMcpClient(array $response): void
    {
        config([
            'agricultural_intelligence.mcp.free_search.enabled' => true,
            'agricultural_intelligence.mcp.free_search.command' => 'uvx',
            'agricultural_intelligence.mcp.free_search.arguments' => 'free-search-mcp',
            'agricultural_intelligence.web_search.enabled' => false,
        ]);
        $this->app->forgetInstance(\App\Contracts\Agriculture\WebSearchProviderInterface::class);
        $this->app->forgetInstance(\App\Services\Agriculture\Intelligence\Adapters\Web\FreeSearchMcpAdapter::class);
        $this->app->forgetInstance(\App\Services\Agriculture\Intelligence\Orchestration\UniversalAnswerOrchestrator::class);

        $this->app->instance(McpToolClientInterface::class, new class($response) implements McpToolClientInterface
        {
            public function __construct(private array $response) {}

            public function callTool(string $name, array $arguments): array
            {
                return $this->response;
            }
        });
    }

    public function test_case_a_scientific_and_web_eligible(): void
    {
        $this->bindMcpClient([
            'is_error' => false,
            'text' => '',
            'structured' => ['results' => [[
                'title' => 'Irrigation overview',
                'url' => 'https://example.com/irr',
                'snippet' => 'Typical drip ranges',
            ]]],
            'error' => null,
        ]);

        $enriched = app(UniversalAnswerOrchestrator::class)->enrichLegacySynthesis([
            'status' => 'synthesis_completed',
            'answer' => 'Scientific synthesis',
            'citations' => [['title' => 'paper']],
            'limitations' => [],
            'research_metadata' => [
                'query' => 'irrigation',
                'evidence_sufficient' => true,
                'direct_evidence_gate' => 'SUFFICIENT',
            ],
        ], ['query' => 'irrigation', 'language' => 'en']);

        $this->assertTrue($enriched['scientific_answer_eligible']);
        $this->assertTrue($enriched['web_answer_eligible']);
        $this->assertTrue($enriched['overall_answer_eligible']);
    }

    public function test_case_b_web_only_yields_general_web(): void
    {
        $this->bindMcpClient([
            'is_error' => false,
            'text' => '',
            'structured' => ['results' => [[
                'title' => 'Soil pH page',
                'url' => 'https://example.com/ph',
                'snippet' => 'Target 6.0 to 6.8',
            ]]],
            'error' => null,
        ]);

        $enriched = app(UniversalAnswerOrchestrator::class)->enrichLegacySynthesis([
            'status' => 'insufficient_evidence',
            'answer' => null,
            'citations' => [],
            'limitations' => ['no_direct_evidence'],
            'research_metadata' => [
                'query' => 'soil pH',
                'evidence_sufficient' => false,
                'direct_evidence_gate' => 'INSUFFICIENT_DIRECT_EVIDENCE',
            ],
        ], ['query' => 'soil pH', 'language' => 'en']);

        $this->assertFalse($enriched['scientific_answer_eligible']);
        $this->assertTrue($enriched['web_answer_eligible']);
        $this->assertTrue($enriched['overall_answer_eligible']);
        $this->assertSame(AnswerStatus::GENERAL_WEB, $enriched['answer_status']);
        $this->assertNotEmpty($enriched['answer']);
    }

    public function test_case_c_both_insufficient_does_not_fabricate(): void
    {
        $this->bindMcpClient([
            'is_error' => false,
            'text' => '',
            'structured' => ['results' => []],
            'error' => null,
        ]);

        $enriched = app(UniversalAnswerOrchestrator::class)->enrichLegacySynthesis([
            'status' => 'insufficient_evidence',
            'answer' => null,
            'citations' => [],
            'limitations' => ['no_direct_evidence'],
            'research_metadata' => [
                'query' => 'unknown topic',
                'evidence_sufficient' => false,
                'direct_evidence_gate' => 'INSUFFICIENT_DIRECT_EVIDENCE',
            ],
        ], ['query' => 'unknown topic', 'language' => 'en']);

        $this->assertFalse($enriched['scientific_answer_eligible']);
        $this->assertFalse($enriched['web_answer_eligible']);
        $this->assertFalse($enriched['overall_answer_eligible']);
        $this->assertSame(AnswerStatus::INSUFFICIENT, $enriched['answer_status']);
    }

    public function test_case_d_web_failure_does_not_block_scientific(): void
    {
        $this->bindMcpClient([
            'is_error' => true,
            'text' => '',
            'structured' => null,
            'error' => 'timeout',
        ]);

        $enriched = app(UniversalAnswerOrchestrator::class)->enrichLegacySynthesis([
            'status' => 'synthesis_completed',
            'answer' => 'Verified scientific answer',
            'citations' => [['title' => 'paper']],
            'limitations' => [],
            'research_metadata' => [
                'query' => 'crop temperature',
                'evidence_sufficient' => true,
                'direct_evidence_gate' => 'SUFFICIENT',
            ],
        ], ['query' => 'crop temperature', 'language' => 'en']);

        $this->assertTrue($enriched['scientific_answer_eligible']);
        $this->assertTrue($enriched['overall_answer_eligible']);
        $this->assertSame('Verified scientific answer', $enriched['answer']);
    }

    public function test_case_e_scientific_failure_web_usable(): void
    {
        $this->bindMcpClient([
            'is_error' => false,
            'text' => '',
            'structured' => ['results' => [[
                'title' => 'Livestock management notes',
                'url' => 'https://example.com/livestock',
                'snippet' => 'Housing and feed',
            ]]],
            'error' => null,
        ]);

        $enriched = app(UniversalAnswerOrchestrator::class)->enrichLegacySynthesis([
            'status' => 'insufficient_evidence',
            'answer' => null,
            'citations' => [],
            'limitations' => ['provider_failure_isolated'],
            'research_metadata' => [
                'query' => 'livestock management',
                'evidence_sufficient' => false,
                'direct_evidence_gate' => 'INSUFFICIENT_DIRECT_EVIDENCE',
            ],
        ], ['query' => 'livestock management', 'language' => 'en']);

        $this->assertFalse($enriched['scientific_answer_eligible']);
        $this->assertTrue($enriched['web_answer_eligible']);
        $this->assertTrue($enriched['overall_answer_eligible']);
        $this->assertSame(AnswerStatus::GENERAL_WEB, $enriched['answer_status']);
    }
}
