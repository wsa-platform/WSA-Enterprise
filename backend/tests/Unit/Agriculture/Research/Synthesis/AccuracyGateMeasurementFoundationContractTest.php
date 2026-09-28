<?php

namespace Tests\Unit\Agriculture\Research\Synthesis;

use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\CanonicalScientificQuestion;
use App\Services\Agriculture\Research\CsqContext;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Synthesis\AnswerExpressionAccuracyGate;
use PHPUnit\Framework\TestCase;

/**
 * Repair 1: AccuracyGate measurement foundation isolated from Composer/RelevanceGate.
 */
class AccuracyGateMeasurementFoundationContractTest extends TestCase
{
    private AnswerExpressionAccuracyGate $gate;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gate = new AnswerExpressionAccuracyGate;
    }

    public function test_celsius_unit_is_compatible_with_temperature_family(): void
    {
        $this->assertTrue($this->familyCompatible('temperature', 'Wheat grew at 18 °C in chambers.'));
        $this->assertTrue($this->gate->haystackHasMeasurementClassSense(
            'Wheat grew at 18 °C in chambers.',
            'temperature',
        ));
    }

    public function test_celsius_without_space_is_compatible_with_temperature_family(): void
    {
        $this->assertTrue($this->familyCompatible('temperature', 'Wheat grew at 18°C in chambers.'));
        $this->assertTrue($this->gate->haystackHasMeasurementClassSense(
            'Wheat grew at 18°C in chambers.',
            'temperature',
        ));
    }

    public function test_temperature_requirement_phrase_is_property_addressed(): void
    {
        $hay = 'Triticum aestivum wheat temperature requirement for plant growth.';
        $this->assertTrue($this->gate->haystackAddressesPropertyTerms(
            $hay,
            $this->gate->propertyAddressTerms('temperature'),
        ));
    }

    public function test_rainfall_mm_is_incompatible_with_temperature_family(): void
    {
        $text = 'Wheat yield increased under 450 mm seasonal rainfall in field trials.';
        $this->assertFalse($this->familyCompatible('temperature', $text));
        $this->assertContains('rate', $this->gate->measurementUnitClassesInText($text));
        $this->assertFalse($this->gate->haystackHasMeasurementClassSense($text, 'temperature'));
    }

    public function test_yield_tonne_per_hectare_is_incompatible_with_temperature_family(): void
    {
        $text = 'Wheat grain yield reached 5 t/ha in irrigated field trials.';
        $this->assertFalse($this->familyCompatible('temperature', $text));
        $this->assertContains('rate', $this->gate->measurementUnitClassesInText($text));
        $this->assertFalse($this->gate->haystackHasMeasurementClassSense($text, 'temperature'));
    }

    public function test_bare_number_is_not_measurement_evidence(): void
    {
        $text = 'Triticum aestivum wheat growth was scored on 18 replicated field plots.';
        $this->assertSame([], $this->gate->measurementUnitClassesInText($text));
        $this->assertFalse($this->gate->haystackHasMeasurementClassSense($text, 'temperature'));
        $this->assertFalse($this->familyCompatible('temperature', $text));
    }

    public function test_wheat_without_thermal_regimes_is_not_property_addressed(): void
    {
        $hay = 'Wheat plant growth and physiology in field agronomy without thermal regimes.';
        $this->assertFalse($this->gate->haystackAddressesPropertyTerms(
            $hay,
            $this->gate->propertyAddressTerms('temperature'),
        ));
        $this->assertNotContains('heat', $this->gate->propertyAddressTerms('temperature'));
        $this->assertNotContains('thermal', $this->gate->propertyAddressTerms('temperature'));
    }

    public function test_heat_requirement_is_not_automatic_temperature_evidence(): void
    {
        $hay = 'Crop heat requirement documented for field agronomy.';
        $this->assertFalse($this->gate->haystackAddressesPropertyTerms(
            $hay,
            $this->gate->propertyAddressTerms('temperature'),
        ));
        $this->assertFalse($this->gate->haystackHasMeasurementClassSense($hay, 'temperature'));
    }

    public function test_thermal_sensor_without_unit_is_not_temperature_class_sense(): void
    {
        $hay = 'Instrument thermal sensor readings near tissue in a laboratory reactor.';
        $this->assertFalse($this->gate->haystackHasMeasurementClassSense($hay, 'temperature'));
        $this->assertFalse($this->gate->haystackAddressesPropertyTerms(
            $hay,
            $this->gate->propertyAddressTerms('temperature'),
        ));
    }

    public function test_question_type_range_without_frozen_family_is_not_temperature(): void
    {
        $plan = $this->planWithoutCsq([
            'question_type' => 'range',
            'scientific_factors' => ['temperature'],
            'requested_property' => 'température optimale',
        ]);
        $this->assertSame('', $this->gate->frozenMeasurementFamily($plan));
        $this->assertSame('', $this->gate->canonicalMeasurementProperty('température optimale', $plan));
    }

    public function test_frozen_french_surface_csq_accepts_english_celsius(): void
    {
        $plan = $this->temperatureFamilyPlan('température optimale');
        $this->assertSame('temperature', $this->gate->frozenMeasurementFamily($plan));
        $this->assertTrue($this->familyCompatible(
            $this->gate->frozenMeasurementFamily($plan),
            'A temperature of 20 °C was more effective than 15 °C for wheat germination.',
        ));
    }

    public function test_negated_temperature_token_is_not_addressed(): void
    {
        $terms = $this->gate->propertyAddressTerms('temperature');
        $this->assertFalse($this->gate->haystackAddressesPropertyTerms(
            'The protocol reported germination without temperature optima.',
            $terms,
        ));
        $this->assertFalse($this->gate->haystackAddressesPropertyTerms(
            'Plots had no temperature records during the trial.',
            $terms,
        ));
        $this->assertFalse($this->gate->haystackAddressesPropertyTerms(
            'Plots not reporting temperature optima during the trial.',
            $terms,
        ));
    }

    /**
     * @param  list<string>  $allowed
     */
    private function familyCompatible(string $family, string $text): bool
    {
        $allowed = $this->gate->requestedPropertyUnitClasses($family);
        if (! is_array($allowed) || $allowed === []) {
            return false;
        }
        foreach ($this->gate->measurementUnitClassesInText($text) as $class) {
            if (in_array($class, $allowed, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $constraints
     */
    private function planWithoutCsq(array $constraints): KnowledgeQueryPlan
    {
        $query = new AgriculturalKnowledgeQuery(
            originalQuestion: 'range question',
            normalizedQuestion: 'range question',
            language: 'fr',
            agriculturalDomain: 'agronomy',
            subject: ['type' => 'crop', 'value' => 'wheat', 'label' => 'wheat'],
            crop: 'wheat',
            cropId: 'wheat',
            scientificName: 'Triticum aestivum',
            topic: 'growth',
            subtopic: null,
            requestedInformation: [],
            constraints: $constraints,
            location: null,
            researchRequired: true,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            researchIntent: 'environmental_requirements',
        );

        return $this->wrapPlan($query);
    }

    private function temperatureFamilyPlan(string $propertySurface): KnowledgeQueryPlan
    {
        $csq = new CanonicalScientificQuestion(
            originalQuestion: $propertySurface.' wheat',
            language: 'fr',
            normalizedForm: $propertySurface.' wheat',
            researchContext: CanonicalScientificQuestion::RESEARCH_CONTEXT_HOME,
            entity: CanonicalScientificQuestion::entity(
                surface: 'blé',
                canonicalId: 'wheat',
                resolution: CanonicalScientificQuestion::RESOLUTION_RESOLVED,
            ),
            property: CanonicalScientificQuestion::property(
                key: 'temperature',
                surface: $propertySurface,
                resolution: CanonicalScientificQuestion::RESOLUTION_RESOLVED,
            ),
            context: new CsqContext(
                domain: 'agronomy',
                scientificSense: 'plant_growth',
                researchIntent: 'environmental_requirements',
                questionType: 'range',
            ),
        );
        $query = new AgriculturalKnowledgeQuery(
            originalQuestion: $propertySurface.' wheat',
            normalizedQuestion: $propertySurface.' wheat',
            language: 'fr',
            agriculturalDomain: 'agronomy',
            subject: ['type' => 'crop', 'value' => 'wheat', 'label' => 'wheat'],
            crop: 'wheat',
            cropId: 'wheat',
            scientificName: 'Triticum aestivum',
            topic: 'temperature',
            subtopic: null,
            requestedInformation: ['temperature'],
            constraints: [
                'question_type' => 'range',
                'requested_property' => $propertySurface,
                'requested_property_surface' => $propertySurface,
            ],
            location: null,
            researchRequired: true,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            researchIntent: 'environmental_requirements',
            canonicalQuestion: $csq,
        );

        return $this->wrapPlan($query);
    }

    private function wrapPlan(AgriculturalKnowledgeQuery $query): KnowledgeQueryPlan
    {
        return new KnowledgeQueryPlan(
            normalizedQuery: $query,
            researchIntent: 'environmental_requirements',
            agriculturalDomain: 'agronomy',
            subjectEntity: $query->subject,
            topics: ['temperature'],
            subtopics: [],
            requestedInformation: $query->requestedInformation,
            evidenceRequirements: ['peer_reviewed'],
            sourcePriorities: ['openalex'],
            primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            readyForStage3: true,
        );
    }
}
