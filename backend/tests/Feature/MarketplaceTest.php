<?php

namespace Tests\Feature;

use App\Models\ContactAccessOrder;
use App\Models\MarketplaceListing;
use App\Models\Organization;
use App\Models\User;
use App\Services\Authorization\EnterpriseRoleService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MarketplaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    /** @return array<string, string> */
    private function adminHeaders(?Organization $organization = null): array
    {
        $organization ??= Organization::first();
        $admin = User::where('email', 'admin@wsa.test')->first();
        $token = $admin->createToken('marketplace-admin')->plainTextToken;

        return [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
        ];
    }

    /** @return array<string, string> */
    private function userHeaders(User $user, Organization $organization): array
    {
        EnterpriseRoleService::seedForOrganization($organization->id);
        $memberRole = \App\Models\Role::withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->where('slug', 'member')
            ->firstOrFail();
        $user->roles()->syncWithoutDetaching([$memberRole->id => ['organization_id' => $organization->id]]);

        $token = $user->createToken('marketplace-user')->plainTextToken;

        return [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
        ];
    }

    public function test_public_listings_do_not_expose_seller_contact(): void
    {
        $response = $this->getJson('/api/v1/public/market/listings');
        $response->assertOk();

        $first = $response->json('data.0');
        $this->assertNotNull($first);
        $this->assertArrayNotHasKey('seller_email', $first);
        $this->assertArrayNotHasKey('seller_phone', $first);
        $this->assertArrayHasKey('seller', $first);
        $this->assertArrayNotHasKey('email', $first['seller']);
        $this->assertArrayNotHasKey('phone', $first['seller']);
    }

    public function test_seller_can_create_and_submit_listing(): void
    {
        $org = Organization::first();
        $seller = User::create([
            'name' => 'New Seller',
            'email' => 'newseller@wsa.test',
            'password' => Hash::make('password'),
        ]);
        $org->members()->syncWithoutDetaching([$seller->id => ['role' => 'member']]);
        $headers = $this->userHeaders($seller, $org);

        $create = $this->postJson('/api/v1/market/listings', [
            'title' => 'Test Listing',
            'description' => 'Organic wheat',
            'seller_type' => 'local',
            'country' => 'SA',
            'city' => 'Riyadh',
            'seller_email' => 'newseller@wsa.test',
            'seller_phone' => '+966522222222',
            'price' => 500,
        ], $headers);

        $create->assertCreated();
        $listingId = $create->json('id');

        $this->postJson("/api/v1/market/listings/{$listingId}/submit", [], $headers)
            ->assertOk()
            ->assertJsonPath('status', MarketplaceListing::STATUS_PENDING_REVIEW);
    }

    public function test_contact_access_denied_before_payment(): void
    {
        $listing = MarketplaceListing::where('status', MarketplaceListing::STATUS_PUBLISHED)->first();
        $this->assertNotNull($listing);

        $org = Organization::first();
        $buyer = User::create([
            'name' => 'Buyer',
            'email' => 'buyer@wsa.test',
            'password' => Hash::make('password'),
        ]);
        $org->members()->syncWithoutDetaching([$buyer->id => ['role' => 'member']]);
        $headers = $this->userHeaders($buyer, $org);

        $detail = $this->getJson("/api/v1/market/listings/{$listing->id}", $headers);
        $detail->assertOk();
        $this->assertArrayNotHasKey('contact', $detail->json());
        $this->assertTrue($detail->json('contact_access_required'));
    }

    public function test_contact_access_allowed_after_paid_order(): void
    {
        $listing = MarketplaceListing::where('status', MarketplaceListing::STATUS_PUBLISHED)->first();
        $org = Organization::first();
        $buyer = User::create([
            'name' => 'Paid Buyer',
            'email' => 'paidbuyer@wsa.test',
            'password' => Hash::make('password'),
        ]);
        $org->members()->syncWithoutDetaching([$buyer->id => ['role' => 'member']]);
        $headers = $this->userHeaders($buyer, $org);

        $orderResponse = $this->postJson("/api/v1/market/listings/{$listing->id}/request-contact-access", [
            'idempotency_key' => 'test-order-1',
        ], $headers)->assertCreated();

        $orderId = $orderResponse->json('id');

        $pay = $this->postJson("/api/v1/market/contact-access-orders/{$orderId}/pay", [
            'idempotency_key' => 'pay-key-1',
        ], $headers);

        $pay->assertOk()
            ->assertJsonPath('order.payment_status', ContactAccessOrder::PAYMENT_PAID)
            ->assertJsonPath('contact.seller_email', $listing->seller_email);

        $detail = $this->getJson("/api/v1/market/listings/{$listing->id}", $headers);
        $detail->assertOk()->assertJsonPath('contact.seller_email', $listing->seller_email);
    }

    public function test_marketplace_routes_are_documented_in_openapi(): void
    {
        $content = file_get_contents($this->openApiSpecPath());
        $this->assertNotFalse($content);

        $required = [
            '/public/market/listings',
            '/public/market/listings/{listing}',
            '/public/market/categories',
            '/market/listings',
            '/market/listings/{listing}',
            '/market/listings/{listing}/submit',
            '/market/listings/{listing}/request-contact-access',
            '/market/contact-access-orders/{order}/pay',
            '/market/categories',
            '/market/my-listings',
            '/market/my-entitlements',
            '/admin/market/listings',
            '/admin/market/listings/{listing}/approve',
            '/admin/market/listings/{listing}/reject',
            '/admin/market/listings/{listing}/suspend',
            '/admin/market/categories',
        ];

        foreach ($required as $path) {
            $this->assertStringContainsString(
                "  {$path}:",
                $content,
                "Missing OpenAPI path {$path}"
            );
        }
    }

    public function test_admin_can_approve_pending_listing(): void
    {
        $listing = MarketplaceListing::where('status', MarketplaceListing::STATUS_PENDING_REVIEW)->first();
        if (! $listing) {
            $draft = MarketplaceListing::where('status', MarketplaceListing::STATUS_DRAFT)->first();
            $draft->update(['status' => MarketplaceListing::STATUS_PENDING_REVIEW]);
            $listing = $draft;
        }

        $this->postJson("/api/v1/admin/market/listings/{$listing->id}/approve", [
            'reason' => 'Verified seller',
        ], $this->adminHeaders())
            ->assertOk()
            ->assertJsonPath('status', MarketplaceListing::STATUS_PUBLISHED);
    }

    public function test_marketplace_report_endpoint(): void
    {
        $this->getJson('/api/v1/reports/marketplace?days=30', $this->adminHeaders())
            ->assertOk()
            ->assertJsonStructure(['summary' => ['listings_total', 'published'], 'by_status']);
    }
}
