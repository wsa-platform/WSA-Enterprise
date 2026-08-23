<?php

namespace Tests\Feature;

use App\Models\MarketplaceAttributeDefinition;
use App\Models\MarketplaceCategory;
use App\Models\MarketplaceListing;
use App\Models\MarketplaceUnit;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Services\Authorization\EnterpriseRoleService;
use App\Support\IsoCountries;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class Adr003ProductDataFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_seller_type_and_location_are_stored_separately_from_origin_country(): void
    {
        [$org, $seller] = $this->seller();
        $unitId = MarketplaceUnit::where('slug', 'ton')->value('id');
        $categoryId = MarketplaceCategory::where('slug', 'fruits')->value('id');

        $response = $this->postJson('/api/v1/market/listings', [
            'title' => 'Egyptian oranges',
            'description' => 'Export-grade citrus',
            'seller_type' => MarketplaceListing::SELLER_INTERNATIONAL,
            'country' => 'tr',
            'city' => 'Istanbul',
            'seller_region' => 'Marmara',
            'origin_country' => 'EG',
            'availability' => MarketplaceListing::AVAILABILITY_SEASONAL,
            'unit_id' => $unitId,
            'category_id' => $categoryId,
            'product_type' => 'crop',
            'brand' => 'Nile Grove',
            'min_order_quantity' => 2,
            'available_quantity' => 40,
            'production_capacity' => 100,
            'wholesale' => true,
            'retail' => false,
            'export_ready' => true,
            'packaging' => 'Carton 15kg',
            'shipping_terms' => 'FOB',
            'lead_time_days' => 5,
            'specifications' => ['variety' => 'Valencia', 'grade' => 'A'],
            'video_url' => 'https://example.com/oranges.mp4',
            'export_countries' => ['SA', 'AE'],
            'model_number' => 'X-100',
            'condition' => 'new',
        ], $this->headers($seller, $org));

        $response->assertCreated()
            ->assertJsonPath('seller_type', MarketplaceListing::SELLER_INTERNATIONAL)
            ->assertJsonPath('country', 'TR')
            ->assertJsonPath('seller_country', 'TR')
            ->assertJsonPath('origin_country', 'EG')
            ->assertJsonPath('city', 'Istanbul')
            ->assertJsonPath('seller_region', 'Marmara')
            ->assertJsonPath('availability', MarketplaceListing::AVAILABILITY_SEASONAL)
            ->assertJsonPath('unit.slug', 'ton')
            ->assertJsonPath('specifications.variety', 'Valencia');

        $this->assertArrayNotHasKey('export_countries', $response->json());
        $this->assertArrayNotHasKey('export_destination', $response->json());
        $this->assertArrayNotHasKey('model_number', $response->json());
        $this->assertArrayNotHasKey('condition', $response->json());
        $this->assertArrayNotHasKey('seller_email', $response->json()['seller']);
        $this->assertArrayNotHasKey('seller_phone', $response->json()['seller']);

        $listing = MarketplaceListing::query()->findOrFail($response->json('id'));
        $this->assertSame(MarketplaceListing::SELLER_INTERNATIONAL, $listing->seller_type);
        $this->assertSame('TR', $listing->country);
        $this->assertSame('EG', $listing->origin_country);
        $this->assertNotSame($listing->country, $listing->origin_country);
        $this->assertSame('Istanbul', $listing->city);
        $this->assertSame('Marmara', $listing->seller_region);
        $this->assertFalse(isset($listing->export_countries));
        $this->assertFalse(isset($listing->model_number));
        $this->assertFalse(isset($listing->condition));
        $this->assertSame($seller->id, $listing->seller_user_id);
        $this->assertSame($org->id, $listing->organization_id);
    }

    public function test_availability_rejects_unapproved_and_used_condition_values(): void
    {
        [$org, $seller] = $this->seller();
        $headers = $this->headers($seller, $org);

        $this->postJson('/api/v1/market/listings', [
            'title' => 'Dates',
            'seller_type' => MarketplaceListing::SELLER_LOCAL,
            'country' => 'SA',
            'availability' => 'new',
        ], $headers)->assertUnprocessable();

        $this->postJson('/api/v1/market/listings', [
            'title' => 'Dates',
            'seller_type' => MarketplaceListing::SELLER_LOCAL,
            'country' => 'SA',
            'availability' => 'used',
        ], $headers)->assertUnprocessable();

        $this->postJson('/api/v1/market/listings', [
            'title' => 'Dates made_to_order',
            'seller_type' => MarketplaceListing::SELLER_LOCAL,
            'country' => 'SA',
            'availability' => 'made_to_order',
        ], $headers)->assertUnprocessable();

        $this->postJson('/api/v1/market/listings', [
            'title' => 'Dates on_demand',
            'seller_type' => MarketplaceListing::SELLER_LOCAL,
            'country' => 'SA',
            'availability' => MarketplaceListing::AVAILABILITY_ON_DEMAND,
        ], $headers)->assertCreated()->assertJsonPath('availability', 'on_demand');

        foreach (MarketplaceListing::AVAILABILITIES as $availability) {
            $this->postJson('/api/v1/market/listings', [
                'title' => 'Dates '.$availability,
                'seller_type' => MarketplaceListing::SELLER_LOCAL,
                'country' => 'SA',
                'availability' => $availability,
            ], $headers)->assertCreated()->assertJsonPath('availability', $availability);
        }
    }

    public function test_no_export_destination_catalog_or_generic_model_columns_were_added(): void
    {
        $this->assertFalse(Schema::hasTable('marketplace_export_destinations'));
        $this->assertFalse(Schema::hasTable('export_destinations'));
        $this->assertFalse(Schema::hasColumn('marketplace_listings', 'export_countries'));
        $this->assertFalse(Schema::hasColumn('marketplace_listings', 'export_regions'));
        $this->assertFalse(Schema::hasColumn('marketplace_listings', 'model_number'));
        $this->assertFalse(Schema::hasColumn('marketplace_listings', 'model'));
        $this->assertFalse(Schema::hasColumn('marketplace_listings', 'condition'));
        $this->assertFalse(Schema::hasColumn('marketplace_listings', 'is_new'));
        $this->assertTrue(Schema::hasColumn('marketplace_listings', 'origin_country'));
        $this->assertTrue(Schema::hasColumn('marketplace_listings', 'country'));
        $this->assertTrue(Schema::hasColumn('marketplace_listings', 'seller_region'));
        $this->assertTrue(Schema::hasColumn('marketplace_listings', 'availability'));
        $this->assertTrue(Schema::hasColumn('marketplace_listings', 'unit_id'));
        $this->assertTrue(IsoCountries::isValid('SA'));
        $this->assertTrue(IsoCountries::isValid('TR'));
        $this->assertFalse(IsoCountries::isValid('Turkey'));
    }

    public function test_units_and_categories_are_extensible_and_attribute_definitions_work(): void
    {
        $this->assertGreaterThanOrEqual(14, MarketplaceCategory::query()->count());
        $this->assertTrue(MarketplaceCategory::query()->where('slug', 'fruits')->exists());
        $this->assertTrue(MarketplaceCategory::query()->where('slug', 'agricultural-supplies')->exists());

        MarketplaceCategory::create([
            'slug' => 'custom-olives',
            'name' => 'Olives',
            'name_ar' => 'الزيتون',
            'is_active' => true,
            'sort_order' => 90,
        ]);
        $this->assertTrue(MarketplaceCategory::query()->where('slug', 'custom-olives')->exists());

        $this->assertTrue(MarketplaceUnit::query()->where('slug', 'kg')->exists());
        MarketplaceUnit::create([
            'slug' => 'sack',
            'name' => 'Sack',
            'name_ar' => 'شوال',
            'is_active' => true,
            'sort_order' => 20,
        ]);
        $this->assertTrue(MarketplaceUnit::query()->where('slug', 'sack')->exists());

        $definition = MarketplaceAttributeDefinition::query()->where('slug', 'variety')->first();
        $this->assertNotNull($definition);
        $this->assertSame('crop', $definition->product_type);
        $this->assertNotNull($definition->category_id);

        MarketplaceAttributeDefinition::create([
            'slug' => 'moisture',
            'name' => 'Moisture',
            'name_ar' => 'الرطوبة',
            'data_type' => MarketplaceAttributeDefinition::TYPE_NUMBER,
            'product_type' => 'crop',
            'is_active' => true,
            'sort_order' => 40,
        ]);
        $this->assertTrue(MarketplaceAttributeDefinition::query()->where('slug', 'moisture')->exists());
    }

    public function test_seller_ownership_and_tenant_isolation_still_hold(): void
    {
        $orgA = Organization::firstOrFail();
        $orgB = Organization::query()->create(['name' => 'Org B Foundation', 'slug' => 'org-b-foundation']);
        $sellerA = $this->member($orgA, 'a-foundation@wsa.test');
        $sellerB = $this->member($orgB, 'b-foundation@wsa.test');

        $listingB = MarketplaceListing::create([
            'seller_user_id' => $sellerB->id,
            'organization_id' => $orgB->id,
            'title' => 'Org B dates',
            'seller_display_name' => $sellerB->name,
            'seller_type' => MarketplaceListing::SELLER_LOCAL,
            'country' => 'SA',
            'status' => MarketplaceListing::STATUS_DRAFT,
        ]);

        $this->patchJson('/api/v1/market/listings/'.$listingB->id, [
            'title' => 'Stolen',
        ], $this->headers($sellerA, $orgA))->assertNotFound();

        $this->deleteJson('/api/v1/market/listings/'.$listingB->id, [], $this->headers($sellerA, $orgA))
            ->assertNotFound();

        $this->getJson('/api/v1/market/my-listings', $this->headers($sellerA, $orgA))
            ->assertOk()
            ->assertJsonMissing(['id' => $listingB->id]);
    }

    public function test_public_payload_still_hides_seller_contact(): void
    {
        $listing = MarketplaceListing::query()
            ->where('status', MarketplaceListing::STATUS_PUBLISHED)
            ->firstOrFail();

        $payload = $this->getJson('/api/v1/public/market/listings/'.$listing->id)
            ->assertOk()
            ->json();

        $this->assertArrayNotHasKey('seller_email', $payload);
        $this->assertArrayNotHasKey('seller_phone', $payload);
        $this->assertArrayNotHasKey('export_destination', $payload);
        $this->assertArrayNotHasKey('email', $payload['seller']);
        $this->assertArrayNotHasKey('phone', $payload['seller']);
        $this->assertArrayHasKey('origin_country', $payload);
        $this->assertArrayHasKey('seller_country', $payload);
        $this->assertSame($payload['country'], $payload['seller_country']);
    }

    /** @return array{0: Organization, 1: User} */
    private function seller(): array
    {
        $org = Organization::firstOrFail();

        return [$org, $this->member($org, 'foundation-seller@wsa.test')];
    }

    private function member(Organization $organization, string $email): User
    {
        $user = User::create([
            'name' => 'Foundation '.$email,
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
        return [
            'Authorization' => 'Bearer '.$user->createToken('foundation')->plainTextToken,
            'X-Organization-Id' => (string) $organization->id,
        ];
    }
}
