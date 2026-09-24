<?php

namespace Tests\Feature;

use App\Models\LibraryCategory;
use App\Models\LibraryItem;
use App\Models\LibraryTag;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * A10 — Authenticated Library catalog vs file isolation.
 *
 * Phase 8B Library Page READ is authentication-only complete browse.
 * File bytes remain tenant-scoped. Catalog listing is intentionally shared.
 */
class LibraryCatalogReadContractA10Test extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_catalog_is_shared_while_file_bytes_stay_tenant_scoped(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('library/a/a.pdf', '%PDF-1.4 a');

        $orgA = Organization::create(['name' => 'A', 'slug' => 'org-a', 'is_active' => true]);
        $orgB = Organization::create(['name' => 'B', 'slug' => 'org-b', 'is_active' => true]);
        $userA = $this->member($orgA, 'a10-a@wsa.test');
        $userB = $this->member($orgB, 'a10-b@wsa.test');

        $category = LibraryCategory::create([
            'organization_id' => $orgA->id,
            'owner_user_id' => $userA->id,
            'code' => 'a10-cat',
            'name' => 'Alpha Category',
        ]);
        $tag = LibraryTag::create([
            'organization_id' => $orgA->id,
            'owner_user_id' => $userA->id,
            'name' => 'AlphaTag',
        ]);
        $item = LibraryItem::create([
            'organization_id' => $orgA->id,
            'owner_user_id' => $userA->id,
            'category_id' => $category->id,
            'slug' => 'a10-item',
            'title' => 'Alpha Catalog Item',
            'publication_status' => 'published',
            'file_disk' => 'local',
            'file_path' => 'library/a/a.pdf',
            'metadata' => [
                'plant_production_category_id' => 'grains',
                'field_crop_id' => 'wheat',
                'library_file_section' => 'farming-needs',
            ],
        ]);
        $item->tags()->sync([$tag->id]);

        $this->getJson('/api/v1/library/items')->assertUnauthorized();

        Sanctum::actingAs($userB);
        $items = $this->getJson('/api/v1/library/items', [
            'X-Organization-Id' => (string) $orgB->id,
        ])->assertOk()->json();
        $itemIds = collect(is_array($items) && array_is_list($items) ? $items : ($items['data'] ?? []))->pluck('id')->all();
        $this->assertContains($item->id, $itemIds);

        $categories = $this->getJson('/api/v1/library/categories', [
            'X-Organization-Id' => (string) $orgB->id,
        ])->assertOk()->json();
        $this->assertContains(
            $category->id,
            collect(is_array($categories) && array_is_list($categories) ? $categories : ($categories['data'] ?? []))->pluck('id')->all(),
        );

        $tags = $this->getJson('/api/v1/library/tags', [
            'X-Organization-Id' => (string) $orgB->id,
        ])->assertOk()->json();
        $this->assertContains(
            $tag->id,
            collect(is_array($tags) && array_is_list($tags) ? $tags : ($tags['data'] ?? []))->pluck('id')->all(),
        );

        $this->get('/api/v1/library/files/'.$item->id.'/content', [
            'X-Organization-Id' => (string) $orgB->id,
        ])->assertNotFound();
    }

    private function member(Organization $organization, string $email): User
    {
        $user = User::create([
            'name' => 'A10 Member',
            'email' => $email,
            'password' => Hash::make('password'),
        ]);
        $organization->members()->syncWithoutDetaching([
            $user->id => ['role' => 'member', 'is_active' => true],
        ]);

        return $user;
    }
}
