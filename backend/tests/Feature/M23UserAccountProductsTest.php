<?php

namespace Tests\Feature;

use App\Models\MarketplaceListing;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Services\Authorization\EnterpriseRoleService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class M23UserAccountProductsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_unauthenticated_user_cannot_access_account_or_listing_management(): void
    {
        $this->patchJson('/api/v1/account/profile', ['name' => 'Hacker'])->assertUnauthorized();
        $this->getJson('/api/v1/market/my-listings')->assertUnauthorized();
        $this->postJson('/api/v1/market/listings', ['title' => 'Nope'])->assertUnauthorized();
    }

    public function test_user_can_read_own_listings_and_not_another_users(): void
    {
        [$org, $owner, $intruder] = $this->twoMembers();
        $own = $this->createListing($org, $owner, 'Own product', MarketplaceListing::STATUS_DRAFT);
        $foreign = $this->createListing($org, $intruder, 'Foreign product', MarketplaceListing::STATUS_DRAFT);

        $this->getJson('/api/v1/market/my-listings', $this->headers($owner, $org))
            ->assertOk()
            ->assertJsonFragment(['id' => $own->id, 'title' => 'Own product'])
            ->assertJsonMissing(['id' => $foreign->id]);

        $this->getJson('/api/v1/market/my-listings/'.$own->id, $this->headers($owner, $org))
            ->assertOk()
            ->assertJsonPath('id', $own->id);

        $this->getJson('/api/v1/market/my-listings/'.$foreign->id, $this->headers($owner, $org))
            ->assertForbidden();
    }

    public function test_user_cannot_edit_hide_delete_or_submit_another_users_listing(): void
    {
        [$org, $owner, $intruder] = $this->twoMembers();
        $foreignDraft = $this->createListing($org, $intruder, 'Foreign draft', MarketplaceListing::STATUS_DRAFT);
        $foreignPublished = $this->createListing($org, $intruder, 'Foreign live', MarketplaceListing::STATUS_PUBLISHED);

        $ownerHeaders = $this->headers($owner, $org);

        $this->patchJson('/api/v1/market/listings/'.$foreignDraft->id, ['title' => 'Stolen'], $ownerHeaders)
            ->assertForbidden();
        $this->postJson('/api/v1/market/listings/'.$foreignDraft->id.'/submit', [], $ownerHeaders)
            ->assertForbidden();
        $this->postJson('/api/v1/market/listings/'.$foreignPublished->id.'/unpublish', [], $ownerHeaders)
            ->assertForbidden();
        $this->deleteJson('/api/v1/market/listings/'.$foreignDraft->id, [], $ownerHeaders)
            ->assertForbidden();
    }

    public function test_user_can_create_edit_hide_and_delete_own_listing(): void
    {
        [$org, $owner] = $this->oneMember();
        $headers = $this->headers($owner, $org);

        $create = $this->postJson('/api/v1/market/listings', [
            'title' => 'Tomatoes',
            'description' => 'Fresh',
            'seller_user_id' => 999999,
            'status' => 'published',
        ], $headers);

        $create->assertCreated()
            ->assertJsonPath('title', 'Tomatoes')
            ->assertJsonPath('status', MarketplaceListing::STATUS_DRAFT);

        $listingId = $create->json('id');
        $this->assertSame($owner->id, MarketplaceListing::query()->findOrFail($listingId)->seller_user_id);

        $this->patchJson('/api/v1/market/listings/'.$listingId, ['title' => 'Tomatoes updated'], $headers)
            ->assertOk()
            ->assertJsonPath('title', 'Tomatoes updated');

        $this->postJson('/api/v1/market/listings/'.$listingId.'/submit', [], $headers)
            ->assertOk()
            ->assertJsonPath('status', MarketplaceListing::STATUS_PENDING_REVIEW);

        MarketplaceListing::query()->whereKey($listingId)->update([
            'status' => MarketplaceListing::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);

        $this->getJson('/api/v1/public/market/listings')
            ->assertOk()
            ->assertJsonFragment(['id' => $listingId]);

        $this->postJson('/api/v1/market/listings/'.$listingId.'/unpublish', [], $headers)
            ->assertOk()
            ->assertJsonPath('status', MarketplaceListing::STATUS_UNPUBLISHED);

        $this->getJson('/api/v1/public/market/listings')
            ->assertOk()
            ->assertJsonMissing(['id' => $listingId]);

        $this->getJson('/api/v1/public/market/listings/'.$listingId)
            ->assertNotFound();

        $this->deleteJson('/api/v1/market/listings/'.$listingId, [], $headers)
            ->assertOk();

        $this->assertSoftDeleted('marketplace_listings', ['id' => $listingId]);
    }

    public function test_draft_and_unpublished_listings_are_not_public(): void
    {
        [$org, $owner] = $this->oneMember();
        $draft = $this->createListing($org, $owner, 'Draft only', MarketplaceListing::STATUS_DRAFT);
        $hidden = $this->createListing($org, $owner, 'Hidden only', MarketplaceListing::STATUS_UNPUBLISHED);

        $public = $this->getJson('/api/v1/public/market/listings')->assertOk();
        $public->assertJsonMissing(['id' => $draft->id]);
        $public->assertJsonMissing(['id' => $hidden->id]);

        $this->getJson('/api/v1/public/market/listings/'.$draft->id)->assertNotFound();
        $this->getJson('/api/v1/public/market/listings/'.$hidden->id)->assertNotFound();
    }

    public function test_profile_patch_updates_only_own_name_and_rejects_privilege_fields(): void
    {
        [$org, $owner, $intruder] = $this->twoMembers();

        $this->patchJson('/api/v1/account/profile', [
            'name' => 'New Display',
            'email' => 'hacked@wsa.test',
            'id' => $intruder->id,
            'role' => 'owner',
        ], $this->headers($owner, $org))
            ->assertOk()
            ->assertJsonPath('name', 'New Display')
            ->assertJsonPath('email', $owner->email)
            ->assertJsonPath('id', $owner->id);

        $owner->refresh();
        $this->assertSame('New Display', $owner->name);
        $this->assertSame('owner-m23@wsa.test', $owner->email);

        $intruder->refresh();
        $this->assertNotSame('New Display', $intruder->name);
    }

    public function test_public_contact_flow_remains_available_for_published_listings(): void
    {
        $listing = MarketplaceListing::query()
            ->where('status', MarketplaceListing::STATUS_PUBLISHED)
            ->first();
        $this->assertNotNull($listing);

        $detail = $this->getJson('/api/v1/public/market/listings/'.$listing->id);
        $detail->assertOk();
        $this->assertArrayNotHasKey('seller_email', $detail->json());
        $this->assertArrayNotHasKey('seller_phone', $detail->json());
        $this->assertArrayHasKey('seller', $detail->json());
    }

    /** @return array{0: Organization, 1: User} */
    private function oneMember(): array
    {
        $org = Organization::firstOrFail();
        $user = $this->makeMember($org, 'owner-m23@wsa.test');

        return [$org, $user];
    }

    /** @return array{0: Organization, 1: User, 2: User} */
    private function twoMembers(): array
    {
        $org = Organization::firstOrFail();
        $owner = $this->makeMember($org, 'owner-m23@wsa.test');
        $intruder = $this->makeMember($org, 'intruder-m23@wsa.test');

        return [$org, $owner, $intruder];
    }

    private function makeMember(Organization $organization, string $email): User
    {
        $user = User::create([
            'name' => 'M23 User '.$email,
            'email' => $email,
            'password' => Hash::make('password'),
        ]);
        $organization->members()->syncWithoutDetaching([$user->id => ['role' => 'member', 'is_active' => true]]);
        EnterpriseRoleService::seedForOrganization($organization->id);
        $memberRole = Role::withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->where('slug', 'member')
            ->firstOrFail();
        $user->roles()->syncWithoutDetaching([$memberRole->id => ['organization_id' => $organization->id]]);

        return $user;
    }

    /** @return array<string, string> */
    private function headers(User $user, Organization $organization): array
    {
        $token = $user->createToken('m23')->plainTextToken;

        return [
            'Authorization' => 'Bearer '.$token,
            'X-Organization-Id' => (string) $organization->id,
        ];
    }

    private function createListing(Organization $organization, User $seller, string $title, string $status): MarketplaceListing
    {
        return MarketplaceListing::create([
            'seller_user_id' => $seller->id,
            'organization_id' => $organization->id,
            'title' => $title,
            'seller_display_name' => $seller->name,
            'status' => $status,
            'published_at' => $status === MarketplaceListing::STATUS_PUBLISHED ? now() : null,
        ]);
    }
}
