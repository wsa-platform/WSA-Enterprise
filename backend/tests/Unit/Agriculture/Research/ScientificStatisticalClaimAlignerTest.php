<?php

namespace Tests\Unit\Agriculture\Research;

use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Search\ScientificStatisticalClaimAligner;
use App\Services\Agriculture\Research\Search\ScientificStructuredObservation;
use Tests\TestCase;

class ScientificStatisticalClaimAlignerTest extends TestCase
{
    public function test_production_quantity_equivalents_share_one_family(): void
    {
        $aligner = new ScientificStatisticalClaimAligner();
        $surfaces = [
            'quantity',
            'production',
            'produced',
            'production quantity',
            'quantity produced',
            'كمية',
            'إنتاج',
            'المنتج',
            'المنتجة',
            'كمية الإنتاج',
            'كمية المنتج',
            'Production',
            'production_quantity',
        ];

        foreach ($surfaces as $surface) {
            $this->assertSame(
                'production_quantity',
                ScientificStatisticalClaimAligner::measureFamily($surface),
                $surface.' must normalize to production_quantity',
            );
        }

        $question = 'كم كمية القمح المنتج فى مصر عام 2020';
        $this->assertSame(
            'production_quantity',
            ScientificStatisticalClaimAligner::measureFamily($question),
            'quantity + produced signals must not collapse to a year token',
        );

        $observation = $this->observation();
        $assessment = $aligner->assess($this->plan([
            'original' => $question,
            'crop' => 'wheat',
            'location' => 'Egypt',
            'year' => '2020',
            'property_surface' => 'كمية',
            'property' => 'quantity',
        ]), $observation);

        $this->assertTrue($assessment['relevant']);
        $this->assertSame([], $assessment['mismatches']);
    }

    public function test_unrelated_measurement_families_stay_distinct_from_production_quantity(): void
    {
        $cases = [
            'yield' => 'yield',
            'crop yield' => 'yield',
            'إنتاجية' => 'yield',
            'محصول' => 'yield',
            'area harvested' => 'area_harvested',
            'harvested area' => 'area_harvested',
            'مساحة' => 'area_harvested',
            'imports' => 'imports',
            'import quantity' => 'imports',
            'واردات' => 'imports',
            'exports' => 'exports',
            'صادرات' => 'exports',
            'stock' => 'stock',
            'مخزون' => 'stock',
            'value' => 'value',
            'قيمة' => 'value',
            'price' => 'price',
            'سعر' => 'price',
        ];

        foreach ($cases as $surface => $family) {
            $this->assertSame(
                $family,
                ScientificStatisticalClaimAligner::measureFamily($surface),
                $surface.' must remain family '.$family,
            );
            $this->assertNotSame(
                'production_quantity',
                ScientificStatisticalClaimAligner::measureFamily($surface),
                $surface.' must not collapse into production_quantity',
            );
        }

        $aligner = new ScientificStatisticalClaimAligner();
        $plan = $this->plan([
            'original' => 'wheat production quantity Egypt 2020',
            'crop' => 'wheat',
            'location' => 'Egypt',
            'year' => '2020',
            'property_surface' => 'production quantity',
            'property' => 'production',
        ]);

        foreach (['Yield', 'Area harvested', 'Import Quantity', 'Export Quantity', 'Stock', 'Gross Value', 'Producer Price'] as $property) {
            $assessment = $aligner->assess($plan, $this->observation(property: $property));
            $this->assertFalse($assessment['relevant'], $property.' must not align as production quantity');
            $this->assertContains('property', $assessment['mismatches']);
        }
    }

    public function test_year_tokens_are_temporal_not_measurement_families(): void
    {
        $this->assertSame('', ScientificStatisticalClaimAligner::measureFamily('2020'));
        $this->assertSame('', ScientificStatisticalClaimAligner::measureFamily('2021'));
        $this->assertSame(
            'production_quantity',
            ScientificStatisticalClaimAligner::measureFamily('production quantity 2020'),
        );
        $this->assertSame(
            'production_quantity',
            ScientificStatisticalClaimAligner::measureFamily('كمية الإنتاج عام 2020'),
        );

        $this->assertNotSame(
            'production_quantity',
            ScientificStatisticalClaimAligner::measureFamily('wheat Egypt 2020'),
        );
        $this->assertNotSame('2020', ScientificStatisticalClaimAligner::measureFamily('wheat Egypt 2020'));
        $this->assertNotSame('_2020', ScientificStatisticalClaimAligner::measureFamily('wheat Egypt 2020'));
        $this->assertNotSame(
            'production_quantity',
            ScientificStatisticalClaimAligner::measureFamily('irrigation water quantity'),
        );

        $aligner = new ScientificStatisticalClaimAligner();
        $yearOnly = $aligner->assess($this->plan([
            'original' => 'wheat Egypt 2020',
            'crop' => 'wheat',
            'location' => 'Egypt',
            'year' => '2020',
            'property_surface' => '',
            'property' => '',
        ]), $this->observation());

        $this->assertNotContains('year', $yearOnly['mismatches']);
        $this->assertContains(
            'property',
            $yearOnly['mismatches'],
            'a year without a measurement signal must not invent production_quantity alignment',
        );
    }

    public function test_entity_location_and_year_are_not_bypassed_by_measurement_match(): void
    {
        $aligner = new ScientificStatisticalClaimAligner();
        $plan = $this->plan([
            'original' => 'wheat production quantity Egypt 2020',
            'crop' => 'wheat',
            'location' => 'Egypt',
            'year' => '2020',
            'property_surface' => 'production quantity',
            'property' => 'production',
        ]);

        $this->assertContains('entity', $aligner->assess($plan, $this->observation(entity: 'Maize'))['mismatches']);
        $this->assertContains('location', $aligner->assess($plan, $this->observation(location: 'Brazil'))['mismatches']);
        $this->assertContains('year', $aligner->assess($plan, $this->observation(year: '2019'))['mismatches']);
        $this->assertTrue($aligner->assess($plan, $this->observation())['relevant']);
    }

    /**
     * @param  array{
     *     original: string,
     *     crop: string,
     *     location: string,
     *     year: string,
     *     property_surface: string,
     *     property: string
     * }  $input
     */
    private function plan(array $input): KnowledgeQueryPlan
    {
        $query = new AgriculturalKnowledgeQuery(
            originalQuestion: $input['original'],
            normalizedQuestion: $input['original'],
            language: 'en',
            agriculturalDomain: 'agronomy',
            subject: ['type' => 'crop', 'value' => $input['crop']],
            crop: $input['crop'],
            cropId: $input['crop'],
            scientificName: null,
            topic: 'production',
            subtopic: null,
            requestedInformation: ['quantity'],
            constraints: [
                'requested_property_surface' => $input['property_surface'],
                'requested_property' => $input['property'],
                'year' => $input['year'],
            ],
            location: $input['location'],
            researchRequired: true,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            researchIntent: 'statistical_lookup',
        );

        return new KnowledgeQueryPlan(
            normalizedQuery: $query,
            researchIntent: 'statistical_lookup',
            agriculturalDomain: 'agronomy',
            subjectEntity: ['type' => 'crop', 'value' => $input['crop']],
            topics: ['production'],
            subtopics: [],
            requestedInformation: ['quantity'],
            evidenceRequirements: ['official_statistics'],
            sourcePriorities: ['openalex'],
            primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            readyForStage3: true,
        );
    }

    private function observation(
        string $entity = 'Wheat',
        string $location = 'Egypt',
        string $year = '2020',
        string $property = 'Production',
    ): ScientificStructuredObservation {
        return new ScientificStructuredObservation(
            entity: $entity,
            location: $location,
            year: $year,
            property: $property,
            unit: 't',
            value: '9101785',
            domain: 'QCL',
            entityCode: '',
            locationCode: '',
            propertyCode: '5510',
        );
    }
}
