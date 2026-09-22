<?php

namespace Tests\Feature;

use App\Services\Agriculture\CropKnowledgeOptionCatalog;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatQclDimensionResolver;
use App\Services\Agriculture\Research\QueryUnderstandingService;
use App\Services\Agriculture\Research\ResearchPlanner;
use App\Services\Agriculture\Research\Search\ScientificSearchQueryBuilder;
use App\Services\Agriculture\Research\Synthesis\QuestionClaimExtractor;
use Tests\TestCase;

/**
 * Phase 6 U6.1 — Crop selector completeness + context propagation + FAOSTAT ID boundary.
 *
 * DIRTY-WIP NOTICE: ResearchPlanner / QueryUnderstandingService / ScientificSearchQueryBuilder
 * / FieldCropTaxonomyCatalog are dirty in the working tree. PHPUnit loads WT classes.
 * These contracts assert behavior present on both HEAD and current WIP for the crop gate
 * (selected_crop_id AND selected_crop_name). Do not treat greens as clean-HEAD-only proof
 * without an isolated HEAD run.
 */
class Phase6U61CropSelectorAndContextContractTest extends TestCase
{
    public function test_d1_id_and_name_enter_crop_profile_path(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'selected_crop_id' => 'wheat',
            'selected_crop_name' => 'Wheat',
            'knowledge_option' => 'farming-needs',
        ]);

        $this->assertTrue($plan->toAgriculturalResearchPlan()->isCropProfileIntent());
        $this->assertSame('wheat', $plan->normalizedQuery->cropId);
        $this->assertSame('Wheat', $plan->normalizedQuery->crop);
        $this->assertSame('wheat', $plan->contextInput['selected_crop_id'] ?? null);
        $this->assertSame('Wheat', $plan->contextInput['selected_crop_name'] ?? null);
        $this->assertSame('farming-needs', $plan->contextInput['knowledge_option'] ?? null);
    }

    public function test_d2_id_only_does_not_enter_crop_profile_path(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'wheat farming needs',
            'selected_crop_id' => 'wheat',
            'knowledge_option' => 'farming-needs',
        ]);

        $this->assertFalse(
            $plan->toAgriculturalResearchPlan()->isCropProfileIntent(),
            'Current contract: id-only fails Crop gate and does not set crop_profile intent'
        );
    }

    public function test_d3_name_only_does_not_enter_crop_profile_path(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'Wheat farming needs',
            'selected_crop_name' => 'Wheat',
            'knowledge_option' => 'farming-needs',
        ]);

        $this->assertFalse(
            $plan->toAgriculturalResearchPlan()->isCropProfileIntent(),
            'Current contract: name-only fails Crop gate and does not set crop_profile intent'
        );
    }

    public function test_d4_neither_id_nor_name_uses_home_generic_path(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'What irrigation methods improve wheat yield?',
        ]);

        $this->assertFalse($plan->toAgriculturalResearchPlan()->isCropProfileIntent());
        $this->assertSame('', trim((string) ($plan->contextInput['selected_crop_id'] ?? '')));
    }

    public function test_d5_invalid_id_with_valid_name_still_enters_crop_path_when_both_present(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'selected_crop_id' => 'not-a-real-crop-xyz',
            'selected_crop_name' => 'Wheat',
            'knowledge_option' => 'farming-needs',
        ]);

        $this->assertTrue(
            $plan->toAgriculturalResearchPlan()->isCropProfileIntent(),
            'Current contract: Crop gate trusts presence of both selectors; does not validate id against taxonomy'
        );
        $this->assertSame('not-a-real-crop-xyz', $plan->normalizedQuery->cropId);
        $this->assertSame('Wheat', $plan->normalizedQuery->crop);
    }

    public function test_d6_valid_id_with_mismatched_name_trusts_both_fields_as_provided(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'selected_crop_id' => 'wheat',
            'selected_crop_name' => 'Maize',
            'knowledge_option' => 'farming-needs',
        ]);

        $this->assertTrue($plan->toAgriculturalResearchPlan()->isCropProfileIntent());
        $this->assertSame('wheat', $plan->normalizedQuery->cropId);
        $this->assertSame('Maize', $plan->normalizedQuery->crop);
        $this->assertSame('wheat', $plan->contextInput['selected_crop_id'] ?? null);
        $this->assertSame('Maize', $plan->contextInput['selected_crop_name'] ?? null);
    }

    public function test_c_qus_crop_context_survives_into_planner_and_plan_array(): void
    {
        $understood = app(QueryUnderstandingService::class)->understand([
            'selected_crop_id' => 'wheat',
            'selected_crop_name' => 'Wheat',
            'knowledge_option' => 'scientific-research',
        ]);
        $this->assertSame('wheat', $understood->cropId);
        $this->assertSame('Wheat', $understood->crop);

        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'selected_crop_id' => 'wheat',
            'selected_crop_name' => 'Wheat',
            'knowledge_option' => 'scientific-research',
        ]);
        $array = $plan->toArray();
        $this->assertArrayHasKey('context_input', $array);
        $this->assertSame('wheat', $array['context_input']['selected_crop_id'] ?? null);
        $this->assertSame('scientific-research', $array['context_input']['knowledge_option'] ?? null);
        $this->assertContains('scientific-research', $plan->subtopics);
    }

    public function test_c_knowledge_options_farming_scientific_industries_enter_crop_plan(): void
    {
        foreach (['farming-needs', 'scientific-research', 'industries'] as $option) {
            $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
                'selected_crop_id' => 'corn',
                'selected_crop_name' => 'Corn',
                'knowledge_option' => $option,
            ]);
            $this->assertTrue($plan->toAgriculturalResearchPlan()->isCropProfileIntent(), $option);
            $this->assertSame($option, $plan->contextInput['knowledge_option'] ?? null, $option);
            $this->assertTrue(CropKnowledgeOptionCatalog::isImplemented($option), $option);
        }
    }

    public function test_c_question_claim_scope_is_crop_when_selected_crop_id_present(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'selected_crop_id' => 'wheat',
            'selected_crop_name' => 'Wheat',
            'knowledge_option' => 'farming-needs',
        ]);
        $claims = (new QuestionClaimExtractor)->extract($plan);
        $this->assertNotEmpty($claims);
        $this->assertSame('crop', $claims[0]->scope['home_or_crop'] ?? null);
    }

    public function test_c_search_builder_consumes_crop_context_without_emitting_faostat_code_as_entity(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'selected_crop_id' => 'wheat',
            'selected_crop_name' => 'Wheat',
            'knowledge_option' => 'farming-needs',
            'scientific_name' => 'Triticum aestivum',
        ]);
        $variants = app(ScientificSearchQueryBuilder::class)->buildVariantsFromPlan($plan);
        $this->assertNotEmpty($variants);
        $blob = mb_strtolower(implode("\n", array_map('strval', $variants)));
        $this->assertStringNotContainsString('item_code=15', $blob);
        $this->assertTrue(
            str_contains($blob, 'wheat')
            || str_contains($blob, 'triticum')
            || str_contains($blob, 'aestivum'),
            'Crop entity terminology must appear in scholarly variants'
        );
    }

    public function test_f_wsa_crop_id_is_not_used_as_numeric_faostat_item_code(): void
    {
        $mapped = FaoStatQclDimensionResolver::itemCodeFromCropIdentity(
            'wheat',
            'Wheat',
            'Triticum aestivum',
            'wheat production quantity'
        );
        $this->assertNotNull($mapped);
        $this->assertNotSame('wheat', $mapped);

        $numericId = FaoStatQclDimensionResolver::itemCodeFromCropIdentity(
            '15',
            null,
            null,
            'production quantity statistics'
        );
        $this->assertNull(
            $numericId,
            'Bare numeric cropId must not become FAOSTAT item code without fao_item_code'
        );
    }

    public function test_f_explicit_fao_item_code_constraint_wins_via_resolver_item_lookup(): void
    {
        $fromLabel = FaoStatQclDimensionResolver::itemCodeFromCropIdentity(
            'wheat',
            'Wheat',
            null,
            ''
        );
        $this->assertNotNull($fromLabel);

        $unmapped = FaoStatQclDimensionResolver::itemCodeFromCropIdentity(
            'not-mapped-crop-zzz',
            'Not Mapped Crop Zzz',
            'Fakeus inventus',
            'Fakeus inventus agronomy notes'
        );
        $this->assertNull(
            $unmapped,
            'Unmapped crop identity must not invent a FAOSTAT item code'
        );
        $this->assertNotSame('not-mapped-crop-zzz', $unmapped);
    }
}
