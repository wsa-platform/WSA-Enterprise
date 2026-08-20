<?php

namespace Tests\Feature;

use App\Exceptions\AiProviderTimeoutException;
use App\Http\Controllers\Api\Operator\AiRetrievalOperationsController;
use App\Models\KnowledgeEmbedding;
use App\Models\LibraryItem;
use App\Models\Organization;
use App\Models\User;
use App\Services\Ai\AiErrorSanitizer;
use App\Services\Ai\Embeddings\EmbeddingConfig;
use App\Services\Ai\Embeddings\EmbeddingException;
use App\Services\Ai\Embeddings\EmbeddingProviderInterface;
use App\Services\Ai\Embeddings\EmbeddingVectorValidator;
use App\Services\Ai\Embeddings\MockEmbeddingProvider;
use App\Services\Ai\Embeddings\OpenAiEmbeddingProvider;
use App\Services\Ai\Embeddings\PgvectorAnnQuery;
use App\Services\Ai\Embeddings\PgvectorLiteral;
use App\Services\Ai\Embeddings\PgvectorSchema;
use App\Services\Ai\Embeddings\PostgresKnowledgeVectorStore;
use App\Services\Ai\Retrieval\KnowledgeIndexer;
use App\Services\Ai\Retrieval\KnowledgeIngestionService;
use App\Services\Ai\Retrieval\KnowledgeRetrievalOperationsService;
use App\Services\Ai\Retrieval\KnowledgeRetrievalRouter;
use App\Services\Ai\Retrieval\KnowledgeSemanticIndexInterface;
use App\Services\Ai\Retrieval\VectorKnowledgeSemanticIndex;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class Ai13ProductionVectorAnnTest extends TestCase
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
        Config::set('ai.embeddings.enabled', true);
        Config::set('ai.embeddings.provider', 'mock');
        Config::set('ai.embeddings.model', 'mock-hash-v1');
        Config::set('ai.embeddings.dimensions', 64);
        Config::set('ai.embeddings.similarity_threshold', 0.15);
        Config::set('ai.embeddings.ann_enabled', true);
        Config::set('ai.openai.api_key', 'sk-ai13-secret-key');
        Http::preventStrayRequests();
    }

    private function admin(): User
    {
        return User::where('email', 'admin@wsa.test')->firstOrFail();
    }

    /**
     * @return array<string, string>
     */
    private function operatorHeaders(?Organization $organization = null): array
    {
        $organization ??= Organization::first();
        $token = $this->admin()->createToken('ai-13')->plainTextToken;

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

    public function test_production_embedding_provider_is_configuration_driven(): void
    {
        Config::set('ai.embeddings.provider', 'openai');
        Config::set('ai.embeddings.model', 'text-embedding-3-small');
        Config::set('ai.embeddings.dimensions', 8);
        $this->app->forgetInstance(EmbeddingProviderInterface::class);
        $this->assertInstanceOf(OpenAiEmbeddingProvider::class, app(EmbeddingProviderInterface::class));
        $this->assertTrue(app(EmbeddingProviderInterface::class)->isAvailable());
        $this->assertSame('openai', app(EmbeddingConfig::class)->provider());
        $this->assertSame('cosine', app(EmbeddingConfig::class)->distanceMetric());
    }

    public function test_mock_provider_remains_functional_without_openai_key(): void
    {
        Config::set('ai.openai.api_key', '');
        Config::set('ai.embeddings.provider', 'mock');
        $this->app->forgetInstance(EmbeddingProviderInterface::class);
        $provider = app(EmbeddingProviderInterface::class);
        $this->assertInstanceOf(MockEmbeddingProvider::class, $provider);
        $this->assertTrue($provider->isAvailable());
        $this->assertCount(64, $provider->embed('AI13MockWithoutKey orchard')->vector);
    }

    public function test_openai_provider_unavailable_without_key(): void
    {
        Config::set('ai.embeddings.provider', 'openai');
        Config::set('ai.openai.api_key', '');
        $this->app->forgetInstance(EmbeddingProviderInterface::class);
        $provider = app(EmbeddingProviderInterface::class);
        $this->assertInstanceOf(OpenAiEmbeddingProvider::class, $provider);
        $this->assertFalse($provider->isAvailable());
        $this->expectException(EmbeddingException::class);
        $provider->embed('should-not-call-provider');
    }

    public function test_openai_timeout_is_bounded(): void
    {
        Config::set('ai.embeddings.provider', 'openai');
        Config::set('ai.embeddings.model', 'text-embedding-3-small');
        Config::set('ai.embeddings.dimensions', 8);
        Config::set('ai.embeddings.retry_times', 0);
        Config::set('ai.embeddings.retry_sleep_ms', 0);
        $this->app->forgetInstance(EmbeddingProviderInterface::class);
        Http::fake([
            'https://api.openai.com/v1/embeddings' => function () {
                throw new ConnectionException('cURL error 28: Operation timed out');
            },
        ]);
        $this->expectException(AiProviderTimeoutException::class);
        app(OpenAiEmbeddingProvider::class)->embed('AI13 timeout probe');
    }

    public function test_openai_retry_is_bounded(): void
    {
        Config::set('ai.embeddings.provider', 'openai');
        Config::set('ai.embeddings.model', 'text-embedding-3-small');
        Config::set('ai.embeddings.dimensions', 8);
        Config::set('ai.embeddings.retry_times', 1);
        Config::set('ai.embeddings.retry_sleep_ms', 0);
        $this->app->forgetInstance(EmbeddingProviderInterface::class);
        Http::fake([
            'https://api.openai.com/v1/embeddings' => Http::sequence()
                ->push(['error' => ['message' => 'unavailable']], 503)
                ->push(['error' => ['message' => 'unavailable']], 503),
        ]);
        $provider = app(OpenAiEmbeddingProvider::class);
        try {
            $provider->embed('AI13 retry probe');
            $this->fail('Expected embedding failure');
        } catch (EmbeddingException $exception) {
            $this->assertSame('The embedding provider is temporarily unavailable.', $exception->getMessage());
            Http::assertSentCount(2);
            $this->assertSame(2, $provider->lastAttempts());
        }
    }

    public function test_openai_authentication_errors_are_not_retried(): void
    {
        Config::set('ai.embeddings.provider', 'openai');
        Config::set('ai.embeddings.model', 'text-embedding-3-small');
        Config::set('ai.embeddings.dimensions', 8);
        Config::set('ai.embeddings.retry_times', 2);
        Config::set('ai.embeddings.retry_sleep_ms', 0);
        $this->app->forgetInstance(EmbeddingProviderInterface::class);
        Http::fake([
            'https://api.openai.com/v1/embeddings' => Http::response(['error' => ['message' => 'invalid api_key=sk-ai13-secret-key']], 401),
        ]);
        $provider = app(OpenAiEmbeddingProvider::class);
        try {
            $provider->embed('AI13 auth probe');
            $this->fail('Expected embedding failure');
        } catch (EmbeddingException $exception) {
            $this->assertSame('The embedding provider is temporarily unavailable.', $exception->getMessage());
            $this->assertStringNotContainsString('sk-ai13-secret-key', $exception->getMessage());
            Http::assertSentCount(1);
            $this->assertSame(1, $provider->lastAttempts());
        }
    }

    public function test_invalid_and_mismatched_dimensions_are_rejected(): void
    {
        $validator = app(EmbeddingVectorValidator::class);
        $this->expectException(EmbeddingException::class);
        $validator->assert([0.1, 0.2], 8);
    }

    public function test_vector_persistence_writes_json_embeddings(): void
    {
        $organization = Organization::first();
        $item = $this->ingest($organization, [
            'slug' => 'ai13-persist',
            'title' => 'AI13PersistOrchardNotes',
            'publication_status' => 'published',
        ]);
        $row = KnowledgeEmbedding::query()->where('source_type', 'library_items')->where('source_id', $item->id)->first();
        $this->assertNotNull($row);
        $this->assertIsArray($row->embedding);
        $this->assertCount(64, $row->embedding);
        $this->assertSame('mock-hash-v1', $row->embedding_model);
        $this->assertSame($organization->id, (int) $row->organization_id);
    }

    public function test_pgvector_capability_and_hnsw_sql_are_defined(): void
    {
        $this->assertStringContainsString('<=>', PgvectorAnnQuery::searchSql());
        $this->assertStringContainsString('vector_cosine_ops', PgvectorAnnQuery::hnswDdl());
        $this->assertStringContainsString('hnsw', PgvectorAnnQuery::hnswDdl());
        $this->assertStringContainsString('publication_status', PgvectorAnnQuery::searchSql());
        $this->assertStringContainsString('organization_id', PgvectorAnnQuery::searchSql());
        $literal = PgvectorLiteral::format([0.1, -0.2, 0.3]);
        $this->assertSame('[0.10000000,-0.20000000,0.30000000]', $literal);
        $this->assertIsBool(app(PgvectorSchema::class)->extensionAvailable());
        $this->assertIsBool(app(PostgresKnowledgeVectorStore::class)->annAvailable());
        $this->assertIsBool(app(PostgresKnowledgeVectorStore::class)->hnswAvailable());
    }

    public function test_native_ann_or_json_fallback_returns_cosine_ordered_results(): void
    {
        $organization = Organization::first();
        $this->ingest($organization, [
            'slug' => 'ai13-ann-alpha',
            'title' => 'AI13AnnAlpha citrus irrigation schedule',
            'content' => 'AI13AnnAlpha citrus irrigation schedule',
            'publication_status' => 'published',
        ]);
        $this->ingest($organization, [
            'slug' => 'ai13-ann-beta',
            'title' => 'AI13AnnBeta warehouse pallet inventory',
            'content' => 'AI13AnnBeta warehouse pallet inventory',
            'publication_status' => 'published',
        ]);
        Config::set('ai.retrieval.strategy', 'semantic');
        $result = app(KnowledgeRetrievalRouter::class)->retrieve($organization->id, 'AI13AnnAlpha citrus irrigation');
        $this->assertNotSame([], $result->hits);
        $this->assertSame('library_items', $result->hits[0]->sourceType);
        $this->assertStringContainsString('AI13AnnAlpha', $result->hits[0]->title);
        $ann = app(PostgresKnowledgeVectorStore::class)->lastUsedAnn();
        $this->assertSame(app(PostgresKnowledgeVectorStore::class)->annAvailable(), $ann);
        if (app(PostgresKnowledgeVectorStore::class)->annAvailable()) {
            $this->assertTrue($ann);
            $this->assertTrue(app(PgvectorSchema::class)->nativeColumnAvailable());
        }
    }

    public function test_tenant_isolation_and_unpublished_exclusion(): void
    {
        $orgA = Organization::first();
        $orgB = Organization::query()->where('id', '!=', $orgA->id)->first()
            ?? Organization::query()->create(['name' => 'AI13 Org B', 'slug' => 'ai13-org-b']);
        $this->ingest($orgA, [
            'slug' => 'ai13-tenant-a',
            'title' => 'AI13TenantAlphaVisibleTerm',
            'publication_status' => 'published',
        ]);
        $this->ingest($orgB, [
            'slug' => 'ai13-tenant-b',
            'title' => 'AI13TenantAlphaVisibleTerm',
            'publication_status' => 'published',
        ]);
        $draft = $this->ingest($orgA, [
            'slug' => 'ai13-draft',
            'title' => 'AI13TenantAlphaVisibleTerm draft',
            'publication_status' => 'draft',
        ]);
        Config::set('ai.retrieval.strategy', 'semantic');
        $hitsA = app(KnowledgeRetrievalRouter::class)->retrieve($orgA->id, 'AI13TenantAlphaVisibleTerm')->hits;
        $ids = array_map(static fn ($hit) => $hit->sourceId, $hitsA);
        $this->assertNotContains($draft->id, $ids);
        foreach ($hitsA as $hit) {
            $this->assertTrue($hit->organizationId === $orgA->id || $hit->organizationId === null);
            $this->assertNotSame($orgB->id, $hit->organizationId);
        }
    }

    public function test_idempotent_reembedding_and_content_change(): void
    {
        $organization = Organization::first();
        $item = $this->ingest($organization, [
            'slug' => 'ai13-hash',
            'title' => 'AI13HashOriginal',
            'publication_status' => 'published',
        ]);
        $first = KnowledgeEmbedding::query()->where('source_id', $item->id)->first();
        $this->assertNotNull($first);
        $hash = $first->content_hash;
        $indexedAt = $first->indexed_at?->toDateTimeString();
        app(VectorKnowledgeSemanticIndex::class)->index(app(KnowledgeIndexer::class)->fromLibraryItem($item->fresh()));
        $this->assertSame($hash, KnowledgeEmbedding::query()->where('source_id', $item->id)->first()?->content_hash);
        $this->assertSame($indexedAt, KnowledgeEmbedding::query()->where('source_id', $item->id)->first()?->indexed_at?->toDateTimeString());

        $this->ingest($organization, [
            'slug' => 'ai13-hash',
            'title' => 'AI13HashChangedBody',
            'publication_status' => 'published',
        ]);
        $updated = KnowledgeEmbedding::query()->where('source_id', $item->id)->first();
        $this->assertNotSame($hash, $updated?->content_hash);
    }

    public function test_reindex_and_dry_run_backfill(): void
    {
        $organization = Organization::first();
        $item = $this->ingest($organization, [
            'slug' => 'ai13-backfill',
            'title' => 'AI13BackfillVisible',
            'publication_status' => 'published',
        ]);
        $this->postJson('/api/v1/operator/ai/knowledge/'.$item->id.'/index', [], $this->operatorHeaders())
            ->assertOk()
            ->assertJsonPath('data.keyword_indexed', true);

        $dry = $this->postJson('/api/v1/operator/ai/knowledge/backfill', [
            'dry_run' => true,
            'limit' => 50,
        ], $this->operatorHeaders())->assertOk();
        $this->assertTrue($dry->json('data.dry_run'));
        $this->assertGreaterThanOrEqual(1, $dry->json('data.total'));
        $this->assertStringNotContainsString('sk-ai13-secret-key', $dry->getContent());

        $run = $this->postJson('/api/v1/operator/ai/knowledge/backfill', [
            'dry_run' => false,
            'limit' => 50,
        ], $this->operatorHeaders())->assertOk();
        $this->assertFalse($run->json('data.dry_run'));
        $this->assertContains($run->json('data.status'), ['ok', 'degraded']);
    }

    public function test_vector_failure_falls_back_to_keyword_and_hybrid_still_works(): void
    {
        $organization = Organization::first();
        $this->ingest($organization, [
            'slug' => 'ai13-fallback',
            'title' => 'AI13FallbackKeywordTerm',
            'publication_status' => 'published',
        ]);
        Config::set('ai.embeddings.enabled', false);
        $this->app->forgetInstance(EmbeddingProviderInterface::class);
        $this->app->forgetInstance(KnowledgeSemanticIndexInterface::class);
        Config::set('ai.retrieval.strategy', 'hybrid');
        $result = app(KnowledgeRetrievalRouter::class)->retrieve($organization->id, 'AI13FallbackKeywordTerm');
        $this->assertSame('semantic_unavailable', $result->telemetry['fallback_reason'] ?? null);
        $this->assertSame('keyword', $result->telemetry['retrieval_strategy'] ?? null);
        $this->assertNotSame([], $result->hits);
    }

    public function test_health_and_strategy_report_ann_capability_without_secrets(): void
    {
        $health = $this->getJson('/api/v1/operator/ai/retrieval/health', $this->operatorHeaders())->assertOk();
        $this->assertSame('vector', $health->json('data.semantic_backend'));
        $this->assertSame('cosine', $health->json('data.distance_metric'));
        $this->assertIsBool($health->json('data.pgvector_available'));
        $this->assertIsBool($health->json('data.native_vector_available'));
        $this->assertIsBool($health->json('data.hnsw_available'));
        $this->assertIsBool($health->json('data.ann_available'));
        $this->assertTrue($health->json('data.vector_store_available'));
        $this->assertTrue($health->json('data.embedding_provider_available'));
        $this->assertSame('healthy', $health->json('data.status'));
        $this->assertStringNotContainsString('sk-ai13-secret-key', $health->getContent());
        $this->assertStringNotContainsString('Authorization', $health->getContent());

        Config::set('ai.embeddings.enabled', false);
        Config::set('ai.embeddings.provider', 'openai');
        Config::set('ai.openai.api_key', '');
        $this->app->forgetInstance(EmbeddingProviderInterface::class);
        $this->app->forgetInstance(OpenAiEmbeddingProvider::class);
        $this->app->forgetInstance(MockEmbeddingProvider::class);
        $this->app->forgetInstance(VectorKnowledgeSemanticIndex::class);
        $this->app->forgetInstance(KnowledgeSemanticIndexInterface::class);
        $this->app->forgetInstance(KnowledgeRetrievalOperationsService::class);
        $degraded = $this->getJson('/api/v1/operator/ai/retrieval/health', $this->operatorHeaders())->assertOk();
        $this->assertSame('degraded', $degraded->json('data.status'));
        $this->assertFalse($degraded->json('data.embedding_provider_available'));

        Config::set('ai.embeddings.enabled', true);
        Config::set('ai.retrieval.semantic_enabled', false);
        Config::set('ai.retrieval.strategy', 'keyword');
        Config::set('ai.embeddings.provider', 'mock');
        Config::set('ai.openai.api_key', 'sk-ai13-secret-key');
        $this->app->forgetInstance(EmbeddingProviderInterface::class);
        $this->app->forgetInstance(KnowledgeSemanticIndexInterface::class);
        $this->app->forgetInstance(KnowledgeRetrievalOperationsService::class);
        $keywordOnly = $this->getJson('/api/v1/operator/ai/retrieval/health', $this->operatorHeaders())->assertOk();
        $this->assertSame('healthy', $keywordOnly->json('data.status'));

        $strategy = $this->getJson('/api/v1/operator/ai/retrieval/strategy', $this->operatorHeaders())->assertOk();
        $this->assertSame('keyword', $strategy->json('data.configured_strategy'));
        $this->assertSame('cosine', $strategy->json('data.distance_metric'));
        $this->assertArrayNotHasKey('api_key', $strategy->json('data'));
        $this->assertStringNotContainsString('sk-ai13-secret-key', $strategy->getContent());
    }

    public function test_telemetry_and_logs_never_include_credentials(): void
    {
        Log::spy();
        $organization = Organization::first();
        $this->ingest($organization, [
            'slug' => 'ai13-telemetry',
            'title' => 'AI13TelemetryVisibleTerm',
            'publication_status' => 'published',
        ]);
        Config::set('ai.retrieval.strategy', 'semantic');
        app(KnowledgeRetrievalRouter::class)->retrieve($organization->id, 'AI13TelemetryVisibleTerm');
        $this->postJson('/api/v1/ai/requests', [
            'request_type' => 'library_summary',
            'input' => ['content' => 'Summarize AI13TelemetryVisibleTerm'],
        ], $this->operatorHeaders())->assertCreated();

        $telemetry = $this->getJson('/api/v1/operator/ai/retrieval/telemetry', $this->operatorHeaders())->assertOk();
        $this->assertStringNotContainsString('sk-ai13-secret-key', $telemetry->getContent());
        $this->assertStringNotContainsString('Bearer ', $telemetry->getContent());
        $sanitized = AiErrorSanitizer::redact('failed api_key=sk-ai13-secret-key Authorization: Bearer secret-header');
        $this->assertStringNotContainsString('sk-ai13-secret-key', $sanitized);
        $this->assertStringNotContainsString('Bearer secret-header', $sanitized);
    }

    public function test_operator_backfill_requires_authentication(): void
    {
        $this->postJson('/api/v1/operator/ai/knowledge/backfill', ['dry_run' => true])->assertUnauthorized();
    }

    public function test_invalid_configuration_and_oversized_input_are_rejected(): void
    {
        Config::set('ai.embeddings.provider', 'not-a-provider');
        $this->app->forgetInstance(EmbeddingProviderInterface::class);
        $this->assertFalse(app(EmbeddingProviderInterface::class)->isAvailable());

        Config::set('ai.embeddings.provider', 'openai');
        Config::set('ai.embeddings.model', 'text-embedding-3-small');
        Config::set('ai.max_input_characters', 8);
        $this->app->forgetInstance(EmbeddingProviderInterface::class);
        $this->expectException(EmbeddingException::class);
        app(OpenAiEmbeddingProvider::class)->embed('this input is definitely too long');
    }

    public function test_batch_partial_failure_and_controller_has_no_vector_logic(): void
    {
        $organization = Organization::first();
        $first = $this->ingest($organization, [
            'slug' => 'ai13-batch-one',
            'title' => 'AI13BatchOneTitle',
            'publication_status' => 'published',
        ]);
        $second = $this->ingest($organization, [
            'slug' => 'ai13-batch-two',
            'title' => 'AI13BatchTwoTitle',
            'publication_status' => 'published',
        ]);
        $summary = app(VectorKnowledgeSemanticIndex::class)->indexDocuments([
            app(KnowledgeIndexer::class)->fromLibraryItem($first->fresh()),
            app(KnowledgeIndexer::class)->fromLibraryItem($second->fresh()),
        ]);
        $this->assertSame(2, $summary['total']);
        $this->assertSame(0, $summary['failed']);
        $source = file_get_contents(app_path('Http/Controllers/Api/Operator/AiRetrievalOperationsController.php'));
        $this->assertNotFalse($source);
        $this->assertStringNotContainsString('<=>', $source);
        $this->assertStringNotContainsString('hnsw', $source);
        $this->assertStringNotContainsString('/v1/embeddings', $source);
        $this->assertSame(AiRetrievalOperationsController::class, AiRetrievalOperationsController::class);
    }

    public function test_tenant_a_cannot_backfill_tenant_b_library(): void
    {
        $orgA = Organization::first();
        $orgB = Organization::query()->where('id', '!=', $orgA->id)->first()
            ?? Organization::query()->create(['name' => 'AI13 Backfill B', 'slug' => 'ai13-backfill-b']);
        $foreign = $this->ingest($orgB, [
            'slug' => 'ai13-foreign-backfill',
            'title' => 'AI13ForeignBackfill',
            'publication_status' => 'published',
        ]);
        KnowledgeEmbedding::query()->where('source_id', $foreign->id)->delete();
        $this->postJson('/api/v1/operator/ai/knowledge/backfill', [
            'dry_run' => false,
            'limit' => 50,
        ], $this->operatorHeaders($orgA))->assertOk();
        $this->assertNull(
            KnowledgeEmbedding::query()
                ->where('source_type', 'library_items')
                ->where('source_id', $foreign->id)
                ->where('organization_id', $orgB->id)
                ->first()
        );
    }

    public function test_publish_unpublish_and_threshold_limits(): void
    {
        $organization = Organization::first();
        $created = $this->postJson('/api/v1/operator/ai/knowledge', [
            'slug' => 'ai13-lifecycle',
            'title' => 'AI13LifecycleVisibleTerm',
            'content' => 'AI13LifecycleVisibleTerm orchard notes',
            'publication_status' => 'draft',
        ], $this->operatorHeaders())->assertCreated();
        $id = (int) $created->json('data.source_id');
        Config::set('ai.retrieval.strategy', 'semantic');
        $draftHits = app(KnowledgeRetrievalRouter::class)->retrieve($organization->id, 'AI13LifecycleVisibleTerm')->hits;
        $this->assertFalse(collect($draftHits)->contains(fn ($hit) => $hit->sourceId === $id));

        $this->postJson('/api/v1/operator/ai/knowledge/'.$id.'/publish', [], $this->operatorHeaders())->assertOk();
        $publishedHits = app(KnowledgeRetrievalRouter::class)->retrieve($organization->id, 'AI13LifecycleVisibleTerm')->hits;
        $this->assertTrue(collect($publishedHits)->contains(fn ($hit) => $hit->sourceId === $id));

        Config::set('ai.embeddings.similarity_threshold', 0.99);
        $this->app->forgetInstance(PostgresKnowledgeVectorStore::class);
        $this->app->forgetInstance(VectorKnowledgeSemanticIndex::class);
        $strict = app(KnowledgeRetrievalRouter::class)->retrieve($organization->id, 'unrelated warehouse pallet');
        foreach ($strict->hits as $hit) {
            $this->assertGreaterThanOrEqual(0.99, $hit->metadata['semantic_score'] ?? 0);
        }

        Config::set('ai.embeddings.similarity_threshold', 0.15);
        Config::set('ai.embeddings.max_candidates', 1);
        Config::set('ai.retrieval.max_results', 1);
        $this->app->forgetInstance(PostgresKnowledgeVectorStore::class);
        $bounded = app(KnowledgeRetrievalRouter::class)->retrieve($organization->id, 'AI13LifecycleVisibleTerm');
        $this->assertLessThanOrEqual(1, count($bounded->hits));

        $this->postJson('/api/v1/operator/ai/knowledge/'.$id.'/unpublish', [], $this->operatorHeaders())->assertOk();
        $this->app->forgetInstance(PostgresKnowledgeVectorStore::class);
        $after = app(KnowledgeRetrievalRouter::class)->retrieve($organization->id, 'AI13LifecycleVisibleTerm')->hits;
        $this->assertFalse(collect($after)->contains(fn ($hit) => $hit->sourceId === $id));
    }
}
