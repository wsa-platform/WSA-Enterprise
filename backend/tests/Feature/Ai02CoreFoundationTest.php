<?php

namespace Tests\Feature;

use App\Contracts\AiProviderInterface;
use App\Exceptions\AiProviderTimeoutException;
use App\Exceptions\AiProviderUnavailableException;
use App\Models\AiRequest;
use App\Models\Organization;
use App\Models\OrganizationSetting;
use App\Models\User;
use App\Services\Ai\AiDomain;
use App\Services\Ai\AiErrorSanitizer;
use App\Services\Ai\AiProviderResolver;
use App\Services\Ai\MockAiProvider;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class Ai02CoreFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        Config::set('ai.provider', 'mock');
        Config::set('ai.fallback_provider', 'mock');
        Config::set('ai.async_dispatch', false);
        putenv('OPENAI_API_KEY');
        putenv('AI_API_KEY');
    }

    private function adminHeaders(): array
    {
        $organization = Organization::first();
        $admin = User::where('email', 'admin@wsa.test')->first();
        $token = $admin->createToken('ai-02')->plainTextToken;

        return [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
        ];
    }

    public function test_mock_provider_works_without_api_key(): void
    {
        $provider = app(MockAiProvider::class);

        $output = $provider->complete('library_summary', ['content' => 'Tomato leaf spots']);

        $this->assertSame('mock', $provider->name());
        $this->assertNotSame('', $provider->model());
        $this->assertArrayHasKey('summary', $output);
        $this->assertSame(0, $output['tokens_used']);
        $this->assertArrayHasKey('sources', $output);
        $this->assertArrayNotHasKey('api_key', $output);
    }

    public function test_provider_resolver_selects_mock_by_default(): void
    {
        $organization = Organization::first();
        $resolved = app(AiProviderResolver::class)->forOrganization($organization->id);

        $this->assertInstanceOf(MockAiProvider::class, $resolved);
        $this->assertSame('mock', $resolved->name());
    }

    public function test_unknown_provider_is_rejected(): void
    {
        Config::set('ai.provider', 'not-implemented');

        $this->expectException(AiProviderUnavailableException::class);
        app(AiProviderResolver::class)->forOrganization(Organization::first()->id);
    }

    public function test_unknown_provider_endpoint_does_not_leak_secrets(): void
    {
        Config::set('ai.provider', 'not-implemented');
        Config::set('ai.api_key', 'sk-SECRETVALUE');

        $response = $this->getJson('/api/v1/ai/provider', $this->adminHeaders())
            ->assertStatus(422)
            ->assertJsonPath('message', 'The configured AI provider is not available.');

        $this->assertStringNotContainsString('sk-SECRETVALUE', $response->getContent());
        $this->assertStringNotContainsString('api_key', $response->getContent());
    }

    public function test_organization_provider_override_still_selects_mock(): void
    {
        $organization = Organization::first();
        OrganizationSetting::create([
            'organization_id' => $organization->id,
            'key' => 'ai.provider',
            'value' => ['provider' => 'mock'],
        ]);

        $this->getJson('/api/v1/ai/provider', $this->adminHeaders())
            ->assertOk()
            ->assertJsonPath('provider', 'mock')
            ->assertJsonPath('requested_provider', 'mock')
            ->assertJsonPath('fallback_provider', 'mock')
            ->assertJsonPath('used_fallback', false)
            ->assertJsonMissingPath('api_key');
    }

    public function test_unknown_organization_provider_override_is_rejected(): void
    {
        $organization = Organization::first();
        OrganizationSetting::create([
            'organization_id' => $organization->id,
            'key' => 'ai.provider',
            'value' => ['provider' => 'not-implemented'],
        ]);

        $this->getJson('/api/v1/ai/provider', $this->adminHeaders())
            ->assertStatus(422)
            ->assertJsonPath('provider.requested', 'not-implemented');
    }

    public function test_unknown_assistant_domain_is_rejected(): void
    {
        $this->postJson('/api/v1/ai/assistant/conversations', [
            'domain' => 'unknown-agent',
            'message' => 'Hello',
        ], $this->adminHeaders())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['domain']);
    }

    public function test_allowed_assistant_domains_are_accepted(): void
    {
        foreach (['agriculture', 'platform', 'marketplace'] as $domain) {
            $this->postJson('/api/v1/ai/assistant/conversations', [
                'domain' => $domain,
                'message' => 'Hello from '.$domain,
            ], $this->adminHeaders())->assertCreated();
        }

        $this->assertTrue(AiDomain::isAllowed('jobs'));
        $this->assertFalse(AiDomain::isAllowed('root'));
    }

    public function test_client_cannot_override_provider_or_model(): void
    {
        $this->postJson('/api/v1/ai/requests', [
            'request_type' => 'library_summary',
            'input' => ['content' => 'Hello'],
            'provider' => 'openai',
            'model' => 'gpt-4',
            'api_key' => 'sk-client',
        ], $this->adminHeaders())->assertStatus(422);

        $created = $this->postJson('/api/v1/ai/requests', [
            'request_type' => 'library_summary',
            'input' => ['content' => 'Hello', 'provider' => 'openai', 'api_key' => 'sk-client'],
        ], $this->adminHeaders())->assertCreated();

        $record = AiRequest::withoutGlobalScopes()->find($created->json('id'));
        $this->assertSame('mock', $record->provider);
        $this->assertArrayNotHasKey('provider', $record->input);
        $this->assertArrayNotHasKey('api_key', $record->input);
        $this->assertSame('completed', $record->status);
        $this->assertSame('mock', $record->output['provider'] ?? null);
        $this->assertArrayHasKey('sources', $record->output);
        $this->assertArrayNotHasKey('input', $created->json());
        $this->assertStringNotContainsString('sk-client', $created->getContent());
    }

    public function test_provider_failures_are_normalized_without_secrets(): void
    {
        $this->app->bind(MockAiProvider::class, fn () => new class implements AiProviderInterface
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
            'input' => ['content' => 'Trigger failure'],
        ], $this->adminHeaders())->assertCreated();

        $this->assertSame('failed', $created->json('status'));
        $this->assertSame('The AI provider failed to complete the request.', $created->json('error_message'));
        $this->assertStringNotContainsString('sk-SECRETKEY123', $created->getContent());
        $this->assertStringNotContainsString('secret-token', $created->getContent());
    }

    public function test_timeout_errors_are_distinguishable_and_safe(): void
    {
        $this->app->bind(MockAiProvider::class, fn () => new class implements AiProviderInterface
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
                throw new AiProviderTimeoutException(30);
            }
        });

        $created = $this->postJson('/api/v1/ai/requests', [
            'request_type' => 'library_summary',
            'input' => ['content' => 'Trigger timeout'],
        ], $this->adminHeaders())->assertCreated();

        $this->assertSame('failed', $created->json('status'));
        $this->assertSame('The AI provider timed out.', $created->json('error_message'));
    }

    public function test_error_sanitizer_redacts_secrets(): void
    {
        $redacted = AiErrorSanitizer::redact('failed api_key=sk-abc Bearer tok-123 password=hunter2');

        $this->assertStringNotContainsString('sk-abc', $redacted);
        $this->assertStringNotContainsString('tok-123', $redacted);
        $this->assertStringNotContainsString('hunter2', $redacted);
        $this->assertStringContainsString('[redacted]', $redacted);
    }

    public function test_existing_ai_request_compatibility_remains_intact(): void
    {
        $created = $this->postJson('/api/v1/ai/requests', [
            'request_type' => 'library_summary',
            'input' => ['content' => 'How should I manage tomato leaf spots?'],
        ], $this->adminHeaders())->assertCreated();

        $this->assertSame('completed', $created->json('status'));
        $this->assertSame('library', $created->json('output.request_type'));
        $this->assertNotEmpty($created->json('output.summary'));
        $this->getJson('/api/v1/ai/usage', $this->adminHeaders())->assertOk();
    }
}
