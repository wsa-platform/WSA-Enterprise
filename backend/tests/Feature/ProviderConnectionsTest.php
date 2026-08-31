<?php

namespace Tests\Feature;

use App\Models\ProviderConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProviderConnectionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_provider_test_records_failure_when_unconfigured(): void
    {
        config([
            'marketing.providers.email' => 'none',
            'providers.email.resend_key' => null,
        ]);

        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/providers/email/test')
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('label', config('providers.status_label_unconfigured'));

        $this->assertDatabaseHas('provider_connections', [
            'provider_key' => 'email',
            'last_test_status' => ProviderConnection::STATUS_FAILED,
        ]);
    }

    public function test_resend_provider_test_uses_api_and_records_success(): void
    {
        config([
            'marketing.providers.email' => 'resend',
            'providers.email.resend_key' => 're_test_key',
        ]);

        Http::fake([
            'api.resend.com/*' => Http::response(['data' => []], 200),
        ]);

        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/providers/email/test')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('connected', true);

        $this->assertDatabaseHas('provider_connections', [
            'provider_key' => 'email',
            'last_test_status' => ProviderConnection::STATUS_SUCCESS,
        ]);
    }

    public function test_mock_sms_and_whatsapp_tests_succeed_in_testing_environment(): void
    {
        config([
            'marketing.providers.sms' => 'mock',
            'marketing.providers.whatsapp' => 'mock',
        ]);

        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/providers/sms/test')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Mock SMS provider.');

        $this->postJson('/api/v1/providers/whatsapp/test')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Mock WhatsApp provider.');
    }

    public function test_marketing_suppressions_endpoint_does_not_error(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@wsa.test')->firstOrFail();
        $organization = \App\Models\Organization::where('slug', 'wsa-demo')->firstOrFail();

        Sanctum::actingAs($admin);

        $this->withHeaders(['X-Organization-Id' => (string) $organization->id])
            ->getJson('/api/v1/marketing/suppressions')
            ->assertOk()
            ->assertJsonStructure(['data']);
    }
}
