<?php

namespace Tests\Feature;

use App\Models\AiUsageRecord;
use App\Models\AuditLog;
use App\Models\LibraryItem;
use App\Models\Organization;
use App\Models\User;
use App\Services\Ai\Retrieval\KnowledgeIndexer;
use App\Services\Ai\Retrieval\KnowledgeIngestionService;
use App\Services\Ai\Retrieval\KnowledgeRetrievalHealthService;
use App\Services\Ai\Retrieval\KnowledgeRetrievalOperationsService;
use App\Services\Ai\Retrieval\KnowledgeRetrievalQualityService;
use App\Services\Ai\Retrieval\KnowledgeRetrievalRouter;
use App\Services\Ai\Retrieval\KnowledgeSemanticIndexInterface;
use App\Services\Ai\Retrieval\KnowledgeTextNormalizer;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class Ai11OperatorRetrievalApiTest extends TestCase
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
        Config::set('ai.openai.api_key', 'sk-ai11-operator-secret');
        Http::preventStrayRequests();
    }

    private function admin(): User
    {
        return User::where('email', 'admin@wsa.test')->firstOrFail();
    }

    private function member(): User
    {
        return User::where('email', 'member@wsa.test')->firstOrFail();
    }

    /**
     * @return array<string, string>
     */
    private function operatorHeaders(?Organization $organization = null): array
    {
        $organization ??= Organization::first();
        $token = $this->admin()->createToken('ai-11')->plainTextToken;

        return [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function memberHeaders(?Organization $organization = null): array
    {
        $organization ??= Organization::first();
        $token = $this->member()->createToken('ai-11-member')->plainTextToken;

        return [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
        ];
    }

    /**
     * @param  array<string, mixed>  $retrieval
     */
    private function recordUsage(Organization $organization, array $retrieval): AiUsageRecord
    {
        return AiUsageRecord::withoutGlobalScopes()->create([
            'organization_id' => $organization->id,
            'user_id' => $this->admin()->id,
            'provider' => 'mock',
            'model' => 'mock',
            'status' => 'completed',
            'retrieval' => $retrieval,
        ]);
    }

    private function orgB(string $slug = 'ai11-org-b'): Organization
    {
        return Organization::create(['name' => 'AI11 Org B', 'slug' => $slug]);
    }

    private function ingestViaService(Organization $organization, array $payload): LibraryItem
    {
        $result = app(KnowledgeIngestionService::class)->ingestLibraryItem(
            $organization->id,
            $payload,
            $this->admin()->id,
        );

        return LibraryItem::query()->findOrFail($result->sourceId);
    }

    private function assertNoSecrets(string $content): void
    {
        $this->assertStringNotContainsString('sk-ai11-operator-secret', $content);
        $this->assertStringNotContainsString('sk-SHOULDNOTLEAK', $content);
        $this->assertStringNotContainsString('Bearer secret-header', $content);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function ingestJson(array $payload, ?array $headers = null)
    {
        return $this->postJson('/api/v1/operator/ai/knowledge', $payload, $headers ?? $this->operatorHeaders());
    }

    public function test_unauthenticated_health_is_rejected(): void
    {
        $this->getJson('/api/v1/operator/ai/retrieval/health')->assertUnauthorized();
    }

    public function test_unauthenticated_strategy_is_rejected(): void
    {
        $this->getJson('/api/v1/operator/ai/retrieval/strategy')->assertUnauthorized();
    }

    public function test_unauthenticated_quality_is_rejected(): void
    {
        $this->getJson('/api/v1/operator/ai/retrieval/quality')->assertUnauthorized();
    }

    public function test_unauthenticated_telemetry_is_rejected(): void
    {
        $this->getJson('/api/v1/operator/ai/retrieval/telemetry')->assertUnauthorized();
    }

    public function test_unauthenticated_ingestion_is_rejected(): void
    {
        $this->postJson('/api/v1/operator/ai/knowledge', [
            'slug' => 'ai11-unauth',
            'title' => 'AI11UnauthTitle',
        ])->assertUnauthorized();
    }

    public function test_authenticated_non_operator_is_rejected(): void
    {
        $headers = $this->memberHeaders();
        $this->getJson('/api/v1/operator/ai/retrieval/health', $headers)->assertForbidden();
        $this->getJson('/api/v1/operator/ai/retrieval/strategy', $headers)->assertForbidden();
        $this->getJson('/api/v1/operator/ai/retrieval/quality', $headers)->assertForbidden();
        $this->getJson('/api/v1/operator/ai/retrieval/telemetry', $headers)->assertForbidden();
        $this->postJson('/api/v1/operator/ai/knowledge', [
            'slug' => 'ai11-member-denied',
            'title' => 'AI11MemberDenied',
        ], $headers)->assertForbidden();
    }

    public function test_authorized_operator_can_access_health(): void
    {
        $this->getJson('/api/v1/operator/ai/retrieval/health', $this->operatorHeaders())
            ->assertOk()
            ->assertJsonPath('data.retrieval_available', true);
    }

    public function test_authorized_operator_can_inspect_strategy(): void
    {
        $this->getJson('/api/v1/operator/ai/retrieval/strategy', $this->operatorHeaders())
            ->assertOk()
            ->assertJsonPath('data.effective_strategy', 'keyword');
    }

    public function test_authorized_operator_can_inspect_quality(): void
    {
        $this->getJson('/api/v1/operator/ai/retrieval/quality', $this->operatorHeaders())
            ->assertOk()
            ->assertJsonPath('data.status', 'ok');
    }

    public function test_authorized_operator_can_inspect_telemetry(): void
    {
        $this->getJson('/api/v1/operator/ai/retrieval/telemetry', $this->operatorHeaders())
            ->assertOk()
            ->assertJsonPath('data.limit', 25);
    }

    public function test_authorized_operator_can_ingest(): void
    {
        $response = $this->ingestJson([
            'slug' => 'ai11-operator-ingest',
            'title' => 'AI11OperatorIngestTitle',
            'summary' => 'Operator summary.',
            'content' => 'Authoritative notes for AI11OperatorIngestTitle.',
            'publication_status' => 'published',
            'source_type' => 'library_items',
        ])->assertCreated();

        $this->assertSame('created', $response->json('data.action'));
        $item = LibraryItem::query()->findOrFail($response->json('data.source_id'));
        $this->assertSame(Organization::first()->id, $item->organization_id);
        $this->assertSame('AI11OperatorIngestTitle', $item->title);
    }

    public function test_authorized_operator_can_reindex(): void
    {
        $item = $this->ingestViaService(Organization::first(), [
            'slug' => 'ai11-reindex',
            'title' => 'AI11ReindexTitle',
            'content' => 'Body for reindex.',
            'publication_status' => 'published',
        ]);

        $this->postJson('/api/v1/operator/ai/knowledge/'.$item->id.'/index', [], $this->operatorHeaders())
            ->assertOk()
            ->assertJsonPath('data.keyword_indexed', true)
            ->assertJsonPath('data.source_id', $item->id);
    }

    public function test_tenant_a_cannot_inspect_tenant_b_quality(): void
    {
        $orgA = Organization::first();
        $orgB = $this->orgB('ai11-org-b-quality');
        $this->recordUsage($orgB, [
            'retrieval_status' => 'ok',
            'retrieval_strategy' => 'semantic',
            'returned_count' => 4,
            'source_types' => ['library_items'],
        ]);

        $quality = $this->getJson('/api/v1/operator/ai/retrieval/quality?organization_id='.$orgB->id, $this->operatorHeaders($orgA))
            ->assertOk();

        $this->assertSame($orgA->id, $quality->json('data.organization_id'));
        $this->assertSame(0, $quality->json('data.semantic_results'));
        $this->getJson('/api/v1/operator/ai/retrieval/quality', $this->operatorHeaders($orgB))->assertForbidden();
    }

    public function test_tenant_a_cannot_inspect_tenant_b_telemetry(): void
    {
        $orgA = Organization::first();
        $orgB = $this->orgB('ai11-org-b-telemetry');
        $foreign = $this->recordUsage($orgB, [
            'retrieval_status' => 'ok',
            'retrieval_strategy' => 'hybrid',
            'prompt' => 'SECRET PROMPT FROM B',
            'response' => 'SECRET RESPONSE FROM B',
        ]);

        $telemetry = $this->getJson('/api/v1/operator/ai/retrieval/telemetry', $this->operatorHeaders($orgA))
            ->assertOk();

        $this->assertSame($orgA->id, $telemetry->json('data.organization_id'));
        $ids = collect($telemetry->json('data.items'))->pluck('id')->all();
        $this->assertNotContains($foreign->id, $ids);
        $this->getJson('/api/v1/operator/ai/retrieval/telemetry?organization_id='.$orgB->id, $this->operatorHeaders($orgA))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['organization_id']);
        $this->getJson('/api/v1/operator/ai/retrieval/telemetry', $this->operatorHeaders($orgB))->assertForbidden();
    }

    public function test_tenant_a_cannot_ingest_into_tenant_b(): void
    {
        $orgB = $this->orgB('ai11-org-b-ingest');
        $this->ingestJson([
            'slug' => 'ai11-cross-ingest',
            'title' => 'AI11CrossIngest',
            'organization_id' => $orgB->id,
        ])->assertStatus(422)->assertJsonValidationErrors(['organization_id']);

        $this->ingestJson([
            'slug' => 'ai11-cross-ingest-header',
            'title' => 'AI11CrossIngestHeader',
        ], $this->operatorHeaders($orgB))->assertForbidden();

        $this->assertNull(LibraryItem::query()->where('slug', 'ai11-cross-ingest')->first());
    }

    public function test_tenant_a_cannot_reindex_publish_or_unpublish_tenant_b(): void
    {
        $orgA = Organization::first();
        $orgB = $this->orgB('ai11-org-b-mutate');
        $foreign = $this->ingestViaService($orgB, [
            'slug' => 'ai11-foreign-item',
            'title' => 'AI11ForeignItemTitle',
            'publication_status' => 'draft',
        ]);

        $headers = $this->operatorHeaders($orgA);
        $this->postJson('/api/v1/operator/ai/knowledge/'.$foreign->id.'/index', [], $headers)->assertNotFound();
        $this->postJson('/api/v1/operator/ai/knowledge/'.$foreign->id.'/publish', [], $headers)->assertNotFound();
        $this->postJson('/api/v1/operator/ai/knowledge/'.$foreign->id.'/unpublish', [], $headers)->assertNotFound();
        $this->postJson('/api/v1/operator/ai/knowledge/'.$foreign->id.'/index', ['organization_id' => $orgB->id], $headers)
            ->assertStatus(422);
        $this->assertSame('draft', $foreign->fresh()->publication_status);
    }

    public function test_keyword_only_system_reports_healthy(): void
    {
        Config::set('ai.retrieval.strategy', 'keyword');
        Config::set('ai.retrieval.semantic_enabled', false);

        $response = $this->getJson('/api/v1/operator/ai/retrieval/health', $this->operatorHeaders())->assertOk();
        $this->assertSame('healthy', $response->json('data.status'));
        $this->assertSame('keyword', $response->json('data.strategy'));
        $this->assertTrue($response->json('data.retrieval_available'));
        $this->assertFalse($response->json('data.semantic_available'));
        $this->assertTrue($response->json('data.ingestion_available'));
        $this->assertNoSecrets($response->getContent());
    }

    public function test_semantic_unavailable_reports_degraded_when_selected(): void
    {
        Config::set('ai.retrieval.strategy', 'hybrid');
        Config::set('ai.retrieval.semantic_enabled', false);

        $this->getJson('/api/v1/operator/ai/retrieval/health', $this->operatorHeaders())
            ->assertOk()
            ->assertJsonPath('data.status', 'degraded')
            ->assertJsonPath('data.semantic_available', false)
            ->assertJsonPath('data.retrieval_available', true);
    }

    public function test_retrieval_unavailable_reports_unavailable(): void
    {
        Config::set('ai.retrieval.enabled', false);

        $this->getJson('/api/v1/operator/ai/retrieval/health', $this->operatorHeaders())
            ->assertOk()
            ->assertJsonPath('data.status', 'unavailable')
            ->assertJsonPath('data.retrieval_available', false);
    }

    public function test_telemetry_failure_does_not_break_health(): void
    {
        $health = \Mockery::mock(KnowledgeRetrievalHealthService::class);
        $health->shouldReceive('summary')->andThrow(new \RuntimeException('telemetry table unavailable api_key=sk-SHOULDNOTLEAK'));
        $this->app->instance(KnowledgeRetrievalHealthService::class, $health);
        $this->app->forgetInstance(KnowledgeRetrievalOperationsService::class);

        $response = $this->getJson('/api/v1/operator/ai/retrieval/health', $this->operatorHeaders())->assertOk();
        $this->assertContains($response->json('data.status'), ['healthy', 'degraded', 'unavailable']);
        $this->assertNull($response->json('data.knowledge'));
        $this->assertStringNotContainsString('sk-SHOULDNOTLEAK', $response->getContent());
    }

    public function test_semantic_failure_cannot_falsely_report_healthy(): void
    {
        Config::set('ai.retrieval.strategy', 'semantic');
        $index = \Mockery::mock(KnowledgeSemanticIndexInterface::class);
        $index->shouldReceive('isAvailable')->andThrow(new \RuntimeException('semantic down api_key=sk-SHOULDNOTLEAK'));
        $this->app->instance(KnowledgeSemanticIndexInterface::class, $index);
        $this->app->forgetInstance(KnowledgeRetrievalOperationsService::class);

        $response = $this->getJson('/api/v1/operator/ai/retrieval/health', $this->operatorHeaders())->assertOk();
        $this->assertSame('degraded', $response->json('data.status'));
        $this->assertFalse($response->json('data.semantic_available'));
        $this->assertStringNotContainsString('sk-SHOULDNOTLEAK', $response->getContent());
    }

    public function test_health_never_returns_secrets(): void
    {
        $health = \Mockery::mock(KnowledgeRetrievalHealthService::class);
        $health->shouldReceive('summary')->andReturn([
            'api_key' => 'sk-SHOULDNOTLEAK',
            'openai_api_key' => 'sk-SHOULDNOTLEAK',
            'authorization' => 'Bearer secret-header',
            'indexed_available' => 1,
        ]);
        $this->app->instance(KnowledgeRetrievalHealthService::class, $health);
        $this->app->forgetInstance(KnowledgeRetrievalOperationsService::class);

        $response = $this->getJson('/api/v1/operator/ai/retrieval/health', $this->operatorHeaders())->assertOk();
        $this->assertArrayNotHasKey('api_key', $response->json('data.knowledge') ?? []);
        $this->assertArrayNotHasKey('openai_api_key', $response->json('data.knowledge') ?? []);
        $this->assertArrayNotHasKey('authorization', $response->json('data.knowledge') ?? []);
        $this->assertStringNotContainsString('sk-ai11-operator-secret', $response->getContent());
        $this->assertStringNotContainsString('sk-SHOULDNOTLEAK', $response->getContent());
        $this->assertStringNotContainsString('Bearer secret-header', $response->getContent());
    }

    public function test_keyword_default_strategy_is_returned(): void
    {
        Config::set('ai.retrieval.strategy', 'keyword');
        $this->getJson('/api/v1/operator/ai/retrieval/strategy', $this->operatorHeaders())
            ->assertOk()
            ->assertJsonPath('data.configured_strategy', 'keyword')
            ->assertJsonPath('data.effective_strategy', 'keyword')
            ->assertJsonPath('data.keyword_enabled', true);
    }

    public function test_hybrid_configuration_is_returned(): void
    {
        Config::set('ai.retrieval.strategy', 'hybrid');
        Config::set('ai.retrieval.semantic_enabled', true);
        $this->getJson('/api/v1/operator/ai/retrieval/strategy', $this->operatorHeaders())
            ->assertOk()
            ->assertJsonPath('data.configured_strategy', 'hybrid')
            ->assertJsonPath('data.effective_strategy', 'hybrid')
            ->assertJsonPath('data.hybrid_enabled', true)
            ->assertJsonPath('data.semantic_enabled', true);
    }

    public function test_semantic_configuration_is_returned(): void
    {
        Config::set('ai.retrieval.strategy', 'semantic');
        Config::set('ai.retrieval.semantic_enabled', true);
        $this->getJson('/api/v1/operator/ai/retrieval/strategy', $this->operatorHeaders())
            ->assertOk()
            ->assertJsonPath('data.configured_strategy', 'semantic')
            ->assertJsonPath('data.effective_strategy', 'semantic')
            ->assertJsonPath('data.semantic_enabled', true);
    }

    public function test_invalid_strategy_cannot_become_active(): void
    {
        Config::set('ai.retrieval.strategy', 'vector-magic');
        $response = $this->getJson('/api/v1/operator/ai/retrieval/strategy', $this->operatorHeaders())->assertOk();
        $this->assertSame('invalid', $response->json('data.configured_strategy'));
        $this->assertFalse($response->json('data.configured_strategy_valid'));
        $this->assertSame('keyword', $response->json('data.effective_strategy'));
        $this->assertStringNotContainsString('vector-magic', $response->getContent());
        $this->assertStringNotContainsString('sk-ai11-operator-secret', $response->getContent());
    }

    public function test_strategy_weights_are_bounded_and_secrets_are_omitted(): void
    {
        Config::set('ai.retrieval.keyword_weight', 99);
        Config::set('ai.retrieval.semantic_weight', 99);
        Config::set('ai.retrieval.freshness_weight', 99);
        $response = $this->getJson('/api/v1/operator/ai/retrieval/strategy', $this->operatorHeaders())->assertOk();
        $this->assertEquals(2, $response->json('data.weights.keyword'));
        $this->assertEquals(0.5, $response->json('data.weights.semantic'));
        $this->assertEquals(1, $response->json('data.weights.freshness'));
        $this->assertArrayNotHasKey('api_key', $response->json('data'));
        $this->assertStringNotContainsString('sk-ai11-operator-secret', $response->getContent());
    }

    public function test_quality_is_tenant_scoped_with_empty_fallback_and_distributions(): void
    {
        $orgA = Organization::first();
        $orgB = $this->orgB('ai11-org-b-quality-dist');
        $before = $this->getJson('/api/v1/operator/ai/retrieval/quality', $this->operatorHeaders($orgA))
            ->assertOk()
            ->json('data');

        $this->recordUsage($orgA, [
            'retrieval_status' => 'empty',
            'retrieval_strategy' => 'keyword',
            'returned_count' => 0,
            'candidate_count' => 3,
            'retrieval_duration_ms' => 10,
            'source_types' => ['library_items'],
        ]);
        $this->recordUsage($orgA, [
            'retrieval_status' => 'fallback',
            'retrieval_strategy' => 'hybrid',
            'returned_count' => 2,
            'candidate_count' => 5,
            'retrieval_duration_ms' => 20,
            'fallback_reason' => 'semantic_unavailable',
            'source_types' => ['bee_knowledge_topics'],
        ]);
        $this->recordUsage($orgB, [
            'retrieval_status' => 'ok',
            'retrieval_strategy' => 'semantic',
            'returned_count' => 9,
            'retrieval_duration_ms' => 999,
            'source_types' => ['library_items'],
        ]);

        $quality = $this->getJson('/api/v1/operator/ai/retrieval/quality', $this->operatorHeaders($orgA))->assertOk();
        $this->assertSame($orgA->id, $quality->json('data.organization_id'));
        $this->assertSame($before['zero_result_count'] + 1, $quality->json('data.zero_result_count'));
        $this->assertSame($before['fallback_count'] + 1, $quality->json('data.fallback_count'));
        $this->assertSame($before['strategy_distribution']['keyword'] + 1, $quality->json('data.strategy_distribution.keyword'));
        $this->assertSame($before['strategy_distribution']['hybrid'] + 1, $quality->json('data.strategy_distribution.hybrid'));
        $this->assertSame($before['strategy_distribution']['semantic'], $quality->json('data.strategy_distribution.semantic'));
        $this->assertSame($before['source_type_distribution']['library_items'] + 1, $quality->json('data.source_type_distribution.library_items'));
        $this->assertSame($before['source_type_distribution']['bee_knowledge_topics'] + 1, $quality->json('data.source_type_distribution.bee_knowledge_topics'));
        $this->assertSame($before['total_retrieval_requests'] + 2, $quality->json('data.total_retrieval_requests'));
        $this->assertIsNumeric($quality->json('data.average_retrieval_duration_ms'));
        $this->assertGreaterThanOrEqual(0, $quality->json('data.average_retrieval_duration_ms'));
        $this->assertNotEquals(999, $quality->json('data.average_retrieval_duration_ms'));
    }

    public function test_quality_telemetry_failure_does_not_break_endpoint(): void
    {
        $quality = \Mockery::mock(KnowledgeRetrievalQualityService::class);
        $quality->shouldReceive('summary')->andThrow(new \RuntimeException('quality unavailable api_key=sk-SHOULDNOTLEAK'));
        $this->app->instance(KnowledgeRetrievalQualityService::class, $quality);
        $this->app->forgetInstance(KnowledgeRetrievalOperationsService::class);

        $response = $this->getJson('/api/v1/operator/ai/retrieval/quality', $this->operatorHeaders())->assertOk();
        $this->assertSame('unavailable', $response->json('data.status'));
        $this->assertStringNotContainsString('sk-SHOULDNOTLEAK', $response->getContent());
    }

    public function test_telemetry_redacts_secrets_prompts_and_is_bounded(): void
    {
        $orgA = Organization::first();
        $this->recordUsage($orgA, [
            'retrieval_status' => 'ok',
            'retrieval_strategy' => 'keyword',
            'candidate_count' => 2,
            'returned_count' => 1,
            'api_key' => 'sk-SHOULDNOTLEAK',
            'authorization' => 'Bearer secret-header',
            'openai_api_key' => 'sk-SHOULDNOTLEAK',
            'prompt' => 'RAW USER PROMPT SHOULD NOT APPEAR',
            'response' => 'RAW MODEL RESPONSE SHOULD NOT APPEAR',
            'provider_secret' => 'super-secret',
        ]);

        $ok = $this->getJson('/api/v1/operator/ai/retrieval/telemetry?limit=25', $this->operatorHeaders($orgA))->assertOk();
        $this->assertSame(25, $ok->json('data.limit'));
        $this->assertSame($orgA->id, $ok->json('data.organization_id'));
        $this->assertGreaterThanOrEqual(1, $ok->json('data.count'));
        $content = $ok->getContent();
        $this->assertStringNotContainsString('sk-SHOULDNOTLEAK', $content);
        $this->assertStringNotContainsString('Bearer secret-header', $content);
        $this->assertStringNotContainsString('RAW USER PROMPT SHOULD NOT APPEAR', $content);
        $this->assertStringNotContainsString('RAW MODEL RESPONSE SHOULD NOT APPEAR', $content);
        $this->assertStringNotContainsString('super-secret', $content);
        $this->assertArrayNotHasKey('prompt', $ok->json('data.items.0') ?? []);
        $this->assertArrayNotHasKey('response', $ok->json('data.items.0') ?? []);
        $this->assertArrayNotHasKey('api_key', $ok->json('data.items.0') ?? []);

        $this->getJson('/api/v1/operator/ai/retrieval/telemetry?limit=101', $this->operatorHeaders())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['limit']);
        $this->getJson('/api/v1/operator/ai/retrieval/telemetry?from=2026-01-01&to=2026-05-02', $this->operatorHeaders())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['to']);
        $this->getJson('/api/v1/operator/ai/retrieval/telemetry?from=not-a-date', $this->operatorHeaders())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['from']);
        $this->getJson('/api/v1/operator/ai/retrieval/telemetry?strategy=vector', $this->operatorHeaders())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['strategy']);
        $this->getJson('/api/v1/operator/ai/retrieval/telemetry?status=exploded', $this->operatorHeaders())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_valid_knowledge_can_be_ingested_and_invalid_payloads_rejected(): void
    {
        $created = $this->ingestJson([
            'slug' => 'ai11-valid-knowledge',
            'title' => 'AI11ValidKnowledgeTitle',
            'summary' => 'Summary text.',
            'body' => 'Body used through existing normalizer.',
            'source_type' => 'library_items',
            'publication_status' => 'published',
        ])->assertCreated();
        $this->assertSame('created', $created->json('data.action'));

        $this->ingestJson([
            'slug' => 'ai11-invalid-title',
            'title' => '',
        ])->assertStatus(422)->assertJsonValidationErrors(['title']);

        $this->ingestJson([
            'slug' => 'ai11-oversized-body',
            'title' => 'AI11OversizedBody',
            'body' => str_repeat('a', 100001),
        ])->assertStatus(422)->assertJsonValidationErrors(['body']);

        $this->ingestJson([
            'slug' => 'ai11-invalid-source',
            'title' => 'AI11InvalidSource',
            'source_type' => 'bee_knowledge_topics',
        ])->assertStatus(422)->assertJsonValidationErrors(['source_type']);

        $this->ingestJson([
            'slug' => 'ai11-tenant-override',
            'title' => 'AI11TenantOverride',
            'organization_id' => 999,
        ])->assertStatus(422)->assertJsonValidationErrors(['organization_id']);
    }

    public function test_ingestion_reuses_normalizer_indexer_and_idempotency(): void
    {
        $first = $this->ingestJson([
            'slug' => 'ai11-idempotent',
            'title' => '  AI11IdempotentTitle  ',
            'summary' => 'Same summary.',
            'content' => 'Same body.',
            'publication_status' => 'published',
        ])->assertCreated();

        $item = LibraryItem::query()->findOrFail($first->json('data.source_id'));
        $this->assertSame('AI11IdempotentTitle', $item->title);
        $document = app(KnowledgeIndexer::class)->fromLibraryItem($item);
        $normalized = app(KnowledgeTextNormalizer::class)->searchable($item->title.' '.$item->content);
        $this->assertStringContainsString('ai11idempotenttitle', $document->searchableText);
        $this->assertSame($normalized, app(KnowledgeTextNormalizer::class)->searchable($item->title.' '.$item->content));

        $second = $this->ingestJson([
            'slug' => 'ai11-idempotent',
            'title' => 'AI11IdempotentTitle',
            'summary' => 'Same summary.',
            'content' => 'Same body.',
            'publication_status' => 'published',
        ])->assertOk();
        $this->assertSame('unchanged', $second->json('data.action'));
        $this->assertSame($item->id, $second->json('data.source_id'));
        $this->assertTrue(AuditLog::query()->where('action', 'ai.knowledge.ingested')->exists());
    }

    public function test_semantic_indexing_failure_does_not_destroy_keyword_ingestion(): void
    {
        $index = \Mockery::mock(KnowledgeSemanticIndexInterface::class);
        $index->shouldReceive('isAvailable')->andReturn(true);
        $index->shouldReceive('index')->andThrow(new \RuntimeException('index unavailable api_key=sk-SHOULDNOTLEAK'));
        $index->shouldReceive('remove')->zeroOrMoreTimes();
        $index->shouldReceive('fingerprint')->andReturn(null);
        $this->app->instance(KnowledgeSemanticIndexInterface::class, $index);

        $response = $this->ingestJson([
            'slug' => 'ai11-semantic-fail',
            'title' => 'AI11SemanticFailTitle',
            'content' => 'Keyword body remains.',
            'publication_status' => 'published',
        ]);
        $response->assertCreated();
        $this->assertNotNull(LibraryItem::query()->where('slug', 'ai11-semantic-fail')->first());
        $this->assertStringNotContainsString('sk-SHOULDNOTLEAK', $response->getContent());

        $reindex = $this->postJson(
            '/api/v1/operator/ai/knowledge/'.$response->json('data.source_id').'/index',
            [],
            $this->operatorHeaders(),
        )->assertOk();
        $this->assertTrue($reindex->json('data.keyword_indexed'));
        $this->assertFalse($reindex->json('data.semantic_indexed'));
        $this->assertSame('degraded', $reindex->json('data.status'));
        $this->assertStringNotContainsString('sk-SHOULDNOTLEAK', $reindex->getContent());
    }

    public function test_publish_and_unpublish_control_retrieval_eligibility(): void
    {
        $organization = Organization::first();
        $created = $this->ingestJson([
            'slug' => 'ai11-publication-gate',
            'title' => 'AI11PublicationGateTerm',
            'content' => 'AI11PublicationGateTerm orchard notes.',
            'publication_status' => 'draft',
        ])->assertCreated();
        $id = (int) $created->json('data.source_id');

        foreach (['keyword', 'semantic', 'hybrid'] as $strategy) {
            Config::set('ai.retrieval.strategy', $strategy);
            $result = app(KnowledgeRetrievalRouter::class)->retrieve($organization->id, 'AI11PublicationGateTerm');
            $this->assertFalse(
                collect($result->hits)->contains(fn ($hit) => $hit->title === 'AI11PublicationGateTerm'),
                "Unpublished knowledge leaked via {$strategy}",
            );
        }

        $this->postJson('/api/v1/operator/ai/knowledge/'.$id.'/publish', [], $this->operatorHeaders())
            ->assertOk()
            ->assertJsonPath('data.source_id', $id);
        $this->assertSame('published', LibraryItem::query()->findOrFail($id)->publication_status);

        foreach (['keyword', 'semantic', 'hybrid'] as $strategy) {
            Config::set('ai.retrieval.strategy', $strategy);
            $this->app->forgetInstance(KnowledgeRetrievalRouter::class);
            $result = app(KnowledgeRetrievalRouter::class)->retrieve($organization->id, 'AI11PublicationGateTerm');
            $this->assertTrue(
                collect($result->hits)->contains(fn ($hit) => $hit->title === 'AI11PublicationGateTerm'),
                "Published knowledge missing from {$strategy}",
            );
        }

        $this->postJson('/api/v1/operator/ai/knowledge/'.$id.'/unpublish', [], $this->operatorHeaders())->assertOk();
        $this->assertSame('draft', LibraryItem::query()->findOrFail($id)->publication_status);

        foreach (['keyword', 'semantic', 'hybrid'] as $strategy) {
            Config::set('ai.retrieval.strategy', $strategy);
            $this->app->forgetInstance(KnowledgeRetrievalRouter::class);
            $result = app(KnowledgeRetrievalRouter::class)->retrieve($organization->id, 'AI11PublicationGateTerm');
            $this->assertFalse(
                collect($result->hits)->contains(fn ($hit) => $hit->title === 'AI11PublicationGateTerm'),
                "Unpublished knowledge leaked via {$strategy} after unpublish",
            );
        }
        $this->assertTrue(AuditLog::query()->where('action', 'ai.knowledge.published')->exists());
        $this->assertTrue(AuditLog::query()->where('action', 'ai.knowledge.unpublished')->exists());
    }

    public function test_reindex_reports_degraded_when_semantic_unavailable(): void
    {
        Config::set('ai.retrieval.semantic_enabled', false);
        $item = $this->ingestViaService(Organization::first(), [
            'slug' => 'ai11-reindex-degraded',
            'title' => 'AI11ReindexDegraded',
            'publication_status' => 'published',
        ]);

        $response = $this->postJson('/api/v1/operator/ai/knowledge/'.$item->id.'/index', [], $this->operatorHeaders())
            ->assertOk();
        $this->assertTrue($response->json('data.keyword_indexed'));
        $this->assertFalse($response->json('data.semantic_indexed'));
        $this->assertSame('degraded', $response->json('data.status'));
        $this->assertSame('semantic_unavailable', $response->json('data.fallback_reason'));
    }
}
