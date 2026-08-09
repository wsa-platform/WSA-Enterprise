<?php

namespace Tests\Feature;

use App\Models\Farm;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Authorization\PermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class Phase7ModuleTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: Organization, 1: User} */
    private function actingAsTenantAdmin(): array
    {
        $organization = Organization::create(['name' => 'Tenant A', 'slug' => 'tenant-a']);
        $user = User::create(['name' => 'Admin', 'email' => 'admin@wsa.test', 'password' => Hash::make('password')]);
        $organization->members()->attach($user->id, ['role' => 'admin']);
        Sanctum::actingAs($user);

        return [$organization, $user];
    }

    public function test_viewer_role_can_read_but_not_manage_farms(): void
    {
        [$organization] = $this->actingAsTenantAdmin();
        PermissionService::seedNamesForOrganization($organization->id);

        $viewerRole = Role::create(['organization_id' => $organization->id, 'name' => 'Viewer']);
        $viewerRole->permissions()->sync(
            Permission::where('organization_id', $organization->id)->whereIn('name', ['platform.view', 'farm.view'])->pluck('id')
        );

        $viewer = User::create(['name' => 'Viewer', 'email' => 'viewer@wsa.test', 'password' => Hash::make('password')]);
        $organization->members()->attach($viewer->id, ['role' => 'member']);
        $viewer->roles()->attach($viewerRole->id, ['organization_id' => $organization->id]);
        Sanctum::actingAs($viewer);

        Farm::create(['organization_id' => $organization->id, 'code' => 'F1', 'name' => 'Farm']);

        $this->getJson('/api/v1/farm/farms')->assertOk()->assertJsonCount(1);
        $this->postJson('/api/v1/farm/farms', ['code' => 'F2', 'name' => 'Blocked'])->assertForbidden();
    }

    public function test_cross_tenant_farm_write_with_foreign_header_is_rejected(): void
    {
        $this->actingAsTenantAdmin();
        $foreign = Organization::create(['name' => 'Tenant B', 'slug' => 'tenant-b']);

        $this->withHeader('X-Organization-Id', (string) $foreign->id)
            ->postJson('/api/v1/farm/farms', ['code' => 'X', 'name' => 'Foreign'])
            ->assertForbidden();
    }

    public function test_phase4_phase5_phase6_regression_endpoints_still_work_for_admin(): void
    {
        $this->actingAsTenantAdmin();

        $this->getJson('/api/v1/farm/farms')->assertOk();
        $this->getJson('/api/v1/crop/types')->assertOk();
        $this->getJson('/api/v1/diagnosis/categories')->assertOk();
        $this->getJson('/api/v1/training/courses')->assertOk();
        $this->getJson('/api/v1/library/items')->assertOk();
        $this->getJson('/api/v1/ai/provider')->assertOk();
        $this->getJson('/api/v1/platform/workflow-summary')->assertOk();
    }
}
