<?php

namespace Tests\Feature;

use App\Models\LibraryItem;
use App\Models\Organization;
use App\Services\Agriculture\FieldCropCultivationProfileService;
use Database\Seeders\FieldCropCultivationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FieldCropCultivationTest extends TestCase
{
    use RefreshDatabase;

    private function fakeEmptyProviders(): void
    {
        Http::fake([
            'api.openalex.org/works*' => Http::response(['results' => []], 200),
            'api.crossref.org/works*' => Http::response(['message' => ['items' => []]], 200),
        ]);
    }

    private function seedCultivationLibrary(): Organization
    {
        $organization = Organization::create(['name' => 'WSA Demo', 'slug' => 'wsa-demo']);
        $this->seed(FieldCropCultivationSeeder::class);

        return $organization;
    }

    /** @param  array<string, mixed>  $payload */
    private function assertScientificCropContract(array $payload, string $cropId): void
    {
        $this->assertSame($cropId, $payload['crop']['id'] ?? null);
        $this->assertSame([], $payload['library']['discoverers_used'] ?? ['missing']);
        $this->assertSame([], $payload['library']['scientific_sections_retrieved'] ?? ['missing']);
        $this->assertNotContains($payload['load_state'] ?? null, [
            'library_complete',
            'library_partial_completed',
            'library_missing',
        ]);
        $this->assertSame('library_search_separated', $payload['research_agent']['discovery']['reason'] ?? $payload['discovery']['reason'] ?? null);
    }

    public function test_public_wheat_farming_needs_profile_uses_scientific_architecture(): void
    {
        $this->fakeEmptyProviders();
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
        $this->assertScientificCropContract($response->json(), 'wheat');
        $this->assertIsArray($response->json('sections'));

        $this->assertDatabaseHas('library_items', [
            'organization_id' => $organization->id,
            'slug' => 'field-crop-wheat-farming-needs',
        ]);
    }

    public function test_corn_profile_is_crop_specific_not_wheat(): void
    {
        $this->fakeEmptyProviders();
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
        $this->assertScientificCropContract($response->json(), 'corn');
        $body = json_encode($response->json());
        $this->assertStringNotContainsString('Triticum aestivum', (string) $body);
    }

    public function test_repeat_request_does_not_duplicate_seeded_library_items(): void
    {
        $this->fakeEmptyProviders();
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
        $this->assertScientificCropContract($second->json(), 'wheat');

        $countAfterSecond = LibraryItem::query()
            ->where('organization_id', $organization->id)
            ->where('slug', 'field-crop-wheat-farming-needs')
            ->count();

        $this->assertSame(1, $countAfterFirst);
        $this->assertSame(1, $countAfterSecond);
    }

    public function test_unverified_crop_returns_honest_scientific_status(): void
    {
        $this->fakeEmptyProviders();
        Organization::create(['name' => 'WSA Demo', 'slug' => 'wsa-demo']);

        $response = $this->getJson('/api/v1/public/field-crops/farming-needs-profile?'.http_build_query([
            'organization' => 'wsa-demo',
            'selected_crop_id' => 'tobacco',
            'selected_crop_name' => 'التبغ',
            'selected_category_id' => 'other',
            'selected_category_name' => 'محاصيل أخرى',
        ]));

        $response->assertOk();
        $this->assertScientificCropContract($response->json(), 'tobacco');
        $this->assertContains($response->json('load_state'), [
            'scientific_generated',
            'insufficient_evidence',
            'no_search_results',
            'synthesis_completed',
            'search_empty',
            'validation_insufficient',
        ]);
    }

    public function test_partial_library_document_does_not_become_library_search(): void
    {
        $this->fakeEmptyProviders();
        Organization::create(['name' => 'WSA Demo', 'slug' => 'wsa-demo']);

        $response = $this->getJson('/api/v1/public/field-crops/farming-needs-profile?'.http_build_query([
            'organization' => 'wsa-demo',
            'selected_crop_id' => 'rice',
            'selected_crop_name' => 'الأرز',
        ]));

        $response->assertOk();
        $this->assertScientificCropContract($response->json(), 'rice');
        $this->assertSame([], $response->json('library.discoverers_used'));
    }

    public function test_profile_service_requires_crop_context(): void
    {
        $this->fakeEmptyProviders();
        Organization::create(['name' => 'WSA Demo 2', 'slug' => 'wsa-demo-2']);

        $profile = app(FieldCropCultivationProfileService::class)->getProfile(1, [
            'selected_crop_id' => 'rice',
            'selected_crop_name' => 'الأرز',
            'selected_category_id' => 'grains',
            'selected_category_name' => 'محاصيل الحبوب',
        ]);

        $this->assertSame('rice', $profile['crop']['id']);
        $this->assertIsArray($profile['sections']);
        $this->assertScientificCropContract($profile, 'rice');
    }

    public function test_oats_without_library_uses_same_scientific_pipeline(): void
    {
        Http::fake([
            'api.openalex.org/works*' => Http::response(['results' => [[
                'id' => 'https://openalex.org/WoatsTest',
                'display_name' => 'Avena sativa oats agronomy fertilization',
                'doi' => 'https://doi.org/10.1000/oats-test',
                'publication_year' => 2022,
                'abstract_inverted_index' => ['Avena' => [0], 'sativa' => [1], 'oats' => [2], 'fertilization' => [3], 'research' => [4]],
                'primary_location' => [
                    'landing_page_url' => 'https://doi.org/10.1000/oats-test',
                    'source' => ['display_name' => 'Field Crops Research'],
                ],
                'authorships' => [[
                    'institutions' => [['display_name' => 'University of Agriculture', 'type' => 'education']],
                ]],
            ]]], 200),
            'api.crossref.org/works*' => Http::response(['message' => ['items' => []]], 200),
        ]);
        Organization::create(['name' => 'WSA Demo', 'slug' => 'wsa-demo']);

        $response = $this->getJson('/api/v1/public/field-crops/farming-needs-profile?'.http_build_query([
            'organization' => 'wsa-demo',
            'selected_crop_id' => 'oats',
            'selected_crop_name' => 'الشوفان',
            'selected_category_id' => 'grains',
            'selected_category_name' => 'محاصيل الحبوب',
            'scientific_name' => 'Avena sativa',
        ]));

        $response->assertOk();
        $response->assertJsonPath('crop.id', 'oats');
        $this->assertScientificCropContract($response->json(), 'oats');
        $body = json_encode($response->json('sections'));
        $this->assertStringNotContainsString('Triticum aestivum', (string) $body);
        $this->assertStringNotContainsString('Zea mays', (string) $body);
    }

    public function test_generic_new_crop_uses_same_pipeline_without_special_handler(): void
    {
        $this->fakeEmptyProviders();
        Organization::create(['name' => 'WSA Demo', 'slug' => 'wsa-demo']);

        $response = $this->getJson('/api/v1/public/field-crops/farming-needs-profile?'.http_build_query([
            'organization' => 'wsa-demo',
            'selected_crop_id' => 'sorghum',
            'selected_crop_name' => 'الذرة الرفيعة',
            'selected_category_id' => 'grains',
            'selected_category_name' => 'محاصيل الحبوب',
        ]));

        $response->assertOk();
        $response->assertJsonPath('crop.id', 'sorghum');
        $this->assertScientificCropContract($response->json(), 'sorghum');
        $response->assertJsonPath('knowledge_option', 'farming-needs');
    }
}
