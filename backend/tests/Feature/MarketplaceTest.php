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

        $this->getJson("/api/v1/market/contact-access-orders/{$orderId}/seller-contact", $headers)
            ->assertOk()
            ->assertJsonPath('seller_email', $listing->seller_email)
            ->assertJsonPath('seller_phone', $listing->seller_phone);

        $this->getJson("/api/v1/public/market/listings/{$listing->id}", $headers)
            ->assertOk()
            ->assertJsonMissingPath('contact.seller_email')
            ->assertJsonMissingPath('contact.seller_phone');
        $publicPayload = $this->getJson("/api/v1/public/market/listings/{$listing->id}", $headers)->json();
        $this->assertArrayNotHasKey('seller_email', $publicPayload);
        $this->assertArrayNotHasKey('seller_phone', $publicPayload);
        $this->assertArrayNotHasKey('contact', $publicPayload);
    }

    public function test_seller_contact_is_not_released_before_payment_or_to_other_buyers(): void
    {
        $listing = MarketplaceListing::where('status', MarketplaceListing::STATUS_PUBLISHED)->first();
        $org = Organization::first();
        $buyer = User::create([
            'name' => 'Pending Buyer',
            'email' => 'pendingbuyer@wsa.test',
            'password' => Hash::make('password'),
        ]);
        $other = User::create([
            'name' => 'Other Buyer',
            'email' => 'otherbuyer@wsa.test',
            'password' => Hash::make('password'),
        ]);
        $org->members()->syncWithoutDetaching([
            $buyer->id => ['role' => 'member'],
            $other->id => ['role' => 'member'],
        ]);
        $buyerHeaders = $this->userHeaders($buyer, $org);
        $otherHeaders = $this->userHeaders($other, $org);

        $orderId = $this->postJson("/api/v1/market/listings/{$listing->id}/request-contact-access", [
            'idempotency_key' => 'pending-order-1',
        ], $buyerHeaders)->assertCreated()->json('id');

        $this->getJson("/api/v1/market/contact-access-orders/{$orderId}/seller-contact")
            ->assertUnauthorized();
        $this->getJson("/api/v1/market/contact-access-orders/{$orderId}/seller-contact", $buyerHeaders)
            ->assertForbidden()
            ->assertJsonMissingPath('seller_email');
        $this->getJson("/api/v1/market/contact-access-orders/{$orderId}/seller-contact", $otherHeaders)
            ->assertForbidden()
            ->assertJsonMissingPath('seller_phone');

        $this->postJson("/api/v1/market/contact-access-orders/{$orderId}/pay", [
            'idempotency_key' => 'fail-pending-order-1',
        ], $buyerHeaders)->assertOk()->assertJsonPath('contact', null);

        $this->getJson("/api/v1/market/contact-access-orders/{$orderId}/seller-contact", $buyerHeaders)
            ->assertForbidden();

        $paidOrderId = $this->postJson("/api/v1/market/listings/{$listing->id}/request-contact-access", [
            'idempotency_key' => 'paid-order-privacy',
        ], $buyerHeaders)->assertCreated()->json('id');
        $this->postJson("/api/v1/market/contact-access-orders/{$paidOrderId}/pay", [
            'idempotency_key' => 'pay-privacy-1',
        ], $buyerHeaders)->assertOk();

        $this->getJson("/api/v1/market/contact-access-orders/{$paidOrderId}/seller-contact", $otherHeaders)
            ->assertForbidden()
            ->assertJsonMissingPath('seller_email');
        $this->getJson("/api/v1/market/contact-access-orders/{$paidOrderId}/seller-contact", $buyerHeaders)
            ->assertOk()
            ->assertJsonPath('seller_email', $listing->seller_email);
    }

    public function test_seller_contact_idor_rejects_other_orders_listings_and_seller_ids(): void
    {
        $listing = MarketplaceListing::where('status', MarketplaceListing::STATUS_PUBLISHED)->first();
        $this->assertNotNull($listing);

        $otherListing = $listing->replicate();
        $otherListing->title = 'IDOR decoy listing';
        $otherListing->seller_email = 'decoy-seller@wsa.test';
        $otherListing->seller_phone = '+966511111111';
        $otherListing->save();

        $org = Organization::first();
        $buyer = User::create([
            'name' => 'IDOR Buyer',
            'email' => 'idorbuyer@wsa.test',
            'password' => Hash::make('password'),
        ]);
        $org->members()->syncWithoutDetaching([$buyer->id => ['role' => 'member']]);
        $headers = $this->userHeaders($buyer, $org);

        $paidOrderId = $this->postJson("/api/v1/market/listings/{$listing->id}/request-contact-access", [
            'idempotency_key' => 'idor-paid-order',
        ], $headers)->assertCreated()->json('id');
        $this->postJson("/api/v1/market/contact-access-orders/{$paidOrderId}/pay", [
            'idempotency_key' => 'idor-pay-1',
        ], $headers)->assertOk();

        $this->getJson("/api/v1/market/contact-access-orders/{$paidOrderId}/seller-contact", $headers)
            ->assertOk()
            ->assertJsonPath('seller_email', $listing->seller_email)
            ->assertJsonPath('seller_phone', $listing->seller_phone);

        $this->getJson('/api/v1/market/contact-access-orders/999999/seller-contact', $headers)
            ->assertNotFound()
            ->assertJsonMissingPath('seller_email');

        $unpaidOtherOrderId = $this->postJson("/api/v1/market/listings/{$otherListing->id}/request-contact-access", [
            'idempotency_key' => 'idor-other-listing',
        ], $headers)->assertCreated()->json('id');
        $this->getJson("/api/v1/market/contact-access-orders/{$unpaidOtherOrderId}/seller-contact", $headers)
            ->assertForbidden()
            ->assertJsonMissingPath('seller_email')
            ->assertJsonMissingPath('seller_phone');

        $otherListingPayload = $this->getJson("/api/v1/market/listings/{$otherListing->id}", $headers)->assertOk()->json();
        $this->assertArrayNotHasKey('contact', $otherListingPayload);
        $this->assertArrayNotHasKey('seller_email', $otherListingPayload);
        $this->assertArrayNotHasKey('seller_phone', $otherListingPayload);

        $sellerIdPayload = $this->getJson("/api/v1/market/listings/{$listing->seller_user_id}", $headers);
        if ($sellerIdPayload->status() === 200) {
            $this->assertNotSame($listing->seller_email, $sellerIdPayload->json('seller_email'));
            $this->assertArrayNotHasKey('seller_email', $sellerIdPayload->json());
        } else {
            $sellerIdPayload->assertNotFound();
        }

        $this->getJson("/api/v1/public/market/listings/{$listing->id}")
            ->assertOk()
            ->assertJsonMissingPath('seller_email')
            ->assertJsonMissingPath('seller_phone');
    }

    public function test_listing_create_requires_international_phone_and_email(): void
    {
        $org = Organization::first();
        $seller = User::create([
            'name' => 'Contact Seller',
            'email' => 'contactseller@wsa.test',
            'password' => Hash::make('password'),
        ]);
        $org->members()->syncWithoutDetaching([$seller->id => ['role' => 'member']]);
        $headers = $this->userHeaders($seller, $org);

        $this->postJson('/api/v1/market/listings', [
            'title' => 'Wheat',
            'seller_type' => 'local',
            'country' => 'SA',
        ], $headers)->assertUnprocessable();

        $this->postJson('/api/v1/market/listings', [
            'title' => 'Wheat',
            'seller_type' => 'local',
            'country' => 'SA',
            'seller_email' => 'not-an-email',
            'seller_phone' => '0501234567',
        ], $headers)->assertUnprocessable();

        $this->postJson('/api/v1/market/listings', [
            'title' => 'Wheat',
            'seller_type' => 'international',
            'country' => 'EG',
            'seller_email' => '  seller@wsa.test  ',
            'seller_phone' => '+201012345678',
        ], $headers)->assertCreated()
            ->assertJsonPath('seller_email', 'seller@wsa.test')
            ->assertJsonPath('seller_phone', '+201012345678');
    }

    public function test_marketplace_routes_are_documented_in_openapi(): void
    {
        $path = $this->openApiSpecPath();
        if (! is_file($path)) {
            $this->markTestSkipped('OpenAPI spec is not available in this test environment.');
        }

        $content = file_get_contents($path);
        $this->assertNotFalse($content);

        $required = [
            '/public/market/listings',
            '/public/market/listings/{listing}',
            '/public/market/categories',
            '/public/market/units',
            '/market/listings',
            '/market/listings/{listing}',
            '/market/listings/{listing}/submit',
            '/market/listings/{listing}/request-contact-access',
            '/market/contact-access-orders/{order}/pay',
            '/market/contact-access-orders/{order}/seller-contact',
            '/market/categories',
            '/market/units',
            '/market/my-listings',
            '/market/my-entitlements',
            '/admin/market/listings',
            '/admin/market/listings/{listing}/approve',
            '/admin/market/listings/{listing}/reject',
            '/admin/market/listings/{listing}/suspend',
            '/admin/market/categories',
            '/reports/marketplace',
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
