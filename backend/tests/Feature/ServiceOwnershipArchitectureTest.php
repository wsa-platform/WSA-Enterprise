<?php

namespace Tests\Feature;

use App\Models\Farm;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Models\Customer;
use App\Services\Authorization\EnterpriseRoleService;
use App\Services\Authorization\PermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('security')]
class ServiceOwnershipArchitectureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('app.allow_registration', true);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    private function createOrganization(string $slug = 'shared-workspace'): Organization
    {
        $organization = Organization::create([
            'name' => 'Shared Workspace',
            'slug' => $slug,
        ]);
        EnterpriseRoleService::seedForOrganization($organization->id);

        return $organization;
    }

    private function attachEnterpriseRole(User $user, Organization $organization, string $slug): void
    {
        $organization->members()->syncWithoutDetaching([
            $user->id => ['role' => 'member', 'is_active' => true],
        ]);

        $role = Role::withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->where('slug', $slug)
            ->firstOrFail();

        $user->roles()->sync([$role->id => ['organization_id' => $organization->id]]);
        app(PermissionService::class)->forget($user, $organization->id);
    }

    private function memberUser(Organization $organization, string $email): User
    {
        $user = User::create([
            'name' => 'Member '.$email,
            'email' => $email,
            'password' => Hash::make('password'),
        ]);
        $this->attachEnterpriseRole($user, $organization, 'member');

        return $user;
    }

    private function managerUser(Organization $organization, string $email): User
    {
        $user = User::create([
            'name' => 'Manager '.$email,
            'email' => $email,
            'password' => Hash::make('password'),
        ]);
        $this->attachEnterpriseRole($user, $organization, 'manager');

        return $user;
    }

    private function orgAdminUser(Organization $organization, string $email): User
    {
        $user = User::create([
            'name' => 'Admin '.$email,
            'email' => $email,
            'password' => Hash::make('password'),
        ]);
        $organization->members()->syncWithoutDetaching([
            $user->id => ['role' => 'admin', 'is_active' => true],
        ]);

        return $user;
    }

    private function headers(User $user, Organization $organization): array
    {
        $token = $user->createToken('ownership-test')->plainTextToken;

        return [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
        ];
    }

    public function test_public_registration_creates_member_service_owner_workspace(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Service Owner',
            'email' => 'owner-new@wsa.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertCreated();

        $response->assertJsonPath('user.email', 'owner-new@wsa.test')
            ->assertJsonStructure(['token', 'organization' => ['id', 'name', 'slug']]);

        $user = User::where('email', 'owner-new@wsa.test')->firstOrFail();
        $organization = Organization::findOrFail($response->json('organization.id'));

        $this->assertSame('member', $organization->members()->where('users.id', $user->id)->first()->pivot->role);
        $this->assertTrue(
            $user->roles()
                ->where('roles.organization_id', $organization->id)
                ->where('roles.slug', 'member')
                ->exists()
        );
        $this->assertFalse(
            $user->roles()
                ->where('roles.organization_id', $organization->id)
                ->whereIn('roles.slug', ['owner', 'admin'])
                ->exists()
        );
    }

    public function test_registered_user_can_authenticate(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Auth Owner',
            'email' => 'auth-owner@wsa.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertCreated();

        $this->postJson('/api/v1/auth/login', [
            'email' => 'auth-owner@wsa.test',
            'password' => 'password123',
        ])->assertOk()
            ->assertJsonPath('user.email', 'auth-owner@wsa.test')
            ->assertJsonStructure(['token']);
    }

    public function test_service_creation_assigns_authenticated_user_as_owner(): void
    {
        $organization = $this->createOrganization('farm-owner-org');
        $owner = $this->memberUser($organization, 'farm-owner@wsa.test');

        $this->postJson('/api/v1/farm/farms', [
            'code' => 'OWN-1',
            'name' => 'Owner Farm',
            'area_hectares' => 5,
            'is_active' => true,
        ], $this->headers($owner, $organization))
            ->assertCreated()
            ->assertJsonPath('owner_user_id', $owner->id);

        $this->assertDatabaseHas('farms', [
            'organization_id' => $organization->id,
            'code' => 'OWN-1',
            'owner_user_id' => $owner->id,
        ]);
    }

    public function test_owner_id_cannot_be_spoofed_on_create(): void
    {
        $organization = $this->createOrganization('spoof-org');
        $owner = $this->memberUser($organization, 'spoof-owner@wsa.test');
        $other = $this->memberUser($organization, 'spoof-other@wsa.test');

        $this->postJson('/api/v1/farm/farms', [
            'code' => 'SPOOF',
            'name' => 'Spoof Farm',
            'area_hectares' => 1,
            'is_active' => true,
            'owner_user_id' => $other->id,
            'owner_id' => $other->id,
        ], $this->headers($owner, $organization))
            ->assertCreated()
            ->assertJsonPath('owner_user_id', $owner->id);
    }

    public function test_owner_can_view_update_and_delete_their_service(): void
    {
        $organization = $this->createOrganization('crud-org');
        $owner = $this->memberUser($organization, 'crud-owner@wsa.test');

        $create = $this->postJson('/api/v1/farm/farms', [
            'code' => 'CRUD',
            'name' => 'CRUD Farm',
            'area_hectares' => 3,
            'is_active' => true,
        ], $this->headers($owner, $organization))->assertCreated();

        $farmId = $create->json('id');

        $this->getJson('/api/v1/farm/farms', $this->headers($owner, $organization))
            ->assertOk()
            ->assertJsonCount(1);

        $this->putJson("/api/v1/farm/farms/{$farmId}", [
            'code' => 'CRUD',
            'name' => 'Updated Farm',
            'area_hectares' => 4,
            'is_active' => true,
        ], $this->headers($owner, $organization))
            ->assertOk()
            ->assertJsonPath('name', 'Updated Farm');

        $this->deleteJson("/api/v1/farm/farms/{$farmId}", [], $this->headers($owner, $organization))
            ->assertNoContent();
    }

    public function test_user_cannot_access_or_modify_another_users_service(): void
    {
        $organization = $this->createOrganization('isolation-org');
        $ownerA = $this->memberUser($organization, 'user-a@wsa.test');
        $ownerB = $this->memberUser($organization, 'user-b@wsa.test');

        $farm = Farm::create([
            'organization_id' => $organization->id,
            'owner_user_id' => $ownerA->id,
            'code' => 'A1',
            'name' => 'Farm A',
            'area_hectares' => 1,
            'is_active' => true,
        ]);

        $this->getJson('/api/v1/farm/farms', $this->headers($ownerB, $organization))
            ->assertOk()
            ->assertJsonCount(0);

        $this->putJson("/api/v1/farm/farms/{$farm->id}", [
            'code' => 'A1',
            'name' => 'Hijacked',
            'area_hectares' => 1,
            'is_active' => true,
        ], $this->headers($ownerB, $organization))
            ->assertNotFound();

        $this->deleteJson("/api/v1/farm/farms/{$farm->id}", [], $this->headers($ownerB, $organization))
            ->assertNotFound();
    }

    public function test_manager_with_supervise_permission_can_access_all_org_services(): void
    {
        $organization = $this->createOrganization('manager-org');
        $owner = $this->memberUser($organization, 'service-owner@wsa.test');
        $manager = $this->managerUser($organization, 'project-manager@wsa.test');

        $farm = Farm::create([
            'organization_id' => $organization->id,
            'owner_user_id' => $owner->id,
            'code' => 'M1',
            'name' => 'Managed Farm',
            'area_hectares' => 2,
            'is_active' => true,
        ]);

        $this->getJson('/api/v1/farm/farms', $this->headers($manager, $organization))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $farm->id);

        $this->putJson("/api/v1/farm/farms/{$farm->id}", [
            'code' => 'M1',
            'name' => 'Manager Updated',
            'area_hectares' => 2,
            'is_active' => true,
        ], $this->headers($manager, $organization))
            ->assertOk()
            ->assertJsonPath('name', 'Manager Updated');
    }

    public function test_organization_admin_can_supervise_without_becoming_owner_on_create(): void
    {
        $organization = $this->createOrganization('admin-org');
        $admin = $this->orgAdminUser($organization, 'org-admin@wsa.test');

        $this->postJson('/api/v1/farm/farms', [
            'code' => 'ADMIN-1',
            'name' => 'Admin Created Farm',
            'area_hectares' => 8,
            'is_active' => true,
        ], $this->headers($admin, $organization))
            ->assertCreated()
            ->assertJsonPath('owner_user_id', $admin->id);
    }

    public function test_multiple_service_types_share_the_same_ownership_architecture(): void
    {
        $organization = $this->createOrganization('multi-module-org');
        $owner = $this->memberUser($organization, 'multi-owner@wsa.test');
        $headers = $this->headers($owner, $organization);

        $this->postJson('/api/v1/crop/types', [
            'code' => 'WHT',
            'name' => 'Wheat',
        ], $headers)->assertCreated()->assertJsonPath('owner_user_id', $owner->id);

        $this->postJson('/api/v1/soil/analyses', [
            'sample_reference' => 'SOIL-1',
            'sampled_at' => now()->toDateString(),
            'ph' => 6.5,
        ], $headers)->assertCreated()->assertJsonPath('owner_user_id', $owner->id);

        $this->postJson('/api/v1/training/courses', [
            'code' => 'TRN-1',
            'title' => 'Safety Training',
        ], $headers)->assertCreated()->assertJsonPath('owner_user_id', $owner->id);

        $this->assertDatabaseHas('crop_types', ['code' => 'WHT', 'owner_user_id' => $owner->id]);
        $this->assertDatabaseHas('soil_analyses', ['sample_reference' => 'SOIL-1', 'owner_user_id' => $owner->id]);
        $this->assertDatabaseHas('training_courses', ['code' => 'TRN-1', 'owner_user_id' => $owner->id]);
    }

    public function test_child_resource_creation_requires_owned_parent(): void
    {
        $organization = $this->createOrganization('parent-org');
        $ownerA = $this->memberUser($organization, 'parent-a@wsa.test');
        $ownerB = $this->memberUser($organization, 'parent-b@wsa.test');

        $foreignFarm = Farm::create([
            'organization_id' => $organization->id,
            'owner_user_id' => $ownerA->id,
            'code' => 'PARENT',
            'name' => 'Parent Farm',
            'area_hectares' => 1,
            'is_active' => true,
        ]);

        $this->postJson('/api/v1/farm/regions', [
            'farm_id' => $foreignFarm->id,
            'code' => 'R1',
            'name' => 'Blocked Region',
            'area_hectares' => 1,
        ], $this->headers($ownerB, $organization))
            ->assertForbidden();
    }

    public function test_registration_is_disabled_when_configuration_is_off(): void
    {
        Config::set('app.allow_registration', false);

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Blocked',
            'email' => 'blocked@wsa.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertForbidden();
    }

    public function test_public_service_catalog_is_accessible_without_authentication(): void
    {
        $this->getJson('/api/v1/public/services')
            ->assertOk()
            ->assertJsonPath('platform', 'WSA Enterprise')
            ->assertJsonStructure(['service_modules', 'public_capabilities', 'protected_capabilities']);
    }

    public function test_protected_farm_endpoint_requires_authentication(): void
    {
        $this->getJson('/api/v1/farm/farms')->assertUnauthorized();
    }

    public function test_business_catalog_customer_ownership_and_isolation(): void
    {
        $organization = $this->createOrganization('business-org');
        $ownerA = $this->memberUser($organization, 'biz-a@wsa.test');
        $ownerB = $this->memberUser($organization, 'biz-b@wsa.test');

        $this->postJson('/api/v1/catalog/customers', [
            'code' => 'CUST-A',
            'name' => 'Customer A',
        ], $this->headers($ownerA, $organization))
            ->assertCreated()
            ->assertJsonPath('owner_user_id', $ownerA->id);

        $this->getJson('/api/v1/catalog/customers', $this->headers($ownerB, $organization))
            ->assertOk()
            ->assertJsonCount(0);

        Customer::unguarded(fn () => Customer::create([
            'organization_id' => $organization->id,
            'owner_user_id' => $ownerA->id,
            'code' => 'CUST-LEG',
            'name' => 'Legacy Customer',
        ]));

        $this->getJson('/api/v1/catalog/customers', $this->headers($ownerB, $organization))
            ->assertOk()
            ->assertJsonCount(0);
    }

    public function test_diagnosis_request_list_is_scoped_to_owner(): void
    {
        $organization = $this->createOrganization('diag-org');
        $ownerA = $this->memberUser($organization, 'diag-a@wsa.test');
        $ownerB = $this->memberUser($organization, 'diag-b@wsa.test');

        \App\Models\DiagnosisRequest::unguarded(fn () => \App\Models\DiagnosisRequest::create([
            'organization_id' => $organization->id,
            'user_id' => $ownerA->id,
            'owner_user_id' => $ownerA->id,
            'reference' => 'DR-001',
            'status' => 'completed',
        ]));

        $this->getJson('/api/v1/diagnosis/requests', $this->headers($ownerA, $organization))
            ->assertOk()
            ->assertJsonPath('data.0.reference', 'DR-001');

        $this->getJson('/api/v1/diagnosis/requests', $this->headers($ownerB, $organization))
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_workflow_summary_counts_are_scoped_to_owned_services_for_members(): void
    {
        $organization = $this->createOrganization('workflow-org');
        $ownerA = $this->memberUser($organization, 'wf-a@wsa.test');
        $ownerB = $this->memberUser($organization, 'wf-b@wsa.test');

        Farm::create([
            'organization_id' => $organization->id,
            'owner_user_id' => $ownerA->id,
            'code' => 'WF-A',
            'name' => 'Farm A',
            'area_hectares' => 1,
            'is_active' => true,
        ]);

        $this->getJson('/api/v1/platform/workflow-summary', $this->headers($ownerB, $organization))
            ->assertOk()
            ->assertJsonPath('scope', 'owned')
            ->assertJsonPath('farms', 0);

        $this->getJson('/api/v1/platform/workflow-summary', $this->headers($ownerA, $organization))
            ->assertOk()
            ->assertJsonPath('farms', 1);
    }

    public function test_legacy_null_owner_services_are_hidden_from_members(): void
    {
        $organization = $this->createOrganization('legacy-org');
        $member = $this->memberUser($organization, 'legacy-member@wsa.test');

        Farm::create([
            'organization_id' => $organization->id,
            'owner_user_id' => null,
            'code' => 'LEG',
            'name' => 'Legacy Farm',
            'area_hectares' => 1,
            'is_active' => true,
        ]);

        $this->getJson('/api/v1/farm/farms', $this->headers($member, $organization))
            ->assertOk()
            ->assertJsonCount(0);
    }

    public function test_beekeeping_apiary_and_hive_records_are_isolated_between_owners(): void
    {
        $organization = $this->createOrganization('bee-org');
        $ownerA = $this->memberUser($organization, 'bee-a@wsa.test');
        $ownerB = $this->memberUser($organization, 'bee-b@wsa.test');

        $profileA = \App\Models\BeekeeperProfile::unguarded(fn () => \App\Models\BeekeeperProfile::create([
            'organization_id' => $organization->id,
            'user_id' => $ownerA->id,
            'owner_user_id' => $ownerA->id,
            'display_name' => 'Beekeeper A',
        ]));

        $apiary = \App\Models\Apiary::unguarded(fn () => \App\Models\Apiary::create([
            'organization_id' => $organization->id,
            'owner_user_id' => $ownerA->id,
            'beekeeper_profile_id' => $profileA->id,
            'name' => 'Apiary A',
        ]));

        $this->getJson('/api/v1/beekeeping/apiaries', $this->headers($ownerB, $organization))
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $hive = \App\Models\Hive::unguarded(fn () => \App\Models\Hive::create([
            'organization_id' => $organization->id,
            'owner_user_id' => $ownerA->id,
            'apiary_id' => $apiary->id,
            'code' => 'H1',
        ]));

        $this->postJson("/api/v1/beekeeping/hives/{$hive->id}/treatments", [
            'treatment_type' => 'Oxalic acid',
            'applied_at' => now()->toDateString(),
        ], $this->headers($ownerB, $organization))
            ->assertForbidden();
    }

    public function test_jobs_talent_profile_is_user_global_and_cannot_be_spoofed(): void
    {
        $organization = $this->createOrganization('jobs-org');
        $talent = $this->memberUser($organization, 'talent@wsa.test');
        $other = $this->memberUser($organization, 'other@wsa.test');

        $this->putJson('/api/v1/jobs/talent/me', [
            'professional_name' => 'Talent One',
            'is_public' => true,
            'user_id' => $other->id,
        ], $this->headers($talent, $organization))
            ->assertOk()
            ->assertJsonPath('user_id', $talent->id);

        $this->getJson('/api/v1/jobs/talent/me', $this->headers($other, $organization))
            ->assertOk()
            ->assertExactJson([]);
    }

    public function test_inventory_balances_are_isolated_by_owner(): void
    {
        $organization = $this->createOrganization('inventory-org');
        $ownerA = $this->memberUser($organization, 'inv-a@wsa.test');
        $ownerB = $this->memberUser($organization, 'inv-b@wsa.test');

        $warehouse = \App\Models\Warehouse::unguarded(fn () => \App\Models\Warehouse::create([
            'organization_id' => $organization->id,
            'owner_user_id' => $ownerA->id,
            'code' => 'WH1',
            'name' => 'Main',
        ]));

        $product = \App\Models\Product::unguarded(fn () => \App\Models\Product::create([
            'organization_id' => $organization->id,
            'owner_user_id' => $ownerA->id,
            'sku' => 'SKU1',
            'name' => 'Product 1',
        ]));

        \App\Models\InventoryBalance::unguarded(fn () => \App\Models\InventoryBalance::create([
            'organization_id' => $organization->id,
            'owner_user_id' => $ownerA->id,
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'quantity' => 10,
        ]));

        $this->getJson('/api/v1/inventory', $this->headers($ownerB, $organization))
            ->assertOk()
            ->assertJsonCount(0);
    }

    public function test_protected_jobs_contact_payment_requires_authentication(): void
    {
        $this->postJson('/api/v1/jobs/contact-requests/1/pay', [
            'idempotency_key' => 'pay-test-key',
        ])->assertUnauthorized();
    }

    public function test_sales_order_cannot_reference_another_owners_product(): void
    {
        $organization = $this->createOrganization('commerce-org');
        $ownerA = $this->memberUser($organization, 'commerce-a@wsa.test');
        $ownerB = $this->memberUser($organization, 'commerce-b@wsa.test');

        $customerB = Customer::unguarded(fn () => Customer::create([
            'organization_id' => $organization->id,
            'owner_user_id' => $ownerB->id,
            'code' => 'C-B',
            'name' => 'Customer B',
        ]));

        $productA = \App\Models\Product::unguarded(fn () => \App\Models\Product::create([
            'organization_id' => $organization->id,
            'owner_user_id' => $ownerA->id,
            'sku' => 'SKU-A',
            'name' => 'Product A',
        ]));

        $this->postJson('/api/v1/sales-orders', [
            'customer_id' => $customerB->id,
            'number' => 'SO-001',
            'items' => [
                ['product_id' => $productA->id, 'quantity' => 1, 'unit_price' => 10],
            ],
        ], $this->headers($ownerB, $organization))
            ->assertForbidden();
    }

    public function test_jobs_contact_payment_is_limited_to_requester_without_supervise(): void
    {
        $organization = $this->createOrganization('jobs-pay-org');
        $employerA = $this->memberUser($organization, 'employer-a@wsa.test');
        $employerB = $this->memberUser($organization, 'employer-b@wsa.test');

        $jobsRole = Role::create([
            'organization_id' => $organization->id,
            'name' => 'Employer',
            'slug' => 'employer-only',
        ]);
        $jobsRole->permissions()->sync(
            \App\Models\Permission::where('organization_id', $organization->id)
                ->whereIn('name', ['jobs.view', 'jobs.manage'])
                ->pluck('id')
        );
        foreach ([$employerA, $employerB] as $employer) {
            $employer->roles()->sync([$jobsRole->id => ['organization_id' => $organization->id]]);
            app(PermissionService::class)->forget($employer, $organization->id);
        }

        $talent = User::create([
            'name' => 'Talent',
            'email' => 'talent-jobs-pay@wsa.test',
            'password' => Hash::make('password'),
        ]);
        $profile = \App\Models\JobTalentProfile::unguarded(fn () => \App\Models\JobTalentProfile::create([
            'user_id' => $talent->id,
            'professional_name' => 'Engineer',
            'is_public' => true,
            'employment_status' => 'available',
        ]));

        $requestId = $this->postJson("/api/v1/jobs/candidates/{$profile->id}/contact-requests", [
            'employer_contact' => [
                'name' => 'Employer A',
                'email' => 'employer-a@wsa.test',
            ],
        ], $this->headers($employerA, $organization))
            ->assertCreated()
            ->json('id');

        $this->postJson("/api/v1/jobs/contact-requests/{$requestId}/pay", [
            'idempotency_key' => 'ownership-pay-key',
        ], $this->headers($employerB, $organization))
            ->assertForbidden();
    }
}
