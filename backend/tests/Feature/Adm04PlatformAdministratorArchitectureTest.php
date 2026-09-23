<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\JobSeekerProfile;
use App\Models\LibraryItem;
use App\Models\MarketplaceListing;
use App\Models\Organization;
use App\Models\PlatformPermission;
use App\Models\User;
use App\Services\Authorization\EnterpriseRoleService;
use App\Services\Authorization\PlatformRbacService;
use App\Services\Recruitment\JobSeekerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class Adm04PlatformAdministratorArchitectureTest extends TestCase
{
    use RefreshDatabase;

    private PlatformRbacService $rbac;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rbac = app(PlatformRbacService::class);
        $this->rbac->bootstrapCatalog();
    }

    public function test_organization_star_is_not_platform_administrator_identity(): void
    {
        [$orgAdmin] = $this->makeOrganizationAdmin();

        $this->assertFalse($orgAdmin->isPlatformAdministrator());
        Sanctum::actingAs($orgAdmin);

        $this->getJson('/api/v1/admin/me')
            ->assertOk()
            ->assertJsonPath('is_platform_administrator', false)
            ->assertJsonPath('platform_permissions', []);
    }

    public function test_organization_admin_receives_403_on_platform_admin_api(): void
    {
        [$orgAdmin] = $this->makeOrganizationAdmin();
        Sanctum::actingAs($orgAdmin);

        $this->getJson('/api/v1/admin/organizations')->assertForbidden();
        $this->getJson('/api/v1/platform/admin/organizations')->assertForbidden();
    }

    public function test_organization_owner_receives_403_without_platform_identity(): void
    {
        [$owner, $organization] = $this->makeOrganizationAdmin();
        app(EnterpriseRoleService::class)->assignDefaultOwner($owner, $organization);
        $this->assertFalse($owner->fresh()->isPlatformAdministrator());

        Sanctum::actingAs($owner);
        $this->getJson('/api/v1/admin/users')->assertForbidden();
    }

    public function test_platform_admin_can_exist_without_any_organization_membership(): void
    {
        $admin = $this->makePlatformAdmin();
        $this->assertSame(0, $admin->organizations()->count());

        Sanctum::actingAs($admin);
        $this->getJson('/api/v1/admin/organizations')
            ->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_platform_admin_can_administer_organization_without_membership(): void
    {
        $target = Organization::create(['name' => 'Target Org', 'slug' => 'target-org', 'is_active' => true]);
        $admin = $this->makePlatformAdmin();
        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/admin/organizations/'.$target->id)
            ->assertOk()
            ->assertJsonPath('slug', 'target-org');

        $this->patchJson('/api/v1/admin/organizations/'.$target->id, ['name' => 'Target Renamed'])
            ->assertOk()
            ->assertJsonPath('name', 'Target Renamed');
    }

    public function test_spoofed_x_organization_id_cannot_create_platform_authority(): void
    {
        [$orgAdmin, $organization] = $this->makeOrganizationAdmin();
        Sanctum::actingAs($orgAdmin);

        $this->withHeaders(['X-Organization-Id' => (string) $organization->id])
            ->getJson('/api/v1/admin/organizations')
            ->assertForbidden();
    }

    public function test_platform_admin_x_organization_id_of_non_member_does_not_block_platform_api(): void
    {
        $foreign = Organization::create(['name' => 'Foreign', 'slug' => 'foreign-org', 'is_active' => true]);
        $admin = $this->makePlatformAdmin();
        Sanctum::actingAs($admin);

        $this->withHeaders(['X-Organization-Id' => (string) $foreign->id])
            ->getJson('/api/v1/admin/organizations')
            ->assertOk();
    }

    public function test_platform_permission_is_required_for_protected_operation(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_platform_administrator' => true])->save();
        $this->rbac->grantPlatformRole($admin, 'platform_auditor');

        Sanctum::actingAs($admin);
        $this->getJson('/api/v1/admin/organizations')->assertOk();
        $this->postJson('/api/v1/admin/organizations', ['name' => 'No'])
            ->assertForbidden();
    }

    public function test_different_platform_roles_have_different_permissions(): void
    {
        $full = $this->makePlatformAdmin();
        $auditor = User::factory()->create();
        $auditor->forceFill(['is_platform_administrator' => true])->save();
        $this->rbac->grantPlatformRole($auditor, 'platform_auditor');

        $fullPerms = $this->rbac->permissionsFor($full);
        $auditorPerms = $this->rbac->permissionsFor($auditor);

        $this->assertContains('platform.organizations.manage', $fullPerms);
        $this->assertNotContains('platform.organizations.manage', $auditorPerms);
        $this->assertContains('platform.audit.view', $auditorPerms);
    }

    public function test_organization_star_still_works_on_organization_apis(): void
    {
        [$orgAdmin, $organization] = $this->makeOrganizationAdmin();
        Sanctum::actingAs($orgAdmin);

        $this->withHeaders(['X-Organization-Id' => (string) $organization->id])
            ->getJson('/api/v1/platform/me')
            ->assertOk()
            ->assertJsonFragment(['*']);
    }

    public function test_platform_org_audit_uses_null_organization_id(): void
    {
        $admin = $this->makePlatformAdmin();
        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/admin/organizations', [
            'name' => 'Audited Org',
            'slug' => 'audited-org',
        ])->assertCreated();

        $log = AuditLog::withoutGlobalScopes()->where('action', 'admin.org.created')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertNull($log->organization_id);
        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame('audited-org', $log->new_values['slug'] ?? null);
        $this->assertArrayNotHasKey('password', $log->new_values ?? []);
    }

    public function test_denied_platform_authorization_is_audited(): void
    {
        [$orgAdmin] = $this->makeOrganizationAdmin();
        Sanctum::actingAs($orgAdmin);

        $this->getJson('/api/v1/admin/organizations')->assertForbidden();

        $log = AuditLog::withoutGlobalScopes()->where('action', 'admin.denied')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertNull($log->organization_id);
        $this->assertSame($orgAdmin->id, $log->user_id);
    }

    public function test_jobs_overlay_authorization_and_audit(): void
    {
        $seekerUser = User::factory()->create();
        $profile = app(JobSeekerService::class)->upsertForUser($seekerUser, [
            'full_name' => 'Overlay Seeker',
            'email' => $seekerUser->email,
        ]);

        [$orgAdmin] = $this->makeOrganizationAdmin();
        Sanctum::actingAs($orgAdmin);
        $this->getJson('/api/v1/admin/jobs/seekers')->assertForbidden();

        $admin = $this->makePlatformAdmin();
        Sanctum::actingAs($admin);
        $this->getJson('/api/v1/admin/jobs/seekers')
            ->assertOk()
            ->assertJsonFragment(['id' => $profile->id]);

        $this->patchJson('/api/v1/admin/jobs/seekers/'.$profile->id.'/status', [
            'status' => JobSeekerProfile::STATUS_UNDER_REVIEW,
        ])->assertOk();

        $jobAudit = AuditLog::withoutGlobalScopes()->where('action', 'admin.jobs.status')->latest('id')->first();
        $this->assertNotNull($jobAudit);
        $this->assertNull($jobAudit->organization_id);

        Sanctum::actingAs($orgAdmin);
        $this->getJson('/api/v1/admin/jobs/seekers/'.$profile->id.'/notes')->assertForbidden();
        $this->getJson('/api/v1/admin/jobs/seekers/'.$profile->id.'/history')->assertForbidden();

        Sanctum::actingAs($admin);
        $this->getJson('/api/v1/admin/jobs/seekers/'.$profile->id)
            ->assertOk()
            ->assertJsonPath('id', $profile->id);

        $this->postJson('/api/v1/admin/jobs/seekers/'.$profile->id.'/notes', [
            'body' => 'Platform admin note',
        ])->assertCreated()->assertJsonPath('body', 'Platform admin note');

        $this->getJson('/api/v1/admin/jobs/seekers/'.$profile->id.'/notes')
            ->assertOk()
            ->assertJsonFragment(['body' => 'Platform admin note']);

        $this->getJson('/api/v1/admin/jobs/seekers/'.$profile->id.'/history')
            ->assertOk()
            ->assertJsonStructure(['data']);

        $noteAudit = AuditLog::withoutGlobalScopes()->where('action', 'admin.jobs.note')->latest('id')->first();
        $this->assertNotNull($noteAudit);
        $this->assertNull($noteAudit->organization_id);
    }

    public function test_platform_role_management_does_not_use_organization_roles(): void
    {
        [$orgAdmin] = $this->makeOrganizationAdmin();
        Sanctum::actingAs($orgAdmin);
        $this->getJson('/api/v1/admin/roles')->assertForbidden();
        $this->postJson('/api/v1/admin/roles', [
            'slug' => 'should-fail',
            'name' => 'Should Fail',
        ])->assertForbidden();

        $admin = $this->makePlatformAdmin();
        Sanctum::actingAs($admin);

        $created = $this->postJson('/api/v1/admin/roles', [
            'slug' => 'platform_reviewer',
            'name' => 'Platform Reviewer',
        ])->assertCreated()->json();

        $this->assertDatabaseHas('platform_roles', ['slug' => 'platform_reviewer']);
        $this->assertDatabaseMissing('roles', ['slug' => 'platform_reviewer']);

        $permissionId = PlatformPermission::query()
            ->where('name', 'platform.audit.view')
            ->value('id');

        $this->patchJson('/api/v1/admin/roles/'.$created['id'], [
            'permission_ids' => [$permissionId],
        ])->assertOk();

        $roleAudit = AuditLog::withoutGlobalScopes()->where('action', 'admin.role.created')->latest('id')->first();
        $this->assertNotNull($roleAudit);
        $this->assertNull($roleAudit->organization_id);
    }

    public function test_marketplace_overlay_authorization(): void
    {
        $org = Organization::create(['name' => 'Mkt Org', 'slug' => 'mkt-org']);
        $seller = User::factory()->create();
        $listing = MarketplaceListing::create([
            'seller_user_id' => $seller->id,
            'organization_id' => $org->id,
            'title' => 'Admin Overlay Listing',
            'seller_display_name' => $seller->name,
            'status' => MarketplaceListing::STATUS_DRAFT,
        ]);

        [$orgAdmin] = $this->makeOrganizationAdmin();
        Sanctum::actingAs($orgAdmin);
        $this->getJson('/api/v1/admin/marketplace/listings')->assertForbidden();

        $admin = $this->makePlatformAdmin();
        Sanctum::actingAs($admin);
        $this->getJson('/api/v1/admin/marketplace/listings')
            ->assertOk()
            ->assertJsonFragment(['id' => $listing->id]);

        $this->postJson('/api/v1/admin/marketplace/listings/'.$listing->id.'/approve')
            ->assertOk();

        $log = AuditLog::withoutGlobalScopes()->where('action', 'admin.mkt.moderate')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertNull($log->organization_id);
    }

    public function test_library_admin_overlay_upload_and_page_read_unchanged(): void
    {
        Storage::fake('local');
        $storageOrg = Organization::create(['name' => 'WSA Demo', 'slug' => 'wsa-demo']);

        $reader = User::factory()->create();
        $reader->organizations()->attach($storageOrg->id, ['role' => 'member', 'is_active' => true]);
        Sanctum::actingAs($reader);

        $this->getJson('/api/v1/library/items')->assertOk();
        $this->getJson('/api/v1/admin/library/items')->assertForbidden();

        $this->getJson('/api/v1/library/items')->assertOk();

        $admin = $this->makePlatformAdmin();
        Sanctum::actingAs($admin);

        $file = UploadedFile::fake()->create('research.pdf', 32, 'application/pdf');
        $created = $this->post('/api/v1/admin/library/items', [
            'title' => 'Admin Research',
            'item_type' => 'research',
            'file' => $file,
        ], ['Accept' => 'application/json'])->assertCreated()->json();

        $this->assertSame('research', $created['item_type']);
        $this->assertNull($created['owner_user_id']);

        $item = LibraryItem::query()->findOrFail($created['id']);
        $this->assertSame($storageOrg->id, $item->organization_id);
        $this->assertNotNull($item->file_path);

        Sanctum::actingAs($reader);
        $libraryPage = $this->getJson('/api/v1/library/items')->assertOk()->json();
        $libraryRows = is_array($libraryPage) && array_is_list($libraryPage)
            ? $libraryPage
            : ($libraryPage['data'] ?? []);
        $ids = collect($libraryRows)->pluck('id');
        $this->assertTrue($ids->contains($item->id));

        $log = AuditLog::withoutGlobalScopes()->where('action', 'admin.lib.created')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertNull($log->organization_id);
    }

    public function test_unauthenticated_user_cannot_access_library_page_or_admin_library(): void
    {
        $this->getJson('/api/v1/library/items')->assertUnauthorized();
        $this->getJson('/api/v1/admin/library/items')->assertUnauthorized();
    }

    public function test_platform_settings_are_not_organization_settings(): void
    {
        [$orgAdmin, $organization] = $this->makeOrganizationAdmin();
        Sanctum::actingAs($orgAdmin);
        $this->withHeaders(['X-Organization-Id' => (string) $organization->id])
            ->getJson('/api/v1/admin/settings')
            ->assertForbidden();

        $admin = $this->makePlatformAdmin();
        Sanctum::actingAs($admin);
        $this->putJson('/api/v1/admin/settings', [
            'settings' => ['maintenance.mode' => false],
        ])->assertOk();

        $log = AuditLog::withoutGlobalScopes()->where('action', 'admin.settings.upd')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertNull($log->organization_id);
    }

    public function test_login_of_platform_admin_emits_admin_login_audit(): void
    {
        $admin = $this->makePlatformAdmin(['email' => 'plat-login@wsa.test']);
        $admin->forceFill(['password' => 'password'])->save();

        $this->postJson('/api/v1/auth/login', [
            'email' => 'plat-login@wsa.test',
            'password' => 'password',
            'device_name' => 'test',
        ])->assertOk()->assertJsonPath('user.is_platform_administrator', true);

        $log = AuditLog::withoutGlobalScopes()->where('action', 'admin.login')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertNull($log->organization_id);
    }

    /**
     * @return array{0: User, 1: Organization}
     */
    private function makeOrganizationAdmin(): array
    {
        $organization = Organization::create([
            'name' => 'Org Admin Workspace',
            'slug' => 'org-admin-'.uniqid(),
            'is_active' => true,
        ]);
        EnterpriseRoleService::seedForOrganization($organization->id);

        $user = User::factory()->create([
            'is_platform_administrator' => false,
        ]);
        $organization->members()->attach($user->id, ['role' => 'admin', 'is_active' => true]);

        return [$user->fresh(), $organization];
    }

    private function makePlatformAdmin(array $overrides = []): User
    {
        $user = User::factory()->create($overrides);
        $this->rbac->grantPlatformAdministrator($user);

        return $user->fresh();
    }
}
