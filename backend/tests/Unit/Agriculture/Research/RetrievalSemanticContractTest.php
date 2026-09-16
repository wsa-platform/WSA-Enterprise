<?php

namespace Tests\Unit\Agriculture\Research;

use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\RetrievalSemanticContract;
use App\Services\Agriculture\Research\Search\ScientificSearchQueryBuilder;
use Tests\TestCase;

class RetrievalSemanticContractTest extends TestCase
{
    public function test_water_measurement_role_does_not_fall_back_to_yield(): void
    {
        $cases = [
            [
                'surface' => 'irrigation water',
                'key' => 'quantity',
                'sense' => 'crop_water_requirement',
                'intent' => 'irrigation',
            ],
            [
                'surface' => 'water requirement',
                'key' => 'irrigation',
                'sense' => 'evapotranspiration',
                'intent' => 'scientific_research',
            ],
            [
                'surface' => '',
                'key' => 'quantity',
                'sense' => 'crop_water_requirement',
                'intent' => 'irrigation',
            ],
            [
                'surface' => 'رطوبة الري',
                'key' => 'quantity',
                'sense' => 'crop_water_requirement',
                'intent' => 'irrigation',
            ],
        ];

        foreach ($cases as $case) {
            $applied = app(RetrievalSemanticContract::class)->apply($this->plan(
                crop: 'barley',
                intent: $case['intent'],
                constraints: [
                    'requested_property_surface' => $case['surface'],
                    'requested_property_key' => $case['key'],
                    'scientific_sense' => $case['sense'],
                    'requested_property_query_terms' => ['yield', 'production', 'quantity'],
                ],
            ));
            $terms = $applied->normalizedQuery->constraints['requested_property_query_terms'];
            $this->assertSame(
                RetrievalSemanticContract::ROLE_IRRIGATION_WATER,
                $applied->normalizedQuery->constraints['retrieval_property_role'],
            );
            $this->assertNotContains('yield', $terms);
            $this->assertNotContains('production', $terms);
            $joined = mb_strtolower(implode(' ', $terms));
            $this->assertTrue(
                str_contains($joined, 'irrigation') || str_contains($joined, 'water'),
                'water-measurement role must keep irrigation/water terms: '.$joined,
            );
        }
    }

    public function test_empty_unresolved_property_does_not_invent_productivity_terms(): void
    {
        $applied = app(RetrievalSemanticContract::class)->apply($this->plan(
            crop: 'sorghum',
            intent: 'scientific_research',
            constraints: [
                'requested_property_surface' => '',
                'requested_property_key' => '',
                'scientific_sense' => '',
                'requested_property_query_terms' => ['yield', 'production'],
            ],
        ));
        $this->assertTrue((bool) $applied->normalizedQuery->constraints['requested_property_unresolved']);
        $this->assertSame([], $applied->normalizedQuery->constraints['requested_property_query_terms']);
    }

    public function test_statistical_production_role_keeps_production_quantity(): void
    {
        $applied = app(RetrievalSemanticContract::class)->apply($this->plan(
            crop: 'maize',
            intent: 'statistical_lookup',
            constraints: [
                'requested_property_surface' => 'production quantity',
                'requested_property_key' => 'production',
                'scientific_sense' => 'production_quantity',
                'year' => '2019',
            ],
            location: 'brazil',
        ));
        $this->assertSame(RetrievalSemanticContract::ROLE_PRODUCTIVITY, $applied->normalizedQuery->constraints['retrieval_property_role']);
        $this->assertContains('production', $applied->normalizedQuery->constraints['requested_property_query_terms']);
        $this->assertContains('quantity', $applied->normalizedQuery->constraints['requested_property_query_terms']);
    }

    public function test_temperature_germination_role_preserves_temperature_not_yield(): void
    {
        foreach (['rice', 'lettuce', 'pepper'] as $crop) {
            $applied = app(RetrievalSemanticContract::class)->apply($this->plan(
                crop: $crop,
                intent: 'scientific_research',
                constraints: [
                    'requested_property_surface' => 'temperature',
                    'requested_property_key' => 'temperature',
                    'scientific_sense' => 'seed_germination',
                    'requested_property_query_terms' => ['yield', 'temperature'],
                ],
            ));
            $terms = $applied->normalizedQuery->constraints['requested_property_query_terms'];
            $this->assertSame(RetrievalSemanticContract::ROLE_TEMPERATURE, $applied->normalizedQuery->constraints['retrieval_property_role']);
            $this->assertContains('temperature', $terms);
            $this->assertContains('germination', $terms);
            $this->assertNotContains('yield', $terms);
            $primary = app(ScientificSearchQueryBuilder::class)->buildVariantsFromPlan($applied)[0] ?? '';
            $this->assertStringContainsString($crop, mb_strtolower($primary));
            $this->assertTrue(
                str_contains(mb_strtolower($primary), 'temperature')
                || str_contains(mb_strtolower($primary), 'germination'),
            );
        }
    }

    public function test_livestock_property_is_not_replaced_by_crop_yield(): void
    {
        $applied = app(RetrievalSemanticContract::class)->apply($this->plan(
            crop: 'cattle',
            intent: 'scientific_research',
            subjectType: 'animal',
            constraints: [
                'requested_property_surface' => 'milk yield',
                'requested_property_key' => 'yield',
                'scientific_sense' => 'production_quantity',
                'named_entity_surface' => 'cattle',
            ],
        ));
        $this->assertSame('cattle', $applied->normalizedQuery->crop);
        $this->assertContains('production', $applied->normalizedQuery->constraints['requested_property_query_terms']);
    }

    public function test_generic_process_token_is_not_promoted_to_entity(): void
    {
        foreach (['physiology', 'germination', 'irrigation', 'temperature'] as $token) {
            $applied = app(RetrievalSemanticContract::class)->apply($this->plan(
                crop: $token,
                intent: 'scientific_research',
                subjectType: 'topic',
                constraints: [
                    'scientific_sense' => 'plant_physiology',
                    'named_entity_surface' => '',
                ],
            ));
            $this->assertTrue((bool) $applied->normalizedQuery->constraints['retrieval_entity_suppressed']);
            $this->assertNull($applied->normalizedQuery->crop);
        }
    }

    public function test_primary_query_contains_mandatory_semantic_components(): void
    {
        $applied = app(RetrievalSemanticContract::class)->apply($this->plan(
            crop: 'barley',
            intent: 'irrigation',
            constraints: [
                'requested_property_surface' => 'water requirement',
                'requested_property_key' => 'quantity',
                'scientific_sense' => 'crop_water_requirement',
                'scientific_factors' => ['drought'],
                'requested_property_query_terms' => ['yield', 'production'],
            ],
        ));
        $primary = app(ScientificSearchQueryBuilder::class)->buildVariantsFromPlan($applied)[0] ?? '';
        $folded = mb_strtolower($primary);
        $this->assertStringContainsString('barley', $folded);
        $this->assertTrue(str_contains($folded, 'irrigation') || str_contains($folded, 'water'));
        $this->assertStringContainsString('drought', $folded);
        $this->assertStringNotContainsString('yield production', $folded);
    }

    /**
     * @param  array<string, mixed>  $constraints
     */
    private function plan(
        string $crop,
        string $intent,
        array $constraints,
        string $subjectType = 'crop',
        ?string $location = null,
    ): KnowledgeQueryPlan {
        $query = new AgriculturalKnowledgeQuery(
            originalQuestion: $crop.' '.$intent,
            normalizedQuestion: $crop.' '.$intent,
            language: 'en',
            agriculturalDomain: 'agronomy',
            subject: ['type' => $subjectType, 'value' => $crop],
            crop: $crop,
            cropId: $subjectType === 'crop' || $subjectType === 'animal' ? $crop : null,
            scientificName: null,
            topic: $intent,
            subtopic: null,
            requestedInformation: ['evidence'],
            constraints: $constraints,
            location: $location,
            researchRequired: true,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            researchIntent: $intent,
        );

        return new KnowledgeQueryPlan(
            normalizedQuery: $query,
            researchIntent: $intent,
            agriculturalDomain: 'agronomy',
            subjectEntity: ['type' => $subjectType, 'value' => $crop],
            topics: [$intent],
            subtopics: [],
            requestedInformation: ['evidence'],
            evidenceRequirements: ['peer_reviewed'],
            sourcePriorities: ['openalex', 'crossref', 'semantic_scholar'],
            primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            readyForStage3: true,
        );
    }
}
