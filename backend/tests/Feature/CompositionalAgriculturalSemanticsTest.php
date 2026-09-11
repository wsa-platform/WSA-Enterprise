<?php

namespace Tests\Feature;

use App\Services\Agriculture\Research\AgriculturalEntityCatalog;
use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\QueryUnderstandingService;
use App\Services\Agriculture\Research\ResearchPlanner;
use App\Services\Agriculture\Research\Search\ScientificSearchQueryBuilder;
use Tests\TestCase;

/**
 * Generic compositional QUS / plan / query-construction tests.
 * Proves topic, constraint, attribute, and user-act roles are distinct.
 */
class CompositionalAgriculturalSemanticsTest extends TestCase
{
    public function test_user_act_vs_topic_water_plays_different_roles(): void
    {
        $recommendation = $this->understand('What crops can be grown where water is scarce?');
        $this->assertSame('cultivation', $recommendation->researchIntent);
        $this->assertSame('recommendation', $recommendation->constraints['question_type'] ?? null);
        $this->assertContains('water_scarcity', $this->constraintTypes($recommendation));
        $this->assertNotSame('crop_water_requirement', $recommendation->constraints['scientific_sense'] ?? null);

        $requirement = $this->understand('ما احتياجات القمح من المياه؟');
        $this->assertSame('wheat', $requirement->cropId);
        $this->assertSame('crop_water_requirement', $requirement->constraints['scientific_sense'] ?? null);
        $this->assertTrue(in_array($requirement->researchIntent, ['irrigation', 'environmental_requirements'], true));

        $salinityEffect = $this->understand('ما تأثير الملوحة على القمح؟');
        $this->assertSame('wheat', $salinityEffect->cropId);
        $this->assertSame('salinity_physiology', $salinityEffect->constraints['scientific_sense'] ?? null);

        $salineRecommendation = $this->understand('Which crops are suitable for saline water irrigation?');
        $this->assertSame('cultivation', $salineRecommendation->researchIntent);
        $this->assertContains('saline_water', $this->constraintTypes($salineRecommendation));
        $this->assertNotSame('irrigation', $salineRecommendation->researchIntent);
    }

    public function test_recommendation_keeps_act_under_environmental_constraints(): void
    {
        $cases = [
            'What crops are recommended for dry regions?',
            'Which crops can be grown with saline water?',
            'Best crops for water scarcity conditions',
            'What crops are suitable under high temperature?',
            'Which crops can be cultivated in saline soil?',
            'What crops can be grown in arid regions with saline water?',
        ];

        foreach ($cases as $question) {
            $understood = $this->understand($question);
            $this->assertSame(
                'cultivation',
                $understood->researchIntent,
                'User act lost for: '.$question,
            );
            $this->assertNotSame('irrigation', $understood->researchIntent, $question);
            $this->assertSame('recommendation', $understood->constraints['question_type'] ?? null, $question);
            $this->assertNotEmpty($understood->constraints['environmental_constraints'] ?? [], $question);
            $this->assertSame(
                AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
                $understood->ambiguityState,
                'Category recommendation should not require a named entity: '.$question,
            );
        }
    }

    public function test_arabic_recommendation_with_arid_and_saline_water_constraints(): void
    {
        $understood = $this->understand('ما أفضل المحاصيل التي يمكن زراعتها في الأراضي الصحراوية ذات المياه المالحة؟');

        $this->assertSame('cultivation', $understood->researchIntent);
        $this->assertSame('recommendation', $understood->constraints['question_type'] ?? null);
        $this->assertSame('crop_category', $understood->subject['type'] ?? null);
        $this->assertNull($understood->cropId);
        $this->assertContains('arid_environment', $this->constraintTypes($understood));
        $this->assertContains('saline_water', $this->constraintTypes($understood));
        $this->assertSame('constraint', $understood->constraints['scientific_factor_roles']['water'] ?? null);
        $this->assertNotSame('crop_water_requirement', $understood->constraints['scientific_sense'] ?? null);
        $this->assertNotSame('irrigation_water', $understood->agriculturalDomain);
        $this->assertSame(AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR, $understood->ambiguityState);

        $plan = $this->plan('ما أفضل المحاصيل التي يمكن زراعتها في الأراضي الصحراوية ذات المياه المالحة؟');
        $this->assertSame('cultivation', $plan->researchIntent);
        $joined = $this->joinedQueries($plan);
        $this->assertTrue(
            str_contains($joined, 'crop suitability')
            || str_contains($joined, 'crop recommendation')
            || str_contains($joined, 'suitable crops')
            || str_contains($joined, 'cultivation'),
        );
        $this->assertTrue(str_contains($joined, 'saline') || str_contains($joined, 'salinity'));
        $this->assertTrue(str_contains($joined, 'arid') || str_contains($joined, 'desert'));
        $this->assertFalse(str_contains($joined, 'evapotranspiration'));
    }

    public function test_genuine_irrigation_questions_remain_irrigation(): void
    {
        $pomegranate = $this->understand('ما كمية الري المناسبة لأشجار الرمان في المناطق الجافة؟');
        $this->assertSame('irrigation', $pomegranate->researchIntent);
        $this->assertSame('crop_water_requirement', $pomegranate->constraints['scientific_sense'] ?? null);
        $this->assertContains('arid_environment', $this->constraintTypes($pomegranate));

        $scheduling = $this->understand('irrigation scheduling for wheat');
        $this->assertSame('wheat', $scheduling->cropId);
        $this->assertSame('irrigation', $scheduling->researchIntent);
        $this->assertSame('crop_water_requirement', $scheduling->constraints['scientific_sense'] ?? null);
    }

    public function test_salinity_role_changes_with_context(): void
    {
        $effect = $this->understand('ما تأثير الملوحة على القمح؟');
        $this->assertSame('salinity_physiology', $effect->constraints['scientific_sense'] ?? null);
        $this->assertSame('requested', $effect->constraints['scientific_factor_roles']['salinity'] ?? 'requested');

        $salineSoil = $this->understand('Which crops can be grown in saline soil?');
        $this->assertSame('cultivation', $salineSoil->researchIntent);
        $this->assertContains('saline_soil', $this->constraintTypes($salineSoil));

        $salineWater = $this->understand('What crops are suitable for saline water?');
        $this->assertSame('cultivation', $salineWater->researchIntent);
        $this->assertContains('saline_water', $this->constraintTypes($salineWater));
    }

    public function test_environment_conditions_are_extracted_as_constraints(): void
    {
        $cases = [
            'What crops can be grown in desert regions?' => 'arid_environment',
            'Which crops are suitable for arid climates?' => 'arid_environment',
            'Best crops under drought conditions' => 'drought',
            'Crops suitable for high temperature regions' => 'high_temperature',
            'Crops that can be grown in cold climate' => 'low_temperature',
            'Recommended crops for low rainfall areas' => 'low_rainfall',
        ];

        foreach ($cases as $question => $expected) {
            $understood = $this->understand($question);
            $this->assertContains($expected, $this->constraintTypes($understood), $question);
            $this->assertSame('cultivation', $understood->researchIntent, $question);
        }
    }

    public function test_arabic_morphology_families_are_equivalent(): void
    {
        $cropNeedles = ['محصول', 'المحصول', 'محاصيل', 'المحاصيل'];
        foreach ($cropNeedles as $needle) {
            $this->assertTrue(
                AgriculturalEntityCatalog::matchesSemanticToken('زراعة المحاصيل في الحقل', $needle),
                $needle,
            );
        }

        foreach (['زراعة', 'الزراعة', 'زراعتها', 'يزرع', 'للزراعة'] as $needle) {
            $this->assertTrue(
                AgriculturalEntityCatalog::matchesSemanticToken('ما المحاصيل التي يمكن زراعتها', $needle),
                $needle,
            );
        }

        $hay = 'ري بالمياه المالحة';
        foreach (['ملح', 'ملوحة', 'الملوحة', 'مالحة', 'مالح', 'المياه المالحة', 'مياه مالحة'] as $needle) {
            $this->assertTrue(
                AgriculturalEntityCatalog::matchesSemanticToken($hay, $needle),
                $needle,
            );
        }
    }

    public function test_subject_category_and_named_entity_are_separate(): void
    {
        $category = $this->understand('What crops can be grown in arid regions?');
        $this->assertSame('crop_category', $category->subject['type'] ?? null);
        $this->assertNull($category->cropId);
        $this->assertSame(AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR, $category->ambiguityState);

        $entity = $this->understand('ما احتياجات القمح من المياه؟');
        $this->assertSame('crop', $entity->subject['type'] ?? null);
        $this->assertSame('wheat', $entity->cropId);
    }

    public function test_attributes_do_not_replace_user_act(): void
    {
        $bestCrops = $this->understand('What are the best crops for drought?');
        $this->assertSame('optimal_range', $bestCrops->constraints['scientific_intent_qualifier'] ?? null);
        $this->assertSame('cultivation', $bestCrops->researchIntent);

        $optimalTemp = $this->understand('ما أفضل درجة حرارة لإنبات بذور الطماطم؟');
        $this->assertSame('tomato', $optimalTemp->cropId);
        $this->assertSame('seed_germination', $optimalTemp->constraints['scientific_sense'] ?? null);
        $this->assertSame('optimal_range', $optimalTemp->constraints['scientific_intent_qualifier'] ?? null);
        $this->assertNotSame('cultivation', $optimalTemp->researchIntent);
    }

    public function test_multiple_constraints_coexist(): void
    {
        $understood = $this->understand('What crops can be grown in arid regions with saline water under high temperature?');
        $types = $this->constraintTypes($understood);
        $this->assertContains('arid_environment', $types);
        $this->assertContains('saline_water', $types);
        $this->assertContains('high_temperature', $types);
        $this->assertSame('cultivation', $understood->researchIntent);
    }

    public function test_sense_and_domain_follow_user_act_not_constraint_keywords(): void
    {
        $understood = $this->understand('Which crops can be cultivated with saline water?');
        $this->assertSame('cultivation', $understood->researchIntent);
        $this->assertNotSame('crop_water_requirement', $understood->constraints['scientific_sense'] ?? null);
        $this->assertNotSame('irrigation_water', $understood->agriculturalDomain);
        $this->assertSame('cultivation', $understood->constraints['primary_user_act'] ?? null);
    }

    public function test_ambiguity_distinguishes_clear_acts_from_vague_questions(): void
    {
        $this->assertSame(
            AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            $this->understand('What crops are suitable for arid regions?')->ambiguityState,
        );
        $this->assertSame(
            'irrigation',
            $this->understand('irrigation scheduling for wheat')->researchIntent,
        );
        $this->assertSame(
            'salinity_physiology',
            $this->understand('ما تأثير الملوحة على القمح؟')->constraints['scientific_sense'] ?? null,
        );
    }

    public function test_research_plan_preserves_semantic_dimensions(): void
    {
        $question = 'What crops can be grown in arid regions with saline water?';
        $plan = $this->plan($question);
        $query = $plan->normalizedQuery;

        $this->assertSame('cultivation', $plan->researchIntent);
        $this->assertSame('crop_category', $plan->subjectEntity['type'] ?? null);
        $this->assertNull($query->cropId);
        $this->assertSame('recommendation', $query->constraints['question_type'] ?? null);
        $this->assertContains($query->constraints['scientific_intent_qualifier'] ?? null, ['general', 'optimal_range']);
        $this->assertNotEmpty($query->constraints['environmental_constraints'] ?? []);
        $this->assertSame('cultivation', $query->constraints['primary_user_act'] ?? null);
        $this->assertTrue(
            in_array('arid', $plan->topics, true)
            || in_array('desert', $plan->topics, true)
            || in_array('dryland', $plan->topics, true),
        );
    }

    public function test_query_construction_consumes_plan_act_and_constraints(): void
    {
        $plan = $this->plan('Which crops are suitable for saline water in arid regions?');
        $joined = $this->joinedQueries($plan);
        $this->assertTrue(
            str_contains($joined, 'crop suitability')
            || str_contains($joined, 'crop recommendation')
            || str_contains($joined, 'suitable crops'),
        );
        $this->assertTrue(str_contains($joined, 'saline') || str_contains($joined, 'salinity'));
        $this->assertTrue(str_contains($joined, 'arid') || str_contains($joined, 'desert'));
        $this->assertFalse(str_contains($joined, 'evapotranspiration'));
    }

    public function test_negative_regression_topics_do_not_replace_acts(): void
    {
        $this->assertNotSame(
            'irrigation',
            $this->understand('What crops can be grown where water is scarce?')->researchIntent,
        );
        $this->assertNotSame(
            'cultivation',
            $this->understand('ما تأثير الملوحة على القمح؟')->constraints['scientific_sense'] ?? null,
        );
        $temp = $this->understand('ما هو تأثير درجة الحرارة على نبات الزنجبيل؟');
        $this->assertSame('ginger', $temp->cropId);
        $this->assertContains('temperature', $temp->constraints['scientific_factors'] ?? []);
        $this->assertSame('effect', $temp->constraints['scientific_intent_qualifier'] ?? null);

        $multi = $this->understand('Crops that can be grown in drought and high temperature');
        $this->assertGreaterThanOrEqual(2, count($this->constraintTypes($multi)));
        $this->assertSame('cultivation', $multi->researchIntent);
    }

    public function test_english_and_arabic_share_the_same_semantic_model(): void
    {
        $en = $this->understand('What crops can be grown in desert land with saline water?');
        $ar = $this->understand('ما أفضل المحاصيل التي يمكن زراعتها في الأراضي الصحراوية ذات المياه المالحة؟');

        $this->assertSame($en->researchIntent, $ar->researchIntent);
        $this->assertSame('cultivation', $en->researchIntent);
        $this->assertSame($en->constraints['question_type'] ?? null, $ar->constraints['question_type'] ?? null);
        $this->assertContains('arid_environment', $this->constraintTypes($en));
        $this->assertContains('arid_environment', $this->constraintTypes($ar));
        $this->assertContains('saline_water', $this->constraintTypes($en));
        $this->assertContains('saline_water', $this->constraintTypes($ar));
    }

    /**
     * @return list<string>
     */
    private function constraintTypes(AgriculturalKnowledgeQuery $query): array
    {
        $out = [];
        foreach ($query->constraints['environmental_constraints'] ?? [] as $constraint) {
            if (is_array($constraint) && isset($constraint['type'])) {
                $out[] = (string) $constraint['type'];
            }
        }

        return $out;
    }

    private function understand(string $query): AgriculturalKnowledgeQuery
    {
        return app(QueryUnderstandingService::class)->understand(['query' => $query]);
    }

    private function plan(string $query): \App\Services\Agriculture\Research\KnowledgeQueryPlan
    {
        return app(ResearchPlanner::class)->planKnowledgeQuery(['query' => $query]);
    }

    private function joinedQueries(\App\Services\Agriculture\Research\KnowledgeQueryPlan $plan): string
    {
        return mb_strtolower(implode(' | ', app(ScientificSearchQueryBuilder::class)->buildVariantsFromPlan($plan)));
    }
}
