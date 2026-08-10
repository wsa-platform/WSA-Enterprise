<?php

namespace Tests\Unit;

use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Services\Authorization\EnterpriseRoleService;
use App\Services\Authorization\PermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EnterpriseRoleServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_slug_cannot_assign_owner_role(): void
    {
        $organization = Organization::create(['name' => 'Org', 'slug' => 'org-unit']);
        EnterpriseRoleService::seedForOrganization($organization->id);

        $orgAdmin = User::create([
            'name' => 'Org Admin',
            'email' => 'org-admin-unit@wsa.test',
            'password' => Hash::make('password'),
        ]);
        $orgAdmin->organizations()->attach($organization->id, ['role' => 'member']);

        $adminRole = Role::withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->where('slug', 'admin')
            ->firstOrFail();
        $ownerRole = Role::withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->where('slug', 'owner')
            ->firstOrFail();

        $orgAdmin->roles()->sync([$adminRole->id => ['organization_id' => $organization->id]]);

        $service = app(EnterpriseRoleService::class);

        $this->assertFalse($service->canAssignRole($orgAdmin, $organization->id, $ownerRole));
        $this->assertTrue($service->canAssignRole($orgAdmin, $organization->id, $adminRole));
    }

    public function test_owner_slug_can_assign_owner_role(): void
    {
        $organization = Organization::create(['name' => 'Org 2', 'slug' => 'org-unit-2']);
        EnterpriseRoleService::seedForOrganization($organization->id);

        $ownerUser = User::create([
            'name' => 'Owner',
            'email' => 'owner-unit@wsa.test',
            'password' => Hash::make('password'),
        ]);
        $ownerUser->organizations()->attach($organization->id, ['role' => 'admin']);

        $ownerRole = Role::withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->where('slug', 'owner')
            ->firstOrFail();

        $ownerUser->roles()->sync([$ownerRole->id => ['organization_id' => $organization->id]]);

        $service = app(EnterpriseRoleService::class);

        $this->assertTrue($service->canAssignRole($ownerUser, $organization->id, $ownerRole));
    }

    public function test_manager_role_does_not_include_access_manage(): void
    {
        $organization = Organization::create(['name' => 'Org 3', 'slug' => 'org-unit-3']);
        EnterpriseRoleService::seedForOrganization($organization->id);

        $manager = User::create([
            'name' => 'Manager',
            'email' => 'manager-unit@wsa.test',
            'password' => Hash::make('password'),
        ]);
        $manager->organizations()->attach($organization->id, ['role' => 'member']);

        $managerRole = Role::withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->where('slug', 'manager')
            ->firstOrFail();

        $manager->roles()->sync([$managerRole->id => ['organization_id' => $organization->id]]);

        app(PermissionService::class)->forget($manager, $organization->id);

        $this->assertFalse(
            app(PermissionService::class)->userCan($manager, $organization->id, 'access.manage')
        );
    }
}
