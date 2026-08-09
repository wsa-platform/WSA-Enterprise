<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Services\Authorization\PermissionService;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Consolidated regression gate covering Phase 4–7 API contracts.
 */
class Phase7FullRegressionTest extends TestCase
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

    public function test_phase4_agricultural_modules_remain_available(): void
    {
        $this->actingAsTenantAdmin();

        $this->getJson('/api/v1/farm/farms')->assertOk();
        $this->getJson('/api/v1/farm/fields')->assertOk();
        $this->getJson('/api/v1/crop/types')->assertOk();
        $this->getJson('/api/v1/soil/analyses')->assertOk();
    }

    public function test_phase5_decision_support_modules_remain_available(): void
    {
        $this->actingAsTenantAdmin();

        $this->getJson('/api/v1/diagnosis/requests')->assertOk();
        $this->getJson('/api/v1/training/courses')->assertOk();
        $this->getJson('/api/v1/library/items')->assertOk();
        $this->getJson('/api/v1/ai/provider')->assertOk();
    }

    public function test_phase6_platform_and_tenant_header_still_work(): void
    {
        [$organizationA, $user] = $this->actingAsTenantAdmin();
        $organizationB = Organization::create(['name' => 'Tenant B', 'slug' => 'tenant-b']);
        $organizationB->members()->attach($user->id, ['role' => 'admin']);

        $this->getJson('/api/v1/platform/organizations')->assertOk();
        $this->getJson('/api/v1/platform/workflow-summary')->assertOk();

        $this->withHeader('X-Organization-Id', (string) $organizationB->id)
            ->getJson('/api/v1/platform/workflow-summary')
            ->assertOk()
            ->assertJsonPath('organization_id', $organizationB->id);
    }

    public function test_phase7_authorization_denies_viewer_manage_actions(): void
    {
        [$organization] = $this->actingAsTenantAdmin();
        PermissionService::seedNamesForOrganization($organization->id);

        $viewerRole = Role::create(['organization_id' => $organization->id, 'name' => 'Viewer']);
        $viewerRole->permissions()->sync(
            Permission::where('organization_id', $organization->id)->whereIn('name', ['farm.view'])->pluck('id')
        );

        $viewer = User::create(['name' => 'Viewer', 'email' => 'viewer@wsa.test', 'password' => Hash::make('password')]);
        $organization->members()->attach($viewer->id, ['role' => 'member']);
        $viewer->roles()->attach($viewerRole->id, ['organization_id' => $organization->id]);
        Sanctum::actingAs($viewer);

        $this->getJson('/api/v1/farm/farms')->assertOk();
        $this->postJson('/api/v1/farm/farms', ['code' => 'X', 'name' => 'Blocked'])->assertForbidden();
    }

    public function test_cross_tenant_organization_header_is_rejected(): void
    {
        $this->actingAsTenantAdmin();
        $foreign = Organization::create(['name' => 'Foreign', 'slug' => 'foreign']);

        $this->withHeader('X-Organization-Id', (string) $foreign->id)
            ->getJson('/api/v1/farm/farms')
            ->assertForbidden();
    }
}
