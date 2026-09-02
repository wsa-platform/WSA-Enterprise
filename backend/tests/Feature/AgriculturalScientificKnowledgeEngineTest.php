<?php

namespace Tests\Feature;

use App\Services\Agriculture\Research\AgriculturalScientificKnowledgeEngine;
use App\Services\Agriculture\Research\ResearchPlanner;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AgriculturalScientificKnowledgeEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_engine_delegates_crop_profile_to_crop_knowledge_engine(): void
    {
        Organization::create(['name' => 'WSA Demo', 'slug' => 'wsa-demo']);
        $this->seed(\Database\Seeders\FieldCropCultivationSeeder::class);

        $planner = app(ResearchPlanner::class);
        $engine = app(AgriculturalScientificKnowledgeEngine::class);

        $plan = $planner->plan([
            'selected_crop_id' => 'corn',
            'selected_crop_name' => 'الذرة',
            'selected_category_id' => 'grains',
            'selected_category_name' => 'محاصيل الحبوب',
            'knowledge_option' => 'farming-needs',
        ]);

        $result = $engine->execute(1, $plan);

        $this->assertSame('library_complete', $result->researchContext['load_state'] ?? null);
        $this->assertSame('corn', $result->researchContext['crop']['id'] ?? null);
    }

    public function test_engine_executes_generic_research_with_external_discovery(): void
    {
        Organization::create(['name' => 'WSA Demo', 'slug' => 'wsa-demo']);

        Http::fake([
            'api.openalex.org/works*' => Http::response([
                'results' => [[
                    'id' => 'https://openalex.org/W123',
                    'display_name' => 'Aquaculture feed formulation for tilapia production',
                    'doi' => 'https://doi.org/10.1000/aqua-feed',
                    'publication_year' => 2022,
                    'abstract_inverted_index' => ['Aquaculture' => [0], 'feed' => [1], 'formulation' => [2]],
                    'primary_location' => [
                        'landing_page_url' => 'https://doi.org/10.1000/aqua-feed',
                        'source' => ['display_name' => 'Aquaculture Research'],
                    ],
                    'authorships' => [[
                        'institutions' => [['display_name' => 'FAO', 'type' => 'government']],
                    ]],
                ]],
            ], 200),
            'api.crossref.org/works*' => Http::response(['message' => ['items' => []]], 200),
        ]);

        $plan = app(ResearchPlanner::class)->plan([
            'query' => 'aquaculture feed formulation for tilapia',
            'domain' => 'aquaculture',
        ]);

        $result = app(AgriculturalScientificKnowledgeEngine::class)->execute(1, $plan);

        $this->assertSame('generic_research', $result->planSummary['intent']);
        $this->assertNotEmpty($result->externalDiscoverersUsed);
    }
}
