<?php

namespace Tests\Unit\Agriculture\Research\Search;

use App\Services\Agriculture\Research\ResearchPlanner;
use App\Services\Agriculture\Research\Search\ScientificEvidenceRelevanceGate;
use App\Services\Agriculture\Research\Search\ScientificSearchQueryBuilder;
use App\Services\Agriculture\Research\Search\ScientificSearchVariantBudget;
use Tests\TestCase;

/**
 * Workstream B: ginger temperature retrieval must prefer cultivation physiology
 * queries and reject chemistry/thermal-processing false topic hits.
 */
class PlantGrowthTemperatureQueryAndRelevanceTest extends TestCase
{
    public function test_budgeted_variants_prefer_optimum_cultivation_over_thermal_growth(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما هو تأثير درجة الحرارة على نبات الزنجبيل؟',
        ]);
        $variants = app(ScientificSearchQueryBuilder::class)->buildVariantsFromPlan($plan);
        $budgeted = ScientificSearchVariantBudget::apply($variants)['variants'];
        $joined = mb_strtolower(implode(' | ', $budgeted));

        $this->assertStringContainsString('zingiber officinale', $joined);
        $this->assertTrue(
            str_contains($joined, 'optimum temperature')
            || str_contains($joined, 'growing temperature')
            || str_contains($joined, 'temperature requirement'),
            'Expected agronomic temperature tails in budget: '.$joined,
        );
        $this->assertFalse(
            str_starts_with($budgeted[0] ?? '', 'Zingiber officinale thermal growth'),
            'Bare thermal growth must not lead the budget',
        );
    }

    public function test_processing_thermal_abstract_is_not_topic_relevant_for_plant_growth(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما هو تأثير درجة الحرارة على نبات الزنجبيل؟',
        ]);
        $gate = app(ScientificEvidenceRelevanceGate::class);

        $chemistry = $gate->assess(
            $plan,
            'Chemistry of essential oil and oleoresins of Zingiber officinale',
            'Thermally unstable gingerols convert to shogaols during thermal processing of ginger oleoresin.',
        );
        $this->assertFalse($chemistry['relevant']);
        $this->assertContains('missing_topic_or_factor', $chemistry['rejection_reasons']);

        $physiology = $gate->assess(
            $plan,
            'Effect of elevated temperature on Zingiber officinale rhizome growth',
            'Elevated temperature regimes reduce ginger plant growth and rhizome yield under field cultivation.',
        );
        $this->assertTrue($physiology['relevant']);
        $this->assertTrue($physiology['topic_matched']);
    }
}
