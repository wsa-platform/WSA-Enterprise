<?php

namespace Tests\Feature;

use App\Contracts\AiProviderInterface;
use App\Exceptions\AiProviderTimeoutException;
use App\Models\AiRequest;
use App\Models\AiUsageRecord;
use App\Models\Organization;
use App\Models\UsageRecord;
use App\Models\User;
use App\Services\Ai\AiService;
use App\Services\Ai\AiUsageRecorder;
use App\Services\Tenancy\TenantContext;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class Ai04UsagePersistenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        Config::set('ai.provider', 'mock');
        Config::set('ai.fallback_provider', 'mock');
        Config::set('ai.async_dispatch', false);
        Http::preventStrayRequests();
    }

    private function adminHeaders(?Organization $organization = null): array
    {
        $organization ??= Organization::first();
        $admin = User::where('email', 'admin@wsa.test')->first();
        $token = $admin->createToken('ai-04')->plainTextToken;

        return [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
        ];
    }

    public function test_successful_ai_request_records_provider_model_tokens_and_latency(): void
    {
        Http::fake();

        $organization = Organization::first();
        $admin = User::where('email', 'admin@wsa.test')->first();

        $created = $this->postJson('/api/v1/ai/requests', [
            'request_type' => 'library_summary',
            'input' => ['content' => 'How should I manage tomato leaf spots?'],
        ], $this->adminHeaders())->assertCreated();

        $this->assertSame('completed', $created->json('status'));
        $this->assertSame('mock', $created->json('provider'));
        Http::assertNothingSent();

        $usage = AiUsageRecord::withoutGlobalScopes()
            ->where('ai_request_id', $created->json('id'))
            ->first();

        $this->assertNotNull($usage);
        $this->assertSame($organization->id, $usage->organization_id);
        $this->assertSame($admin->id, $usage->user_id);
        $this->assertSame('mock', $usage->provider);
        $this->assertSame('mock-v1', $usage->model);
        $this->assertSame(0, $usage->tokens_used);
        $this->assertNotNull($usage->latency_ms);
        $this->assertGreaterThanOrEqual(0, $usage->latency_ms);
        $this->assertSame('completed', $usage->status);
        $this->assertNull($usage->error_category);
        $this->assertArrayNotHasKey('api_key', $usage->getAttributes());
        $this->assertArrayNotHasKey('input', $usage->getAttributes());
        $this->assertArrayNotHasKey('output', $usage->getAttributes());

        $quota = UsageRecord::withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->where('metric', 'ai.requests')
            ->get()
            ->first(fn (UsageRecord $row) => (int) ($row->metadata['ai_request_id'] ?? 0) === (int) $created->json('id'));
        $this->assertNotNull($quota);
    }

    public function test_token_usage_and_request_id_are_persisted_when_supplied(): void
    {
        Config::set('ai.provider', 'openai');
        Config::set('ai.openai.api_key', 'test-openai-key');
        Config::set('ai.openai.model', 'gpt-test');
        Config::set('ai.openai.retry_times', 0);
        Config::set('ai.openai.retry_sleep_ms', 0);
        Http::fake([
            '*' => Http::response([
                'id' => 'resp_usage_1',
                'status' => 'completed',
                'model' => 'gpt-test',
                'output' => [[
                    'type' => 'message',
                    'content' => [['type' => 'output_text', 'text' => 'Persisted summary']],
                ]],
                'usage' => ['total_tokens' => 42],
            ], 200),
        ]);

        $created = $this->postJson('/api/v1/ai/requests', [
            'request_type' => 'library_summary',
            'input' => ['content' => 'Persist tokens'],
        ], $this->adminHeaders())->assertCreated();

        $this->assertSame('completed', $created->json('status'));
        $this->assertSame('resp_usage_1', $created->json('output.request_id'));

        $usage = AiUsageRecord::withoutGlobalScopes()
            ->where('ai_request_id', $created->json('id'))
            ->first();

        $this->assertNotNull($usage);
        $this->assertSame('openai', $usage->provider);
        $this->assertSame('gpt-test', $usage->model);
        $this->assertSame('resp_usage_1', $usage->provider_request_id);
        $this->assertSame(42, $usage->tokens_used);
        $this->assertNotNull($usage->latency_ms);
        $this->assertStringNotContainsString('test-openai-key', json_encode($usage->getAttributes()));
    }

    public function test_provider_error_does_not_leak_secrets_and_records_category(): void
    {
        $this->app->bind(\App\Services\Ai\MockAiProvider::class, fn () => new class implements AiProviderInterface
        {
            public function name(): string
            {
                return 'mock';
            }

            public function model(): string
            {
                return 'mock-v1';
            }

            public function complete(string $requestType, array $input): array
            {
                throw new \RuntimeException('upstream failed api_key=sk-SECRETKEY123 Bearer secret-token');
            }
        });

        $created = $this->postJson('/api/v1/ai/requests', [
            'request_type' => 'library_summary',
            'input' => ['content' => 'Trigger failure', 'api_key' => 'ATTACKER_KEY'],
        ], $this->adminHeaders())->assertCreated();

        $this->assertSame('failed', $created->json('status'));
        $this->assertSame('The AI provider failed to complete the request.', $created->json('error_message'));
        $this->assertStringNotContainsString('sk-SECRETKEY123', $created->getContent());
        $this->assertStringNotContainsString('ATTACKER_KEY', $created->getContent());

        $usage = AiUsageRecord::withoutGlobalScopes()
            ->where('ai_request_id', $created->json('id'))
            ->first();

        $this->assertNotNull($usage);
        $this->assertSame('failed', $usage->status);
        $this->assertSame('provider_failure', $usage->error_category);
        $this->assertStringNotContainsString('sk-SECRETKEY123', json_encode($usage->getAttributes()));
        $this->assertStringNotContainsString('ATTACKER_KEY', json_encode($usage->getAttributes()));
    }

    public function test_timeout_is_represented_on_usage_record(): void
    {
        $this->app->bind(\App\Services\Ai\MockAiProvider::class, fn () => new class implements AiProviderInterface
        {
            public function name(): string
            {
                return 'mock';
            }

            public function model(): string
            {
                return 'mock-v1';
            }

            public function complete(string $requestType, array $input): array
            {
                throw new AiProviderTimeoutException(5);
            }
        });

        $created = $this->postJson('/api/v1/ai/requests', [
            'request_type' => 'library_summary',
            'input' => ['content' => 'Trigger timeout'],
        ], $this->adminHeaders())->assertCreated();

        $this->assertSame('failed', $created->json('status'));
        $this->assertSame('The AI provider timed out.', $created->json('error_message'));

        $usage = AiUsageRecord::withoutGlobalScopes()
            ->where('ai_request_id', $created->json('id'))
            ->first();

        $this->assertNotNull($usage);
        $this->assertSame('timeout', $usage->error_category);
        $this->assertSame('mock', $usage->provider);
        $this->assertSame('mock-v1', $usage->model);
        $this->assertNotNull($usage->latency_ms);
    }

    public function test_usage_persistence_failure_does_not_break_ai_response(): void
    {
        $this->app->instance(AiUsageRecorder::class, new class extends AiUsageRecorder
        {
            public function recordOutcome(AiRequest $request, ?string $errorCategory = null, ?string $model = null): void
            {
                throw new \RuntimeException('usage table unavailable api_key=sk-SHOULDNOTLEAK');
            }
        });
        $this->app->forgetInstance(AiService::class);

        $created = $this->postJson('/api/v1/ai/requests', [
            'request_type' => 'library_summary',
            'input' => ['content' => 'Telemetry down'],
        ], $this->adminHeaders())->assertCreated();

        $this->assertSame('completed', $created->json('status'));
        $this->assertSame('mock', $created->json('provider'));
        $this->assertNotEmpty($created->json('output.summary'));
        $this->assertStringNotContainsString('sk-SHOULDNOTLEAK', $created->getContent());
        $this->assertDatabaseHas('ai_requests', [
            'id' => $created->json('id'),
            'status' => 'completed',
        ]);
    }

    public function test_tenant_and_user_isolation_is_preserved(): void
    {
        $orgA = Organization::first();
        $orgB = Organization::create(['name' => 'Org B', 'slug' => 'ai04-org-b']);
        $userA = User::where('email', 'admin@wsa.test')->first();
        $userB = User::factory()->create();

        $this->postJson('/api/v1/ai/requests', [
            'request_type' => 'library_summary',
            'input' => ['content' => 'Org A request'],
        ], $this->adminHeaders($orgA))->assertCreated();

        AiUsageRecord::withoutGlobalScopes()->create([
            'organization_id' => $orgB->id,
            'user_id' => $userB->id,
            'provider' => 'mock',
            'model' => 'mock-v1',
            'status' => 'completed',
            'tokens_used' => 9,
            'latency_ms' => 15,
        ]);

        app(TenantContext::class)->setOrganizationId($orgA->id);
        $visible = AiUsageRecord::query()->get();

        $this->assertTrue($visible->isNotEmpty());
        $this->assertTrue($visible->every(fn (AiUsageRecord $row) => $row->organization_id === $orgA->id));
        $this->assertFalse($visible->contains(fn (AiUsageRecord $row) => $row->organization_id === $orgB->id));
        $this->assertTrue($visible->every(fn (AiUsageRecord $row) => $row->user_id === $userA->id));

        app(TenantContext::class)->setOrganizationId($orgB->id);
        $visibleB = AiUsageRecord::query()->get();
        $this->assertTrue($visibleB->every(fn (AiUsageRecord $row) => $row->organization_id === $orgB->id));
        $this->assertFalse($visibleB->contains(fn (AiUsageRecord $row) => $row->user_id === $userA->id));
    }

    public function test_mock_provider_remains_usable_without_openai_api_key(): void
    {
        Config::set('ai.openai.api_key', '');
        putenv('OPENAI_API_KEY');
        Http::fake();

        $created = $this->postJson('/api/v1/ai/requests', [
            'request_type' => 'library_summary',
            'input' => ['content' => 'No OpenAI key required'],
        ], $this->adminHeaders())->assertCreated();

        $this->assertSame('completed', $created->json('status'));
        $this->assertSame('mock', $created->json('provider'));
        Http::assertNothingSent();
        $this->assertTrue(AiUsageRecord::withoutGlobalScopes()->where('ai_request_id', $created->json('id'))->exists());
    }
}
