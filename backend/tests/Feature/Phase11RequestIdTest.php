<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase11RequestIdTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_api_responses_include_request_id_header(): void
    {
        $organization = Organization::first();
        $admin = User::where('email', 'admin@wsa.test')->first();
        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->getJson('/api/v1/health');

        $response->assertOk();
        $this->assertNotEmpty($response->headers->get('X-Request-Id'));

        $response = $this->getJson('/api/v1/dashboard', [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
            'X-Request-Id' => 'test-request-id-123',
        ]);

        $response->assertOk();
        $this->assertSame('test-request-id-123', $response->headers->get('X-Request-Id'));
    }

    public function test_cross_tenant_denial_is_audited_with_request_id(): void
    {
        $organization = Organization::first();
        $admin = User::where('email', 'admin@wsa.test')->first();
        $token = $admin->createToken('test')->plainTextToken;

        $this->getJson('/api/v1/dashboard', [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => '999999',
            'X-Request-Id' => 'cross-tenant-req',
        ])->assertForbidden();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'security.cross_tenant_denied',
            'user_id' => $admin->id,
        ]);

        $log = \App\Models\AuditLog::where('action', 'security.cross_tenant_denied')->latest('id')->first();
        $this->assertSame('cross-tenant-req', $log?->request_id);
    }
}
