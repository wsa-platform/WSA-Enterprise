<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Services\Authorization\EnterpriseRoleService;
use App\Services\Authorization\PermissionService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Phase11RbacTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_enterprise_roles_are_seeded_for_demo_organization(): void
    {
        $organization = Organization::first();
        $slugs = Role::withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->whereNotNull('slug')
            ->pluck('slug')
            ->all();

        foreach (EnterpriseRoleService::slugs() as $slug) {
            $this->assertContains($slug, $slugs);
        }
    }

    public function test_viewer_role_cannot_create_farm_records(): void
    {
        $organization = Organization::first();
        EnterpriseRoleService::seedForOrganization($organization->id);

        $viewer = User::create([
            'name' => 'Viewer User',
            'email' => 'viewer-rbac@wsa.test',
            'password' => Hash::make('password'),
        ]);
        $viewer->organizations()->attach($organization->id, ['role' => 'member']);

        $viewerRole = Role::withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->where('slug', 'viewer')
            ->firstOrFail();
        $viewer->roles()->sync([$viewerRole->id => ['organization_id' => $organization->id]]);

        $token = $viewer->createToken('test')->plainTextToken;

        $this->postJson('/api/v1/farm/farms', [
            'code' => 'V1',
            'name' => 'Viewer Farm',
        ], [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
        ])->assertForbidden();
    }

    public function test_manager_role_can_manage_farms_but_not_access_administration(): void
    {
        $organization = Organization::first();
        EnterpriseRoleService::seedForOrganization($organization->id);

        $manager = User::create([
            'name' => 'Manager User',
            'email' => 'manager-rbac@wsa.test',
            'password' => Hash::make('password'),
        ]);
        $manager->organizations()->attach($organization->id, ['role' => 'member']);

        $managerRole = Role::withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->where('slug', 'manager')
            ->firstOrFail();
        $manager->roles()->sync([$managerRole->id => ['organization_id' => $organization->id]]);

        $token = $manager->createToken('test')->plainTextToken;
        $headers = [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
        ];

        $this->postJson('/api/v1/farm/farms', [
            'code' => 'M1',
            'name' => 'Manager Farm',
        ], $headers)->assertCreated();

        $this->getJson('/api/v1/users', $headers)->assertForbidden();
    }

    public function test_owner_role_has_access_manage_permission_via_gate(): void
    {
        $organization = Organization::first();
        $owner = User::where('email', 'admin@wsa.test')->first();

        $this->assertTrue(
            app(PermissionService::class)->userCan($owner, $organization->id, 'access.manage')
        );
    }
}
