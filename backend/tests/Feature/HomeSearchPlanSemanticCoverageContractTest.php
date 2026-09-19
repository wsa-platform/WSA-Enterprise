<?php

namespace Tests\Feature;

use App\Services\Agriculture\FieldCropTaxonomyCatalog;
use App\Services\Agriculture\Research\ResearchPlanner;
use App\Services\Agriculture\Research\Search\ScientificSearchQueryBuilder;
use Tests\TestCase;

/**
 * Phase 10B — Home search-plan semantic coverage (multi-entity / dimension preservation).
 * Crop-profile plans must remain unaffected (ADR-019).
 */
class HomeSearchPlanSemanticCoverageContractTest extends TestCase
{
    /**
     * @return array<string, array{0: string}>
     */
    public static function multiEntityProvider(): array
    {
        return [
            'comparison_irrigation_en' => ['Which needs more irrigation water, wheat or maize?'],
            'comparison_yield_en' => ['Compare wheat and maize in terms of yield.'],
            'comparison_irrigation_ar' => ['أيهما يحتاج مياه ري أكثر، القمح أم الذرة؟'],
            'comparison_yield_tr' => ['Buğday ve mısırın verimini karşılaştır.'],
            'comparison_yield_fr' => ['Comparez le rendement du blé et du maïs.'],
        ];
    }

    /**
     * @dataProvider multiEntityProvider
     */
    public function test_home_multi_entity_variants_cover_every_comparison_entity(string $query): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery(['query' => $query]);
        $this->assertFalse($plan->toAgriculturalResearchPlan()->isCropProfileIntent(), $query);

        $constraints = $plan->normalizedQuery->constraints;
        $this->assertTrue((bool) ($constraints['is_comparison'] ?? false), $query);

        $cropIds = [];
        foreach ($constraints['comparison_entities'] ?? [] as $entity) {
            if (is_array($entity) && isset($entity['crop_id'])) {
                $cropIds[] = (string) $entity['crop_id'];
            }
        }
        if ($plan->normalizedQuery->cropId) {
            $cropIds[] = (string) $plan->normalizedQuery->cropId;
        }
        $cropIds = array_values(array_unique($cropIds));
        $this->assertGreaterThanOrEqual(2, count($cropIds), $query.' '.json_encode($cropIds));

        $variants = app(ScientificSearchQueryBuilder::class)->buildVariantsFromPlan($plan);
        $this->assertNotEmpty($variants, $query);
        $this->assertLessThanOrEqual(5, count($variants), 'variant cap must remain bounded');

        $joined = mb_strtolower(implode(' | ', $variants));
        foreach ($cropIds as $cropId) {
            $this->assertTrue(
                $this->variantsMentionCrop($joined, $cropId),
                "entity {$cropId} missing from variants for: {$query}\n".implode("\n", $variants),
            );
        }
    }

    public function test_single_entity_property_location_preserved(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'What are the irrigation requirements of wheat in Egypt?',
        ]);
        $variants = app(ScientificSearchQueryBuilder::class)->buildVariantsFromPlan($plan);
        $joined = mb_strtolower(implode(' | ', $variants));

        $this->assertTrue($this->variantsMentionCrop($joined, 'wheat'), implode("\n", $variants));
        $this->assertTrue(
            (bool) preg_match('/irrigation|water|evapotranspiration|requirement/u', $joined),
            implode("\n", $variants),
        );
        $this->assertStringContainsString('egypt', $joined);
    }

    public function test_year_remains_on_plan_and_is_intentional_scholarly_omit(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'What was wheat production in Egypt in 2022?',
        ]);
        $this->assertSame('2022', (string) ($plan->normalizedQuery->constraints['year'] ?? ''));
        $this->assertSame('Egypt', $plan->normalizedQuery->location);

        $variants = app(ScientificSearchQueryBuilder::class)->buildVariantsFromPlan($plan);
        $joined = mb_strtolower(implode(' | ', $variants));
        $this->assertTrue($this->variantsMentionCrop($joined, 'wheat'));
        $this->assertStringContainsString('egypt', $joined);
        // Scholarly text variants intentionally omit year; FAOSTAT options carry year.
        $this->assertStringNotContainsString('2022', $joined);

        $options = app(ScientificSearchQueryBuilder::class)->buildConsensusRequestOptions($plan);
        $this->assertArrayNotHasKey('year', $options);
    }

    public function test_multilingual_germination_preserves_wheat_and_temperature(): void
    {
        $queries = [
            'ما درجة حرارة إنبات القمح؟',
            'What is the germination temperature of wheat?',
            'Buğdayın çimlenme sıcaklığı nedir?',
            'Quelle est la température de germination du blé ?',
        ];
        foreach ($queries as $query) {
            $plan = app(ResearchPlanner::class)->planKnowledgeQuery(['query' => $query]);
            $variants = app(ScientificSearchQueryBuilder::class)->buildVariantsFromPlan($plan);
            $joined = mb_strtolower(implode(' | ', $variants));
            $this->assertTrue($this->variantsMentionCrop($joined, 'wheat'), $query);
            $this->assertTrue(
                (bool) preg_match('/temperature|germination/u', $joined),
                $query."\n".implode("\n", $variants),
            );
        }
    }

    public function test_salinity_relationship_preserves_entity_and_factor(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'How does salinity affect wheat germination?',
        ]);
        $variants = app(ScientificSearchQueryBuilder::class)->buildVariantsFromPlan($plan);
        $joined = mb_strtolower(implode(' | ', $variants));
        $this->assertTrue($this->variantsMentionCrop($joined, 'wheat'));
        $this->assertTrue((bool) preg_match('/salinity|salt/u', $joined), implode("\n", $variants));
    }

    public function test_crop_profile_plan_skips_home_multi_entity_coverage(): void
    {
        $base = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'farming needs',
            'selected_crop_id' => 'wheat',
            'selected_crop_name' => 'Wheat',
            'knowledge_option' => 'farming-needs',
        ]);
        $this->assertTrue($base->toAgriculturalResearchPlan()->isCropProfileIntent());

        $q = $base->normalizedQuery;
        $constraints = $q->constraints;
        $constraints['is_comparison'] = true;
        $constraints['comparison_entities'] = [
            ['crop_id' => 'wheat', 'label' => 'Wheat'],
            ['crop_id' => 'corn', 'label' => 'Maize'],
        ];

        $query = new \App\Services\Agriculture\Research\AgriculturalKnowledgeQuery(
            originalQuestion: $q->originalQuestion,
            normalizedQuestion: $q->normalizedQuestion,
            language: $q->language,
            agriculturalDomain: $q->agriculturalDomain,
            subject: $q->subject,
            crop: $q->crop,
            cropId: $q->cropId,
            scientificName: $q->scientificName,
            topic: $q->topic,
            subtopic: $q->subtopic,
            requestedInformation: $q->requestedInformation,
            constraints: $constraints,
            location: $q->location,
            researchRequired: $q->researchRequired,
            ambiguityState: $q->ambiguityState,
            clarificationRequirements: $q->clarificationRequirements,
            researchIntent: $q->researchIntent,
        );

        $plan = new \App\Services\Agriculture\Research\KnowledgeQueryPlan(
            normalizedQuery: $query,
            researchIntent: $base->researchIntent,
            agriculturalDomain: $base->agriculturalDomain,
            subjectEntity: $base->subjectEntity,
            topics: $base->topics,
            subtopics: $base->subtopics,
            requestedInformation: $base->requestedInformation,
            evidenceRequirements: $base->evidenceRequirements,
            sourcePriorities: $base->sourcePriorities,
            primaryResearchStrategy: $base->primaryResearchStrategy,
            researchSequence: $base->researchSequence,
            ambiguityState: $base->ambiguityState,
            clarificationRequirements: $base->clarificationRequirements,
            contextInput: $base->contextInput,
            readyForStage3: $base->readyForStage3,
        );
        $this->assertTrue($plan->toAgriculturalResearchPlan()->isCropProfileIntent());

        $variants = app(ScientificSearchQueryBuilder::class)->buildVariantsFromPlan($plan);
        $joined = mb_strtolower(implode(' | ', $variants));
        $this->assertFalse(
            $this->variantsMentionCrop($joined, 'corn'),
            'Crop-profile variants must not gain Home multi-entity maize coverage',
        );
    }

    private function variantsMentionCrop(string $joinedLower, string $cropId): bool
    {
        $tokens = [];
        $scientific = mb_strtolower(trim(FieldCropTaxonomyCatalog::scientificNameFor($cropId)));
        if ($scientific !== '') {
            $tokens[] = $scientific;
            $genus = trim(explode(' ', $scientific)[0] ?? '');
            if ($genus !== '') {
                $tokens[] = $genus;
            }
        }
        $tokens[] = mb_strtolower(str_replace('-', ' ', $cropId));
        foreach (FieldCropTaxonomyCatalog::searchTermsFor($cropId) as $term) {
            $label = mb_strtolower(trim((string) $term));
            if ($label !== '' && preg_match('/\p{Arabic}/u', $label) !== 1) {
                $tokens[] = $label;
            }
        }
        foreach (array_unique($tokens) as $token) {
            if ($token !== '' && mb_strpos($joinedLower, $token) !== false) {
                return true;
            }
        }

        return false;
    }
}
