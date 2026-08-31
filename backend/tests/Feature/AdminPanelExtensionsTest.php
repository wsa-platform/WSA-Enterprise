<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminPanelExtensionsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->admin = User::where('email', 'admin@wsa.test')->firstOrFail();
        $this->organization = Organization::where('slug', 'wsa-demo')->firstOrFail();
    }

    private function headers(): array
    {
        return ['X-Organization-Id' => (string) $this->organization->id];
    }

    public function test_user_can_update_profile(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->withHeaders($this->headers())
            ->patchJson('/api/v1/user', ['name' => 'Admin Updated']);

        $response->assertOk()->assertJsonPath('name', 'Admin Updated');
    }

    public function test_user_settings_persist(): void
    {
        Sanctum::actingAs($this->admin);

        $this->withHeaders($this->headers())
            ->putJson('/api/v1/user/settings', [
                'settings' => [
                    'appearance.theme' => 'dark',
                    'notifications.email' => false,
                ],
            ])
            ->assertOk()
            ->assertJsonFragment(['appearance.theme' => ['value' => 'dark']]);

        $this->withHeaders($this->headers())
            ->getJson('/api/v1/user/settings')
            ->assertOk()
            ->assertJsonFragment(['appearance.theme' => ['value' => 'dark']]);
    }

    public function test_admin_can_broadcast_notification(): void
    {
        Sanctum::actingAs($this->admin);

        $this->withHeaders($this->headers())
            ->postJson('/api/v1/notifications', [
                'title' => 'Test Broadcast',
                'body' => 'Hello from admin panel',
            ])
            ->assertCreated()
            ->assertJsonPath('title', 'Test Broadcast');
    }

    public function test_reports_overview_returns_data_with_charts(): void
    {
        Sanctum::actingAs($this->admin);

        $this->withHeaders($this->headers())
            ->getJson('/api/v1/reports/overview?days=7')
            ->assertOk()
            ->assertJsonStructure([
                'commerce',
                'catalog',
                'agriculture',
                'marketing',
                'access',
                'charts' => [
                    'users_over_time',
                    'products_over_time',
                    'audit_over_time',
                    'campaigns_over_time',
                ],
            ]);
    }

    public function test_reports_export_includes_utf8_bom(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->withHeaders($this->headers())
            ->get('/api/v1/reports/export?days=7');

        $response->assertOk();
        $content = method_exists($response, 'streamedContent')
            ? $response->streamedContent()
            : $response->getContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
    }

    public function test_communications_inbox_returns_items(): void
    {
        Sanctum::actingAs($this->admin);

        $this->withHeaders($this->headers())
            ->getJson('/api/v1/communications/inbox')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta']);
    }

    public function test_platform_admin_can_list_all_organizations(): void
    {
        Sanctum::actingAs($this->admin);

        Organization::create(['name' => 'Extra Org', 'slug' => 'extra-org', 'is_active' => true]);

        $this->withHeaders($this->headers())
            ->getJson('/api/v1/platform/admin/organizations?page=1&per_page=10')
            ->assertOk()
            ->assertJsonStructure(['data', 'current_page', 'last_page', 'total']);
    }

    public function test_permissions_catalog_endpoint(): void
    {
        Sanctum::actingAs($this->admin);

        $this->withHeaders($this->headers())
            ->getJson('/api/v1/permissions/catalog')
            ->assertOk()
            ->assertJsonStructure(['catalog', 'groups']);
    }

    public function test_media_upload_and_delete(): void
    {
        Storage::fake('public');
        Sanctum::actingAs($this->admin);

        $upload = $this->withHeaders($this->headers())
            ->post('/api/v1/media/uploads', [
                'file' => UploadedFile::fake()->create('article.png', 128, 'image/png'),
                'context' => 'library',
            ])
            ->assertCreated()
            ->json();

        $this->withHeaders($this->headers())
            ->deleteJson('/api/v1/media/uploads/'.$upload['id'])
            ->assertOk();
    }

    public function test_inventory_movements_are_paginated(): void
    {
        Sanctum::actingAs($this->admin);

        $this->withHeaders($this->headers())
            ->getJson('/api/v1/inventory/movements?page=1&per_page=10')
            ->assertOk()
            ->assertJsonStructure(['data', 'current_page', 'last_page', 'total']);
    }

    public function test_product_images_endpoints(): void
    {
        Storage::fake('public');
        Sanctum::actingAs($this->admin);

        $product = $this->withHeaders($this->headers())
            ->postJson('/api/v1/catalog/products', [
                'sku' => 'TEST-IMG-1',
                'name' => 'Test Product Image',
                'cost_price' => 0,
                'sale_price' => 10,
                'is_active' => true,
            ])
            ->assertCreated()
            ->json();

        $image = $this->withHeaders($this->headers())
            ->post("/api/v1/catalog/products/{$product['id']}/images", [
                'file' => UploadedFile::fake()->create('product.png', 128, 'image/png'),
            ])
            ->assertCreated()
            ->json();

        $this->withHeaders($this->headers())
            ->getJson("/api/v1/catalog/products/{$product['id']}/images")
            ->assertOk()
            ->assertJsonFragment(['id' => $image['id']]);

        $this->withHeaders($this->headers())
            ->deleteJson("/api/v1/catalog/products/{$product['id']}/images/{$image['id']}")
            ->assertOk();
    }
}
