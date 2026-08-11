<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\MonitoringEvent;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Authorization\EnterpriseRoleService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * @group security
 */
class Phase14M14EnterpriseAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function adminHeaders(?Organization $organization = null): array
    {
        $organization ??= Organization::first();
        $admin = User::where('email', 'admin@wsa.test')->first();
        $token = $admin->createToken('phase14-admin')->plainTextToken;

        return [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
        ];
    }

    public function test_organization_profile_and_settings_endpoints_require_authorization(): void
    {
        $organization = Organization::first();
        $member = User::create([
            'name' => 'Viewer Member',
            'email' => 'phase14-viewer@wsa.test',
            'password' => Hash::make('password'),
        ]);
        $member->organizations()->attach($organization->id, ['role' => 'member', 'is_active' => true]);
        EnterpriseRoleService::seedForOrganization($organization->id);
        $viewerRole = Role::withoutGlobalScopes()->where('organization_id', $organization->id)->where('slug', 'viewer')->firstOrFail();
        $member->roles()->sync([$viewerRole->id => ['organization_id' => $organization->id]]);
        app(\App\Services\Authorization\PermissionService::class)->forget($member, $organization->id);
        $token = $member->createToken('viewer')->plainTextToken;

        $this->getJson('/api/v1/organization', [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
        ])->assertOk();

        $this->patchJson('/api/v1/organization', ['name' => 'Blocked'], [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
        ])->assertForbidden();

        $this->getJson('/api/v1/organization/settings', $this->adminHeaders($organization))
            ->assertOk();

        $this->putJson('/api/v1/organization/settings', [
            'settings' => ['operations.timezone' => 'Europe/London'],
        ], $this->adminHeaders($organization))
            ->assertOk()
            ->assertJsonFragment(['operations.timezone' => ['value' => 'Europe/London']]);
    }

    public function test_user_administration_supports_update_deactivate_and_remove(): void
    {
        $organization = Organization::first();
        $headers = $this->adminHeaders($organization);

        $create = $this->postJson('/api/v1/users', [
            'name' => 'Phase14 User',
            'email' => 'phase14-user@wsa.test',
            'password' => 'password123',
        ], $headers)->assertCreated();

        $userId = $create->json('id');

        $this->getJson("/api/v1/users/{$userId}", $headers)
            ->assertOk()
            ->assertJsonPath('email', 'phase14-user@wsa.test')
            ->assertJsonPath('is_active', true);

        $this->patchJson("/api/v1/users/{$userId}", [
            'name' => 'Phase14 Updated',
            'is_active' => false,
        ], $headers)->assertOk()
            ->assertJsonPath('is_active', false);

        $this->assertTrue(AuditLog::where('action', 'user.updated')->exists());

        $this->deleteJson("/api/v1/users/{$userId}", [], $headers)
            ->assertOk();

        $this->assertFalse($organization->members()->where('users.id', $userId)->exists());
        $this->assertTrue(AuditLog::where('action', 'user.removed')->exists());
    }

    public function test_inactive_membership_blocks_organization_access(): void
    {
        $organization = Organization::first();
        $user = User::create([
            'name' => 'Inactive Member',
            'email' => 'phase14-inactive@wsa.test',
            'password' => Hash::make('password'),
        ]);
        $user->organizations()->attach($organization->id, ['role' => 'member', 'is_active' => false]);
        $token = $user->createToken('inactive')->plainTextToken;

        $this->getJson('/api/v1/dashboard', [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
        ])->assertForbidden();
    }

    public function test_role_and_permission_management_with_guards(): void
    {
        $organization = Organization::first();
        $headers = $this->adminHeaders($organization);
        EnterpriseRoleService::seedForOrganization($organization->id);

        $permission = $this->postJson('/api/v1/permissions', [
            'name' => 'reports.custom',
            'description' => 'Custom reports',
        ], $headers)->assertCreated()->json();

        $role = $this->postJson('/api/v1/roles', [
            'name' => 'Reports Analyst',
            'description' => 'Custom role',
            'permission_ids' => [$permission['id']],
        ], $headers)->assertCreated();

        $roleId = $role->json('id');

        $this->patchJson("/api/v1/roles/{$roleId}", [
            'name' => 'Reports Analyst Updated',
        ], $headers)->assertOk()
            ->assertJsonPath('name', 'Reports Analyst Updated');

        $this->assertTrue(AuditLog::where('action', 'role.updated')->exists());

        $this->deleteJson("/api/v1/roles/{$roleId}", [], $headers)->assertOk();
        $this->assertTrue(AuditLog::where('action', 'role.deleted')->exists());

        $systemRole = Role::withoutGlobalScopes()->where('organization_id', $organization->id)->where('slug', 'owner')->firstOrFail();
        $this->patchJson("/api/v1/roles/{$systemRole->id}", ['name' => 'Blocked'], $headers)->assertStatus(422);

        $catalogPermission = Permission::withoutGlobalScopes()->where('organization_id', $organization->id)->where('name', 'platform.view')->firstOrFail();
        $this->deleteJson("/api/v1/permissions/{$catalogPermission->id}", [], $headers)->assertStatus(422);

        $this->deleteJson("/api/v1/permissions/{$permission['id']}", [], $headers)->assertOk();
    }

    public function test_role_assignment_and_unassignment_are_audited(): void
    {
        $organization = Organization::first();
        $headers = $this->adminHeaders($organization);
        EnterpriseRoleService::seedForOrganization($organization->id);

        $user = User::create([
            'name' => 'Assign Target',
            'email' => 'phase14-assign@wsa.test',
            'password' => Hash::make('password'),
        ]);
        $user->organizations()->attach($organization->id, ['role' => 'member', 'is_active' => true]);

        $managerRole = Role::withoutGlobalScopes()->where('organization_id', $organization->id)->where('slug', 'manager')->firstOrFail();

        $this->postJson("/api/v1/users/{$user->id}/roles", ['role_id' => $managerRole->id], $headers)
            ->assertOk();

        $this->assertTrue(AuditLog::where('action', 'role.assigned')->exists());

        $this->deleteJson("/api/v1/users/{$user->id}/roles/{$managerRole->id}", [], $headers)
            ->assertOk();

        $this->assertTrue(AuditLog::where('action', 'role.unassigned')->exists());
    }

    public function test_monitoring_admin_endpoints_expose_health_and_incidents(): void
    {
        $organization = Organization::first();
        $headers = $this->adminHeaders($organization);

        MonitoringEvent::create([
            'component' => 'cache',
            'status' => MonitoringEvent::STATUS_OPEN,
            'severity' => 'warning',
            'lifecycle_stage' => MonitoringEvent::STAGE_DETECTED,
            'detected_at' => now(),
            'details' => ['message' => 'Cache probe failed.'],
        ]);

        $this->getJson('/api/v1/monitoring/health', $headers)
            ->assertOk()
            ->assertJsonStructure(['status', 'checked_at', 'components']);

        $incidents = $this->getJson('/api/v1/monitoring/incidents', $headers)
            ->assertOk();

        $this->assertNotEmpty($incidents->json());

        $incidentId = MonitoringEvent::first()->id;

        $this->postJson("/api/v1/monitoring/incidents/{$incidentId}/resolve", [
            'note' => 'Resolved during phase14 test',
        ], $headers)->assertOk()
            ->assertJsonPath('status', MonitoringEvent::STATUS_RESOLVED);
    }

    public function test_monitoring_endpoints_require_access_manage(): void
    {
        $organization = Organization::first();
        $member = User::create([
            'name' => 'No Access',
            'email' => 'phase14-no-access@wsa.test',
            'password' => Hash::make('password'),
        ]);
        $member->organizations()->attach($organization->id, ['role' => 'member', 'is_active' => true]);
        EnterpriseRoleService::seedForOrganization($organization->id);
        $viewerRole = Role::withoutGlobalScopes()->where('organization_id', $organization->id)->where('slug', 'viewer')->firstOrFail();
        $member->roles()->sync([$viewerRole->id => ['organization_id' => $organization->id]]);
        app(\App\Services\Authorization\PermissionService::class)->forget($member, $organization->id);
        $token = $member->createToken('viewer')->plainTextToken;

        $this->getJson('/api/v1/monitoring/health', [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
        ])->assertForbidden();
    }

    public function test_organization_update_is_audited(): void
    {
        $organization = Organization::first();
        $headers = $this->adminHeaders($organization);

        $this->patchJson('/api/v1/organization', [
            'name' => 'WSA Updated Org',
        ], $headers)->assertOk();

        $this->assertTrue(AuditLog::where('action', 'organization.updated')->exists());
    }
}
