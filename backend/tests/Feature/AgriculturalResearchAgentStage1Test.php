<?php

namespace Tests\Feature;

use App\Models\LibraryItem;
use App\Models\Organization;
use App\Services\Agriculture\Research\AgriculturalResearchAgent;
use App\Services\Agriculture\Research\ResearchPlanner;
use App\Services\Agriculture\ScientificSourceDiscoveryPipeline;
use App\Services\Agriculture\ScientificSourceValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AgriculturalResearchAgentStage1Test extends TestCase
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

    public function test_pipeline_discoverer_order_is_internet_first(): void
    {
        $pipeline = app(ScientificSourceDiscoveryPipeline::class);
        $order = $pipeline->discovererOrder();

        $this->assertSame(
            ['external_openalex', 'external_crossref', 'library_structured', 'library_crop_files', 'library_rag', 'library_keyword'],
            $order,
        );
    }

    public function test_external_discovery_runs_before_library_discovery(): void
    {
        Organization::create(['name' => 'WSA Demo', 'slug' => 'wsa-demo']);

        LibraryItem::query()->create([
            'organization_id' => 1,
            'slug' => 'library-file-millet-farming-needs',
            'title' => 'Millet farming guide',
            'title_ar' => 'دليل زراعة الدخن',
            'summary_ar' => 'معلومات عن كمية التقاوي Pennisetum glaucum.',
            'item_type' => 'document',
            'locale' => 'ar',
            'publication_status' => 'published',
            'published_at' => now(),
            'file_path' => 'library/millet-guide.pdf',
            'metadata' => [
                'plant_production_category_id' => 'grains',
                'field_crop_id' => 'millet',
                'library_file_section' => 'farming-needs',
                'scientific_source' => [
                    'organization' => 'Ministry of Agriculture',
                    'title' => 'Millet cultivation extension bulletin',
                    'year' => 2019,
                    'url' => 'https://example.org/millet-bulletin',
                    'source_type' => 'extension_publication',
                ],
            ],
        ]);

        Http::fake([
            'api.openalex.org/works*' => Http::response([
                'results' => [
                    $this->openAlexWork(
                        'Pennisetum glaucum seed rate and sowing density in rainfed agriculture',
                        'Pennisetum glaucum cultivation requires seed rate management and sowing density planning for millet production systems.',
                        '10.1000/millet-seed',
                        'University of Agriculture',
                    ),
                ],
            ], 200),
            'api.crossref.org/works*' => Http::response(['message' => ['items' => []]], 200),
        ]);

        $response = $this->getJson('/api/v1/public/field-crops/farming-needs-profile?'.http_build_query([
            'organization' => 'wsa-demo',
            'selected_crop_id' => 'millet',
            'selected_crop_name' => 'الدخن',
            'selected_category_id' => 'grains',
            'selected_category_name' => 'محاصيل الحبوب',
            'scientific_name' => 'Pennisetum glaucum',
        ]));

        $response->assertOk();
        $discoverers = $response->json('library.discoverers_used');
        $this->assertContains('external_openalex', $discoverers);
        $this->assertTrue($response->json('research_agent.discovery.internet_first'));

        if (in_array('library_crop_files', $discoverers, true)) {
            $this->assertLessThan(
                array_search('library_crop_files', $discoverers, true),
                array_search('external_openalex', $discoverers, true),
            );
        }
    }

    public function test_library_discovery_remains_available_after_external_research(): void
    {
        Organization::create(['name' => 'WSA Demo', 'slug' => 'wsa-demo']);

        Http::fake([
            'api.openalex.org/works*' => Http::response(['results' => []], 200),
            'api.crossref.org/works*' => Http::response(['message' => ['items' => []]], 200),
        ]);

        LibraryItem::query()->create([
            'organization_id' => 1,
            'slug' => 'library-file-rye-farming-needs',
            'title' => 'Rye farming guide',
            'title_ar' => 'دليل زراعة الجاودار',
            'summary_ar' => 'الجاودار Secale cereale seed rate sowing density guidance.',
            'item_type' => 'document',
            'locale' => 'ar',
            'publication_status' => 'published',
            'published_at' => now(),
            'file_path' => 'library/rye-guide.pdf',
            'metadata' => [
                'plant_production_category_id' => 'grains',
                'field_crop_id' => 'rye',
                'library_file_section' => 'farming-needs',
                'scientific_source' => [
                    'organization' => 'USDA ARS',
                    'title' => 'Rye cultivation bulletin',
                    'year' => 2020,
                    'url' => 'https://example.org/rye-bulletin',
                    'source_type' => 'extension_publication',
                ],
            ],
        ]);

        $response = $this->getJson('/api/v1/public/field-crops/farming-needs-profile?'.http_build_query([
            'organization' => 'wsa-demo',
            'selected_crop_id' => 'rye',
            'selected_crop_name' => 'الجاودار',
            'selected_category_id' => 'grains',
            'selected_category_name' => 'محاصيل الحبوب',
            'scientific_name' => 'Secale cereale',
        ]));

        $response->assertOk();
        $this->assertContains('library_crop_files', $response->json('library.discoverers_used'));
    }

    public function test_research_agent_api_supports_generic_agricultural_query(): void
    {
        Organization::create(['name' => 'WSA Demo', 'slug' => 'wsa-demo']);

        Http::fake([
            'api.openalex.org/works*' => Http::response([
                'results' => [
                    $this->openAlexWork(
                        'Integrated irrigation scheduling for cereal crops in arid regions',
                        'Irrigation scheduling improves water use efficiency for cereal crop production in arid agriculture.',
                        '10.1000/irrigation-cereal',
                        'FAO',
                    ),
                ],
            ], 200),
            'api.crossref.org/works*' => Http::response(['message' => ['items' => []]], 200),
        ]);

        $response = $this->postJson('/api/v1/public/research-agent/query', [
            'organization' => 'wsa-demo',
            'query' => 'irrigation scheduling for cereal crops in arid regions',
            'domain' => 'irrigation',
        ]);

        $response->assertOk();
        $response->assertJsonPath('status', 'scientific_generated');
        $response->assertJsonPath('plan.intent', 'generic_research');
        $response->assertJsonPath('plan.agricultural_domain', 'irrigation');
        $response->assertJsonStructure([
            'plan' => ['user_query', 'intent', 'agricultural_domain', 'research_sequence'],
            'research' => ['sections', 'references'],
            'discovery' => ['discoverers_used', 'internet_first'],
        ]);
    }

    public function test_research_planner_supports_multiple_agricultural_domains(): void
    {
        $planner = app(ResearchPlanner::class);

        $irrigationPlan = $planner->plan([
            'query' => 'drip irrigation scheduling for orchard crops',
        ]);
        $this->assertSame('irrigation', $irrigationPlan->agriculturalDomain);

        $beePlan = $planner->plan([
            'query' => 'beekeeping pollination management for orchards',
        ]);
        $this->assertSame('beekeeping', $beePlan->agriculturalDomain);

        $cropPlan = $planner->plan([
            'selected_crop_id' => 'oats',
            'selected_crop_name' => 'الشوفان',
            'knowledge_option' => 'farming-needs',
        ]);
        $this->assertSame('crop_profile', $cropPlan->intent);
        $this->assertSame('crop_cultivation', $cropPlan->agriculturalDomain);
        $this->assertNotEmpty($cropPlan->researchSections);
    }

    public function test_openalex_failure_falls_back_to_crossref_through_research_agent(): void
    {
        Organization::create(['name' => 'WSA Demo', 'slug' => 'wsa-demo']);

        Http::fake([
            'api.openalex.org/works*' => Http::response(['results' => []], 500),
            'api.crossref.org/works*' => Http::response([
                'message' => [
                    'items' => [
                        $this->crossRefWork(
                            'Pennisetum glaucum fertilization response trials',
                            'Pennisetum glaucum fertilization requirements influence nutrient management in millet agriculture.',
                            '10.1000/millet-fertilizer',
                            'University of Agriculture',
                        ),
                    ],
                ],
            ], 200),
        ]);

        $response = $this->postJson('/api/v1/public/research-agent/query', [
            'organization' => 'wsa-demo',
            'query' => 'fertilization for millet Pennisetum glaucum',
            'domain' => 'fertilization',
            'entities' => [
                ['type' => 'scientific_name', 'value' => 'Pennisetum glaucum'],
            ],
        ]);

        $response->assertOk();
        $this->assertContains('external_crossref', $response->json('discovery.discoverers_used'));
        $this->assertNotSame('insufficient_verified_sources', $response->json('status'));
    }

    public function test_architecture_is_not_tied_to_one_crop(): void
    {
        Organization::create(['name' => 'WSA Demo', 'slug' => 'wsa-demo']);

        Http::fake([
            'api.openalex.org/works*' => Http::response(['results' => []], 200),
            'api.crossref.org/works*' => Http::response(['message' => ['items' => []]], 200),
        ]);

        foreach (['sorghum', 'oats', 'barley'] as $cropId) {
            $plan = app(ResearchPlanner::class)->plan([
                'selected_crop_id' => $cropId,
                'selected_crop_name' => $cropId,
                'knowledge_option' => 'farming-needs',
            ]);
            $this->assertSame('crop_profile', $plan->intent);
            $this->assertSame($cropId, $plan->entities[0]['value']);
        }
    }

    public function test_only_one_research_agent_orchestrator_exists(): void
    {
        $this->assertTrue(class_exists(AgriculturalResearchAgent::class));
        $this->assertFalse(class_exists('App\\Services\\Agriculture\\Research\\AgriculturalResearchAgentV2'));
    }

    public function test_scientific_source_validator_remains_functional(): void
    {
        $validator = app(ScientificSourceValidator::class);

        $this->assertTrue($validator->isVerifiedSource([
            'source_type' => 'peer_reviewed_journal',
            'organization' => 'University of Agriculture',
            'title' => 'Crop science journal article',
            'url' => 'https://doi.org/10.1000/example',
        ]));

        $this->assertFalse($validator->isVerifiedSource([
            'source_type' => 'unknown_type',
            'organization' => 'Unknown',
            'title' => 'Untrusted',
        ]));
    }

    public function test_crop_farming_needs_backward_compatibility_through_research_agent(): void
    {
        Organization::create(['name' => 'WSA Demo', 'slug' => 'wsa-demo']);
        $this->seed(\Database\Seeders\FieldCropCultivationSeeder::class);

        $response = $this->getJson('/api/v1/public/field-crops/farming-needs-profile?'.http_build_query([
            'organization' => 'wsa-demo',
            'selected_crop_id' => 'wheat',
            'selected_crop_name' => 'القمح',
            'selected_category_id' => 'grains',
            'selected_category_name' => 'محاصيل الحبوب',
        ]));

        $response->assertOk();
        $response->assertJsonPath('crop.id', 'wheat');
        $response->assertJsonPath('load_state', 'library_complete');
        $response->assertJsonCount(13, 'sections');
        $response->assertJsonPath('research_agent.orchestrated', true);
    }
}
