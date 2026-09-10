<?php

namespace Tests\Feature\Agriculture\Intelligence;

use App\Contracts\Agriculture\WebSearchProviderInterface;
use App\Services\Agriculture\Intelligence\Contracts\AnswerStatus;
use App\Services\Agriculture\Intelligence\DTO\WebSearchOutcome;
use App\Services\Agriculture\Intelligence\Orchestration\UniversalAnswerOrchestrator;
use App\Services\Agriculture\Research\AgriculturalResearchAgent;
use App\Services\Agriculture\Research\Search\Adapters\CrossRefScientificSourceAdapter;
use App\Services\Agriculture\Research\Search\Adapters\OpenAlexScientificSourceAdapter;
use App\Services\Agriculture\Research\Search\Adapters\SemanticScholarScientificSourceAdapter;
use App\Services\Agriculture\Research\Search\ScientificSourceSearchOutcome;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class UniversalAnswerOrchestratorFeatureTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_mandatory_general_web_when_scientific_insufficient_and_web_sufficient(): void
    {
        config([
            'agricultural_intelligence.enrich_legacy_synthesis' => true,
            'agricultural_intelligence.web_search.enabled' => true,
            'agricultural_intelligence.web_search.api_key' => 'k',
            'agricultural_intelligence.web_search.endpoint' => 'https://web.search.test/v1',
            'agricultural_intelligence.fao.enabled' => false,
        ]);

        Http::fake([
            'web.search.test/*' => Http::response([
                'results' => [
                    [
                        'title' => 'General agronomy page',
                        'url' => 'https://example.com/agronomy',
                        'snippet' => 'Discusses typical field practices at 18 C',
                    ],
                ],
            ], 200),
            '*' => Http::response(['results' => [], 'message' => []], 200),
        ]);

        // Force scholarly adapters empty so scientific path is insufficient.
        $this->mockEmptyScholarlyAdapters();

        $result = app(UniversalAnswerOrchestrator::class)->answer([
            'query' => 'What are general field management practices for cereals?',
            'force_execute' => true,
            'language' => 'en',
        ])->toArray();

        $this->assertFalse($result['scientific_answer_eligible']);
        $this->assertTrue($result['web_answer_eligible']);
        $this->assertTrue($result['overall_answer_eligible']);
        $this->assertSame(AnswerStatus::GENERAL_WEB, $result['answer_status']);
        $this->assertNotEmpty($result['answer']);
        $this->assertStringContainsStringIgnoringCase('web', (string) $result['answer']);
        $this->assertArrayHasKey('required_capabilities', $result['universal_orchestrator']['observability'] ?? []);
        $this->assertArrayHasKey('skipped_providers', $result['universal_orchestrator']['observability'] ?? []);
    }

    public function test_enrich_legacy_synthesis_preserves_general_web_contract(): void
    {
        config([
            'agricultural_intelligence.web_search.enabled' => true,
            'agricultural_intelligence.web_search.api_key' => 'k',
            'agricultural_intelligence.web_search.endpoint' => 'https://web.search.test/v1',
        ]);

        Http::fake([
            'web.search.test/*' => Http::response([
                'results' => [
                    ['title' => 'Web note', 'url' => 'https://example.com/n', 'snippet' => 'Value 7 mm'],
                ],
            ], 200),
        ]);

        $enriched = app(UniversalAnswerOrchestrator::class)->enrichLegacySynthesis([
            'status' => 'insufficient_evidence',
            'answer' => null,
            'concise_summary' => null,
            'citations' => [],
            'limitations' => ['no_direct_evidence'],
            'research_metadata' => [
                'query' => 'generic agricultural question',
                'evidence_sufficient' => false,
                'direct_evidence_gate' => 'INSUFFICIENT_DIRECT_EVIDENCE',
            ],
        ], ['query' => 'generic agricultural question', 'language' => 'en']);

        $this->assertTrue($enriched['overall_answer_eligible']);
        $this->assertTrue($enriched['web_answer_eligible']);
        $this->assertFalse($enriched['scientific_answer_eligible']);
        $this->assertSame(AnswerStatus::GENERAL_WEB, $enriched['answer_status']);
        $this->assertNotEmpty($enriched['answer']);
    }

    public function test_ar_and_en_general_web_messages(): void
    {
        config([
            'agricultural_intelligence.web_search.enabled' => true,
            'agricultural_intelligence.web_search.api_key' => 'k',
            'agricultural_intelligence.web_search.endpoint' => 'https://web.search.test/v1',
        ]);

        Http::fake([
            'web.search.test/*' => Http::response([
                'results' => [
                    ['title' => 'Hit', 'url' => 'https://example.com', 'snippet' => 'info'],
                ],
            ], 200),
        ]);

        $en = app(UniversalAnswerOrchestrator::class)->enrichLegacySynthesis([
            'status' => 'insufficient_evidence',
            'answer' => null,
            'research_metadata' => ['query' => 'q', 'evidence_sufficient' => false, 'direct_evidence_gate' => 'INSUFFICIENT_DIRECT_EVIDENCE'],
            'citations' => [],
            'limitations' => [],
        ], ['query' => 'q', 'language' => 'en']);

        $ar = app(UniversalAnswerOrchestrator::class)->enrichLegacySynthesis([
            'status' => 'insufficient_evidence',
            'answer' => null,
            'research_metadata' => ['query' => 'q', 'evidence_sufficient' => false, 'direct_evidence_gate' => 'INSUFFICIENT_DIRECT_EVIDENCE'],
            'citations' => [],
            'limitations' => [],
        ], ['query' => 'q', 'language' => 'ar']);

        $this->assertStringContainsString('General web', (string) $en['answer']);
        $this->assertStringContainsString('الويب', (string) $ar['answer']);
    }

    public function test_agent_answer_universal_method_exists_and_returns_eligibility_keys(): void
    {
        config([
            'agricultural_intelligence.orchestrator_enabled' => true,
            'agricultural_intelligence.fao.enabled' => false,
        ]);
        Http::fake(['*' => Http::response(['results' => []], 200)]);
        $this->mockEmptyScholarlyAdapters();

        $web = Mockery::mock(WebSearchProviderInterface::class);
        $web->shouldReceive('providerId')->andReturn('web_search');
        $web->shouldReceive('displayName')->andReturn('Web Search');
        $web->shouldReceive('isConfigured')->andReturn(false);
        $web->shouldReceive('search')->andReturn(new WebSearchOutcome(
            providerId: 'web_search',
            status: WebSearchOutcome::STATUS_NOT_CONFIGURED,
            error: 'NOT_CONFIGURED',
        ));
        $this->app->instance(WebSearchProviderInterface::class, $web);

        $payload = app(AgriculturalResearchAgent::class)->answerUniversal([
            'query' => 'plant nutrition research overview',
            'force_execute' => true,
        ]);

        $this->assertArrayHasKey('web_answer_eligible', $payload);
        $this->assertArrayHasKey('scientific_answer_eligible', $payload);
        $this->assertArrayHasKey('overall_answer_eligible', $payload);
        $this->assertArrayHasKey('answer_status', $payload);
        $this->assertArrayHasKey('providers_used', $payload);
    }

    public function test_orchestrator_enabled_false_disables_answer_universal_and_enrichment(): void
    {
        config([
            'agricultural_intelligence.orchestrator_enabled' => false,
            'agricultural_intelligence.enrich_legacy_synthesis' => true,
        ]);

        $disabled = app(AgriculturalResearchAgent::class)->answerUniversal([
            'query' => 'should not run orchestrator',
        ]);

        $this->assertSame('disabled', $disabled['status']);
        $this->assertFalse($disabled['overall_answer_eligible']);
        $this->assertFalse($disabled['universal_orchestrator']['enabled']);
        $this->assertContains('universal_orchestrator_disabled', $disabled['limitations']);

        $legacy = [
            'status' => 'insufficient_evidence',
            'answer' => null,
            'citations' => [],
            'limitations' => ['no_direct_evidence'],
            'research_metadata' => [
                'query' => 'q',
                'evidence_sufficient' => false,
            ],
        ];

        $enriched = app(UniversalAnswerOrchestrator::class)->enrichLegacySynthesis($legacy, ['query' => 'q']);
        // Direct orchestrator still works; agent enrichment path is gated separately.
        $this->assertArrayHasKey('answer_status', $enriched);

        // Agent synthesize enrichment gate: when flag false, payload must remain untouched.
        $agent = app(AgriculturalResearchAgent::class);
        $ref = new \ReflectionClass($agent);
        $method = $ref->getMethod('maybeEnrichWithUniversalOrchestrator');
        $method->setAccessible(true);
        $passthrough = $method->invoke($agent, $legacy, ['query' => 'q']);
        $this->assertSame($legacy, $passthrough);
    }

    private function mockEmptyScholarlyAdapters(): void
    {
        foreach ([
            OpenAlexScientificSourceAdapter::class => 'openalex',
            CrossRefScientificSourceAdapter::class => 'crossref',
            SemanticScholarScientificSourceAdapter::class => 'semantic_scholar',
        ] as $class => $key) {
            $mock = Mockery::mock($class);
            $mock->shouldReceive('sourceKey')->andReturn($key);
            $mock->shouldReceive('displayName')->andReturn($key);
            $mock->shouldReceive('search')->andReturn(new ScientificSourceSearchOutcome(
                sourceKey: $key,
                status: ScientificSourceSearchOutcome::STATUS_EMPTY,
                error: 'empty_results',
            ));
            $this->app->instance($class, $mock);
        }
    }
}
