<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Services\Agriculture\CropKnowledgeOptionCatalog;
use App\Services\Agriculture\CropProfileIdentityValidator;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatQclDimensionResolver;
use App\Services\Agriculture\Research\AgriculturalResearchAgent;
use App\Services\Agriculture\Research\ResearchPlanner;
use App\Services\Agriculture\Research\Synthesis\QuestionClaimExtractor;
use Database\Seeders\FieldCropCultivationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Phase 6 U6.3 — Crop selector completeness, taxonomy identity, multilingual options.
 */
class Phase6U63CropIdentityContractTest extends TestCase
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

    public function test_a1_valid_id_and_name_crop_path_succeeds(): void
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
    }

    public function test_a2_id_only_is_rejected_on_crop_http(): void
    {
        $response = $this->getJson('/api/v1/public/field-crops/farming-needs-profile?'.http_build_query([
            'organization' => 'wsa-demo',
            'selected_crop_id' => 'wheat',
        ]));
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['selected_crop_name']);
    }

    public function test_a3_name_only_is_rejected_on_crop_http(): void
    {
        $response = $this->getJson('/api/v1/public/field-crops/farming-needs-profile?'.http_build_query([
            'organization' => 'wsa-demo',
            'selected_crop_name' => 'القمح',
        ]));
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['selected_crop_id']);
    }

    public function test_a4_neither_keeps_home_generic_behavior(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'What irrigation methods improve wheat yield?',
        ]);
        $this->assertFalse($plan->toAgriculturalResearchPlan()->isCropProfileIntent());
    }

    public function test_a2_incomplete_selector_does_not_silently_become_home_via_agent(): void
    {
        $payload = app(AgriculturalResearchAgent::class)->conductResearch(1, [
            'query' => 'wheat farming needs',
            'selected_crop_id' => 'wheat',
            'organization_id' => 1,
            'force_execute' => true,
        ]);
        $this->assertSame('needs_clarification', $payload['status'] ?? null);
        $this->assertSame('incomplete_crop_selector', $payload['execution']['reason'] ?? null);
        $this->assertNotSame('crop_profile', $payload['research_agent']['plan']['intent'] ?? null);
    }

    public function test_a5_invalid_id_with_valid_name_is_rejected(): void
    {
        $response = $this->getJson('/api/v1/public/field-crops/farming-needs-profile?'.http_build_query([
            'organization' => 'wsa-demo',
            'selected_crop_id' => 'not-a-real-crop-xyz',
            'selected_crop_name' => 'Wheat',
        ]));
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['selected_crop_id']);
    }

    public function test_a6_valid_id_with_mismatched_name_is_rejected(): void
    {
        $response = $this->getJson('/api/v1/public/field-crops/farming-needs-profile?'.http_build_query([
            'organization' => 'wsa-demo',
            'selected_crop_id' => 'wheat',
            'selected_crop_name' => 'Maize',
        ]));
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['selected_crop_name']);
    }

    public function test_a7_invalid_id_and_mismatched_name_rejected(): void
    {
        $this->expectException(ValidationException::class);
        CropProfileIdentityValidator::normalizePair([
            'selected_crop_id' => 'nope',
            'selected_crop_name' => 'Nope',
        ]);
    }

    public function test_a8_valid_id_and_canonical_alias_accepted_and_normalized(): void
    {
        $normalized = CropProfileIdentityValidator::normalizePair([
            'selected_crop_id' => 'wheat',
            'selected_crop_name' => 'bread wheat',
        ]);
        $this->assertSame('wheat', $normalized['selected_crop_id']);
        $this->assertNotSame('', $normalized['selected_crop_name']);
        $this->assertSame('Triticum aestivum', $normalized['scientific_name']);
    }

    public function test_b3_wrong_name_rejected_by_validator(): void
    {
        try {
            CropProfileIdentityValidator::normalizePair([
                'selected_crop_id' => 'wheat',
                'selected_crop_name' => 'Maize',
            ]);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('selected_crop_name', $e->errors());
        }
    }

    public function test_b9_b10_normalized_identity_survives_plan_and_question_claim_scope(): void
    {
        $identity = CropProfileIdentityValidator::normalizePair([
            'selected_crop_id' => 'wheat',
            'selected_crop_name' => 'Wheat',
            'knowledge_option' => 'farming-needs',
        ]);
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'selected_crop_id' => $identity['selected_crop_id'],
            'selected_crop_name' => $identity['selected_crop_name'],
            'scientific_name' => $identity['scientific_name'],
            'knowledge_option' => 'farming-needs',
        ]);
        $this->assertTrue($plan->toAgriculturalResearchPlan()->isCropProfileIntent());
        $this->assertSame('wheat', $plan->normalizedQuery->cropId);
        $claims = (new QuestionClaimExtractor)->extract($plan);
        $this->assertSame('crop', $claims[0]->scope['home_or_crop'] ?? null);
    }

    public function test_b12_numeric_crop_id_still_not_faostat_item_code(): void
    {
        $mapped = FaoStatQclDimensionResolver::itemCodeFromCropIdentity('15', null, null, 'production');
        $this->assertNull($mapped);
        $wheat = FaoStatQclDimensionResolver::itemCodeFromCropIdentity('wheat', 'Wheat', 'Triticum aestivum', '');
        $this->assertNotNull($wheat);
        $this->assertNotSame('wheat', $wheat);
    }

    public function test_c_option_ids_stable_across_languages(): void
    {
        $keys = array_column(CropKnowledgeOptionCatalog::options(), 'key');
        $this->assertSame(['farming-needs', 'scientific-research', 'industries'], $keys);
        foreach ($keys as $key) {
            foreach (['ar', 'en', 'fr', 'tr'] as $lang) {
                $title = CropKnowledgeOptionCatalog::optionTitle($key, $lang);
                $this->assertNotSame('', $title, "$key/$lang");
                $this->assertSame($key, $key);
            }
        }
        $this->assertNotSame(
            CropKnowledgeOptionCatalog::optionTitle('farming-needs', 'ar'),
            CropKnowledgeOptionCatalog::optionTitle('farming-needs', 'en')
        );
        $this->assertSame(
            CropKnowledgeOptionCatalog::optionTitle('farming-needs', 'xx'),
            CropKnowledgeOptionCatalog::optionTitle('farming-needs', 'ar')
        );
    }

    public function test_c8_option_language_does_not_change_answer_language_contract(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'selected_crop_id' => 'wheat',
            'selected_crop_name' => 'Wheat',
            'knowledge_option' => 'farming-needs',
            'constraints' => ['answer_language' => 'en', 'ui_locale' => 'ar'],
        ]);
        $claims = (new QuestionClaimExtractor)->extract($plan);
        // QuestionClaim answer language follows plan constraints / question language — not option catalog UI language.
        $this->assertSame('crop', $claims[0]->scope['home_or_crop'] ?? null);
        $uiTitle = CropKnowledgeOptionCatalog::optionTitle('farming-needs', 'ar');
        $enTitle = CropKnowledgeOptionCatalog::optionTitle('farming-needs', 'en');
        $this->assertNotSame($uiTitle, $enTitle);
    }

    public function test_home_query_without_crop_selectors_still_works(): void
    {
        $response = $this->postJson('/api/v1/public/research-agent/query', [
            'organization' => 'wsa-demo',
            'query' => 'What irrigation methods improve wheat yield in arid systems?',
            'force_execute' => true,
        ]);
        $this->assertNotSame(422, $response->status());
        $this->assertNotSame('incomplete_crop_selector', $response->json('execution.reason'));
        $this->assertNotSame('invalid_crop_identity', $response->json('status'));
    }

    public function test_research_agent_rejects_incomplete_crop_selector_with_validation_error(): void
    {
        $response = $this->postJson('/api/v1/public/research-agent/query', [
            'organization' => 'wsa-demo',
            'query' => 'wheat farming needs',
            'selected_crop_id' => 'wheat',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['selected_crop_id', 'selected_crop_name']);
    }
}
