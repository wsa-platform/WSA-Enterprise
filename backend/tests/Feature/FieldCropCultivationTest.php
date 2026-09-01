<?php

namespace Tests\Feature;

use App\Models\LibraryItem;
use App\Models\Organization;
use App\Services\Agriculture\FieldCropCultivationProfileService;
use App\Services\Agriculture\FieldCropLibraryRepository;
use App\Services\Agriculture\ScientificSourceValidator;
use Database\Seeders\FieldCropCultivationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FieldCropCultivationTest extends TestCase
{
    use RefreshDatabase;

    private function seedCultivationLibrary(): Organization
    {
        $organization = Organization::create(['name' => 'WSA Demo', 'slug' => 'wsa-demo']);
        $this->seed(FieldCropCultivationSeeder::class);

        return $organization;
    }

    public function test_public_wheat_farming_needs_profile_returns_thirteen_sections(): void
    {
        $organization = $this->seedCultivationLibrary();

        $response = $this->getJson('/api/v1/public/field-crops/farming-needs-profile?'.http_build_query([
            'organization' => 'wsa-demo',
            'selected_crop_id' => 'wheat',
            'selected_crop_name' => 'القمح',
            'selected_category_id' => 'grains',
            'selected_category_name' => 'محاصيل الحبوب',
        ]));

        $response->assertOk();
        $response->assertJsonPath('crop.id', 'wheat');
        $response->assertJsonPath('crop.name', 'القمح');
        $response->assertJsonPath('service_option', 'farming-needs');
        $response->assertJsonPath('load_state', 'library_complete');
        $response->assertJsonCount(13, 'sections');
        $response->assertJsonPath('sections.0.title', 'اسم المحصول التجاري والاسم العلمي');
        $response->assertJsonFragment(['title' => 'زراعة واحتياجات محصول القمح']);

        $scientificSection = collect($response->json('sections'))
            ->firstWhere('key', 'commercial_scientific_name');
        $this->assertStringContainsString('Triticum aestivum', (string) ($scientificSection['content'] ?? ''));
        $this->assertTrue((bool) ($scientificSection['verified'] ?? false));
        $this->assertNotEmpty($scientificSection['source']['url'] ?? null);

        $this->assertDatabaseHas('library_items', [
            'organization_id' => $organization->id,
            'slug' => 'field-crop-wheat-farming-needs',
        ]);
    }

    public function test_corn_profile_is_crop_specific_not_wheat(): void
    {
        $this->seedCultivationLibrary();

        $response = $this->getJson('/api/v1/public/field-crops/farming-needs-profile?'.http_build_query([
            'organization' => 'wsa-demo',
            'selected_crop_id' => 'corn',
            'selected_crop_name' => 'الذرة',
            'selected_category_id' => 'grains',
            'selected_category_name' => 'محاصيل الحبوب',
        ]));

        $response->assertOk();
        $response->assertJsonPath('crop.id', 'corn');
        $response->assertJsonPath('title', 'زراعة واحتياجات محصول الذرة');
        $response->assertJsonPath('load_state', 'library_complete');

        $scientificSection = collect($response->json('sections'))
            ->firstWhere('key', 'commercial_scientific_name');
        $this->assertStringContainsString('Zea mays', (string) ($scientificSection['content'] ?? ''));
        $this->assertStringNotContainsString('Triticum aestivum', (string) ($scientificSection['content'] ?? ''));
    }

    public function test_repeat_request_reuses_library_item_without_duplicates(): void
    {
        $organization = $this->seedCultivationLibrary();

        $this->getJson('/api/v1/public/field-crops/farming-needs-profile?'.http_build_query([
            'organization' => 'wsa-demo',
            'selected_crop_id' => 'wheat',
            'selected_crop_name' => 'القمح',
        ]))->assertOk();

        $countAfterFirst = LibraryItem::query()
            ->where('organization_id', $organization->id)
            ->where('slug', 'field-crop-wheat-farming-needs')
            ->count();

        $second = $this->getJson('/api/v1/public/field-crops/farming-needs-profile?'.http_build_query([
            'organization' => 'wsa-demo',
            'selected_crop_id' => 'wheat',
            'selected_crop_name' => 'القمح',
        ]));
        $second->assertOk();
        $second->assertJsonPath('library.reused_existing', true);
        $second->assertJsonPath('load_state', 'library_complete');

        $countAfterSecond = LibraryItem::query()
            ->where('organization_id', $organization->id)
            ->where('slug', 'field-crop-wheat-farming-needs')
            ->count();

        $this->assertSame(1, $countAfterFirst);
        $this->assertSame(1, $countAfterSecond);
    }

    public function test_unverified_crop_shows_uncertainty_for_missing_verified_data(): void
    {
        Organization::create(['name' => 'WSA Demo', 'slug' => 'wsa-demo']);

        $response = $this->getJson('/api/v1/public/field-crops/farming-needs-profile?'.http_build_query([
            'organization' => 'wsa-demo',
            'selected_crop_id' => 'tobacco',
            'selected_crop_name' => 'التبغ',
            'selected_category_id' => 'other',
            'selected_category_name' => 'محاصيل أخرى',
        ]));

        $response->assertOk();
        $response->assertJsonPath('load_state', 'insufficient_verified_sources');
        $firstSection = $response->json('sections.0');
        $this->assertStringContainsString(
            'لا تتوفر حاليًا معلومات علمية موثقة كافية',
            (string) ($firstSection['content'] ?? ''),
        );
    }

    public function test_partial_library_document_preserves_existing_and_fills_missing(): void
    {
        $organization = Organization::create(['name' => 'WSA Demo', 'slug' => 'wsa-demo']);
        $repository = app(FieldCropLibraryRepository::class);

        $repository->mergeSections($organization->id, [
            'selected_crop_id' => 'rice',
            'selected_crop_name' => 'الأرز',
            'selected_category_id' => 'grains',
            'selected_category_name' => 'محاصيل الحبوب',
        ], [
            'commercial_scientific_name' => [
                'content' => 'الأرز Oryza sativa L. محصول حبوبي أساسي في أنظمة الري.',
                'source' => [
                    'organization' => 'FAO',
                    'title' => 'Rice market monitor',
                    'year' => 2024,
                    'url' => 'https://www.fao.org/worldfoodsituation/foodpricesindex/en/',
                    'source_type' => 'government',
                ],
                'verified' => true,
            ],
        ]);

        $response = $this->getJson('/api/v1/public/field-crops/farming-needs-profile?'.http_build_query([
            'organization' => 'wsa-demo',
            'selected_crop_id' => 'rice',
            'selected_crop_name' => 'الأرز',
        ]));

        $response->assertOk();
        $response->assertJsonPath('load_state', 'library_partial_completed');
        $scientificSection = collect($response->json('sections'))
            ->firstWhere('key', 'commercial_scientific_name');
        $this->assertStringContainsString('Oryza sativa', (string) ($scientificSection['content'] ?? ''));
        $seedSection = collect($response->json('sections'))
            ->firstWhere('key', 'seed_rate');
        $this->assertStringContainsString(
            ScientificSourceValidator::UNCERTAINTY_MESSAGE,
            (string) ($seedSection['content'] ?? ''),
        );
    }

    public function test_profile_service_requires_crop_context(): void
    {
        $organization = Organization::create(['name' => 'WSA Demo 2', 'slug' => 'wsa-demo-2']);

        $profile = app(FieldCropCultivationProfileService::class)->getProfile($organization->id, [
            'selected_crop_id' => 'rice',
            'selected_crop_name' => 'الأرز',
            'selected_category_id' => 'grains',
            'selected_category_name' => 'محاصيل الحبوب',
        ]);

        $this->assertSame('rice', $profile['crop']['id']);
        $this->assertCount(13, $profile['sections']);
        $this->assertSame('insufficient_verified_sources', $profile['load_state']);
    }
}
