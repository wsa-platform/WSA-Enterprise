<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Phase10AuditExpansionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_user_creation_is_audited(): void
    {
        $organization = Organization::first();
        $admin = User::where('email', 'admin@wsa.test')->first();
        $token = $admin->createToken('test')->plainTextToken;

        $this->postJson('/api/v1/users', [
            'name' => 'New Member',
            'email' => 'phase10-member-'.uniqid('', true).'@wsa.test',
            'password' => 'password123',
        ], [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
        ])->assertCreated();

        $log = AuditLog::where('action', 'user.created')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame($organization->id, $log->organization_id);
        $this->assertArrayNotHasKey('password', $log->new_values ?? []);
    }

    public function test_permission_creation_is_audited(): void
    {
        $organization = Organization::first();
        $admin = User::where('email', 'admin@wsa.test')->first();
        $token = $admin->createToken('test')->plainTextToken;

        $this->postJson('/api/v1/permissions', [
            'name' => 'reports.view',
            'description' => 'View reports',
        ], [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
        ])->assertCreated();

        $this->assertTrue(AuditLog::where('action', 'created')
            ->where('auditable_type', Permission::class)
            ->exists());
    }

    public function test_audit_logs_support_action_filter_and_pagination(): void
    {
        $organization = Organization::first();
        $admin = User::where('email', 'admin@wsa.test')->first();
        $token = $admin->createToken('test')->plainTextToken;

        AuditLog::create([
            'organization_id' => $organization->id,
            'user_id' => $admin->id,
            'action' => 'auth.login',
        ]);
        AuditLog::create([
            'organization_id' => $organization->id,
            'user_id' => $admin->id,
            'action' => 'user.created',
        ]);

        $response = $this->getJson('/api/v1/audit-logs?action=auth.login&page=1&per_page=10', [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.0.action', 'auth.login');
    }
}
