<?php

namespace Tests\Feature;

use App\Contracts\AiKnowledgeRetrieverInterface;
use App\Models\AiRequest;
use App\Models\AiUsageRecord;
use App\Models\LibraryItem;
use App\Models\Organization;
use App\Models\User;
use App\Services\Ai\AiGroundedAnswerDisclosurePolicy;
use App\Services\Ai\AiService;
use App\Services\Ai\AiUsageRecorder;
use App\Services\Ai\Retrieval\KeywordKnowledgeRetriever;
use App\Services\Ai\Retrieval\KnowledgeFreshnessService;
use App\Services\Ai\Retrieval\KnowledgeIndexer;
use App\Services\Ai\Retrieval\KnowledgeIngestionResult;
use App\Services\Ai\Retrieval\KnowledgeIngestionService;
use App\Services\Ai\Retrieval\KnowledgeRetrievalConfig;
use App\Services\Ai\Retrieval\KnowledgeRetrievalQualityService;
use App\Services\Ai\Retrieval\KnowledgeRetrievalRouter;
use App\Services\Ai\Retrieval\KnowledgeSemanticIndexInterface;
use App\Services\Ai\Retrieval\KnowledgeSemanticIndexSync;
use App\Services\Ai\Retrieval\VectorKnowledgeSemanticIndex;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class Ai10HybridKnowledgeRetrievalTest extends TestCase
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
        Config::set('ai.retrieval.strategy', 'keyword');
        Config::set('ai.retrieval.semantic_enabled', true);
        Config::set('ai.retrieval.max_results', 5);
        Config::set('ai.retrieval.candidate_limit', 40);
        Http::preventStrayRequests();
    }

    private function admin(): User
    {
        return User::where('email', 'admin@wsa.test')->firstOrFail();
    }

    private function adminHeaders(?Organization $organization = null): array
    {
        $organization ??= Organization::first();
        $token = $this->admin()->createToken('ai-10')->plainTextToken;

        return [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function ingestLibrary(Organization $organization, array $payload): KnowledgeIngestionResult
    {
        return app(KnowledgeIngestionService::class)->ingestLibraryItem(
            $organization->id,
            $payload,
            $this->admin()->id,
        );
    }

    public function test_keyword_strategy_remains_unchanged(): void
    {
        $organization = Organization::first();
        $created = $this->ingestLibrary($organization, [
            'slug' => 'ai10-keyword-same',
            'title' => 'AI10KeywordSameTitle',
            'summary' => 'Keyword baseline.',
            'publication_status' => 'published',
        ]);

        $direct = app(KeywordKnowledgeRetriever::class)->retrieve($organization->id, 'AI10KeywordSameTitle');
        $routed = app(KnowledgeRetrievalRouter::class)->retrieve($organization->id, 'AI10KeywordSameTitle');

        $this->assertSame($created->sourceId, $direct->hits[0]->sourceId);
        $this->assertSame($direct->hits[0]->sourceId, $routed->hits[0]->sourceId);
        $this->assertSame('keyword', $routed->telemetry['retrieval_strategy'] ?? null);
        $this->assertSame('keyword', config('ai.retrieval.strategy'));
    }

    public function test_semantic_strategy_uses_deterministic_local_implementation(): void
    {
        Config::set('ai.retrieval.strategy', 'semantic');
        $organization = Organization::first();
        $created = $this->ingestLibrary($organization, [
            'slug' => 'ai10-semantic-local',
            'title' => 'Orchard notes',
            'summary' => 'AI10SemanticLocalTerm overlap.',
            'content' => 'Deterministic lexical approximation body.',
            'publication_status' => 'published',
        ]);

        $result = app(KnowledgeRetrievalRouter::class)->retrieve($organization->id, 'AI10SemanticLocalTerm');

        $this->assertSame('semantic', $result->telemetry['retrieval_strategy'] ?? null);
        $this->assertTrue(collect($result->hits)->contains(fn ($hit) => $hit->sourceId === $created->sourceId));
        $this->assertGreaterThan(0, $result->hits[0]->metadata['semantic_score'] ?? 0);
        $this->assertInstanceOf(VectorKnowledgeSemanticIndex::class, app(KnowledgeSemanticIndexInterface::class));
    }

    public function test_hybrid_strategy_combines_scores_deterministically(): void
    {
        Config::set('ai.retrieval.strategy', 'hybrid');
        $organization = Organization::first();
        $this->ingestLibrary($organization, [
            'slug' => 'ai10-hybrid-mix',
            'title' => 'AI10HybridMixTitle',
            'summary' => 'Hybrid notes.',
            'publication_status' => 'published',
        ]);

        $first = app(KnowledgeRetrievalRouter::class)->retrieve($organization->id, 'AI10HybridMixTitle');
        $second = app(KnowledgeRetrievalRouter::class)->retrieve($organization->id, 'AI10HybridMixTitle');

        $this->assertSame('hybrid', $first->telemetry['retrieval_strategy'] ?? null);
        $this->assertSame($first->hits[0]->sourceId, $second->hits[0]->sourceId);
        $this->assertSame($first->hits[0]->score, $second->hits[0]->score);
        $this->assertArrayHasKey('keyword_score', $first->hits[0]->metadata);
        $this->assertArrayHasKey('semantic_score', $first->hits[0]->metadata);
        $this->assertArrayHasKey('freshness_score', $first->hits[0]->metadata);
        $this->assertArrayHasKey('hybrid_score', $first->hits[0]->metadata);
        $this->assertSame($first->hits[0]->metadata['hybrid_score'], $first->hits[0]->score);
    }

    public function test_exact_title_remains_higher_than_weak_semantic_similarity(): void
    {
        Config::set('ai.retrieval.strategy', 'hybrid');
        $organization = Organization::first();
        $exact = $this->ingestLibrary($organization, [
            'slug' => 'ai10-exact-title',
            'title' => 'AI10HybridExactTitle',
            'summary' => 'Exact title document.',
            'publication_status' => 'published',
        ]);
        $this->ingestLibrary($organization, [
            'slug' => 'ai10-weak-semantic',
            'title' => 'Unrelated newsletter',
            'summary' => 'Passing mention.',
            'content' => str_repeat('AI10HybridExactTitle mentioned in body. ', 6),
            'publication_status' => 'published',
        ]);

        $result = app(KnowledgeRetrievalRouter::class)->retrieve($organization->id, 'AI10HybridExactTitle');
        $this->assertSame($exact->sourceId, $result->hits[0]->sourceId);
    }

    public function test_freshness_cannot_overpower_strong_relevance(): void
    {
        Config::set('ai.retrieval.strategy', 'hybrid');
        $organization = Organization::first();
        $relevant = $this->ingestLibrary($organization, [
            'slug' => 'ai10-old-relevant',
            'title' => 'AI10FreshnessGuard',
            'summary' => 'Exact title relevance.',
            'publication_status' => 'published',
        ]);
        $fresh = $this->ingestLibrary($organization, [
            'slug' => 'ai10-new-irrelevant',
            'title' => 'Brand new newsletter',
            'summary' => 'Unrelated.',
            'content' => 'AI10FreshnessGuard is mentioned once in passing.',
            'publication_status' => 'published',
        ]);
        DB::table('library_items')->where('id', $relevant->sourceId)->update(['updated_at' => now()->subDays(300)]);
        DB::table('library_items')->where('id', $fresh->sourceId)->update(['updated_at' => now()]);

        $result = app(KnowledgeRetrievalRouter::class)->retrieve($organization->id, 'AI10FreshnessGuard');
        $this->assertSame($relevant->sourceId, $result->hits[0]->sourceId);
        $this->assertSame('stale', app(KnowledgeFreshnessService::class)->classify(
            LibraryItem::query()->find($relevant->sourceId)->updated_at,
            now(),
        ));
    }

    public function test_semantic_unavailable_falls_back_to_keyword_and_records_telemetry(): void
    {
        Config::set('ai.retrieval.strategy', 'hybrid');
        Config::set('ai.retrieval.semantic_enabled', false);
        $organization = Organization::first();
        $this->ingestLibrary($organization, [
            'slug' => 'ai10-fallback-hit',
            'title' => 'AI10FallbackGuide',
            'summary' => 'Fallback source.',
            'publication_status' => 'published',
        ]);

        $created = $this->postJson('/api/v1/ai/requests', [
            'request_type' => 'library_summary',
            'input' => ['content' => 'Summarize AI10FallbackGuide'],
        ], $this->adminHeaders())->assertCreated();

        $usage = AiUsageRecord::withoutGlobalScopes()->where('ai_request_id', $created->json('id'))->first();
        $this->assertNotNull($usage);
        $this->assertSame('fallback', $usage->retrieval['retrieval_status'] ?? null);
        $this->assertSame('semantic_unavailable', $usage->retrieval['fallback_reason'] ?? null);
        $this->assertSame('keyword', $usage->retrieval['retrieval_strategy'] ?? null);
        $this->assertTrue($created->json('output.grounded'));
        $this->assertSame('completed', $created->json('status'));
    }

    public function test_tenant_isolation_for_keyword_semantic_and_hybrid(): void
    {
        $orgA = Organization::first();
        $orgB = Organization::create(['name' => 'AI10 Org B', 'slug' => 'ai10-org-b']);
        $this->ingestLibrary($orgB, [
            'slug' => 'ai10-org-b-only',
            'title' => 'AI10PrivateOrgBOnly',
            'summary' => 'Foreign tenant body.',
            'publication_status' => 'published',
        ]);

        foreach (['keyword', 'semantic', 'hybrid'] as $strategy) {
            Config::set('ai.retrieval.strategy', $strategy);
            $result = app(KnowledgeRetrievalRouter::class)->retrieve($orgA->id, 'AI10PrivateOrgBOnly');
            $this->assertFalse(
                collect($result->hits)->contains(fn ($hit) => $hit->title === 'AI10PrivateOrgBOnly'),
                "Strategy {$strategy} leaked tenant B content",
            );
        }
    }

    public function test_unpublished_content_is_excluded_from_all_strategies(): void
    {
        $organization = Organization::first();
        $this->ingestLibrary($organization, [
            'slug' => 'ai10-draft-secret',
            'title' => 'AI10DraftSecretTerm',
            'summary' => 'Hidden draft.',
            'publication_status' => 'draft',
        ]);

        foreach (['keyword', 'semantic', 'hybrid'] as $strategy) {
            Config::set('ai.retrieval.strategy', $strategy);
            $result = app(KnowledgeRetrievalRouter::class)->retrieve($organization->id, 'AI10DraftSecretTerm');
            $this->assertFalse(
                collect($result->hits)->contains(fn ($hit) => $hit->title === 'AI10DraftSecretTerm'),
                "Strategy {$strategy} leaked unpublished content",
            );
        }
    }

    public function test_empty_and_malformed_queries_are_safe(): void
    {
        $organization = Organization::first();
        $empty = app(KnowledgeRetrievalRouter::class)->retrieve($organization->id, '');
        $malformed = app(KnowledgeRetrievalRouter::class)->retrieve($organization->id, "\0\0   ");

        $this->assertTrue($empty->isEmpty());
        $this->assertTrue($malformed->isEmpty());
        $this->assertContains($empty->telemetry['retrieval_status'] ?? null, ['empty', 'ok']);
        $this->assertSame('completed', $this->postJson('/api/v1/ai/requests', [
            'request_type' => 'library_summary',
            'input' => ['content' => 'zzzzzxqai10nomatch'],
        ], $this->adminHeaders())->json('status'));
    }

    public function test_semantic_indexing_failure_does_not_break_ingestion(): void
    {
        $index = \Mockery::mock(KnowledgeSemanticIndexInterface::class);
        $index->shouldReceive('isAvailable')->andReturn(true);
        $index->shouldReceive('index')->andThrow(new \RuntimeException('index unavailable api_key=sk-SHOULDNOTLEAK'));
        $index->shouldReceive('remove')->andThrow(new \RuntimeException('index unavailable'));
        $this->app->instance(KnowledgeSemanticIndexInterface::class, $index);
        $this->app->forgetInstance(KnowledgeSemanticIndexSync::class);
        $this->app->forgetInstance(KnowledgeIngestionService::class);

        $organization = Organization::first();
        $result = $this->ingestLibrary($organization, [
            'slug' => 'ai10-index-fail',
            'title' => 'AI10IndexFailTitle',
            'publication_status' => 'published',
        ]);

        $item = LibraryItem::query()->find($result->sourceId);
        $this->assertSame('created', $result->action);
        $this->assertSame('AI10IndexFailTitle', $item->title);
        $this->assertSame('published', $item->publication_status);
    }

    public function test_repeated_indexing_is_idempotent_and_update_refreshes_representation(): void
    {
        $organization = Organization::first();
        $created = $this->ingestLibrary($organization, [
            'slug' => 'ai10-index-refresh',
            'title' => 'AI10IndexOriginalTerm',
            'summary' => 'Original.',
            'publication_status' => 'published',
        ]);
        $index = app(KnowledgeSemanticIndexInterface::class);
        $first = $index->fingerprint('library_items', $created->sourceId);
        $item = LibraryItem::query()->find($created->sourceId);
        $index->index(app(KnowledgeIndexer::class)->fromLibraryItem($item));
        $second = $index->fingerprint('library_items', $created->sourceId);
        $this->assertNotNull($first);
        $this->assertSame($first, $second);

        $this->ingestLibrary($organization, [
            'slug' => 'ai10-index-refresh',
            'title' => 'AI10IndexUpdatedTerm',
            'summary' => 'Updated.',
            'publication_status' => 'published',
        ]);
        $updated = $index->fingerprint('library_items', $created->sourceId);
        $this->assertNotSame($first, $updated);

        Config::set('ai.retrieval.strategy', 'semantic');
        $result = app(KnowledgeRetrievalRouter::class)->retrieve($organization->id, 'AI10IndexUpdatedTerm');
        $this->assertTrue(collect($result->hits)->contains(fn ($hit) => $hit->sourceId === $created->sourceId));
    }

    public function test_unpublishing_removes_document_from_semantic_retrieval(): void
    {
        $organization = Organization::first();
        $created = $this->ingestLibrary($organization, [
            'slug' => 'ai10-unpublish-semantic',
            'title' => 'AI10UnpublishSemanticTerm',
            'publication_status' => 'published',
        ]);
        Config::set('ai.retrieval.strategy', 'semantic');
        $before = app(KnowledgeRetrievalRouter::class)->retrieve($organization->id, 'AI10UnpublishSemanticTerm');
        $this->assertTrue(collect($before->hits)->contains(fn ($hit) => $hit->sourceId === $created->sourceId));

        $this->ingestLibrary($organization, [
            'slug' => 'ai10-unpublish-semantic',
            'title' => 'AI10UnpublishSemanticTerm',
            'publication_status' => 'draft',
        ]);
        $after = app(KnowledgeRetrievalRouter::class)->retrieve($organization->id, 'AI10UnpublishSemanticTerm');
        $this->assertFalse(collect($after->hits)->contains(fn ($hit) => $hit->sourceId === $created->sourceId));
    }

    public function test_source_freshness_remains_compatible_with_ai09(): void
    {
        $freshness = app(KnowledgeFreshnessService::class);
        $this->assertSame('unknown', $freshness->classify(null, now()));
        $this->assertSame(0.0, $freshness->rankingScore(null, now()));
        $this->assertLessThanOrEqual(2.0, $freshness->rankingScore(now(), now()));
    }

    public function test_existing_ai04_ai06_ai07_ai08_ai09_contracts_still_work(): void
    {
        $organization = Organization::first();
        $this->ingestLibrary($organization, [
            'slug' => 'ai10-regression-hit',
            'title' => 'AI10RegressionGuide',
            'summary' => 'Regression source.',
            'content' => 'Grounded body.',
            'publication_status' => 'published',
        ]);

        $grounded = $this->postJson('/api/v1/ai/requests', [
            'request_type' => 'library_summary',
            'input' => ['content' => 'Summarize AI10RegressionGuide'],
        ], $this->adminHeaders())->assertCreated();
        $this->assertTrue($grounded->json('output.grounded'));
        $this->assertFalse($grounded->json('output.disclosure_applied'));
        $this->assertSame('library_items', $grounded->json('output.sources.0.source_type'));
        $this->assertArrayNotHasKey('keyword_score', $grounded->json('output.sources.0') ?? []);

        $empty = $this->postJson('/api/v1/ai/requests', [
            'request_type' => 'library_summary',
            'input' => ['content' => 'zzzzzxqai10regressionempty'],
        ], $this->adminHeaders())->assertCreated();
        $this->assertTrue($empty->json('output.disclosure_applied'));
        $this->assertSame(AiGroundedAnswerDisclosurePolicy::STATE_EMPTY_RETRIEVAL, $empty->json('output.grounding_state'));

        $usage = AiUsageRecord::withoutGlobalScopes()->where('ai_request_id', $grounded->json('id'))->first();
        $this->assertNotNull($usage);
        $this->assertSame('completed', $usage->status);
        $this->assertSame('mock', $usage->provider);
        $this->assertSame('keyword', $usage->retrieval['retrieval_strategy'] ?? null);
    }

    public function test_configuration_defaults_to_keyword_and_invalid_strategy_is_safe(): void
    {
        $this->assertSame('keyword', config('ai.retrieval.strategy'));
        $this->assertSame('keyword', app(KnowledgeRetrievalConfig::class)->strategy());

        Config::set('ai.retrieval.strategy', 'vector-magic');
        Config::set('ai.retrieval.semantic_weight', 99);
        Config::set('ai.retrieval.freshness_weight', 50);
        $config = app(KnowledgeRetrievalConfig::class);
        $this->assertSame('keyword', $config->strategy());
        $this->assertTrue($config->configuredStrategyIsInvalid());
        $this->assertSame(0.5, $config->semanticWeight());
        $this->assertSame(1.0, $config->freshnessWeight());

        $organization = Organization::first();
        $this->ingestLibrary($organization, [
            'slug' => 'ai10-invalid-strategy',
            'title' => 'AI10InvalidStrategyTitle',
            'publication_status' => 'published',
        ]);
        $result = app(KnowledgeRetrievalRouter::class)->retrieve($organization->id, 'AI10InvalidStrategyTitle');
        $this->assertSame('fallback', $result->telemetry['retrieval_status'] ?? null);
        $this->assertSame('invalid_strategy', $result->telemetry['fallback_reason'] ?? null);
        $this->assertNotEmpty($result->hits);
    }

    public function test_telemetry_persistence_failure_does_not_break_ai_response(): void
    {
        $this->app->instance(AiUsageRecorder::class, new class extends AiUsageRecorder
        {
            public function recordRetrievalTelemetry(AiRequest $request, array $telemetry): void
            {
                throw new \RuntimeException('telemetry table unavailable api_key=sk-SHOULDNOTLEAK');
            }
        });
        $this->app->forgetInstance(AiService::class);

        $created = $this->postJson('/api/v1/ai/requests', [
            'request_type' => 'library_summary',
            'input' => ['content' => 'zzzzzxqai10nomatch'],
        ], $this->adminHeaders())->assertCreated();

        $this->assertSame('completed', $created->json('status'));
        $this->assertStringNotContainsString('sk-SHOULDNOTLEAK', $created->getContent());
    }

    public function test_quality_summary_is_tenant_scoped(): void
    {
        $orgA = Organization::first();
        $orgB = Organization::create(['name' => 'AI10 Org B Quality', 'slug' => 'ai10-org-b-quality']);
        $this->ingestLibrary($orgA, [
            'slug' => 'ai10-quality-a',
            'title' => 'AI10QualityAlpha',
            'publication_status' => 'published',
        ]);
        $this->postJson('/api/v1/ai/requests', [
            'request_type' => 'library_summary',
            'input' => ['content' => 'Summarize AI10QualityAlpha'],
        ], $this->adminHeaders($orgA))->assertCreated();

        $summaryA = app(KnowledgeRetrievalQualityService::class)->summary($orgA->id);
        $summaryB = app(KnowledgeRetrievalQualityService::class)->summary($orgB->id);

        $this->assertGreaterThanOrEqual(1, $summaryA['keyword_results'] + $summaryA['success_count']);
        $this->assertSame(0, $summaryB['success_count']);
        $this->assertSame($orgA->id, $summaryA['organization_id']);
        $this->assertInstanceOf(KnowledgeRetrievalRouter::class, app(AiKnowledgeRetrieverInterface::class));
    }
}
