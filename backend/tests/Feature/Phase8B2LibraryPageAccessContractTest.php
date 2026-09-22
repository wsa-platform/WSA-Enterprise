<?php

namespace Tests\Feature;

use App\Models\LibraryCategory;
use App\Models\LibraryItem;
use App\Models\LibraryTag;
use App\Models\Organization;
use App\Models\User;
use App\Services\Agriculture\Research\Persistence\ScientificKnowledgePersistenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Phase 8B-2 correction — authenticated Library Page complete-access contract.
 *
 * @group security
 */
class Phase8B2LibraryPageAccessContractTest extends TestCase
{
    use RefreshDatabase;

    private Organization $orgA;

    private Organization $orgB;

    private User $ownerA;

    private User $memberB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orgA = Organization::create([
            'name' => 'Org Alpha',
            'slug' => 'org-alpha',
            'is_active' => true,
        ]);
        $this->orgB = Organization::create([
            'name' => 'Org Beta',
            'slug' => 'org-beta',
            'is_active' => true,
        ]);

        $this->ownerA = $this->member($this->orgA, 'owner-a@wsa.test');
        $this->memberB = $this->member($this->orgB, 'member-b@wsa.test');
    }

    public function test_01_unauthenticated_library_page_apis_are_rejected(): void
    {
        $this->getJson('/api/v1/library/items')->assertUnauthorized();
        $this->getJson('/api/v1/library/categories')->assertUnauthorized();
        $this->getJson('/api/v1/library/tags')->assertUnauthorized();
        $this->getJson('/api/v1/library/files?'.http_build_query([
            'plant_production_category_id' => 'grains',
            'field_crop_id' => 'wheat',
            'library_file_section' => 'farming-needs',
        ]))->assertUnauthorized();
        $this->get('/api/v1/library/files/1/content')->assertUnauthorized();
    }

    public function test_02_through_09_authenticated_member_sees_complete_library_without_supervisor(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('library/a/guide.pdf', '%PDF-1.4 alpha');
        Storage::disk('local')->put('library/b/research.pdf', '%PDF-1.4 research');

        $categoryA = LibraryCategory::create([
            'organization_id' => $this->orgA->id,
            'owner_user_id' => $this->ownerA->id,
            'code' => 'crop-wheat',
            'name' => 'Wheat',
            'name_ar' => 'القمح',
        ]);
        $categoryB = LibraryCategory::create([
            'organization_id' => $this->orgB->id,
            'owner_user_id' => $this->memberB->id,
            'code' => 'topic-soil',
            'name' => 'Soil',
            'name_ar' => 'التربة',
        ]);

        $tagA = LibraryTag::create([
            'organization_id' => $this->orgA->id,
            'owner_user_id' => $this->ownerA->id,
            'name' => 'AlphaTag',
        ]);
        $tagB = LibraryTag::create([
            'organization_id' => $this->orgB->id,
            'owner_user_id' => $this->memberB->id,
            'name' => 'BetaTag',
        ]);

        $article = LibraryItem::create([
            'organization_id' => $this->orgA->id,
            'owner_user_id' => $this->ownerA->id,
            'category_id' => $categoryA->id,
            'slug' => 'alpha-article',
            'title' => 'Alpha Article',
            'item_type' => 'article',
            'publication_status' => 'published',
        ]);
        $article->tags()->sync([$tagA->id]);

        $cropFile = LibraryItem::create([
            'organization_id' => $this->orgA->id,
            'owner_user_id' => $this->ownerA->id,
            'category_id' => $categoryA->id,
            'slug' => 'alpha-crop-file',
            'title' => 'Alpha Crop File',
            'title_ar' => 'ملف القمح',
            'item_type' => 'crop_library_file',
            'publication_status' => 'published',
            'file_disk' => 'local',
            'file_path' => 'library/a/guide.pdf',
            'metadata' => [
                'plant_production_category_id' => 'grains',
                'field_crop_id' => 'wheat',
                'library_file_section' => 'farming-needs',
            ],
        ]);

        $research = LibraryItem::create([
            'organization_id' => $this->orgA->id,
            'owner_user_id' => null,
            'category_id' => $categoryA->id,
            'slug' => 'alpha-research',
            'title' => 'Alpha Research',
            'title_ar' => 'بحث علمي',
            'item_type' => ScientificKnowledgePersistenceService::ITEM_TYPE_VERIFIED_RESEARCH,
            'publication_status' => 'published',
            'file_disk' => 'local',
            'file_path' => 'library/b/research.pdf',
            'metadata' => [
                'plant_production_category_id' => 'grains',
                'field_crop_id' => 'wheat',
                'library_file_section' => 'scientific-research',
            ],
        ]);

        $foreignOwned = LibraryItem::create([
            'organization_id' => $this->orgB->id,
            'owner_user_id' => $this->memberB->id,
            'category_id' => $categoryB->id,
            'slug' => 'beta-article',
            'title' => 'Beta Article',
            'item_type' => 'guide',
            'publication_status' => 'draft',
        ]);
        $foreignOwned->tags()->sync([$tagB->id]);

        $this->assertFalse(
            app(\App\Services\Ownership\ServiceOwnershipAuthorizer::class)
                ->canSupervise($this->memberB, $this->orgB->id),
        );

        Sanctum::actingAs($this->memberB);

        $items = $this->getJson('/api/v1/library/items', [
            'X-Organization-Id' => (string) $this->orgB->id,
        ])->assertOk()->json();
        $itemIds = collect($this->rows($items))->pluck('id')->all();
        $this->assertContains($article->id, $itemIds);
        $this->assertContains($cropFile->id, $itemIds);
        $this->assertContains($research->id, $itemIds);
        $this->assertContains($foreignOwned->id, $itemIds);

        $types = collect($this->rows($items))->pluck('item_type')->unique()->values()->all();
        $this->assertContains('article', $types);
        $this->assertContains('crop_library_file', $types);
        $this->assertContains('guide', $types);
        $this->assertContains(ScientificKnowledgePersistenceService::ITEM_TYPE_VERIFIED_RESEARCH, $types);

        $categories = $this->getJson('/api/v1/library/categories', [
            'X-Organization-Id' => (string) $this->orgB->id,
        ])->assertOk()->json();
        $categoryIds = collect($this->rows($categories))->pluck('id')->all();
        $this->assertContains($categoryA->id, $categoryIds);
        $this->assertContains($categoryB->id, $categoryIds);

        $tags = $this->getJson('/api/v1/library/tags', [
            'X-Organization-Id' => (string) $this->orgB->id,
        ])->assertOk()->json();
        $tagIds = collect($this->rows($tags))->pluck('id')->all();
        $this->assertContains($tagA->id, $tagIds);
        $this->assertContains($tagB->id, $tagIds);

        $files = $this->getJson('/api/v1/library/files?'.http_build_query([
            'plant_production_category_id' => 'grains',
            'field_crop_id' => 'wheat',
            'library_file_section' => 'scientific-research',
        ]), [
            'X-Organization-Id' => (string) $this->orgB->id,
        ])->assertOk()->json('data');
        $fileIds = collect($files)->pluck('id')->all();
        $this->assertContains($research->id, $fileIds);

        Sanctum::actingAs($this->memberB);

        $this->get('/api/v1/library/files/'.$cropFile->id.'/content')
            ->assertOk();
        $this->get('/api/v1/library/files/'.$research->id.'/content')
            ->assertOk();
    }

    public function test_10_public_endpoints_remain_separated(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('library/public/ok.pdf', '%PDF-1.4 ok');
        Storage::disk('local')->put('library/public/secret.pdf', '%PDF-1.4 secret');

        $allowed = LibraryItem::create([
            'organization_id' => $this->orgA->id,
            'slug' => 'public-ok',
            'title' => 'Public OK',
            'title_ar' => 'عام',
            'item_type' => 'crop_library_file',
            'publication_status' => 'published',
            'file_disk' => 'local',
            'file_path' => 'library/public/ok.pdf',
            'metadata' => [
                'plant_production_category_id' => 'grains',
                'field_crop_id' => 'wheat',
                'library_file_section' => 'farming-needs',
            ],
        ]);

        $research = LibraryItem::create([
            'organization_id' => $this->orgA->id,
            'slug' => 'public-research',
            'title' => 'Secret Research',
            'title_ar' => 'سري',
            'item_type' => ScientificKnowledgePersistenceService::ITEM_TYPE_VERIFIED_RESEARCH,
            'publication_status' => 'published',
            'file_disk' => 'local',
            'file_path' => 'library/public/secret.pdf',
            'metadata' => [
                'plant_production_category_id' => 'grains',
                'field_crop_id' => 'wheat',
                'library_file_section' => 'scientific-research',
            ],
        ]);

        $items = $this->getJson('/api/v1/public/library/items?organization=org-alpha')
            ->assertOk()
            ->json('data');
        $ids = collect($items)->pluck('id')->all();
        $this->assertContains($allowed->id, $ids);
        $this->assertNotContains($research->id, $ids);

        $files = $this->getJson('/api/v1/public/library/crop-files?'.http_build_query([
            'organization' => 'org-alpha',
            'plant_production_category_id' => 'grains',
            'field_crop_id' => 'wheat',
            'library_file_section' => 'scientific-research',
        ]))->assertOk()->json('data');
        $this->assertNotContains($research->id, collect($files)->pluck('id')->all());

        $this->get('/api/v1/public/library/crop-files/'.$research->id.'/content?organization=org-alpha')
            ->assertNotFound();
    }

    private function member(Organization $organization, string $email): User
    {
        $user = User::create([
            'name' => 'Member',
            'email' => $email,
            'password' => Hash::make('password'),
        ]);
        $organization->members()->syncWithoutDetaching([
            $user->id => ['role' => 'member', 'is_active' => true],
        ]);

        return $user;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function rows(mixed $payload): array
    {
        if (is_array($payload) && array_is_list($payload)) {
            return $payload;
        }
        if (is_array($payload) && is_array($payload['data'] ?? null)) {
            return $payload['data'];
        }

        return [];
    }
}
