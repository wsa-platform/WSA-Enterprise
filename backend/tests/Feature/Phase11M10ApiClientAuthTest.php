<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Services\Api\ApiClientService;
use Database\Seeders\BillingSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * @group security
 */
class Phase11M10ApiClientAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->seed(BillingSeeder::class);
    }

    public function test_api_client_can_access_analytics_with_bearer_credentials_and_scope(): void
    {
        [$clientId, $secret, $organization] = $this->createClient(['analytics.read']);

        $response = $this->getJson('/api/v1/analytics/overview', $this->clientHeaders($clientId, $secret, $organization));

        $response->assertOk()
            ->assertJsonPath('organization_id', $organization->id);
    }

    public function test_api_client_without_required_scope_is_forbidden(): void
    {
        [$clientId, $secret, $organization] = $this->createClient(['billing.read']);

        $this->getJson('/api/v1/analytics/overview', $this->clientHeaders($clientId, $secret, $organization))
            ->assertForbidden();
    }

    public function test_invalid_api_client_secret_is_rejected(): void
    {
        [$clientId, , $organization] = $this->createClient(['analytics.read']);

        $this->getJson('/api/v1/analytics/overview', $this->clientHeaders($clientId, 'invalid-secret', $organization))
            ->assertUnauthorized();
    }

    public function test_revoked_api_client_is_rejected(): void
    {
        [$clientId, $secret, $organization, $client] = $this->createClient(['analytics.read']);
        app(ApiClientService::class)->revoke($client);

        $this->getJson('/api/v1/analytics/overview', $this->clientHeaders($clientId, $secret, $organization))
            ->assertUnauthorized();
    }

    public function test_api_client_cannot_access_non_allowlisted_endpoints(): void
    {
        [$clientId, $secret, $organization] = $this->createClient(['analytics.read', 'ai.read', 'billing.read']);

        $this->getJson('/api/v1/users', $this->clientHeaders($clientId, $secret, $organization))
            ->assertForbidden();
    }

    public function test_api_client_cannot_use_foreign_organization_header(): void
    {
        [$clientId, $secret, $organization] = $this->createClient(['analytics.read']);
        $foreign = Organization::create(['name' => 'M10 Foreign', 'slug' => 'm10-foreign']);

        $this->getJson('/api/v1/analytics/overview', $this->clientHeaders($clientId, $secret, $foreign))
            ->assertForbidden();
    }

    public function test_sanctum_user_authentication_still_works(): void
    {
        $organization = Organization::first();
        $admin = User::where('email', 'admin@wsa.test')->first();
        $token = $admin->createToken('m10-regression')->plainTextToken;

        $this->getJson('/api/v1/analytics/overview', [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
        ])->assertOk();
    }

    /** @return array{0: string, 1: string, 2: Organization, 3: \App\Models\ApiClient} */
    private function createClient(array $scopes): array
    {
        $organization = Organization::first();
        $admin = User::where('email', 'admin@wsa.test')->first();

        $result = app(ApiClientService::class)->create(
            $organization->id,
            'M10 Test Client '.uniqid(),
            $admin->id,
            $scopes,
        );

        return [
            $result['client']->client_id,
            $result['client_secret'],
            $organization,
            $result['client'],
        ];
    }

    /** @return array<string, string> */
    private function clientHeaders(string $clientId, string $secret, Organization $organization): array
    {
        return [
            'Authorization' => 'Bearer '.$clientId.':'.$secret,
            'X-Organization-Id' => (string) $organization->id,
        ];
    }
}
