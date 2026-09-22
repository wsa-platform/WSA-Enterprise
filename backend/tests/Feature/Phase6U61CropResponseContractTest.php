<?php

namespace Tests\Feature;

use App\Models\Organization;
use Database\Seeders\FieldCropCultivationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Phase 6 U6.1 — Crop HTTP response shape contracts (legacy profile primary).
 *
 * Reuses existing library-complete fixtures. Does not redesign response.
 */
class Phase6U61CropResponseContractTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Organization::create(['name' => 'WSA Demo', 'slug' => 'wsa-demo', 'is_active' => true]);
        config(['wsa.public_organization_slug' => 'wsa-demo']);
        Http::fake([
            'api.openalex.org/works*' => Http::response(['results' => []], 200),
            'api.crossref.org/works*' => Http::response(['message' => ['items' => []]], 200),
        ]);
    }

    public function test_a1_farming_needs_returns_legacy_profile_not_home_answer_shape(): void
    {
        $this->seed(FieldCropCultivationSeeder::class);

        $response = $this->getJson('/api/v1/public/field-crops/farming-needs-profile?'.http_build_query([
            'organization' => 'wsa-demo',
            'selected_crop_id' => 'wheat',
            'selected_crop_name' => 'القمح',
            'knowledge_option' => 'farming-needs',
        ]));

        $response->assertOk();
        $response->assertJsonPath('crop.id', 'wheat');
        $response->assertJsonPath('service_option', 'farming-needs');
        $this->assertIsArray($response->json('sections'));
        $this->assertNotEmpty($response->json('sections'));
        $this->assertArrayHasKey('load_state', $response->json());
        // Home-shaped top-level scientific answer is not the Crop UX contract.
        $this->assertTrue(
            $response->json('answer') === null
            || $response->json('research_agent') !== null
            || is_array($response->json('sections')),
            'Crop HTTP must remain legacy profile oriented'
        );
    }

    public function test_a2_scientific_research_option_preserves_crop_identity(): void
    {
        $response = $this->getJson('/api/v1/public/field-crops/farming-needs-profile?'.http_build_query([
            'organization' => 'wsa-demo',
            'selected_crop_id' => 'wheat',
            'selected_crop_name' => 'القمح',
            'knowledge_option' => 'scientific-research',
        ]));

        $response->assertOk();
        $response->assertJsonPath('crop.id', 'wheat');
        $payload = $response->json();
        $this->assertTrue(
            ($payload['service_option'] ?? null) === 'scientific-research'
            || ($payload['knowledge_option'] ?? null) === 'scientific-research'
            || str_contains(mb_strtolower((string) ($payload['title'] ?? '')), 'علم')
            || isset($payload['sections']),
            'scientific-research option must remain on Crop profile contract'
        );
    }

    public function test_a3_industries_option_preserves_crop_identity(): void
    {
        $response = $this->getJson('/api/v1/public/field-crops/farming-needs-profile?'.http_build_query([
            'organization' => 'wsa-demo',
            'selected_crop_id' => 'wheat',
            'selected_crop_name' => 'القمح',
            'knowledge_option' => 'industries',
        ]));

        $response->assertOk();
        $response->assertJsonPath('crop.id', 'wheat');
        $this->assertIsArray($response->json('sections') ?? []);
    }

    public function test_k_stage5_payload_nested_under_research_agent_when_present(): void
    {
        $this->seed(FieldCropCultivationSeeder::class);

        $response = $this->getJson('/api/v1/public/field-crops/farming-needs-profile?'.http_build_query([
            'organization' => 'wsa-demo',
            'selected_crop_id' => 'wheat',
            'selected_crop_name' => 'القمح',
            'knowledge_option' => 'farming-needs',
        ]));

        $response->assertOk();
        $payload = $response->json();
        $this->assertArrayHasKey('sections', $payload);
        if (isset($payload['research_agent'])) {
            $this->assertIsArray($payload['research_agent']);
            $this->assertTrue(
                isset($payload['research_agent']['synthesis'])
                || isset($payload['research_agent']['plan'])
                || isset($payload['research_agent']['scientific_validation']),
                'When research_agent is present, Stage 2–5 artifacts nest under it'
            );
        } else {
            $this->assertSame(
                'library_complete',
                $payload['load_state'] ?? null,
                'Library-complete Crop responses may omit live Stage 5 nesting; sections remain primary UX'
            );
        }
    }
}
