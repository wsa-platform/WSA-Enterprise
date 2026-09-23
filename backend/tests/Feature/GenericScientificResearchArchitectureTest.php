<?php

namespace Tests\Feature;

use App\Services\Agriculture\Research\QueryUnderstandingService;
use App\Services\Agriculture\Research\ResearchPlanner;
use App\Services\Agriculture\Research\ScientificQuestionSemantics;
use App\Services\Agriculture\Research\Search\ScientificEvidenceRelevanceGate;
use App\Services\Agriculture\Research\Search\ScientificSearchQueryBuilder;
use Tests\TestCase;

/**
 * Phase 2C — generic scientific-research architecture.
 *
 * Crop identity is DATA. The same algorithm must serve every crop and every
 * supported question type. Wheat is only one row in the matrix.
 */
class GenericScientificResearchArchitectureTest extends TestCase
{
    /**
     * @return list<array{
     *     crop_id: string,
     *     crop_name: string,
     *     scientific_name: string,
     *     home_query: string,
     *     expected_question_type: string,
     *     expected_topic_needle: string,
     *     expected_variant_needle: string
     * }>
     */
    public static function cropRequirementMatrix(): array
    {
        return [
            ['wheat', 'Wheat', 'Triticum aestivum', 'What are the agronomic requirements for cultivating bread wheat?', 'requirements', 'agronomic requirements', 'agronomic requirements'],
            ['corn', 'Maize', 'Zea mays', 'What are the agronomic requirements for cultivating maize?', 'requirements', 'agronomic requirements', 'agronomic requirements'],
            ['rice', 'Rice', 'Oryza sativa', 'What are the agronomic requirements for cultivating rice?', 'requirements', 'agronomic requirements', 'agronomic requirements'],
            ['barley', 'Barley', 'Hordeum vulgare', 'What are the agronomic requirements for cultivating barley?', 'requirements', 'agronomic requirements', 'agronomic requirements'],
            ['tomato', 'Tomato', 'Solanum lycopersicum', 'What are the agronomic requirements for cultivating tomatoes?', 'requirements', 'agronomic requirements', 'agronomic requirements'],
            ['potato', 'Potato', 'Solanum tuberosum', 'What are the water requirements of potatoes?', 'requirements', 'water requirements', 'water requirements'],
        ];
    }

    /**
     * @return list<array{query: string, expected_type: string, expected_topic: string}>
     */
    public static function questionTypeMatrix(): array
    {
        return [
            ['What are the agronomic requirements for cultivating maize?', 'requirements', 'agronomic requirements'],
            ['What are the soil requirements of tomatoes?', 'requirements', 'soil requirements'],
            ['What are the soil requirements of strawberries?', 'requirements', 'soil requirements'],
            ['What are the water requirements of potatoes?', 'requirements', 'water requirements'],
            ['What are the cultivation practices for rice?', 'definition', 'cultivation practices'],
            ['What are the major diseases of wheat?', 'symptoms', 'plant diseases'],
            ['What are the major diseases of tomato?', 'symptoms', 'plant diseases'],
            ['What are the optimal temperature requirements for lettuce?', 'range', 'temperature'],
        ];
    }

    /**
     * @dataProvider cropRequirementMatrix
     */
    public function test_home_and_crop_share_requirement_semantics_for_each_crop(
        string $cropId,
        string $cropName,
        string $scientificName,
        string $homeQuery,
        string $expectedQuestionType,
        string $expectedTopicNeedle,
        string $expectedVariantNeedle,
    ): void {
        $qus = app(QueryUnderstandingService::class);
        $planner = app(ResearchPlanner::class);
        $builder = app(ScientificSearchQueryBuilder::class);

        $home = $qus->understand(['query' => $homeQuery]);
        $this->assertSame($expectedQuestionType, $home->constraints['question_type'] ?? null, $homeQuery);
        $this->assertContains(
            $expectedTopicNeedle,
            $home->constraints['scientific_topics'] ?? [],
            $homeQuery
        );

        $crop = $qus->understand([
            'selected_crop_id' => $cropId,
            'selected_crop_name' => $cropName,
            'scientific_name' => $scientificName,
            'knowledge_option' => 'farming-needs',
        ]);
        $this->assertSame('requirements', $crop->constraints['question_type'] ?? null, $cropId);
        $this->assertContains(
            'agronomic requirements',
            $crop->constraints['scientific_topics'] ?? [],
            $cropId
        );

        $homePlan = $planner->planKnowledgeQuery(['query' => $homeQuery]);
        $cropPlan = $planner->planKnowledgeQuery([
            'selected_crop_id' => $cropId,
            'selected_crop_name' => $cropName,
            'scientific_name' => $scientificName,
            'knowledge_option' => 'farming-needs',
        ]);
        $homeVariants = mb_strtolower(implode("\n", $builder->buildVariantsFromPlan($homePlan)));
        $cropVariants = mb_strtolower(implode("\n", $builder->buildVariantsFromPlan($cropPlan)));

        $this->assertStringContainsString($expectedVariantNeedle, $homeVariants, $homeQuery);
        $this->assertStringContainsString($expectedVariantNeedle === 'water requirements'
            ? 'agronomic requirements'
            : $expectedVariantNeedle, $cropVariants, $cropId);
        $this->assertTrue(
            str_contains($homeVariants, mb_strtolower($scientificName))
            || str_contains($homeVariants, mb_strtolower($cropId))
            || str_contains($homeVariants, mb_strtolower($cropName)),
            $homeVariants
        );
        $this->assertTrue(
            str_contains($cropVariants, mb_strtolower($scientificName))
            || str_contains($cropVariants, mb_strtolower($cropId)),
            $cropVariants
        );

        $this->assertStringNotContainsString('if wheat', $homeVariants);
        $this->assertDoesNotMatchRegularExpression('/\bif\s+'.$cropId.'\b/i', $homeVariants);
    }

    /**
     * @dataProvider questionTypeMatrix
     */
    public function test_question_type_matrix_is_entity_agnostic(
        string $query,
        string $expectedType,
        string $expectedTopic,
    ): void {
        $understood = app(QueryUnderstandingService::class)->understand(['query' => $query]);
        $this->assertSame($expectedType, $understood->constraints['question_type'] ?? null, $query);
        $topics = $understood->constraints['scientific_topics'] ?? [];
        $blob = mb_strtolower(implode(' ', is_array($topics) ? $topics : []));
        $this->assertStringContainsString($expectedTopic, $blob, $query);

        $plan = app(ResearchPlanner::class)->planKnowledgeQuery(['query' => $query]);
        $variants = mb_strtolower(implode("\n", app(ScientificSearchQueryBuilder::class)->buildVariantsFromPlan($plan)));
        $entityResolved = $understood->cropId !== null || $understood->scientificName !== null;
        if ($entityResolved) {
            $this->assertStringContainsString($expectedTopic, $variants, $query);
        }
    }

    public function test_uncatalogued_crop_data_uses_the_same_requirement_engine(): void
    {
        $this->assertNull(
            \App\Services\Agriculture\FieldCropTaxonomyCatalog::entryFor('future-crop-x')
        );

        $qus = app(QueryUnderstandingService::class);
        $understood = $qus->understand([
            'selected_crop_id' => 'future-crop-x',
            'selected_crop_name' => 'Future Crop X',
            'scientific_name' => 'Futurus cropus',
            'knowledge_option' => 'farming-needs',
        ]);

        $this->assertSame('requirements', $understood->constraints['question_type'] ?? null);
        $this->assertContains('agronomic requirements', $understood->constraints['scientific_topics'] ?? []);
        $this->assertSame('future-crop-x', $understood->cropId);
        $this->assertSame('Futurus cropus', $understood->scientificName);

        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'selected_crop_id' => 'future-crop-x',
            'selected_crop_name' => 'Future Crop X',
            'scientific_name' => 'Futurus cropus',
            'knowledge_option' => 'farming-needs',
        ]);
        $variants = $plan->normalizedQuery !== null
            ? app(ScientificSearchQueryBuilder::class)->buildVariantsFromPlan($plan)
            : [];
        $blob = mb_strtolower(implode("\n", $variants));
        $this->assertStringContainsString('futurus cropus', $blob);
        $this->assertStringContainsString('agronomic requirements', $blob);

        $source = file_get_contents(base_path('app/Services/Agriculture/Research/ScientificQuestionSemantics.php')) ?: '';
        $this->assertStringNotContainsString('future-crop-x', $source);
        $this->assertStringNotContainsString('Futurus', $source);
        $this->assertStringNotContainsString('wheat', mb_strtolower($source));
        $this->assertStringNotContainsString('triticum', mb_strtolower($source));
    }

    public function test_home_agronomic_paper_is_not_rejected_as_missing_physiology_topic(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'What are the agronomic requirements for cultivating maize?',
        ]);
        $gate = app(ScientificEvidenceRelevanceGate::class);
        $assessment = $gate->assess(
            $plan,
            'Agronomic requirements of Zea mays for crop management',
            'This review specifies agronomic requirements and cultivation practices of Zea mays.',
        );

        $this->assertTrue($assessment['relevant'], json_encode($assessment['rejection_reasons'] ?? []));
        $this->assertTrue($assessment['topic_matched']);
        $this->assertNotContains('missing_topic_or_factor', $assessment['rejection_reasons'] ?? []);
    }

    public function test_requirement_questions_do_not_require_numeric_measurements(): void
    {
        $this->assertContains('requirements', ScientificQuestionSemantics::qualitativeQuestionTypes());
        $this->assertContains('quantity', ScientificQuestionSemantics::quantitativeQuestionTypes());
        $this->assertContains('range', ScientificQuestionSemantics::quantitativeQuestionTypes());
        $this->assertTrue(ScientificQuestionSemantics::isRequirementSpecification(
            'requirements',
            'requirement_specification',
            'requirement',
        ));
        $this->assertFalse(ScientificQuestionSemantics::isRequirementSpecification(
            'quantity',
            'numeric_rate_or_quantity',
            'general',
        ));
    }

    public function test_home_and_crop_do_not_use_wheat_specific_production_branches(): void
    {
        $files = [
            base_path('app/Services/Agriculture/Research/ScientificQuestionSemantics.php'),
            base_path('app/Services/Agriculture/Research/QueryUnderstandingService.php'),
            base_path('app/Services/Agriculture/Research/Search/ScientificSearchQueryBuilder.php'),
            base_path('app/Services/Agriculture/Research/Search/ScientificEvidenceRelevanceGate.php'),
        ];
        foreach ($files as $file) {
            $source = file_get_contents($file) ?: '';
            $this->assertDoesNotMatchRegularExpression(
                '/if\s*\(\s*(?:\$\w+\s*===?\s*[\'"]wheat[\'"]|[\'"]wheat[\'"]\s*===?\s*\$\w+)/i',
                $source,
                $file
            );
            $this->assertDoesNotMatchRegularExpression(
                '/if\s*\(\s*.*triticum/i',
                $source,
                $file
            );
        }
    }
}
