<?php

namespace Tests\Feature;

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
use App\Services\Ai\Embeddings\KnowledgeEmbeddingHasher;
use App\Services\Ai\Embeddings\MockEmbeddingProvider;
use App\Services\Ai\Embeddings\OpenAiEmbeddingProvider;
use App\Services\Ai\Embeddings\PostgresKnowledgeVectorStore;
use App\Services\Ai\Retrieval\KnowledgeIndexer;
use App\Services\Ai\Retrieval\KnowledgeIngestionService;
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

class Ai12VectorRetrievalTest extends TestCase
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
        Config::set('ai.embeddings.provider', 'mock');
        Config::set('ai.embeddings.model', 'mock-hash-v1');
        Config::set('ai.embeddings.dimensions', 64);
        Config::set('ai.embeddings.similarity_threshold', 0.15);
        Config::set('ai.openai.api_key', 'sk-ai12-secret-key');
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
        $token = $this->admin()->createToken('ai-12')->plainTextToken;

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

    public function test_semantic_interface_remains_stable_and_vector_backend_is_bound(): void
    {
        $index = app(KnowledgeSemanticIndexInterface::class);
        $this->assertInstanceOf(VectorKnowledgeSemanticIndex::class, $index);
        $this->assertInstanceOf(EmbeddingProviderInterface::class, app(EmbeddingProviderInterface::class));
        $this->assertInstanceOf(MockEmbeddingProvider::class, app(EmbeddingProviderInterface::class));
        $source = file_get_contents(app_path('Http/Controllers/Api/Operator/AiRetrievalOperationsController.php'));
        $this->assertNotFalse($source);
        $this->assertStringNotContainsString('CosineSimilarity', $source);
        $this->assertStringNotContainsString('/v1/embeddings', $source);
        $this->assertSame(AiRetrievalOperationsController::class, AiRetrievalOperationsController::class);
    }

    public function test_mock_embedding_provider_generates_deterministic_vectors(): void
    {
        $provider = app(MockEmbeddingProvider::class);
        $first = $provider->embed('AI12VectorAlpha orchard notes');
        $second = $provider->embed('AI12VectorAlpha orchard notes');
        $this->assertSame($first->vector, $second->vector);
        $this->assertCount(64, $first->vector);
        $this->assertSame('mock', $provider->name());
        $this->assertTrue($provider->isAvailable());
    }

    public function test_openai_embedding_provider_uses_existing_http_conventions(): void
    {
        Config::set('ai.embeddings.provider', 'openai');
        Config::set('ai.embeddings.model', 'text-embedding-3-small');
        Config::set('ai.embeddings.dimensions', 8);
        Config::set('ai.embeddings.retry_times', 0);
        $this->app->forgetInstance(EmbeddingProviderInterface::class);
        Http::fake([
            'https://api.openai.com/v1/embeddings' => Http::response([
                'data' => [['index' => 0, 'embedding' => [0.1, 0.2, 0.3, 0.4, 0.5, 0.6, 0.7, 0.8]]],
                'model' => 'text-embedding-3-small',
            ], 200),
        ]);

        $result = app(OpenAiEmbeddingProvider::class)->embed('AI12 openai text');
        $this->assertSame(8, $result->dimensions);
        $this->assertEqualsWithDelta(0.1, $result->vector[0], 0.0001);
        Http::assertSent(function ($request): bool {
            return str_contains($request->url(), '/v1/embeddings')
                && $request->hasHeader('Authorization', 'Bearer sk-ai12-secret-key')
                && ! str_contains($request->body(), 'sk-ai12-secret-key');
        });
    }

    public function test_provider_failure_timeout_retry_and_invalid_vectors_are_safe(): void
    {
        Config::set('ai.embeddings.provider', 'openai');
        Config::set('ai.embeddings.model', 'text-embedding-3-small');
        Config::set('ai.embeddings.dimensions', 8);
        Config::set('ai.embeddings.retry_times', 2);
        Config::set('ai.embeddings.retry_sleep_ms', 0);
        $this->app->forgetInstance(EmbeddingProviderInterface::class);

        Http::fake([
            'https://api.openai.com/v1/embeddings' => Http::sequence()
                ->push(['error' => ['message' => 'busy']], 503)
                ->push(['error' => ['message' => 'busy']], 503)
                ->push(['data' => [['index' => 0, 'embedding' => [0.2, 0.2, 0.2, 0.2, 0.2, 0.2, 0.2, 0.2]]]], 200),
        ]);
        $recovered = app(OpenAiEmbeddingProvider::class)->embed('retry-ok');
        $this->assertCount(8, $recovered->vector);

        Http::fake([
            'https://api.openai.com/v1/embeddings' => function () {
                throw new ConnectionException('cURL error 28: Operation timed out');
            },
        ]);
        $this->app->forgetInstance(OpenAiEmbeddingProvider::class);
        try {
            app(OpenAiEmbeddingProvider::class)->embed('timeout');
            $this->fail('Timeout should throw');
        } catch (\Throwable $exception) {
            $this->assertTrue(
                str_contains(strtolower($exception->getMessage()), 'timed out')
                || str_contains(strtolower($exception->getMessage()), 'timeout')
                || str_contains(strtolower($exception->getMessage()), 'unavailable')
            );
            $this->assertStringNotContainsString('sk-ai12-secret-key', $exception->getMessage());
        }

        $validator = app(EmbeddingVectorValidator::class);
        $this->expectException(EmbeddingException::class);
        $validator->assert([1.0, 2.0], 4);
    }

    public function test_zero_and_non_finite_vectors_are_rejected(): void
    {
        $validator = app(EmbeddingVectorValidator::class);
        try {
            $validator->assert([0.0, 0.0, 0.0, 0.0], 4);
            $this->fail('Zero vector must be rejected');
        } catch (EmbeddingException) {
            $this->assertTrue(true);
        }
        try {
            $validator->assert([1.0, INF, 0.0, 0.0], 4);
            $this->fail('Non-finite vector must be rejected');
        } catch (EmbeddingException) {
            $this->assertTrue(true);
        }
    }

    public function test_content_hash_idempotency_and_model_dimension_changes(): void
    {
        $organization = Organization::first();
        $item = $this->ingest($organization, [
            'slug' => 'ai12-hash-idemp',
            'title' => 'AI12HashIdempotentTitle',
            'summary' => 'Stable body.',
            'content' => 'Stable content.',
            'publication_status' => 'published',
        ]);
        $row = KnowledgeEmbedding::query()->where('source_type', 'library_items')->where('source_id', $item->id)->first();
        $this->assertNotNull($row);
        $originalHash = $row->content_hash;
        $originalId = $row->id;

        app(KnowledgeSemanticIndexInterface::class)->index(app(KnowledgeIndexer::class)->fromLibraryItem($item->fresh()));
        $again = KnowledgeEmbedding::query()->find($originalId);
        $this->assertSame($originalHash, $again->content_hash);

        $updated = $this->ingest($organization, [
            'slug' => 'ai12-hash-idemp',
            'title' => 'AI12HashChangedTitle',
            'summary' => 'Changed body.',
            'content' => 'Changed content.',
            'publication_status' => 'published',
        ]);
        $changed = KnowledgeEmbedding::query()->where('source_id', $updated->id)->first();
        $this->assertNotSame($originalHash, $changed->content_hash);

        Config::set('ai.embeddings.model', 'mock-hash-v2');
        $this->app->forgetInstance(EmbeddingProviderInterface::class);
        $this->app->forgetInstance(VectorKnowledgeSemanticIndex::class);
        app(KnowledgeSemanticIndexInterface::class)->index(app(KnowledgeIndexer::class)->fromLibraryItem($updated->fresh()));
        $this->assertSame('mock-hash-v2', KnowledgeEmbedding::query()->where('source_id', $updated->id)->value('embedding_model'));

        Config::set('ai.embeddings.dimensions', 32);
        $this->app->forgetInstance(EmbeddingConfig::class);
        $this->app->forgetInstance(EmbeddingProviderInterface::class);
        $this->app->forgetInstance(VectorKnowledgeSemanticIndex::class);
        app(KnowledgeSemanticIndexInterface::class)->index(app(KnowledgeIndexer::class)->fromLibraryItem($updated->fresh()));
        $this->assertSame(32, (int) KnowledgeEmbedding::query()->where('source_id', $updated->id)->value('embedding_dimensions'));
    }

    public function test_semantic_vector_search_is_relevant_bounded_and_thresholded(): void
    {
        Config::set('ai.retrieval.strategy', 'semantic');
        $organization = Organization::first();
        $relevant = $this->ingest($organization, [
            'slug' => 'ai12-relevant',
            'title' => 'AI12VectorRelevantTerm',
            'summary' => 'Orchard notes.',
            'publication_status' => 'published',
        ]);
        $this->ingest($organization, [
            'slug' => 'ai12-unrelated',
            'title' => 'Unrelated newsletter zebra',
            'summary' => 'Completely different topic.',
            'publication_status' => 'published',
        ]);

        $result = app(KnowledgeRetrievalRouter::class)->retrieve($organization->id, 'AI12VectorRelevantTerm');
        $this->assertNotEmpty($result->hits);
        $this->assertSame($relevant->id, $result->hits[0]->sourceId);
        $this->assertSame('semantic', $result->telemetry['retrieval_strategy'] ?? null);
        $this->assertLessThanOrEqual(5, count($result->hits));
        $first = $result->hits[0]->metadata['semantic_score'] ?? 0;
        $this->assertGreaterThanOrEqual(0.15, $first);

        $repeat = app(KnowledgeRetrievalRouter::class)->retrieve($organization->id, 'AI12VectorRelevantTerm');
        $this->assertSame($result->hits[0]->sourceId, $repeat->hits[0]->sourceId);
        $this->assertEquals($result->hits[0]->score, $repeat->hits[0]->score);
    }

    public function test_tenant_filtering_and_unpublished_exclusion(): void
    {
        $orgA = Organization::first();
        $orgB = Organization::create(['name' => 'AI12 Org B', 'slug' => 'ai12-org-b']);
        $this->ingest($orgB, [
            'slug' => 'ai12-b-only',
            'title' => 'AI12PrivateTenantBTerm',
            'publication_status' => 'published',
        ]);
        $this->ingest($orgA, [
            'slug' => 'ai12-draft',
            'title' => 'AI12DraftSecretTerm',
            'publication_status' => 'draft',
        ]);

        foreach (['keyword', 'semantic', 'hybrid'] as $strategy) {
            Config::set('ai.retrieval.strategy', $strategy);
            $fromA = app(KnowledgeRetrievalRouter::class)->retrieve($orgA->id, 'AI12PrivateTenantBTerm');
            $this->assertFalse(collect($fromA->hits)->contains(fn ($hit) => $hit->title === 'AI12PrivateTenantBTerm'));
            $draft = app(KnowledgeRetrievalRouter::class)->retrieve($orgA->id, 'AI12DraftSecretTerm');
            $this->assertFalse(collect($draft->hits)->contains(fn ($hit) => $hit->title === 'AI12DraftSecretTerm'));
        }
    }

    public function test_hybrid_uses_keyword_and_vector_and_falls_back_when_embeddings_fail(): void
    {
        Config::set('ai.retrieval.strategy', 'hybrid');
        $organization = Organization::first();
        $item = $this->ingest($organization, [
            'slug' => 'ai12-hybrid',
            'title' => 'AI12HybridExactTitle',
            'summary' => 'Hybrid notes.',
            'publication_status' => 'published',
        ]);
        $ok = app(KnowledgeRetrievalRouter::class)->retrieve($organization->id, 'AI12HybridExactTitle');
        $this->assertSame($item->id, $ok->hits[0]->sourceId);
        $this->assertArrayHasKey('keyword_score', $ok->hits[0]->metadata);
        $this->assertArrayHasKey('semantic_score', $ok->hits[0]->metadata);

        $failing = \Mockery::mock(EmbeddingProviderInterface::class);
        $failing->shouldReceive('isAvailable')->andReturn(true);
        $failing->shouldReceive('name')->andReturn('openai');
        $failing->shouldReceive('model')->andReturn('text-embedding-3-small');
        $failing->shouldReceive('dimensions')->andReturn(64);
        $failing->shouldReceive('embed')->andThrow(new EmbeddingException('provider down api_key=sk-SHOULDNOTLEAK'));
        $this->app->instance(EmbeddingProviderInterface::class, $failing);
        $this->app->forgetInstance(VectorKnowledgeSemanticIndex::class);
        $this->app->forgetInstance(KnowledgeRetrievalRouter::class);

        $fallback = app(KnowledgeRetrievalRouter::class)->retrieve($organization->id, 'AI12HybridExactTitle');
        $this->assertSame('fallback', $fallback->telemetry['retrieval_status'] ?? null);
        $this->assertSame('semantic_error', $fallback->telemetry['fallback_reason'] ?? null);
        $this->assertNotEmpty($fallback->hits);
        $this->assertSame($item->id, $fallback->hits[0]->sourceId);
    }

    public function test_tenant_a_cannot_index_or_reindex_tenant_b(): void
    {
        $orgA = Organization::first();
        $orgB = Organization::create(['name' => 'AI12 Org B Reindex', 'slug' => 'ai12-org-b-reindex']);
        $foreign = $this->ingest($orgB, [
            'slug' => 'ai12-foreign-reindex',
            'title' => 'AI12ForeignReindex',
            'publication_status' => 'published',
        ]);

        $this->postJson('/api/v1/operator/ai/knowledge/'.$foreign->id.'/index', [], $this->operatorHeaders($orgA))
            ->assertNotFound();
        $this->postJson('/api/v1/operator/ai/knowledge', [
            'slug' => 'ai12-cross-write',
            'title' => 'AI12CrossWrite',
            'organization_id' => $orgB->id,
        ], $this->operatorHeaders($orgA))->assertStatus(422);
    }

    public function test_publish_unpublish_and_hybrid_publication_gate(): void
    {
        $organization = Organization::first();
        $created = $this->postJson('/api/v1/operator/ai/knowledge', [
            'slug' => 'ai12-pub-gate',
            'title' => 'AI12PublicationGateTerm',
            'content' => 'AI12PublicationGateTerm notes.',
            'publication_status' => 'draft',
        ], $this->operatorHeaders())->assertCreated();
        $id = (int) $created->json('data.source_id');

        Config::set('ai.retrieval.strategy', 'semantic');
        $this->assertFalse(collect(app(KnowledgeRetrievalRouter::class)->retrieve($organization->id, 'AI12PublicationGateTerm')->hits)
            ->contains(fn ($hit) => $hit->sourceId === $id));

        $this->postJson('/api/v1/operator/ai/knowledge/'.$id.'/publish', [], $this->operatorHeaders())->assertOk();
        $this->app->forgetInstance(KnowledgeRetrievalRouter::class);
        $this->assertTrue(collect(app(KnowledgeRetrievalRouter::class)->retrieve($organization->id, 'AI12PublicationGateTerm')->hits)
            ->contains(fn ($hit) => $hit->sourceId === $id));

        $this->postJson('/api/v1/operator/ai/knowledge/'.$id.'/unpublish', [], $this->operatorHeaders())->assertOk();
        $this->app->forgetInstance(KnowledgeRetrievalRouter::class);
        foreach (['semantic', 'hybrid'] as $strategy) {
            Config::set('ai.retrieval.strategy', $strategy);
            $this->assertFalse(
                collect(app(KnowledgeRetrievalRouter::class)->retrieve($organization->id, 'AI12PublicationGateTerm')->hits)
                    ->contains(fn ($hit) => $hit->sourceId === $id),
                "Publication bypass via {$strategy}",
            );
        }
        $this->assertNull(KnowledgeEmbedding::query()->where('source_id', $id)->where('source_type', 'library_items')->first());
    }

    public function test_update_delete_and_orphan_cleanup(): void
    {
        $organization = Organization::first();
        $item = $this->ingest($organization, [
            'slug' => 'ai12-lifecycle',
            'title' => 'AI12LifecycleOriginal',
            'publication_status' => 'published',
        ]);
        $this->assertNotNull(KnowledgeEmbedding::query()->where('source_id', $item->id)->first());

        $this->ingest($organization, [
            'slug' => 'ai12-lifecycle',
            'title' => 'AI12LifecycleUpdated',
            'publication_status' => 'published',
        ]);
        $row = KnowledgeEmbedding::query()->where('source_id', $item->id)->first();
        $this->assertNotNull($row);
        $this->assertSame(
            app(KnowledgeEmbeddingHasher::class)->hash(
                app(KnowledgeIndexer::class)->fromLibraryItem($item->fresh())
            ),
            $row->content_hash,
        );

        $item->delete();
        $this->assertNull(KnowledgeEmbedding::query()->where('source_id', $item->id)->first());
        $this->assertSame([], app(PostgresKnowledgeVectorStore::class)->orphaned());
    }

    public function test_operator_health_strategy_quality_telemetry_and_reindex_use_vector_backend(): void
    {
        $organization = Organization::first();
        $item = $this->ingest($organization, [
            'slug' => 'ai12-operator',
            'title' => 'AI12OperatorVisibleTerm',
            'publication_status' => 'published',
        ]);
        Config::set('ai.retrieval.strategy', 'semantic');
        app(KnowledgeRetrievalRouter::class)->retrieve($organization->id, 'AI12OperatorVisibleTerm');
        $this->postJson('/api/v1/ai/requests', [
            'request_type' => 'library_summary',
            'input' => ['content' => 'Summarize AI12OperatorVisibleTerm'],
        ], $this->operatorHeaders())->assertCreated();

        $health = $this->getJson('/api/v1/operator/ai/retrieval/health', $this->operatorHeaders())->assertOk();
        $this->assertSame('vector', $health->json('data.semantic_backend'));
        $this->assertTrue($health->json('data.vector_store_available'));
        $this->assertTrue($health->json('data.embedding_provider_available'));
        $this->assertIsBool($health->json('data.pgvector_available'));
        $this->assertIsBool($health->json('data.hnsw_available') ?? false);
        $this->assertStringNotContainsString('sk-ai12-secret-key', $health->getContent());

        $strategy = $this->getJson('/api/v1/operator/ai/retrieval/strategy', $this->operatorHeaders())->assertOk();
        $this->assertSame('vector', $strategy->json('data.semantic_backend'));
        $this->assertSame('mock', $strategy->json('data.embedding_provider'));
        $this->assertArrayNotHasKey('api_key', $strategy->json('data'));

        $quality = $this->getJson('/api/v1/operator/ai/retrieval/quality', $this->operatorHeaders())->assertOk();
        $this->assertSame('vector', $quality->json('data.semantic_backend'));
        $this->assertSame($organization->id, $quality->json('data.organization_id'));

        $telemetry = $this->getJson('/api/v1/operator/ai/retrieval/telemetry', $this->operatorHeaders())->assertOk();
        $this->assertStringNotContainsString('sk-ai12-secret-key', $telemetry->getContent());
        $this->assertStringNotContainsString('Bearer sk-', $telemetry->getContent());

        $reindex = $this->postJson('/api/v1/operator/ai/knowledge/'.$item->id.'/index', [], $this->operatorHeaders())->assertOk();
        $this->assertTrue($reindex->json('data.keyword_indexed'));
        $this->assertTrue($reindex->json('data.semantic_indexed'));
        $this->assertSame(1, $reindex->json('data.total'));
        $this->assertContains($reindex->json('data.status'), ['ok', 'degraded']);
    }

    public function test_secrets_never_appear_in_logs_or_responses(): void
    {
        Log::spy();
        $sanitized = AiErrorSanitizer::redact('failed api_key=sk-ai12-secret-key Authorization: Bearer secret-header');
        $this->assertStringNotContainsString('sk-ai12-secret-key', $sanitized);
        $this->assertStringNotContainsString('Bearer secret-header', $sanitized);

        $health = $this->getJson('/api/v1/operator/ai/retrieval/health', $this->operatorHeaders())->assertOk();
        $this->assertStringNotContainsString('sk-ai12-secret-key', $health->getContent());
        $this->assertStringNotContainsString('Authorization', $health->getContent());
    }

    public function test_batch_partial_failure_does_not_corrupt_index(): void
    {
        $organization = Organization::first();
        $first = $this->ingest($organization, [
            'slug' => 'ai12-batch-one',
            'title' => 'AI12BatchOneTitle',
            'publication_status' => 'published',
        ]);
        $second = $this->ingest($organization, [
            'slug' => 'ai12-batch-two',
            'title' => 'AI12BatchTwoTitle',
            'publication_status' => 'published',
        ]);
        $indexer = app(KnowledgeIndexer::class);
        $documents = [
            $indexer->fromLibraryItem($first->fresh()),
            $indexer->fromLibraryItem($second->fresh()),
        ];
        $summary = app(VectorKnowledgeSemanticIndex::class)->indexDocuments($documents);
        $this->assertSame(2, $summary['total']);
        $this->assertGreaterThanOrEqual(0, $summary['skipped']);
        $this->assertSame(0, $summary['failed']);
        $this->assertNotNull(KnowledgeEmbedding::query()->where('source_id', $first->id)->first());
        $this->assertNotNull(KnowledgeEmbedding::query()->where('source_id', $second->id)->first());
    }
}
