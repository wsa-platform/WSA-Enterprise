<?php

namespace Tests\Feature;

use App\Models\LibraryItem;
use App\Models\Organization;
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

    /** @param  array<string, mixed>  $payload */
    private function assertZeroLibrarySearch(array $payload): void
    {
        $this->assertSame([], $payload['library']['discoverers_used'] ?? ['missing']);
        $this->assertSame([], $payload['library']['scientific_sections_retrieved'] ?? ['missing']);
        $this->assertNotContains('library_crop_files', $payload['library']['discoverers_used'] ?? []);
        $this->assertNotContains($payload['load_state'] ?? null, [
            'library_complete',
            'library_partial_completed',
            'library_missing',
        ]);
    }

    public function test_new_generic_crop_uses_scientific_architecture_without_library_search(): void
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
        $this->assertZeroLibrarySearch($first->json());

        Http::fake([
            'api.openalex.org/works*' => Http::response(['results' => []], 500),
            'api.crossref.org/works*' => Http::response(['message' => ['items' => []]], 200),
        ]);

        $second = $this->getJson($url);
        $second->assertOk();
        $second->assertJsonPath('crop.id', 'sorghum');
        $this->assertZeroLibrarySearch($second->json());
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
            $response->assertJsonPath('crop.id', 'oats');
            $this->assertZeroLibrarySearch($response->json());
            $this->assertNotSame('knowledge_option_not_implemented', $response->json('load_state'));
        }
    }

    public function test_independent_library_search_still_finds_manually_stored_items(): void
    {
        $organization = Organization::create(['name' => 'WSA Demo', 'slug' => 'wsa-demo']);
        $user = \App\Models\User::factory()->create();
        $organization->members()->attach($user->id, ['role' => 'admin']);

        LibraryItem::query()->create([
            'organization_id' => $organization->id,
            'slug' => 'field-crop-sorghum-scientific-research',
            'title' => 'Crop knowledge profile: sorghum / scientific-research',
            'title_ar' => 'الذرة الرفيعة scientific research',
            'item_type' => 'crop_cultivation_profile',
            'locale' => 'ar',
            'publication_status' => 'published',
            'published_at' => now(),
            'metadata' => [
                'field_crop_id' => 'sorghum',
                'knowledge_option' => 'scientific-research',
            ],
        ]);

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

        $item = LibraryItem::query()->create([
            'organization_id' => $organization->id,
            'slug' => 'field-crop-sorghum-industries',
            'title' => 'Crop knowledge profile: sorghum / industries',
            'title_ar' => 'صناعات محصول الذرة الرفيعة',
            'item_type' => 'crop_cultivation_profile',
            'locale' => 'ar',
            'publication_status' => 'published',
            'published_at' => now(),
            'metadata' => [
                'field_crop_id' => 'sorghum',
                'knowledge_option' => 'industries',
                'cultivation_sections' => [
                    'commercial_scientific_name' => [
                        'content' => 'Sorghum bicolor.',
                        'verified' => true,
                        'source' => [
                            'organization' => 'USDA ARS',
                            'title' => 'Sorghum reference',
                            'year' => 2020,
                            'url' => 'https://doi.org/10.1000/sorghum',
                            'source_type' => 'government',
                        ],
                    ],
                ],
            ],
        ]);

        $show = $this->actingAs($user)
            ->withHeader('X-Organization-Id', (string) $organization->id)
            ->getJson('/api/v1/library/crop-knowledge/items/'.$item->id);

        $show->assertOk();
        $show->assertJsonStructure([
            'sections' => [['key', 'title', 'content', 'source', 'verified']],
            'references',
            'crop' => ['id', 'name'],
        ]);
        $show->assertJsonPath('crop.id', 'sorghum');
    }

    public function test_sesame_farming_needs_resolves_taxonomy_without_library_search(): void
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
        $this->assertZeroLibrarySearch($response->json());
        $this->assertNull($response->json('message'));
    }

    public function test_openalex_failure_still_allows_crossref_scientific_search(): void
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
        $this->assertZeroLibrarySearch($response->json());
        $this->assertNotSame('retrieval_error', $response->json('load_state'));
    }

    public function test_stored_library_item_does_not_trigger_library_search_from_research(): void
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
        $response->assertJsonPath('crop.id', 'barley');
        $this->assertZeroLibrarySearch($response->json());
    }

    public function test_seeded_library_does_not_skip_scientific_research(): void
    {
        Organization::create(['name' => 'WSA Demo', 'slug' => 'wsa-demo']);
        $this->seed(\Database\Seeders\FieldCropCultivationSeeder::class);

        Http::fake([
            'api.openalex.org/works*' => Http::response(['results' => []], 500),
            'api.crossref.org/works*' => Http::response(['message' => ['items' => []]], 500),
        ]);

        $response = $this->getJson($this->farmingNeedsQuery('wheat', 'القمح'));
        $response->assertOk();
        $this->assertZeroLibrarySearch($response->json());
        $this->assertNotSame('library_complete', $response->json('load_state'));
    }

    public function test_library_crop_files_are_not_used_by_scientific_research(): void
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
            ],
        ]);

        Http::fake([
            'api.openalex.org/works*' => Http::response(['results' => []], 200),
            'api.crossref.org/works*' => Http::response(['message' => ['items' => []]], 200),
        ]);

        $response = $this->getJson($this->farmingNeedsQuery('sesame', 'السمسم', 'oil', 'المحاصيل الزيتية'));
        $response->assertOk();
        $this->assertZeroLibrarySearch($response->json());
        $this->assertNotContains('library_crop_files', $response->json('library.discoverers_used') ?? []);
    }
}
