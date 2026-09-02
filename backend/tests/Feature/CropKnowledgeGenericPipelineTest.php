<?php

namespace Tests\Feature;

use App\Models\LibraryItem;
use App\Models\Organization;
use App\Services\Agriculture\CropKnowledgeSectionCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CropKnowledgeGenericPipelineTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    private function openAlexWork(string $title, string $abstract, string $doi, string $institution): array
    {
        $inverted = [];
        foreach (preg_split('/\s+/', $abstract) as $index => $word) {
            $inverted[$word][] = $index;
        }

        return [
            'id' => 'https://openalex.org/W'.md5($title),
            'display_name' => $title,
            'doi' => 'https://doi.org/'.$doi,
            'publication_year' => 2023,
            'abstract_inverted_index' => $inverted,
            'primary_location' => [
                'landing_page_url' => 'https://doi.org/'.$doi,
                'source' => ['display_name' => 'Journal of Agronomy'],
            ],
            'authorships' => [[
                'institutions' => [[
                    'display_name' => $institution,
                    'type' => 'education',
                ]],
            ]],
        ];
    }

  /** @param list<array<string, mixed>> $works */
    private function fakeOpenAlex(array $works): void
    {
        Http::fake([
            'api.openalex.org/works*' => Http::response(['results' => $works], 200),
            'api.crossref.org/works*' => Http::response(['message' => ['items' => []]], 200),
        ]);
    }

    /** @return array<string, mixed> */
    private function crossRefWork(string $title, string $abstract, string $doi, string $publisher): array
    {
        return [
            'DOI' => $doi,
            'title' => [$title],
            'abstract' => $abstract,
            'publisher' => $publisher,
            'container-title' => ['Journal of Agricultural Science'],
            'issued' => ['date-parts' => [[2022]]],
        ];
    }

    private function farmingNeedsQuery(
        string $cropId,
        string $cropName,
        string $categoryId = 'grains',
        string $categoryName = 'محاصيل الحبوب',
        string $scientificName = '',
    ): string {
        return '/api/v1/public/field-crops/farming-needs-profile?'.http_build_query(array_filter([
            'organization' => 'wsa-demo',
            'selected_crop_id' => $cropId,
            'selected_crop_name' => $cropName,
            'selected_category_id' => $categoryId,
            'selected_category_name' => $categoryName,
            'knowledge_option' => 'farming-needs',
            'scientific_name' => $scientificName,
        ]));
    }

    private function profileQuery(string $cropId, string $cropName, string $option, string $scientificName = ''): string
    {
        return '/api/v1/public/field-crops/farming-needs-profile?'.http_build_query(array_filter([
            'organization' => 'wsa-demo',
            'selected_crop_id' => $cropId,
            'selected_crop_name' => $cropName,
            'selected_category_id' => 'grains',
            'selected_category_name' => 'محاصيل الحبوب',
            'knowledge_option' => $option,
            'scientific_name' => $scientificName,
        ]));
    }

    public function test_new_generic_crop_persists_and_reuses_library_without_duplicates(): void
    {
        Organization::create(['name' => 'WSA Demo', 'slug' => 'wsa-demo']);

        $this->fakeOpenAlex([
            $this->openAlexWork(
                'Sorghum bicolor agronomy field trials in dry regions',
                'Sorghum bicolor field trials demonstrate seed rate and fertilization responses under rainfed agriculture.',
                '10.1000/sorghum-agronomy',
                'University of Agriculture',
            ),
        ]);

        $url = $this->profileQuery('sorghum', 'الذرة الرفيعة', 'scientific-research', 'Sorghum bicolor');

        $first = $this->getJson($url);
        $first->assertOk();
        $first->assertJsonPath('crop.id', 'sorghum');
        $first->assertJsonPath('knowledge_option', 'scientific-research');
        $this->assertContains($first->json('load_state'), [
            'scientific_generated',
            'library_partial_completed',
        ]);
        $this->assertNotEmpty($first->json('library.scientific_sections_retrieved'));

        $itemId = $first->json('library.item_id');
        $this->assertNotNull($itemId);

        Http::fake([
            'api.openalex.org/works*' => Http::response(['results' => []], 500),
        ]);

        $second = $this->getJson($url);
        $second->assertOk();
        $second->assertJsonPath('library.item_id', $itemId);
        $second->assertJsonPath('library.reused_existing', true);
        $second->assertJsonPath('library.scientific_sections_retrieved', []);

        $this->assertSame(
            1,
            LibraryItem::query()->where('slug', 'field-crop-sorghum-scientific-research')->count(),
        );
    }

    public function test_scientific_research_and_industries_options_are_generic(): void
    {
        Organization::create(['name' => 'WSA Demo', 'slug' => 'wsa-demo']);

        $this->fakeOpenAlex([
            $this->openAlexWork(
                'Oats Avena sativa food industry applications',
                'Avena sativa oats processing supports food products and livestock feed value chains.',
                '10.1000/oats-industry',
                'FAO',
            ),
        ]);

        foreach (['scientific-research', 'industries'] as $option) {
            $response = $this->getJson($this->profileQuery('oats', 'الشوفان', $option, 'Avena sativa'));
            $response->assertOk();
            $response->assertJsonPath('knowledge_option', $option);
            $this->assertNotSame('knowledge_option_not_implemented', $response->json('load_state'));
            $sectionCount = count(CropKnowledgeSectionCatalog::keysFor($option));
            $response->assertJsonCount($sectionCount, 'sections');
        }
    }

    public function test_library_search_finds_persisted_crop_knowledge(): void
    {
        $organization = Organization::create(['name' => 'WSA Demo', 'slug' => 'wsa-demo']);
        $user = \App\Models\User::factory()->create();
        $organization->members()->attach($user->id, ['role' => 'admin']);

        $this->fakeOpenAlex([
            $this->openAlexWork(
                'Sorghum bicolor research overview',
                'Sorghum bicolor is a major cereal crop with extensive agricultural research.',
                '10.1000/sorghum-overview',
                'CIMMYT',
            ),
        ]);

        $this->getJson($this->profileQuery('sorghum', 'الذرة الرفيعة', 'scientific-research', 'Sorghum bicolor'))
            ->assertOk();

        $search = $this->actingAs($user)
            ->withHeader('X-Organization-Id', (string) $organization->id)
            ->getJson('/api/v1/library/search?q='.urlencode('Sorghum'));

        $search->assertOk();
        $payload = $search->json();
        $rows = is_array($payload['data'] ?? null) ? $payload['data'] : $payload;
        $titles = collect($rows)->pluck('title_ar')->implode(' ');
        $this->assertStringContainsString('الذرة الرفيعة', $titles);
    }

    public function test_crop_knowledge_item_show_includes_sections_and_sources(): void
    {
        $organization = Organization::create(['name' => 'WSA Demo', 'slug' => 'wsa-demo']);
        $user = \App\Models\User::factory()->create();
        $organization->members()->attach($user->id, ['role' => 'admin']);

        $this->fakeOpenAlex([
            $this->openAlexWork(
                'Sorghum bicolor breeding genetics',
                'Sorghum bicolor breeding programs improve drought tolerance and yield stability.',
                '10.1000/sorghum-genetics',
                'USDA ARS',
            ),
        ]);

        $profile = $this->getJson($this->profileQuery('sorghum', 'الذرة الرفيعة', 'industries', 'Sorghum bicolor'));
        $profile->assertOk();
        $itemId = $profile->json('library.item_id');
        $this->assertNotNull($itemId);

        $show = $this->actingAs($user)
            ->withHeader('X-Organization-Id', (string) $organization->id)
            ->getJson('/api/v1/library/crop-knowledge/items/'.$itemId);

        $show->assertOk();
        $show->assertJsonStructure([
            'sections' => [['key', 'title', 'content', 'source', 'verified']],
            'references',
            'crop' => ['id', 'name'],
        ]);
        $show->assertJsonPath('crop.id', 'sorghum');
    }

    public function test_sesame_farming_needs_resolves_taxonomy_and_discovers_externally(): void
    {
        Organization::create(['name' => 'WSA Demo', 'slug' => 'wsa-demo']);

        $this->fakeOpenAlex([
            $this->openAlexWork(
                'Sesamum indicum seed rate and sowing density in rainfed agriculture',
                'Sesamum indicum cultivation requires seed rate management and sowing density planning for sesame production systems.',
                '10.1000/sesame-seed',
                'Alexandria University Faculty of Agriculture',
            ),
        ]);

        $response = $this->getJson($this->farmingNeedsQuery('sesame', 'السمسم', 'oil', 'المحاصيل الزيتية'));
        $response->assertOk();
        $response->assertJsonPath('crop.id', 'sesame');
        $response->assertJsonPath('crop.scientific_name', 'Sesamum indicum');
        $this->assertNotEmpty($response->json('library.scientific_sections_retrieved'));
        $this->assertContains('external_openalex', $response->json('library.discoverers_used'));
        $this->assertTrue($response->json('research_agent.discovery.internet_first'));
        $this->assertNull($response->json('message'));

        $this->assertDatabaseHas('library_items', [
            'slug' => 'field-crop-sesame-farming-needs',
        ]);
    }

    public function test_openalex_failure_falls_back_to_crossref_for_generic_crop(): void
    {
        Organization::create(['name' => 'WSA Demo', 'slug' => 'wsa-demo']);

        Http::fake([
            'api.openalex.org/works*' => Http::response(['results' => []], 500),
            'api.crossref.org/works*' => Http::response([
                'message' => [
                    'items' => [
                        $this->crossRefWork(
                            'Sesamum indicum irrigation scheduling in arid regions',
                            'Sesamum indicum irrigation requirements influence water use efficiency in sesame agriculture.',
                            '10.1000/sesame-irrigation',
                            'Mansoura University',
                        ),
                    ],
                ],
            ], 200),
        ]);

        $response = $this->getJson($this->farmingNeedsQuery('sesame', 'السمسم', 'oil', 'المحاصيل الزيتية'));
        $response->assertOk();
        $this->assertNotEmpty($response->json('library.scientific_sections_retrieved'));
        $this->assertContains('external_crossref', $response->json('library.discoverers_used'));
        $this->assertNotSame('retrieval_error', $response->json('load_state'));
    }

    public function test_library_partial_hit_merges_with_external_discovery(): void
    {
        $organization = Organization::create(['name' => 'WSA Demo', 'slug' => 'wsa-demo']);

        LibraryItem::query()->create([
            'organization_id' => $organization->id,
            'slug' => 'field-crop-barley-farming-needs',
            'title' => 'Crop knowledge profile: barley / farming-needs',
            'title_ar' => 'زراعة واحتياجات محصول الشعير',
            'item_type' => 'crop_cultivation_profile',
            'locale' => 'ar',
            'publication_status' => 'published',
            'published_at' => now(),
            'metadata' => [
                'field_crop_id' => 'barley',
                'service_option' => 'farming-needs',
                'knowledge_option' => 'farming-needs',
                'cultivation_sections' => [
                    'commercial_scientific_name' => [
                        'content' => 'Hordeum vulgare is the scientific name for barley.',
                        'verified' => true,
                        'source' => [
                            'organization' => 'USDA ARS',
                            'title' => 'Hordeum vulgare reference',
                            'year' => 2020,
                            'url' => 'https://doi.org/10.1000/barley-taxonomy',
                            'doi' => '10.1000/barley-taxonomy',
                            'source_type' => 'government',
                        ],
                    ],
                ],
            ],
        ]);

        $this->fakeOpenAlex([
            $this->openAlexWork(
                'Hordeum vulgare seed rate and sowing density trials',
                'Hordeum vulgare seed rate trials demonstrate optimal sowing density for barley agriculture.',
                '10.1000/barley-seed',
                'University of Agriculture',
            ),
        ]);

        $response = $this->getJson($this->farmingNeedsQuery('barley', 'الشعير'));
        $response->assertOk();
        $response->assertJsonPath('library.reused_existing', true);
        $this->assertNotEmpty($response->json('library.scientific_sections_retrieved'));

        $scientificSection = collect($response->json('sections'))
            ->firstWhere('key', 'commercial_scientific_name');
        $this->assertTrue((bool) ($scientificSection['verified'] ?? false));
        $this->assertStringContainsString('Hordeum vulgare', (string) ($scientificSection['content'] ?? ''));
    }

    public function test_complete_library_skips_external_discovery_on_subsequent_request(): void
    {
        $organization = Organization::create(['name' => 'WSA Demo', 'slug' => 'wsa-demo']);
        $this->seed(\Database\Seeders\FieldCropCultivationSeeder::class);

        $first = $this->getJson($this->farmingNeedsQuery('wheat', 'القمح'));
        $first->assertOk();
        $first->assertJsonPath('load_state', 'library_complete');

        Http::fake([
            'api.openalex.org/works*' => Http::response(['results' => []], 500),
            'api.crossref.org/works*' => Http::response(['message' => ['items' => []]], 500),
        ]);

        $second = $this->getJson($this->farmingNeedsQuery('wheat', 'القمح'));
        $second->assertOk();
        $second->assertJsonPath('load_state', 'library_complete');
        $second->assertJsonPath('library.reused_existing', true);
        $second->assertJsonPath('library.scientific_sections_retrieved', []);
        $second->assertJsonPath('library.discoverers_used', []);
    }

    public function test_library_crop_files_discoverer_reads_phase_two_metadata(): void
    {
        $organization = Organization::create(['name' => 'WSA Demo', 'slug' => 'wsa-demo']);

        LibraryItem::query()->create([
            'organization_id' => $organization->id,
            'slug' => 'library-file-sesame-farming-needs',
            'title' => 'Sesame farming guide',
            'title_ar' => 'دليل زراعة السمسم',
            'summary_ar' => 'معلومات عن كمية التقاوي وزراعة السمسم Sesamum indicum.',
            'item_type' => 'document',
            'locale' => 'ar',
            'publication_status' => 'published',
            'published_at' => now(),
            'file_path' => 'library/sesame-guide.pdf',
            'metadata' => [
                'plant_production_category_id' => 'oil',
                'field_crop_id' => 'sesame',
                'library_file_section' => 'farming-needs',
                'scientific_source' => [
                    'organization' => 'Ministry of Agriculture',
                    'title' => 'Sesame cultivation extension bulletin',
                    'year' => 2019,
                    'url' => 'https://example.org/sesame-bulletin',
                    'source_type' => 'extension_publication',
                ],
            ],
        ]);

        Http::fake([
            'api.openalex.org/works*' => Http::response(['results' => []], 200),
            'api.crossref.org/works*' => Http::response(['message' => ['items' => []]], 200),
        ]);

        $response = $this->getJson($this->farmingNeedsQuery('sesame', 'السمسم', 'oil', 'المحاصيل الزيتية'));
        $response->assertOk();
        $this->assertContains('library_crop_files', $response->json('library.discoverers_used'));
        $this->assertNotEmpty($response->json('library.scientific_sections_retrieved'));
    }
}
