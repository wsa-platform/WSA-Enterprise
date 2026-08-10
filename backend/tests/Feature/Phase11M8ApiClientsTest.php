<?php

namespace Tests\Feature;

use App\Models\ApiClient;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * @group security
 */
class Phase11M8ApiClientsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_admin_can_create_api_client_and_secret_is_hashed(): void
    {
        $organization = Organization::first();
        $admin = User::where('email', 'admin@wsa.test')->first();
        $token = $admin->createToken('test')->plainTextToken;
        $headers = [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
        ];

        $response = $this->postJson('/api/v1/api-clients', [
            'name' => 'Integration Bot',
            'scopes' => ['read'],
        ], $headers);

        $response->assertCreated()
            ->assertJsonStructure(['client', 'client_secret', 'message'])
            ->assertJsonMissing(['secret_hash']);

        $plainSecret = $response->json('client_secret');
        $this->assertNotEmpty($plainSecret);

        $stored = ApiClient::withoutGlobalScopes()->first();
        $this->assertNotSame($plainSecret, $stored->secret_hash);
        $this->assertTrue(Hash::check($plainSecret, $stored->secret_hash));
    }

    public function test_api_clients_are_isolated_by_organization(): void
    {
        $orgA = Organization::first();
        $orgB = Organization::create(['name' => 'Client Org B', 'slug' => 'client-org-b']);
        $admin = User::where('email', 'admin@wsa.test')->first();
        $admin->organizations()->syncWithoutDetaching([$orgB->id]);
        $token = $admin->createToken('test')->plainTextToken;

        $clientB = ApiClient::withoutGlobalScopes()->create([
            'organization_id' => $orgB->id,
            'name' => 'Org B Client',
            'client_id' => (string) \Illuminate\Support\Str::uuid(),
            'secret_hash' => Hash::make('secret-b'),
        ]);

        $this->postJson("/api/v1/api-clients/{$clientB->id}/revoke", [], [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $orgA->id,
        ])->assertNotFound();

        $this->getJson('/api/v1/api-clients', [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $orgA->id,
        ])->assertOk();

        $names = collect($this->getJson('/api/v1/api-clients', [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $orgA->id,
        ])->json())->pluck('name');

        $this->assertFalse($names->contains('Org B Client'));
    }
}
