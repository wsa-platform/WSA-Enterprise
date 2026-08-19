<?php

namespace Tests\Feature;

use App\Exceptions\AiProviderTimeoutException;
use App\Exceptions\AiProviderUnavailableException;
use App\Models\AiRequest;
use App\Models\Organization;
use App\Models\OrganizationSetting;
use App\Models\User;
use App\Services\Ai\AiProviderResolver;
use App\Services\Ai\MockAiProvider;
use App\Services\Ai\Providers\OpenAiProvider;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class Ai03OpenAiProviderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        Config::set('ai.provider', 'mock');
        Config::set('ai.fallback_provider', 'mock');
        Config::set('ai.async_dispatch', false);
        Config::set('ai.openai.api_key', 'test-openai-key');
        Config::set('ai.openai.base_url', 'https://api.openai.com');
        Config::set('ai.openai.model', 'gpt-test');
        Config::set('ai.openai.timeout', 5);
        Config::set('ai.openai.connect_timeout', 2);
        Config::set('ai.openai.retry_times', 2);
        Config::set('ai.openai.retry_sleep_ms', 0);
        Http::preventStrayRequests();
    }

    private function adminHeaders(): array
    {
        $organization = Organization::first();
        $admin = User::where('email', 'admin@wsa.test')->first();
        $token = $admin->createToken('ai-03')->plainTextToken;

        return [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
        ];
    }

    /** @return array<string, mixed> */
    private function successPayload(string $text, array $extraOutput = []): array
    {
        return [
            'id' => 'resp_test_1',
            'object' => 'response',
            'status' => 'completed',
            'model' => 'gpt-test',
            'output' => array_merge([
                ['id' => 'rs_1', 'type' => 'reasoning', 'content' => []],
                [
                    'id' => 'msg_1',
                    'type' => 'message',
                    'role' => 'assistant',
                    'content' => [
                        ['type' => 'output_text', 'text' => $text, 'annotations' => []],
                    ],
                ],
            ], $extraOutput),
            'usage' => [
                'input_tokens' => 11,
                'output_tokens' => 7,
                'total_tokens' => 18,
            ],
        ];
    }

    public function test_mock_provider_still_works_without_openai_key_or_http(): void
    {
        Config::set('ai.openai.api_key', '');
        Http::fake();

        $provider = app(MockAiProvider::class);
        $output = $provider->complete('library_summary', ['content' => 'Tomato leaf spots']);

        $this->assertSame('mock', $provider->name());
        $this->assertArrayHasKey('summary', $output);
        Http::assertNothingSent();
    }

    public function test_mock_remains_default_in_test_environment(): void
    {
        $resolved = app(AiProviderResolver::class)->forOrganization(Organization::first()->id);

        $this->assertInstanceOf(MockAiProvider::class, $resolved);
        $this->assertSame('mock', $resolved->name());
    }

    public function test_openai_provider_resolves_when_configured(): void
    {
        Config::set('ai.provider', 'openai');

        $resolved = app(AiProviderResolver::class)->forOrganization(Organization::first()->id);

        $this->assertInstanceOf(OpenAiProvider::class, $resolved);
        $this->assertSame('openai', $resolved->name());
        $this->assertSame('gpt-test', $resolved->model());
    }

    public function test_openai_request_uses_configured_base_url_model_and_server_authorization(): void
    {
        Config::set('ai.provider', 'openai');
        Config::set('ai.openai.base_url', 'https://ai.example.test');
        Http::fake([
            'https://ai.example.test/v1/responses' => Http::response($this->successPayload('Leaf spot guidance'), 200),
        ]);

        $output = app(OpenAiProvider::class)->complete('library_summary', [
            'content' => 'How should I manage tomato leaf spots?',
            'api_key' => 'ATTACKER_KEY',
            'model' => 'attacker-model',
            'base_url' => 'http://evil.example',
            'provider' => 'mock',
        ]);

        $this->assertSame('Leaf spot guidance', $output['summary']);
        $this->assertSame('openai', $output['provider']);
        $this->assertSame('gpt-test', $output['model']);
        $this->assertSame(18, $output['tokens_used']);
        $this->assertSame('resp_test_1', $output['request_id']);
        $this->assertArrayNotHasKey('api_key', $output);

        Http::assertSentCount(1);
        Http::assertSent(function ($request) {
            $body = $request->body();

            return $request->url() === 'https://ai.example.test/v1/responses'
                && $request->hasHeader('Authorization', 'Bearer test-openai-key')
                && $request->hasHeader('Content-Type', 'application/json')
                && str_contains($body, '"model":"gpt-test"')
                && str_contains($body, 'How should I manage tomato leaf spots?')
                && ! str_contains($body, 'ATTACKER_KEY')
                && ! str_contains($body, 'attacker-model')
                && ! str_contains($body, 'evil.example')
                && ! str_contains($body, 'test-openai-key');
        });
    }

    public function test_successful_responses_api_output_is_normalized(): void
    {
        Http::fake([
            '*' => Http::response($this->successPayload('Normalized library answer'), 200),
        ]);

        $output = app(OpenAiProvider::class)->complete('library_qa', ['query' => 'What is early blight?']);

        $this->assertSame('Normalized library answer', $output['summary']);
        $this->assertSame('Normalized library answer', $output['answer']);
        $this->assertSame('stop', $output['finish_reason']);
        $this->assertSame([], $output['sources']);
        $this->assertArrayNotHasKey('output', $output);
    }

    public function test_multiple_output_items_are_concatenated_safely(): void
    {
        $payload = $this->successPayload('First paragraph.', [
            [
                'type' => 'message',
                'role' => 'assistant',
                'content' => [
                    ['type' => 'output_text', 'text' => 'Second paragraph.', 'annotations' => [
                        ['type' => 'url_citation', 'title' => 'Guide', 'url' => 'https://example.com/guide'],
                    ]],
                ],
            ],
        ]);
        Http::fake(['*' => Http::response($payload, 200)]);

        $output = app(OpenAiProvider::class)->complete('assistant', [
            'message' => 'Hello',
            'domain' => 'agriculture',
        ]);

        $this->assertSame("First paragraph.\nSecond paragraph.", $output['reply']);
        $this->assertSame('agriculture', $output['domain']);
        $this->assertSame('Guide', $output['sources'][0]['title'] ?? null);
    }

    public function test_empty_or_malformed_response_is_rejected(): void
    {
        Http::fake([
            '*' => Http::response([
                'id' => 'resp_empty',
                'status' => 'completed',
                'output' => [['type' => 'reasoning', 'content' => []]],
            ], 200),
        ]);

        try {
            app(OpenAiProvider::class)->complete('library_summary', ['content' => 'Hello']);
            $this->fail('Malformed success should not complete.');
        } catch (AiProviderUnavailableException $exception) {
            $this->assertSame('The AI provider returned a malformed response.', $exception->getMessage());
        }
    }

    public function test_timeout_becomes_timeout_exception_after_retries(): void
    {
        $attempts = 0;
        Http::fake(function () use (&$attempts) {
            $attempts++;
            throw new ConnectionException('cURL error 28: Operation timed out after 5000 milliseconds');
        });

        try {
            app(OpenAiProvider::class)->complete('library_summary', ['content' => 'Hello']);
            $this->fail('Timeout should throw.');
        } catch (AiProviderTimeoutException $exception) {
            $this->assertSame('The AI provider timed out.', $exception->getMessage());
            $this->assertStringNotContainsString('test-openai-key', $exception->getMessage());
        }

        $this->assertSame(3, $attempts);
    }

    public function test_transient_500_and_429_are_retried_within_bound(): void
    {
        Http::fake([
            '*' => Http::sequence()
                ->push(['error' => ['message' => 'busy']], 503)
                ->push(['error' => ['message' => 'rate']], 429)
                ->push($this->successPayload('Recovered'), 200),
        ]);

        $output = app(OpenAiProvider::class)->complete('library_summary', ['content' => 'Retry me']);

        $this->assertSame('Recovered', $output['summary']);
        Http::assertSentCount(3);
    }

    public function test_401_and_403_are_not_retried(): void
    {
        Http::fake(['*' => Http::response(['error' => ['message' => 'invalid_api_key sk-ATTACKSECRET']], 401)]);

        try {
            app(OpenAiProvider::class)->complete('library_summary', ['content' => 'Hello']);
            $this->fail('401 should fail without retry.');
        } catch (AiProviderUnavailableException $exception) {
            $this->assertSame('The AI provider could not authenticate.', $exception->getMessage());
            $this->assertStringNotContainsString('sk-ATTACKSECRET', $exception->getMessage());
            $this->assertStringNotContainsString('test-openai-key', $exception->getMessage());
        }

        Http::assertSentCount(1);

        Http::fake(['*' => Http::response(['error' => ['message' => 'forbidden']], 403)]);
        $this->expectException(AiProviderUnavailableException::class);
        app(OpenAiProvider::class)->complete('library_summary', ['content' => 'Hello']);
    }

    public function test_400_invalid_request_is_not_retried(): void
    {
        Http::fake(['*' => Http::response(['error' => ['message' => 'invalid model']], 400)]);

        try {
            app(OpenAiProvider::class)->complete('library_summary', ['content' => 'Hello']);
            $this->fail('400 should fail without retry.');
        } catch (AiProviderUnavailableException $exception) {
            $this->assertSame('The AI provider rejected the request.', $exception->getMessage());
        }

        Http::assertSentCount(1);
    }

    public function test_api_key_never_appears_in_logs_or_endpoint_output(): void
    {
        Config::set('ai.provider', 'openai');
        $logged = [];
        Event::listen(MessageLogged::class, function (MessageLogged $event) use (&$logged): void {
            $logged[] = $event->message.' '.json_encode($event->context);
        });
        Http::fake([
            '*' => Http::response(['error' => ['message' => 'invalid_api_key test-openai-key']], 401),
        ]);

        $response = $this->postJson('/api/v1/ai/requests', [
            'request_type' => 'library_summary',
            'input' => ['content' => 'Hello', 'api_key' => 'ATTACKER_KEY'],
            'provider' => 'openai',
            'api_key' => 'ATTACKER_KEY',
        ], $this->adminHeaders())->assertStatus(422);

        $this->assertStringNotContainsString('test-openai-key', $response->getContent());
        $this->assertStringNotContainsString('ATTACKER_KEY', $response->getContent());

        $created = $this->postJson('/api/v1/ai/requests', [
            'request_type' => 'library_summary',
            'input' => ['content' => 'Hello', 'api_key' => 'ATTACKER_KEY', 'provider' => 'openai'],
        ], $this->adminHeaders())->assertCreated();

        $this->assertSame('failed', $created->json('status'));
        $this->assertSame('The AI provider could not authenticate.', $created->json('error_message'));
        $this->assertStringNotContainsString('test-openai-key', $created->getContent());
        $this->assertStringNotContainsString('ATTACKER_KEY', $created->getContent());
        $this->assertSame('openai', AiRequest::withoutGlobalScopes()->find($created->json('id'))?->provider);

        $logDump = implode("\n", $logged);
        $this->assertStringNotContainsString('test-openai-key', $logDump);
        $this->assertStringNotContainsString('ATTACKER_KEY', $logDump);
        $this->assertStringNotContainsString('Authorization', $logDump);
    }

    public function test_unknown_provider_remains_rejected(): void
    {
        Config::set('ai.provider', 'not-implemented');

        $this->expectException(AiProviderUnavailableException::class);
        app(AiProviderResolver::class)->forOrganization(Organization::first()->id);
    }

    public function test_organization_provider_override_to_openai_is_validated(): void
    {
        $organization = Organization::first();
        OrganizationSetting::create([
            'organization_id' => $organization->id,
            'key' => 'ai.provider',
            'value' => ['provider' => 'openai'],
        ]);
        Http::fake([
            '*' => Http::response($this->successPayload('Org override answer'), 200),
        ]);

        $created = $this->postJson('/api/v1/ai/requests', [
            'request_type' => 'library_summary',
            'input' => ['content' => 'Hello from org override'],
        ], $this->adminHeaders())->assertCreated();

        $this->assertSame('completed', $created->json('status'));
        $this->assertSame('openai', $created->json('provider'));
        $this->assertStringContainsString('Org override answer', (string) $created->json('output.summary'));
        Http::assertSentCount(1);
    }

    public function test_existing_ai_endpoint_compatibility_with_mock_default(): void
    {
        Http::fake();

        $created = $this->postJson('/api/v1/ai/requests', [
            'request_type' => 'library_summary',
            'input' => ['content' => 'How should I manage tomato leaf spots?'],
        ], $this->adminHeaders())->assertCreated();

        $this->assertSame('completed', $created->json('status'));
        $this->assertSame('mock', $created->json('provider'));
        Http::assertNothingSent();
    }
}
