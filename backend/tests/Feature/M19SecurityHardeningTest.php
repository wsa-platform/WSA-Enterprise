<?php

namespace Tests\Feature;

use App\Models\AiConversation;
use App\Models\AiVisionUpload;
use App\Models\CropType;
use App\Models\Customer;
use App\Models\Farm;
use App\Models\LibraryItem;
use App\Models\MarketingAudienceSegment;
use App\Models\Organization;
use App\Models\Role;
use App\Models\SalesOrder;
use App\Models\SoilAnalysis;
use App\Models\TrainingCourse;
use App\Models\User;
use App\Services\Authorization\EnterpriseRoleService;
use App\Services\Authorization\PermissionService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('security')]
class M19SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('app.allow_registration', true);
    }

    private function createOrganization(string $slug = 'sec-org'): Organization
    {
        $organization = Organization::create([
            'name' => 'Security Org',
            'slug' => $slug,
        ]);
        EnterpriseRoleService::seedForOrganization($organization->id);

        return $organization;
    }

    private function attachRole(User $user, Organization $organization, string $slug): void
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
            'name' => 'Member',
            'email' => $email,
            'password' => Hash::make('password'),
        ]);
        $this->attachRole($user, $organization, 'member');

        return $user;
    }

    private function headers(User $user, Organization $organization): array
    {
        return [
            'Authorization' => 'Bearer '.$user->createToken('m19-security')->plainTextToken,
            'X-Organization-Id' => (string) $organization->id,
        ];
    }

    public function test_member_analytics_counts_are_limited_to_owned_services(): void
    {
        $organization = $this->createOrganization('analytics-member-org');
        $ownerA = $this->memberUser($organization, 'analytics-a@wsa.test');
        $ownerB = $this->memberUser($organization, 'analytics-b@wsa.test');

        Farm::create([
            'organization_id' => $organization->id,
            'owner_user_id' => $ownerA->id,
            'code' => 'FA',
            'name' => 'Farm A',
            'area_hectares' => 1,
            'is_active' => true,
        ]);

        $this->getJson('/api/v1/analytics/overview', $this->headers($ownerB, $organization))
            ->assertOk()
            ->assertJsonPath('scope', 'owned')
            ->assertJsonPath('farms.total', 0)
            ->assertJsonPath('users', null)
            ->assertJsonPath('audit', null);
    }

    public function test_supervisor_analytics_counts_remain_organization_wide(): void
    {
        $organization = $this->createOrganization('analytics-manager-org');
        $ownerA = $this->memberUser($organization, 'mgr-owner@wsa.test');
        $manager = User::create([
            'name' => 'Manager',
            'email' => 'mgr-analytics@wsa.test',
            'password' => Hash::make('password'),
        ]);
        $this->attachRole($manager, $organization, 'manager');

        Farm::create([
            'organization_id' => $organization->id,
            'owner_user_id' => $ownerA->id,
            'code' => 'MF',
            'name' => 'Managed Farm',
            'area_hectares' => 1,
            'is_active' => true,
        ]);

        $this->getJson('/api/v1/analytics/overview', $this->headers($manager, $organization))
            ->assertOk()
            ->assertJsonPath('scope', 'organization')
            ->assertJsonPath('farms.total', 1);
    }

    public function test_public_library_browse_requires_organization_boundary(): void
    {
        $orgA = $this->createOrganization('public-org-a');
        $orgB = $this->createOrganization('public-org-b');
        $owner = User::factory()->create();

        LibraryItem::unguarded(fn () => LibraryItem::create([
            'organization_id' => $orgA->id,
            'owner_user_id' => $owner->id,
            'slug' => 'item-a',
            'title' => 'Org A Item',
            'publication_status' => 'published',
            'published_at' => now(),
        ]));

        LibraryItem::unguarded(fn () => LibraryItem::create([
            'organization_id' => $orgB->id,
            'owner_user_id' => $owner->id,
            'slug' => 'item-b',
            'title' => 'Org B Item',
            'publication_status' => 'published',
            'published_at' => now(),
        ]));

        $this->getJson('/api/v1/public/library/items?organization='.$orgA->slug)
            ->assertOk()
            ->assertJsonPath('organization_slug', $orgA->slug)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Org A Item');

        $this->getJson('/api/v1/public/library/items')
            ->assertStatus(422);
    }

    public function test_public_training_browse_is_scoped_to_organization(): void
    {
        $orgA = $this->createOrganization('training-public-a');
        $orgB = $this->createOrganization('training-public-b');
        $owner = User::factory()->create();

        TrainingCourse::unguarded(fn () => TrainingCourse::create([
            'organization_id' => $orgA->id,
            'owner_user_id' => $owner->id,
            'code' => 'A-101',
            'title' => 'Course A',
            'status' => 'published',
        ]));

        TrainingCourse::unguarded(fn () => TrainingCourse::create([
            'organization_id' => $orgB->id,
            'owner_user_id' => $owner->id,
            'code' => 'B-101',
            'title' => 'Course B',
            'status' => 'published',
        ]));

        $this->getJson('/api/v1/public/training/courses?organization='.$orgB->slug)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Course B');
    }

    public function test_members_cannot_list_marketing_consents(): void
    {
        $organization = $this->createOrganization('consent-org');
        $member = $this->memberUser($organization, 'consent-member@wsa.test');

        $this->getJson('/api/v1/marketing/consents', $this->headers($member, $organization))
            ->assertForbidden();
    }

    public function test_marketing_managers_can_list_marketing_consents(): void
    {
        $organization = $this->createOrganization('consent-manager-org');
        $manager = User::create([
            'name' => 'Marketing Manager',
            'email' => 'consent-mgr@wsa.test',
            'password' => Hash::make('password'),
        ]);
        $this->attachRole($manager, $organization, 'manager');

        $this->getJson('/api/v1/marketing/consents', $this->headers($manager, $organization))
            ->assertOk();
    }

    public function test_user_cannot_update_or_delete_another_users_crop_type(): void
    {
        $organization = $this->createOrganization('crop-org');
        $ownerA = $this->memberUser($organization, 'crop-a@wsa.test');
        $ownerB = $this->memberUser($organization, 'crop-b@wsa.test');

        $crop = CropType::unguarded(fn () => CropType::create([
            'organization_id' => $organization->id,
            'owner_user_id' => $ownerA->id,
            'code' => 'TOM',
            'name' => 'Tomato',
        ]));

        $this->putJson('/api/v1/crop/types/'.$crop->id, [
            'code' => 'TOM',
            'name' => 'Stolen',
        ], $this->headers($ownerB, $organization))
            ->assertNotFound();

        $this->deleteJson('/api/v1/crop/types/'.$crop->id, [], $this->headers($ownerB, $organization))
            ->assertNotFound();
    }

    public function test_user_cannot_update_or_delete_another_users_soil_analysis(): void
    {
        $organization = $this->createOrganization('soil-org');
        $ownerA = $this->memberUser($organization, 'soil-a@wsa.test');
        $ownerB = $this->memberUser($organization, 'soil-b@wsa.test');

        $analysis = SoilAnalysis::unguarded(fn () => SoilAnalysis::create([
            'organization_id' => $organization->id,
            'owner_user_id' => $ownerA->id,
            'sample_reference' => 'SOIL-1',
            'sampled_at' => now()->toDateString(),
        ]));

        $this->putJson('/api/v1/soil/analyses/'.$analysis->id, [
            'sample_reference' => 'SOIL-1',
            'sampled_at' => now()->toDateString(),
            'notes' => 'Stolen',
        ], $this->headers($ownerB, $organization))
            ->assertNotFound();

        $this->deleteJson('/api/v1/soil/analyses/'.$analysis->id, [], $this->headers($ownerB, $organization))
            ->assertNotFound();
    }

    public function test_ai_assistant_conversations_are_isolated_between_users(): void
    {
        $organization = $this->createOrganization('assistant-org');
        $ownerA = $this->memberUser($organization, 'assistant-a@wsa.test');
        $ownerB = $this->memberUser($organization, 'assistant-b@wsa.test');

        $conversation = AiConversation::unguarded(fn () => AiConversation::create([
            'organization_id' => $organization->id,
            'owner_user_id' => $ownerA->id,
            'user_id' => $ownerA->id,
            'domain' => 'farm',
            'title' => 'Private chat',
        ]));

        $this->getJson('/api/v1/ai/assistant/conversations/'.$conversation->id, $this->headers($ownerB, $organization))
            ->assertForbidden();
    }

    public function test_ai_vision_uploads_are_isolated_between_users(): void
    {
        $organization = $this->createOrganization('vision-org');
        $ownerA = $this->memberUser($organization, 'vision-a@wsa.test');
        $ownerB = $this->memberUser($organization, 'vision-b@wsa.test');

        $upload = AiVisionUpload::unguarded(fn () => AiVisionUpload::create([
            'organization_id' => $organization->id,
            'owner_user_id' => $ownerA->id,
            'user_id' => $ownerA->id,
            'storage_path' => 'vision/test.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 100,
        ]));

        $this->getJson('/api/v1/ai/vision/uploads/'.$upload->id, $this->headers($ownerB, $organization))
            ->assertForbidden();
    }

    public function test_marketing_campaign_rejects_foreign_owned_segment(): void
    {
        $organization = $this->createOrganization('campaign-fk-org');
        $ownerA = $this->memberUser($organization, 'campaign-a@wsa.test');
        $ownerB = $this->memberUser($organization, 'campaign-b@wsa.test');

        $segment = MarketingAudienceSegment::unguarded(fn () => MarketingAudienceSegment::create([
            'organization_id' => $organization->id,
            'owner_user_id' => $ownerA->id,
            'created_by_user_id' => $ownerA->id,
            'name' => 'Segment A',
            'criteria' => [],
        ]));

        $this->postJson('/api/v1/marketing/campaigns', [
            'name' => 'Cross-owner campaign',
            'channel' => 'email',
            'audience_segment_id' => $segment->id,
        ], $this->headers($ownerB, $organization))
            ->assertForbidden();
    }

    public function test_marketing_segments_list_is_scoped_to_owner(): void
    {
        $organization = $this->createOrganization('segment-org');
        $ownerA = $this->memberUser($organization, 'segment-a@wsa.test');
        $ownerB = $this->memberUser($organization, 'segment-b@wsa.test');

        MarketingAudienceSegment::unguarded(fn () => MarketingAudienceSegment::create([
            'organization_id' => $organization->id,
            'owner_user_id' => $ownerA->id,
            'created_by_user_id' => $ownerA->id,
            'name' => 'Segment A',
            'criteria' => [],
        ]));

        $this->getJson('/api/v1/marketing/segments', $this->headers($ownerB, $organization))
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_training_enrollment_rejects_another_owners_course(): void
    {
        $organization = $this->createOrganization('training-enroll-org');
        $ownerA = $this->memberUser($organization, 'training-a@wsa.test');
        $ownerB = $this->memberUser($organization, 'training-b@wsa.test');

        $course = TrainingCourse::unguarded(fn () => TrainingCourse::create([
            'organization_id' => $organization->id,
            'owner_user_id' => $ownerA->id,
            'code' => 'C-101',
            'title' => 'Private course',
            'status' => 'published',
        ]));

        $this->postJson('/api/v1/training/enrollments', [
            'course_id' => $course->id,
        ], $this->headers($ownerB, $organization))
            ->assertForbidden();
    }

    public function test_invoice_rejects_foreign_owned_sales_order_reference(): void
    {
        $organization = $this->createOrganization('invoice-fk-org');
        $ownerA = $this->memberUser($organization, 'invoice-a@wsa.test');
        $ownerB = $this->memberUser($organization, 'invoice-b@wsa.test');

        $customerB = Customer::unguarded(fn () => Customer::create([
            'organization_id' => $organization->id,
            'owner_user_id' => $ownerB->id,
            'code' => 'CUST-B',
            'name' => 'Customer B',
        ]));

        $salesOrderA = SalesOrder::unguarded(fn () => SalesOrder::create([
            'organization_id' => $organization->id,
            'owner_user_id' => $ownerA->id,
            'customer_id' => $customerB->id,
            'number' => 'SO-A',
            'subtotal' => 10,
            'tax_total' => 0,
            'total' => 10,
        ]));

        $this->postJson('/api/v1/invoices', [
            'customer_id' => $customerB->id,
            'sales_order_id' => $salesOrderA->id,
            'number' => 'INV-1',
            'items' => [
                ['description' => 'Line', 'quantity' => 1, 'unit_price' => 10],
            ],
        ], $this->headers($ownerB, $organization))
            ->assertForbidden();
    }

    public function test_legacy_null_owner_farm_is_hidden_from_members_but_visible_to_supervisors(): void
    {
        $organization = $this->createOrganization('legacy-null-org');
        $member = $this->memberUser($organization, 'legacy-member@wsa.test');
        $manager = User::create([
            'name' => 'Supervisor',
            'email' => 'legacy-mgr@wsa.test',
            'password' => Hash::make('password'),
        ]);
        $this->attachRole($manager, $organization, 'manager');

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

        $this->getJson('/api/v1/farm/farms', $this->headers($manager, $organization))
            ->assertOk()
            ->assertJsonCount(1);
    }

    public function test_gps_coordinate_creation_requires_owned_farm_parent(): void
    {
        $organization = $this->createOrganization('gps-org');
        $ownerA = $this->memberUser($organization, 'gps-a@wsa.test');
        $ownerB = $this->memberUser($organization, 'gps-b@wsa.test');

        $farm = Farm::create([
            'organization_id' => $organization->id,
            'owner_user_id' => $ownerA->id,
            'code' => 'GPS-F',
            'name' => 'GPS Farm',
            'area_hectares' => 1,
            'is_active' => true,
        ]);

        $this->postJson('/api/v1/farm/gps-coordinates', [
            'coordinateable_type' => Farm::class,
            'coordinateable_id' => $farm->id,
            'latitude' => 30.0,
            'longitude' => 31.0,
        ], $this->headers($ownerB, $organization))
            ->assertForbidden();
    }

    public function test_seeded_admin_analytics_remain_organization_scoped_across_tenants(): void
    {
        $this->seed(DatabaseSeeder::class);

        $orgA = Organization::first();
        $orgB = Organization::create(['name' => 'Analytics Org B', 'slug' => 'analytics-org-b']);
        $admin = User::where('email', 'admin@wsa.test')->first();
        $admin->organizations()->syncWithoutDetaching([$orgB->id]);

        Farm::create(['organization_id' => $orgA->id, 'owner_user_id' => $admin->id, 'code' => 'A-F1', 'name' => 'Farm A', 'area_hectares' => 1, 'is_active' => true]);
        Farm::create(['organization_id' => $orgB->id, 'owner_user_id' => $admin->id, 'code' => 'B-F1', 'name' => 'Farm B', 'area_hectares' => 1, 'is_active' => true]);

        $token = $admin->createToken('analytics-admin')->plainTextToken;

        $this->getJson('/api/v1/analytics/overview', [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $orgA->id,
        ])
            ->assertOk()
            ->assertJsonPath('scope', 'organization')
            ->assertJsonPath('farms.total', Farm::where('organization_id', $orgA->id)->count());
    }
}
