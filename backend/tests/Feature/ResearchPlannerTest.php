<?php

namespace Tests\Feature;

use App\Services\Agriculture\Research\ResearchPlanner;
use Tests\TestCase;

class ResearchPlannerTest extends TestCase
{
    public function test_plan_includes_research_sequence(): void
    {
        $plan = app(ResearchPlanner::class)->plan([
            'query' => 'poultry feed nutrition formulation',
            'domain' => 'poultry',
        ]);

        $this->assertSame(ResearchPlanner::RESEARCH_SEQUENCE, $plan->researchSequence);
        $this->assertSame('generic_research', $plan->intent);
        $this->assertSame('poultry', $plan->agriculturalDomain);
    }

    public function test_crop_profile_plan_is_generic_not_crop_specific_architecture(): void
    {
        $plan = app(ResearchPlanner::class)->plan([
            'selected_crop_id' => 'sesame',
            'selected_crop_name' => 'السمسم',
            'knowledge_option' => 'farming-needs',
            'scientific_name' => 'Sesamum indicum',
        ]);

        $this->assertSame('crop_profile', $plan->intent);
        $this->assertSame('sesame', $plan->entities[0]['value']);
        $this->assertContains('Sesamum indicum', array_column($plan->entities, 'value'));
        $this->assertNotEmpty($plan->researchSections);
        $this->assertNotEmpty($plan->sourceClasses);
    }

    public function test_domain_inference_from_query(): void
    {
        $planner = app(ResearchPlanner::class);

        $this->assertSame(
            'diseases',
            $planner->plan(['query' => 'plant diseases pathology in tomato crops'])->agriculturalDomain,
        );
        $this->assertSame(
            'agricultural_economics',
            $planner->plan(['query' => 'agricultural economics farm profitability'])->agriculturalDomain,
        );
    }
}
