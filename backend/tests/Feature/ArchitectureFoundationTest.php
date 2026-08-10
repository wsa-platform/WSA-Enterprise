<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Services\Authorization\PermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ArchitectureFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_organization_context_middleware_resolves_header_once(): void
    {
        $organization = Organization::create(['name' => 'Tenant A', 'slug' => 'tenant-a']);
        $user = User::create(['name' => 'Admin', 'email' => 'admin@wsa.test', 'password' => Hash::make('password')]);
        $user->organizations()->attach($organization->id, ['role' => 'admin']);
        PermissionService::seedNamesForOrganization($organization->id);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/dashboard', ['X-Organization-Id' => (string) $organization->id])
            ->assertOk();
    }

    public function test_api_response_envelope_on_audit_logs(): void
    {
        [$organization, $user] = $this->actingAsTenantAdmin();

        AuditLog::create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'action' => 'created',
            'auditable_type' => Role::class,
            'auditable_id' => 1,
            'new_values' => ['name' => 'Manager'],
        ]);

        $response = $this->getJson('/api/v1/audit-logs', [
            'X-Organization-Id' => (string) $organization->id,
        ]);

        $response->assertOk()
            ->assertJsonStructure(['data' => [['id', 'action', 'organization_id', 'user']]]);
    }

    public function test_role_assignment_is_audited_and_clears_permission_cache(): void
    {
        [$organization, $admin, $member] = $this->actingAsTenantAdminWithMember();

        $role = Role::create([
            'organization_id' => $organization->id,
            'name' => 'Custom Manager',
            'description' => 'Scoped manager role',
        ]);

        Cache::put("user_permissions:{$member->id}:{$organization->id}", ['platform.view'], 60);

        Sanctum::actingAs($admin);

        $this->postJson("/api/v1/users/{$member->id}/roles", [
            'role_id' => $role->id,
        ], [
            'X-Organization-Id' => (string) $organization->id,
        ])->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $organization->id,
            'action' => 'role.assigned',
            'auditable_type' => User::class,
            'auditable_id' => $member->id,
        ]);

        $this->assertFalse(Cache::has("user_permissions:{$member->id}:{$organization->id}"));
    }

    public function test_audit_logs_are_scoped_to_organization(): void
    {
        [$organizationA, $userA] = $this->actingAsTenantAdmin();
        $organizationB = Organization::create(['name' => 'Tenant B', 'slug' => 'tenant-b']);

        AuditLog::create([
            'organization_id' => $organizationB->id,
            'user_id' => $userA->id,
            'action' => 'created',
        ]);

        AuditLog::create([
            'organization_id' => $organizationA->id,
            'user_id' => $userA->id,
            'action' => 'updated',
        ]);

        Sanctum::actingAs($userA);

        $response = $this->getJson('/api/v1/audit-logs?action=updated', [
            'X-Organization-Id' => (string) $organizationA->id,
        ]);

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertSame('updated', $response->json('data.0.action'));
    }

    /** @return array{0: Organization, 1: User} */
    private function actingAsTenantAdmin(): array
    {
        $organization = Organization::create(['name' => 'Tenant A', 'slug' => 'tenant-a']);
        $user = User::create(['name' => 'Admin', 'email' => 'admin@wsa.test', 'password' => Hash::make('password')]);
        $user->organizations()->attach($organization->id, ['role' => 'admin']);
        PermissionService::seedNamesForOrganization($organization->id);
        Sanctum::actingAs($user);

        return [$organization, $user];
    }

    /** @return array{0: Organization, 1: User, 2: User} */
    private function actingAsTenantAdminWithMember(): array
    {
        [$organization, $admin] = $this->actingAsTenantAdmin();
        $member = User::create(['name' => 'Member', 'email' => 'member@wsa.test', 'password' => Hash::make('password')]);
        $member->organizations()->attach($organization->id, ['role' => 'member']);

        return [$organization, $admin, $member];
    }
}
