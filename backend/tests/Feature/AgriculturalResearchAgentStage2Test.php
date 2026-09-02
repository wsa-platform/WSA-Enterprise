<?php

namespace Tests\Feature;

use App\Services\Agriculture\Research\AgriculturalDomainCatalog;
use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\AgriculturalResearchAgent;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\QueryUnderstandingService;
use App\Services\Agriculture\Research\ResearchPlanner;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AgriculturalResearchAgentStage2Test extends TestCase
{
    /** @return array<string, mixed> */
    private function planFor(string $query, array $extra = []): array
    {
        return app(AgriculturalResearchAgent::class)->planResearch(array_merge(['query' => $query], $extra));
    }

    /** @param  array<string, mixed>  $planResponse  */
    private function knowledgePlan(array $planResponse): array
    {
        return $planResponse['knowledge_query_plan'];
    }

    /** @param  array<string, mixed>  $planResponse  */
    private function understoodQuery(array $planResponse): array
    {
        return $planResponse['query_understanding'];
    }

    public function test_wheat_cultivation_question_is_clear_and_internet_first(): void
    {
        $response = $this->planFor('كيف أزرع القمح؟');

        $this->assertSame('plan_ready', $response['status']);
        $understood = $this->understoodQuery($response);
        $plan = $this->knowledgePlan($response);

        $this->assertSame(AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR, $understood['ambiguity_state']);
        $this->assertSame('cultivation', $understood['research_intent']);
        $this->assertSame('wheat', $understood['crop_id']);
        $this->assertSame(KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST, $plan['primary_research_strategy']);
        $this->assertSame(KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE, $plan['research_sequence']);
        $this->assertFalse($response['execution']['performed']);
    }

    public function test_tomato_irrigation_question(): void
    {
        $response = $this->planFor('ما أفضل جدولة ري للطماطم؟');

        $understood = $this->understoodQuery($response);
        $plan = $this->knowledgePlan($response);

        $this->assertSame('irrigation', $understood['research_intent']);
        $this->assertSame(AgriculturalDomainCatalog::IRRIGATION_WATER, $plan['agricultural_domain']);
        $this->assertSame('crop', $understood['subject']['type']);
        $this->assertSame('tomato', $understood['crop_id']);
    }

    public function test_fertilization_question_without_crop_needs_clarification(): void
    {
        $response = $this->planFor('أفضل سماد؟');

        $understood = $this->understoodQuery($response);
        $plan = $this->knowledgePlan($response);

        $this->assertSame('needs_clarification', $response['status']);
        $this->assertSame(AgriculturalKnowledgeQuery::AMBIGUITY_NEEDS_CLARIFICATION, $understood['ambiguity_state']);
        $this->assertContains('crop_or_crop_system', $understood['clarification_requirements']);
        $this->assertNull($understood['crop_id']);
        $this->assertFalse($plan['ready_for_stage_3']);
        $this->assertFalse($response['execution']['performed']);
    }

    public function test_soil_question(): void
    {
        $response = $this->planFor('How to improve soil fertility for farming?');

        $understood = $this->understoodQuery($response);
        $plan = $this->knowledgePlan($response);

        $this->assertSame('soil_management', $understood['research_intent']);
        $this->assertSame(AgriculturalDomainCatalog::SOIL, $plan['agricultural_domain']);
        $this->assertSame('soil', $understood['subject']['type']);
    }

    public function test_plant_nutrition_question(): void
    {
        $response = $this->planFor('plant nutrition micronutrient deficiency in cereals');

        $understood = $this->understoodQuery($response);
        $this->assertSame('plant_nutrition', $understood['research_intent']);
        $this->assertSame(AgriculturalDomainCatalog::PLANT_NUTRITION, $this->knowledgePlan($response)['agricultural_domain']);
    }

    public function test_plant_disease_vague_question_needs_clarification(): void
    {
        $response = $this->planFor('مرض في النبات');

        $understood = $this->understoodQuery($response);
        $this->assertSame(AgriculturalKnowledgeQuery::AMBIGUITY_NEEDS_CLARIFICATION, $understood['ambiguity_state']);
        $this->assertContains('affected_crop_or_plant', $understood['clarification_requirements']);
        $this->assertNull($understood['crop_id']);
    }

    public function test_pest_question(): void
    {
        $response = $this->planFor('integrated pest management for wheat fields');

        $understood = $this->understoodQuery($response);
        $this->assertSame('pest', $understood['research_intent']);
        $this->assertSame('wheat', $understood['crop_id']);
    }

    public function test_beekeeping_question(): void
    {
        $response = $this->planFor('beekeeping pollination management practices');

        $understood = $this->understoodQuery($response);
        $plan = $this->knowledgePlan($response);

        $this->assertSame('beekeeping', $understood['research_intent']);
        $this->assertSame(AgriculturalDomainCatalog::BEEKEEPING, $plan['agricultural_domain']);
        $this->assertSame('production_system', $understood['subject']['type']);
    }

    public function test_poultry_question(): void
    {
        $response = $this->planFor('poultry broiler feed nutrition');

        $understood = $this->understoodQuery($response);
        $this->assertSame('poultry_production', $understood['research_intent']);
        $this->assertSame(AgriculturalDomainCatalog::POULTRY, $this->knowledgePlan($response)['agricultural_domain']);
    }

    public function test_aquaculture_question(): void
    {
        $response = $this->planFor('aquaculture fish farming water quality');

        $understood = $this->understoodQuery($response);
        $this->assertSame('aquaculture', $understood['research_intent']);
        $this->assertSame(AgriculturalDomainCatalog::AQUACULTURE, $this->knowledgePlan($response)['agricultural_domain']);
    }

    public function test_animal_production_question(): void
    {
        $response = $this->planFor('livestock animal production husbandry practices');

        $understood = $this->understoodQuery($response);
        $this->assertSame('animal_production', $understood['research_intent']);
        $this->assertSame(AgriculturalDomainCatalog::ANIMAL_PRODUCTION, $this->knowledgePlan($response)['agricultural_domain']);
    }

    public function test_agricultural_economics_question(): void
    {
        $response = $this->planFor('agricultural economics farm profitability analysis');

        $understood = $this->understoodQuery($response);
        $this->assertSame('agricultural_economics', $understood['research_intent']);
        $this->assertSame(AgriculturalDomainCatalog::AGRICULTURAL_ECONOMICS, $this->knowledgePlan($response)['agricultural_domain']);
    }

    public function test_agricultural_research_question(): void
    {
        $response = $this->planFor('peer reviewed scientific publications on sustainable agriculture');

        $understood = $this->understoodQuery($response);
        $this->assertSame('scientific_literature', $understood['research_intent']);
        $this->assertSame(AgriculturalDomainCatalog::AGRICULTURAL_RESEARCH, $this->knowledgePlan($response)['agricultural_domain']);
    }

    public function test_agricultural_industry_question(): void
    {
        $response = $this->planFor('agricultural industry value chain processing');

        $understood = $this->understoodQuery($response);
        $this->assertSame('agricultural_industry', $understood['research_intent']);
        $this->assertSame(AgriculturalDomainCatalog::AGRICULTURAL_INDUSTRIES, $this->knowledgePlan($response)['agricultural_domain']);
    }

    public function test_general_agriculture_without_crop(): void
    {
        $response = $this->planFor('general farming practices for smallholders');

        $understood = $this->understoodQuery($response);
        $this->assertNull($understood['crop_id']);
        $this->assertSame('general_knowledge', $understood['research_intent']);
    }

    public function test_scientific_literature_question(): void
    {
        $response = $this->planFor('scientific literature on crop rotation');

        $understood = $this->understoodQuery($response);
        $this->assertSame('scientific_literature', $understood['research_intent']);
    }

    public function test_ambiguous_question_does_not_invent_crop_or_soil(): void
    {
        $response = $this->planFor('best fertilizer');

        $understood = $this->understoodQuery($response);
        $this->assertSame('needs_clarification', $response['status']);
        $this->assertNull($understood['crop_id']);
        $this->assertSame([], array_filter([
            $understood['constraints']['soil_type'] ?? null,
            $understood['constraints']['production_target'] ?? null,
        ]));
    }

    public function test_incomplete_question_needs_clarification(): void
    {
        $response = $this->planFor('crop?');

        $understood = $this->understoodQuery($response);
        $this->assertSame(AgriculturalKnowledgeQuery::AMBIGUITY_NEEDS_CLARIFICATION, $understood['ambiguity_state']);
    }

    public function test_arabic_question_preserves_language(): void
    {
        $response = $this->planFor('كيف أزرع القمح في الأراضي الجافة؟');

        $understood = $this->understoodQuery($response);
        $this->assertSame('ar', $understood['language']);
        $this->assertStringContainsString('قمح', $understood['original_question']);
    }

    public function test_internet_first_plan_generation_and_library_not_primary(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'irrigation scheduling for orchard crops',
        ]);

        $this->assertTrue($plan->isInternetFirst());
        $this->assertSame('external_scientific_research', $plan->researchSequence[0]);
        $this->assertContains('library_memory', $plan->researchSequence);
        $this->assertLessThan(
            array_search('library_memory', $plan->researchSequence, true),
            array_search('external_scientific_research', $plan->researchSequence, true),
        );
        $this->assertContains('memory', KnowledgeQueryPlan::LIBRARY_ROLES);
        $this->assertNotContains('primary_discovery', KnowledgeQueryPlan::LIBRARY_ROLES);
    }

    public function test_stage_2_plan_endpoint_performs_no_external_search(): void
    {
        Http::fake();

        $response = $this->postJson('/api/v1/public/research-agent/plan', [
            'query' => 'irrigation scheduling for wheat',
        ]);

        $response->assertOk();
        $response->assertJsonPath('execution.performed', false);
        $response->assertJsonPath('execution.reason', 'stage_2_planning_only');
        $response->assertJsonPath('knowledge_query_plan.primary_research_strategy', KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST);
        Http::assertNothingSent();
    }

    public function test_plan_does_not_fabricate_source_metadata(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'soil management best practices',
        ])->toArray();

        $this->assertArrayNotHasKey('doi', $plan);
        $this->assertArrayNotHasKey('url', $plan);
        $this->assertArrayNotHasKey('authors', $plan);
        foreach ($plan['source_priorities'] as $priority) {
            $this->assertIsString($priority);
            $this->assertStringNotContainsString('http', $priority);
        }
    }

    public function test_query_understanding_service_does_not_call_external_apis(): void
    {
        Http::fake();

        app(QueryUnderstandingService::class)->understand([
            'query' => 'fertilization for millet Pennisetum glaucum',
        ]);

        Http::assertNothingSent();
    }

    public function test_research_planner_produces_knowledge_query_plan_with_normalized_query(): void
    {
        $planner = app(ResearchPlanner::class);
        $knowledgePlan = $planner->planKnowledgeQuery([
            'query' => 'How to cultivate wheat in dryland systems?',
        ]);

        $this->assertInstanceOf(KnowledgeQueryPlan::class, $knowledgePlan);
        $this->assertSame('cultivation', $knowledgePlan->researchIntent);
        $this->assertSame('wheat', $knowledgePlan->normalizedQuery->cropId);
        $this->assertTrue($knowledgePlan->readyForStage3);
    }

    public function test_legacy_agricultural_research_plan_still_available_for_stage_3_engine(): void
    {
        $legacyPlan = app(ResearchPlanner::class)->plan([
            'query' => 'beekeeping pollination management',
        ]);

        $this->assertSame('generic_research', $legacyPlan->intent);
        $this->assertNotEmpty($legacyPlan->researchSequence);
        $this->assertNotNull($legacyPlan->knowledgeQueryPlan);
    }

    public function test_explicit_scientific_name_only_when_reliably_known(): void
    {
        $understood = app(QueryUnderstandingService::class)->understand([
            'query' => 'best fertilizer',
        ]);

        $this->assertNull($understood->scientificName);

        $cropUnderstood = app(QueryUnderstandingService::class)->understand([
            'selected_crop_id' => 'wheat',
            'selected_crop_name' => 'القمح',
            'query' => 'farming needs',
        ]);

        $this->assertSame('Triticum aestivum', $cropUnderstood->scientificName);
    }
}
