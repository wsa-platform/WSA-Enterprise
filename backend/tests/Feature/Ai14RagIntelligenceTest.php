<?php

namespace Tests\Feature;

use App\Contracts\AiKnowledgeRetrieverInterface;
use App\Models\AiUsageRecord;
use App\Models\LibraryItem;
use App\Models\Organization;
use App\Models\User;
use App\Services\Ai\AiErrorSanitizer;
use App\Services\Ai\AiGroundedAnswerPolicy;
use App\Services\Ai\Embeddings\EmbeddingException;
use App\Services\Ai\Rag\IdentityReranker;
use App\Services\Ai\Rag\KnowledgeRerankerInterface;
use App\Services\Ai\Rag\RagOrchestrator;
use App\Services\Ai\Rag\WeightedScoreReranker;
use App\Services\Ai\Retrieval\AiRetrievalHit;
use App\Services\Ai\Retrieval\AiRetrievalResult;
use App\Services\Ai\Retrieval\HybridKnowledgeRetrievalStrategy;
use App\Services\Ai\Retrieval\KnowledgeIngestionService;
use App\Services\Ai\Retrieval\KnowledgeRetrievalRouter;
use App\Services\Ai\Retrieval\KnowledgeSemanticIndexInterface;
use App\Services\Ai\Retrieval\LibraryItemKnowledgeSource;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class Ai14RagIntelligenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        Config::set('ai.provider', 'mock');
        Config::set('ai.fallback_provider', 'mock');
        Config::set('ai.async_dispatch', false);
        Config::set('ai.retrieval.enabled', true);
        Config::set('ai.retrieval.strategy', 'hybrid');
        Config::set('ai.retrieval.semantic_enabled', true);
        Config::set('ai.retrieval.max_results', 5);
        Config::set('ai.retrieval.candidate_limit', 40);
        Config::set('ai.embeddings.enabled', true);
        Config::set('ai.embeddings.provider', 'mock');
        Config::set('ai.embeddings.model', 'mock-hash-v1');
        Config::set('ai.embeddings.dimensions', 64);
        Config::set('ai.rag.min_score', 0);
        Config::set('ai.rag.reranker', 'weighted');
        Config::set('ai.rag.dedupe_content', true);
        Config::set('ai.openai.api_key', 'sk-ai14-secret-key');
        Http::preventStrayRequests();
    }

    private function admin(): User
    {
        return User::where('email', 'admin@wsa.test')->firstOrFail();
    }

    /**
     * @return array<string, string>
     */
    private function headers(?Organization $organization = null): array
    {
        $organization ??= Organization::first();
        $token = $this->admin()->createToken('ai-14')->plainTextToken;

        return [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function ingest(Organization $organization, array $payload): LibraryItem
    {
        $result = app(KnowledgeIngestionService::class)->ingestLibraryItem(
            $organization->id,
            $payload,
            $this->admin()->id,
        );

        return LibraryItem::query()->findOrFail($result->sourceId);
    }

    public function test_rag_orchestrator_uses_existing_retriever_and_mock_provider(): void
    {
        $organization = Organization::first();
        $item = $this->ingest($organization, [
            'slug' => 'ai14-orchard-guide',
            'title' => 'AI14OrchardGuide citrus irrigation',
            'content' => 'AI14OrchardGuide citrus irrigation schedule for orchards.',
            'publication_status' => 'published',
        ]);

        $decision = app(AiGroundedAnswerPolicy::class)->prepare($organization->id, [
            'content' => 'AI14OrchardGuide citrus irrigation',
        ]);

        $this->assertTrue($decision->grounded);
        $this->assertStringContainsString('UNTRUSTED RETRIEVED KNOWLEDGE', $decision->retrievedContext);
        $this->assertSame($item->id, (int) ($decision->citations[0]['source_id'] ?? 0));
        $this->assertArrayHasKey('score', $decision->citations[0]);
        $this->assertTrue($decision->retrievalTelemetry['rag_orchestrated'] ?? false);
        $this->assertSame('weighted', $decision->retrievalTelemetry['reranker'] ?? null);
        $this->assertArrayHasKey('final_context_count', $decision->retrievalTelemetry);
        $this->assertArrayHasKey('context_assembly_duration_ms', $decision->retrievalTelemetry);

        $created = $this->postJson('/api/v1/ai/requests', [
            'request_type' => 'library_summary',
            'input' => ['content' => 'Summarize AI14OrchardGuide citrus irrigation'],
        ], $this->headers())->assertCreated();
        $this->assertTrue($created->json('output.grounded'));
        $this->assertSame($item->id, (int) ($created->json('output.sources.0.source_id') ?? 0));
        $this->assertArrayNotHasKey('url', $created->json('output.sources.0') ?? []);
    }

    public function test_keyword_semantic_and_hybrid_strategies_remain_usable(): void
    {
        $organization = Organization::first();
        $this->ingest($organization, [
            'slug' => 'ai14-hybrid-alpha',
            'title' => 'AI14HybridAlpha tomato blight notes',
            'content' => 'AI14HybridAlpha tomato blight notes',
            'publication_status' => 'published',
        ]);

        foreach (['keyword', 'semantic', 'hybrid'] as $strategy) {
            Config::set('ai.retrieval.strategy', $strategy);
            $result = app(KnowledgeRetrievalRouter::class)->retrieve($organization->id, 'AI14HybridAlpha tomato blight');
            $this->assertNotSame([], $result->hits, $strategy.' should return hits');
            $this->assertContains($result->telemetry['retrieval_strategy'] ?? '', ['keyword', 'semantic', 'hybrid']);
        }
    }

    public function test_candidate_merge_and_duplicate_content_removal(): void
    {
        $organization = Organization::first();
        $this->ingest($organization, [
            'slug' => 'ai14-dup-a',
            'title' => 'AI14DupTitle identical body',
            'content' => 'AI14DupBody identical excerpt for citrus.',
            'publication_status' => 'published',
        ]);
        $this->ingest($organization, [
            'slug' => 'ai14-dup-b',
            'title' => 'AI14DupTitle identical body',
            'content' => 'AI14DupBody identical excerpt for citrus.',
            'publication_status' => 'published',
        ]);

        $rag = app(RagOrchestrator::class)->assemble($organization->id, [
            'query' => 'AI14DupTitle identical body citrus',
        ]);
        $this->assertCount(1, array_values(array_filter(
            $rag->hits,
            static fn (AiRetrievalHit $hit): bool => $hit->title === 'AI14DupTitle identical body',
        )));
    }

    public function test_ranking_is_deterministic_and_respects_limits_and_threshold(): void
    {
        $organization = Organization::first();
        $alpha = $this->ingest($organization, [
            'slug' => 'ai14-rank-alpha',
            'title' => 'AI14RankAlpha citrus',
            'content' => 'AI14RankAlpha citrus orchard notes',
            'publication_status' => 'published',
        ]);
        $beta = $this->ingest($organization, [
            'slug' => 'ai14-rank-beta',
            'title' => 'AI14RankBeta warehouse',
            'content' => 'AI14RankBeta warehouse pallet notes',
            'publication_status' => 'published',
        ]);
        Config::set('ai.retrieval.max_results', 1);
        $rag = app(RagOrchestrator::class)->assemble($organization->id, [
            'content' => 'AI14RankAlpha citrus',
        ]);
        $this->assertCount(1, $rag->hits);
        $this->assertSame($alpha->id, $rag->hits[0]->sourceId);
        $this->assertSame(1, $rag->telemetry['final_context_count'] ?? 0);

        Config::set('ai.rag.min_score', 1000);
        $filtered = app(RagOrchestrator::class)->assemble($organization->id, [
            'content' => 'AI14RankBeta warehouse',
        ]);
        $this->assertSame([], $filtered->hits);
        $this->assertSame('', $filtered->context);
        $this->assertNotSame($beta->id, $filtered->hits[0]->sourceId ?? null);
    }

    public function test_context_size_limit_and_source_attribution(): void
    {
        $organization = Organization::first();
        $item = $this->ingest($organization, [
            'slug' => 'ai14-context-limit',
            'title' => 'AI14ContextLimit irrigation',
            'content' => str_repeat('AI14ContextLimit irrigation details. ', 40),
            'publication_status' => 'published',
        ]);
        Config::set('ai.retrieval.max_context_characters', 120);
        Config::set('ai.retrieval.max_excerpt_characters', 40);
        $rag = app(RagOrchestrator::class)->assemble($organization->id, [
            'question' => 'AI14ContextLimit irrigation',
        ]);
        $this->assertLessThanOrEqual(120, mb_strlen($rag->context));
        $this->assertSame($item->id, $rag->citations[0]['source_id'] ?? null);
        $this->assertSame('library_items', $rag->citations[0]['source_type'] ?? null);
        $this->assertArrayHasKey('score', $rag->citations[0]);
        $this->assertArrayNotHasKey('organization_id', $rag->citations[0]);
        $this->assertArrayNotHasKey('api_key', $rag->citations[0]);
    }

    public function test_unpublished_deleted_and_foreign_tenant_knowledge_cannot_enter_rag(): void
    {
        $orgA = Organization::first();
        $orgB = Organization::query()->create([
            'name' => 'AI14 Org B',
            'slug' => 'ai14-org-b',
        ]);
        $visible = $this->ingest($orgA, [
            'slug' => 'ai14-visible',
            'title' => 'AI14Visible citrus notes',
            'content' => 'AI14Visible citrus notes',
            'publication_status' => 'published',
        ]);
        $draft = $this->ingest($orgA, [
            'slug' => 'ai14-draft',
            'title' => 'AI14Draft citrus notes',
            'content' => 'AI14Draft citrus notes',
            'publication_status' => 'draft',
        ]);
        $foreign = $this->ingest($orgB, [
            'slug' => 'ai14-foreign',
            'title' => 'AI14Foreign citrus notes',
            'content' => 'AI14Foreign citrus notes',
            'publication_status' => 'published',
        ]);
        $deleted = $this->ingest($orgA, [
            'slug' => 'ai14-deleted',
            'title' => 'AI14Deleted citrus notes',
            'content' => 'AI14Deleted citrus notes',
            'publication_status' => 'published',
        ]);
        $deleted->delete();

        $rag = app(RagOrchestrator::class)->assemble($orgA->id, [
            'content' => 'AI14Visible AI14Draft AI14Foreign AI14Deleted citrus notes',
        ]);
        $ids = array_map(static fn (AiRetrievalHit $hit): int => $hit->sourceId, $rag->hits);
        $this->assertContains($visible->id, $ids);
        $this->assertNotContains($draft->id, $ids);
        $this->assertNotContains($foreign->id, $ids);
        $this->assertNotContains($deleted->id, $ids);
        $this->assertFalse(str_contains($rag->context, 'AI14Draft'));
        $this->assertFalse(str_contains($rag->context, 'AI14Foreign'));
        $this->assertFalse(str_contains($rag->context, 'AI14Deleted'));
    }

    public function test_updated_knowledge_replaces_stale_rag_candidates(): void
    {
        $organization = Organization::first();
        $item = $this->ingest($organization, [
            'slug' => 'ai14-stale',
            'title' => 'AI14StaleOriginal barley notes',
            'content' => 'AI14StaleOriginal barley notes',
            'publication_status' => 'published',
        ]);
        $this->ingest($organization, [
            'slug' => 'ai14-stale',
            'title' => 'AI14StaleUpdated wheat irrigation',
            'content' => 'AI14StaleUpdated wheat irrigation',
            'publication_status' => 'published',
        ]);

        $rag = app(RagOrchestrator::class)->assemble($organization->id, [
            'content' => 'AI14StaleUpdated wheat irrigation',
        ]);
        $this->assertSame($item->id, $rag->hits[0]->sourceId ?? null);
        $this->assertStringContainsString('AI14StaleUpdated', $rag->context);
        $this->assertStringNotContainsString('AI14StaleOriginal barley', $rag->context);
    }

    public function test_semantic_failure_falls_back_to_keyword_and_keyword_failure_can_use_semantic(): void
    {
        $organization = Organization::first();
        $this->ingest($organization, [
            'slug' => 'ai14-fallback',
            'title' => 'AI14Fallback citrus irrigation',
            'content' => 'AI14Fallback citrus irrigation',
            'publication_status' => 'published',
        ]);

        $failingIndex = \Mockery::mock(KnowledgeSemanticIndexInterface::class);
        $failingIndex->shouldReceive('isAvailable')->andReturn(true);
        $failingIndex->shouldReceive('search')->andThrow(new EmbeddingException('vector backend down'));
        $this->app->instance(KnowledgeSemanticIndexInterface::class, $failingIndex);
        $this->app->forgetInstance(HybridKnowledgeRetrievalStrategy::class);
        $this->app->forgetInstance(KnowledgeRetrievalRouter::class);
        $this->app->forgetInstance(AiKnowledgeRetrieverInterface::class);
        $this->app->forgetInstance(RagOrchestrator::class);

        Config::set('ai.retrieval.strategy', 'hybrid');
        $semanticDown = app(RagOrchestrator::class)->assemble($organization->id, [
            'content' => 'AI14Fallback citrus irrigation',
        ]);
        $this->assertNotSame([], $semanticDown->hits);
        $this->assertSame('fallback', $semanticDown->telemetry['retrieval_status'] ?? null);
        $this->assertSame('semantic_error', $semanticDown->telemetry['fallback_reason'] ?? null);

        $this->mock(LibraryItemKnowledgeSource::class, function ($mock): void {
            $mock->shouldReceive('search')->andThrow(new \RuntimeException('keyword backend down'));
        });
        $availableIndex = \Mockery::mock(KnowledgeSemanticIndexInterface::class);
        $availableIndex->shouldReceive('isAvailable')->andReturn(true);
        $availableIndex->shouldReceive('search')->andReturn([
            new AiRetrievalHit(
                sourceType: 'library_items',
                sourceId: LibraryItem::query()->where('slug', 'ai14-fallback')->value('id'),
                title: 'AI14Fallback citrus irrigation',
                content: 'AI14Fallback citrus irrigation',
                score: 0.9,
                organizationId: $organization->id,
            ),
        ]);
        $this->app->instance(KnowledgeSemanticIndexInterface::class, $availableIndex);
        $this->app->forgetInstance(HybridKnowledgeRetrievalStrategy::class);
        $this->app->forgetInstance(KnowledgeRetrievalRouter::class);
        $this->app->forgetInstance(AiKnowledgeRetrieverInterface::class);
        $this->app->forgetInstance(RagOrchestrator::class);

        $keywordDown = app(RagOrchestrator::class)->assemble($organization->id, [
            'content' => 'AI14Fallback citrus irrigation',
        ]);
        $this->assertNotSame([], $keywordDown->hits);
        $this->assertSame('fallback', $keywordDown->telemetry['retrieval_status'] ?? null);
        $this->assertSame('keyword_error', $keywordDown->telemetry['fallback_reason'] ?? null);
    }

    public function test_empty_retrieval_and_both_backends_failing_stay_sanitized(): void
    {
        $organization = Organization::first();
        $empty = app(RagOrchestrator::class)->assemble($organization->id, [
            'content' => 'AI14NoSuchKnowledge zzzqqq',
        ]);
        $this->assertSame([], $empty->hits);
        $this->assertSame('', $empty->context);

        $failingIndex = \Mockery::mock(KnowledgeSemanticIndexInterface::class);
        $failingIndex->shouldReceive('isAvailable')->andReturn(true);
        $failingIndex->shouldReceive('search')->andThrow(new EmbeddingException('vector backend down'));
        $this->app->instance(KnowledgeSemanticIndexInterface::class, $failingIndex);
        $this->mock(LibraryItemKnowledgeSource::class, function ($mock): void {
            $mock->shouldReceive('search')->andThrow(new \RuntimeException('keyword backend down'));
        });
        $this->app->forgetInstance(HybridKnowledgeRetrievalStrategy::class);
        $this->app->forgetInstance(KnowledgeRetrievalRouter::class);
        $this->app->forgetInstance(AiKnowledgeRetrieverInterface::class);
        $this->app->forgetInstance(RagOrchestrator::class);

        $failed = app(RagOrchestrator::class)->assemble($organization->id, [
            'content' => 'AI14Fallback citrus irrigation',
        ]);
        $this->assertTrue($failed->failed || $failed->hits === []);
        $this->assertSame([], $failed->citations);
        $this->assertStringNotContainsString('sk-ai14-secret-key', json_encode($failed->telemetry));
    }

    public function test_telemetry_and_strategy_never_include_secrets(): void
    {
        Log::spy();
        $organization = Organization::first();
        $this->ingest($organization, [
            'slug' => 'ai14-telemetry',
            'title' => 'AI14Telemetry citrus',
            'content' => 'AI14Telemetry citrus',
            'publication_status' => 'published',
        ]);
        $this->postJson('/api/v1/ai/requests', [
            'request_type' => 'library_summary',
            'input' => ['content' => 'AI14Telemetry citrus'],
        ], $this->headers())->assertCreated();

        $usage = AiUsageRecord::query()->latest('id')->first();
        $encoded = json_encode($usage?->retrieval ?? []);
        $this->assertStringNotContainsString('sk-ai14-secret-key', $encoded);
        $this->assertStringNotContainsString('Bearer ', $encoded);
        $this->assertTrue($usage?->retrieval['rag_orchestrated'] ?? false);

        $strategy = $this->getJson('/api/v1/operator/ai/retrieval/strategy', $this->headers())->assertOk();
        $this->assertTrue($strategy->json('data.rag_orchestration'));
        $this->assertSame('weighted', $strategy->json('data.reranker'));
        $this->assertStringNotContainsString('sk-ai14-secret-key', $strategy->getContent());
        $this->assertStringNotContainsString('Authorization', $strategy->getContent());

        $sanitized = AiErrorSanitizer::redact('failed api_key=sk-ai14-secret-key Authorization: Bearer secret-header');
        $this->assertStringNotContainsString('sk-ai14-secret-key', $sanitized);
        $this->assertInstanceOf(WeightedScoreReranker::class, app(KnowledgeRerankerInterface::class));
        Config::set('ai.rag.reranker', 'identity');
        $this->app->forgetInstance(KnowledgeRerankerInterface::class);
        $this->assertInstanceOf(IdentityReranker::class, app(KnowledgeRerankerInterface::class));
    }

    public function test_defense_in_depth_drops_cross_tenant_hits_in_processor(): void
    {
        $organization = Organization::first();
        $foreign = new AiRetrievalHit(
            sourceType: 'library_items',
            sourceId: 9999,
            title: 'AI14Leaked',
            content: 'AI14Leaked secret tenant body',
            score: 99.0,
            organizationId: $organization->id + 50,
        );
        $local = new AiRetrievalHit(
            sourceType: 'library_items',
            sourceId: 12,
            title: 'AI14Local citrus',
            content: 'AI14Local citrus',
            score: 1.0,
            organizationId: $organization->id,
        );
        $retriever = \Mockery::mock(AiKnowledgeRetrieverInterface::class);
        $retriever->shouldReceive('retrieve')->andReturn(new AiRetrievalResult([$foreign, $local], 'x', [
            'retrieval_status' => 'ok',
            'retrieval_strategy' => 'hybrid',
            'keyword_candidate_count' => 2,
            'semantic_candidate_count' => 0,
        ]));
        $this->app->instance(AiKnowledgeRetrieverInterface::class, $retriever);
        $this->app->forgetInstance(RagOrchestrator::class);

        $rag = app(RagOrchestrator::class)->assemble($organization->id, ['query' => 'AI14Local citrus']);
        $this->assertCount(1, $rag->hits);
        $this->assertSame(12, $rag->hits[0]->sourceId);
        $this->assertStringNotContainsString('AI14Leaked', $rag->context);
    }
}
