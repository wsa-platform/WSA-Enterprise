<?php

namespace Tests\Feature;

use App\Services\Agriculture\Research\ResearchPlanner;
use App\Services\Agriculture\Research\Search\ScientificSearchQueryBuilder;
use Tests\TestCase;

/**
 * Isolated Q3 irrigation-quantity search architecture.
 * Semantic gates: irrigation / crop_water_requirement resource-before-environment.
 * Copied from GenericAgriculturalEntityArchitectureTest Q3 cases; mixed file unchanged.
 */
class GenericIrrigationQuantitySearchArchitectureTest extends TestCase
{
    public function test_irrigation_is_retained_when_research_intent_is_irrigation(): void
    {
        $questions = [
            'ما كمية الري المناسبة لأشجار الرمان في المناطق الجافة؟',
            'What is the irrigation requirement for olive trees in arid regions?',
            'ما كمية الري المناسبة للمانجو في المناطق الجافة؟',
            'ما احتياجات القمح من المياه؟',
        ];

        foreach ($questions as $question) {
            $plan = app(ResearchPlanner::class)->planKnowledgeQuery(['query' => $question]);
            $this->assertSame('irrigation', $plan->researchIntent, $question);
            $this->assertSame('irrigation', $plan->normalizedQuery->researchIntent, $question);

            $joined = mb_strtolower(implode(' | ', app(ScientificSearchQueryBuilder::class)->buildVariantsFromPlan($plan)));
            $this->assertTrue($this->mentionsIrrigationResource($joined), $question.' | '.$joined);
        }
    }

    public function test_irrigation_quantity_search_prefers_resource_over_environment(): void
    {
        $questions = [
            'ما كمية الري المناسبة لأشجار الرمان في المناطق الجافة؟',
            'What is the irrigation requirement for olive trees in arid regions?',
            'ما كمية الري المناسبة للمانجو في المناطق الجافة؟',
            'ما احتياجات القمح من المياه؟',
        ];

        foreach ($questions as $question) {
            $plan = app(ResearchPlanner::class)->planKnowledgeQuery(['query' => $question]);
            $variants = app(ScientificSearchQueryBuilder::class)->buildVariantsFromPlan($plan);
            $this->assertLessThanOrEqual(5, count($variants), $question);
            $this->assertSame(5, (new \ReflectionClass(ScientificSearchQueryBuilder::class))->getConstant('MAX_VARIANTS'));

            $joined = mb_strtolower(implode(' | ', $variants));
            $this->assertTrue($this->mentionsIrrigationResource($joined), $question.' | '.$joined);
            $this->assertTrue($this->mentionsIrrigationResource(mb_strtolower((string) ($variants[0] ?? ''))), $question.' | '.$joined);

            $resourceCount = 0;
            $environmentOnly = 0;
            foreach ($variants as $variant) {
                $normalized = mb_strtolower($variant);
                $hasResource = $this->mentionsIrrigationResource($normalized);
                $hasEnvironment = preg_match('/\b(?:arid|desert|dryland)\b/u', $normalized) === 1;
                if ($hasResource) {
                    $resourceCount++;
                }
                if ($hasEnvironment && ! $hasResource) {
                    $environmentOnly++;
                }
            }
            $this->assertGreaterThanOrEqual(3, $resourceCount, $question.' | '.$joined);
            $this->assertGreaterThan($environmentOnly, $resourceCount, $question.' | '.$joined);
            $this->assertFalse(
                $this->isEnvironmentOnlyVariant(mb_strtolower((string) ($variants[0] ?? ''))),
                'Leading variant is environment-only: '.$question.' | '.$joined,
            );
        }
    }

    public function test_unrelated_quantity_search_does_not_inherit_irrigation(): void
    {
        $questions = [
            'What fertilizer quantity do tomato plants need in arid regions?',
            'How much pruning should olive trees receive each year?',
            'What seed quantity is required when planting wheat?',
        ];

        foreach ($questions as $question) {
            $plan = app(ResearchPlanner::class)->planKnowledgeQuery(['query' => $question]);
            $understood = $plan->normalizedQuery;
            $this->assertNotSame('irrigation', $understood->researchIntent, $question);
            $this->assertNotSame('crop_water_requirement', $understood->constraints['scientific_sense'] ?? null, $question);

            $joined = mb_strtolower(implode(' | ', app(ScientificSearchQueryBuilder::class)->buildVariantsFromPlan($plan)));
            $this->assertFalse(str_contains($joined, 'water requirement'), $question.' | '.$joined);
            $this->assertFalse(str_contains($joined, 'evapotranspiration'), $question.' | '.$joined);
        }
    }

    public function test_pomegranate_irrigation_search_remains_entity_specific(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما كمية الري المناسبة لأشجار الرمان في المناطق الجافة؟',
        ]);
        $variants = app(ScientificSearchQueryBuilder::class)->buildVariantsFromPlan($plan);
        $joined = mb_strtolower(implode(' | ', $variants));
        $this->assertTrue(str_contains($joined, 'punica granatum'), $joined);
        $this->assertTrue(str_contains($joined, 'pomegranate'), $joined);
        $this->assertTrue($this->mentionsIrrigationResource($joined), $joined);
        $this->assertTrue($this->mentionsIrrigationResource(mb_strtolower((string) ($variants[0] ?? ''))), $joined);
    }

    public function test_mango_arid_irrigation_preserves_irrigation_semantics(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما كمية الري المناسبة للمانجو في المناطق الجافة؟',
        ]);
        $this->assertSame('irrigation', $plan->researchIntent);
        $variants = app(ScientificSearchQueryBuilder::class)->buildVariantsFromPlan($plan);
        $joined = mb_strtolower(implode(' | ', $variants));
        $this->assertTrue($this->mentionsIrrigationResource($joined), $joined);
        $this->assertTrue($this->mentionsIrrigationResource(mb_strtolower((string) ($variants[0] ?? ''))), $joined);
        $this->assertFalse($this->isEnvironmentOnlyVariant(mb_strtolower((string) ($variants[0] ?? ''))), $joined);
        $this->assertLessThanOrEqual(5, count($variants), $joined);
    }

    private function mentionsIrrigationResource(string $haystack): bool
    {
        return str_contains($haystack, 'irrigation')
            || str_contains($haystack, 'water requirement')
            || str_contains($haystack, 'evapotranspiration')
            || str_contains($haystack, 'water use');
    }

    private function isEnvironmentOnlyVariant(string $variant): bool
    {
        return ! $this->mentionsIrrigationResource($variant)
            && preg_match('/\b(?:arid|desert|dryland)\b/u', $variant) === 1;
    }
}
