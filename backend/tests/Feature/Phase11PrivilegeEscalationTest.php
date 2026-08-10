<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Services\Authorization\EnterpriseRoleService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Phase11PrivilegeEscalationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_assign_role_endpoint_enforces_can_assign_role_for_owner(): void
    {
        $organization = Organization::first();
        EnterpriseRoleService::seedForOrganization($organization->id);

        $orgAdmin = User::create([
            'name' => 'Org Admin',
            'email' => 'org-admin-escalation@wsa.test',
            'password' => Hash::make('password'),
        ]);
        $orgAdmin->organizations()->attach($organization->id, ['role' => 'member']);

        $target = User::create([
            'name' => 'Target',
            'email' => 'target-escalation@wsa.test',
            'password' => Hash::make('password'),
        ]);
        $target->organizations()->attach($organization->id, ['role' => 'member']);

        $adminRole = Role::withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->where('slug', 'admin')
            ->firstOrFail();
        $ownerRole = Role::withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->where('slug', 'owner')
            ->firstOrFail();

        $orgAdmin->roles()->sync([$adminRole->id => ['organization_id' => $organization->id]]);
        app(\App\Services\Authorization\PermissionService::class)->forget($orgAdmin, $organization->id);

        $orgAdminToken = $orgAdmin->createToken('org-admin')->plainTextToken;

        $this->postJson("/api/v1/users/{$target->id}/roles", [
            'role_id' => $ownerRole->id,
        ], [
            'Authorization' => "Bearer {$orgAdminToken}",
            'X-Organization-Id' => (string) $organization->id,
        ])->assertForbidden();
    }

    public function test_member_without_access_manage_cannot_assign_roles(): void
    {
        $organization = Organization::first();
        EnterpriseRoleService::seedForOrganization($organization->id);

        $member = User::create([
            'name' => 'Member',
            'email' => 'member-escalation@wsa.test',
            'password' => Hash::make('password'),
        ]);
        $member->organizations()->attach($organization->id, ['role' => 'member']);

        $viewerRole = Role::withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->where('slug', 'viewer')
            ->firstOrFail();

        $target = User::where('email', 'admin@wsa.test')->first();
        $token = $member->createToken('member')->plainTextToken;

        $this->postJson("/api/v1/users/{$target->id}/roles", [
            'role_id' => $viewerRole->id,
        ], [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
        ])->assertForbidden();
    }
}
