<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Authorization\PermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BusinessModuleTest extends TestCase
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

    public function test_catalog_customer_crud_is_organization_scoped(): void
    {
        [$organization] = $this->actingAsTenantAdmin();

        $this->postJson('/api/v1/catalog/customers', [
            'code' => 'CUST-1',
            'name' => 'Demo Customer',
        ])->assertCreated()->assertJsonPath('code', 'CUST-1');

        $foreign = Organization::create(['name' => 'Tenant B', 'slug' => 'tenant-b']);
        Customer::create(['organization_id' => $foreign->id, 'code' => 'FOREIGN', 'name' => 'Foreign']);

        $this->getJson('/api/v1/catalog/customers')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.organization_id', $organization->id);
    }

    public function test_directory_company_respects_tenant_header(): void
    {
        [$organizationA, $user] = $this->actingAsTenantAdmin();
        $organizationB = Organization::create(['name' => 'Tenant B', 'slug' => 'tenant-b']);
        $organizationB->members()->attach($user->id, ['role' => 'admin']);

        Company::create(['organization_id' => $organizationA->id, 'name' => 'Company A']);
        Company::create(['organization_id' => $organizationB->id, 'name' => 'Company B']);

        $this->withHeader('X-Organization-Id', (string) $organizationB->id)
            ->getJson('/api/v1/directory/companies')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.name', 'Company B');
    }

    public function test_viewer_role_cannot_create_catalog_records(): void
    {
        [$organization] = $this->actingAsTenantAdmin();
        PermissionService::seedNamesForOrganization($organization->id);

        $viewerRole = Role::create(['organization_id' => $organization->id, 'name' => 'Viewer']);
        $viewerRole->permissions()->sync(
            Permission::where('organization_id', $organization->id)->where('name', 'business.view')->pluck('id')
        );

        $viewer = User::create(['name' => 'Viewer', 'email' => 'viewer@wsa.test', 'password' => Hash::make('password')]);
        $organization->members()->attach($viewer->id, ['role' => 'member']);
        $viewer->roles()->attach($viewerRole->id, ['organization_id' => $organization->id]);

        Sanctum::actingAs($viewer);

        $this->getJson('/api/v1/catalog/customers')->assertOk();
        $this->postJson('/api/v1/catalog/customers', ['code' => 'V-1', 'name' => 'Blocked'])->assertForbidden();
    }
}
