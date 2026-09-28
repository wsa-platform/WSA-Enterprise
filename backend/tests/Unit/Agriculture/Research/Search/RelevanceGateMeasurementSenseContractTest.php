<?php

namespace Tests\Unit\Agriculture\Research\Search;

use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\CanonicalScientificQuestion;
use App\Services\Agriculture\Research\CsqContext;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Search\ScientificEvidenceRelevanceGate;
use App\Services\Agriculture\Research\Synthesis\AnswerExpressionAccuracyGate;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * RelevanceGate measurement-sense: consume frozen CSQ/RS family via AccuracyGate.
 * Topic-strength upgrade only. Not a second property-identity resolver.
 */
class RelevanceGateMeasurementSenseContractTest extends TestCase
{
    private ScientificEvidenceRelevanceGate $gate;

    private ReflectionMethod $classSense;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gate = new ScientificEvidenceRelevanceGate(new AnswerExpressionAccuracyGate);
        $this->classSense = new ReflectionMethod(ScientificEvidenceRelevanceGate::class, 'haystackHasMeasurementClassSense');
        $this->classSense->setAccessible(true);
    }

    public function test_frozen_temperature_and_celsius_has_measurement_sense(): void
    {
        $plan = $this->frozenFamilyPlan('temperature', 'température optimale');
        $hay = 'Wheat germinated best at 18 °C under controlled growth chambers.';

        $this->assertTrue($this->measurementSense($plan, $hay));
        $assessment = $this->gate->assess($plan, $hay, $hay);
        $this->assertSame('strong', $assessment['factors']['topic_strength'] ?? null);
        $this->assertTrue($assessment['topic_matched']);
    }

    public function test_frozen_temperature_and_rainfall_mm_has_no_measurement_sense(): void
    {
        $plan = $this->frozenFamilyPlan('temperature', 'optimal temperature');
        $hay = 'Wheat yield increased under 450 mm seasonal rainfall in field trials.';

        $this->assertFalse($this->measurementSense($plan, $hay));
    }

    public function test_frozen_temperature_and_bare_number_has_no_measurement_sense(): void
    {
        $plan = $this->frozenFamilyPlan('temperature', 'temperature');
        $hay = 'Triticum aestivum wheat growth was scored on 18 replicated field plots.';

        $this->assertFalse($this->measurementSense($plan, $hay));
    }

    public function test_no_csq_range_and_factor_do_not_invent_temperature_sense(): void
    {
        $plan = $this->planWithoutCsq([
            'question_type' => 'range',
            'scientific_factors' => ['temperature'],
            'requested_property' => 'température optimale',
            'requested_property_query_terms' => ['température optimale'],
        ]);
        $hay = 'Wheat germinated best at 18 °C under controlled growth chambers.';

        $this->assertFalse($this->measurementSense($plan, $hay));
        $assessment = $this->gate->assess($plan, $hay, $hay);
        $this->assertNotSame('strong', $assessment['factors']['topic_strength'] ?? null);
    }

    public function test_frozen_irrigation_family_rejects_celsius_as_measurement_sense(): void
    {
        $plan = $this->frozenFamilyPlan('irrigation', 'rainfall');
        $hay = 'Wheat germinated best at 18 °C under controlled growth chambers.';

        $this->assertSame('irrigation', (new AnswerExpressionAccuracyGate)->frozenMeasurementFamily($plan));
        $this->assertFalse($this->measurementSense($plan, $hay));
    }

    /**
     * @dataProvider temperatureSurfaces
     */
    public function test_multilingual_frozen_temperature_accepts_english_celsius(string $language, string $surface): void
    {
        $plan = $this->frozenFamilyPlan('temperature', $surface, $language);
        $hay = 'Wheat germinated best at 18 °C under controlled growth chambers.';

        $this->assertTrue($this->measurementSense($plan, $hay));
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    public static function temperatureSurfaces(): array
    {
        return [
            ['en', 'optimal temperature'],
            ['fr', 'température optimale'],
            ['tr', 'optimum sıcaklık'],
            ['ar', 'درجة الحرارة'],
        ];
    }

    public function test_wheat_without_thermal_regimes_is_not_temperature_measurement_sense(): void
    {
        $plan = $this->frozenFamilyPlan('temperature', 'température optimale');
        $hay = 'Wheat plant growth and physiology in field agronomy without thermal regimes.';

        $this->assertFalse($this->measurementSense($plan, $hay));
    }

    public function test_thermal_wording_is_not_temperature_measurement_sense(): void
    {
        $plan = $this->frozenFamilyPlan('temperature', 'temperature');
        $hay = 'Instrument thermal sensor readings near tissue in a laboratory reactor.';

        $this->assertFalse($this->measurementSense($plan, $hay));
    }

    public function test_yield_tonne_per_hectare_is_not_temperature_measurement_sense(): void
    {
        $plan = $this->frozenFamilyPlan('temperature', 'temperature');
        $hay = 'Wheat grain yield reached 5 t/ha in irrigated field trials.';

        $this->assertFalse($this->measurementSense($plan, $hay));
    }

    private function measurementSense(KnowledgeQueryPlan $plan, string $haystack): bool
    {
        return (bool) $this->classSense->invoke($this->gate, $plan, $haystack);
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

    private function frozenFamilyPlan(string $familyKey, string $propertySurface, string $language = 'fr'): KnowledgeQueryPlan
    {
        $csq = new CanonicalScientificQuestion(
            originalQuestion: $propertySurface.' wheat',
            language: $language,
            normalizedForm: $propertySurface.' wheat',
            researchContext: CanonicalScientificQuestion::RESEARCH_CONTEXT_HOME,
            entity: CanonicalScientificQuestion::entity(
                surface: 'wheat',
                canonicalId: 'wheat',
                resolution: CanonicalScientificQuestion::RESOLUTION_RESOLVED,
            ),
            property: CanonicalScientificQuestion::property(
                key: $familyKey,
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
            language: $language,
            normalizedQuestion: $propertySurface.' wheat',
            agriculturalDomain: 'agronomy',
            subject: ['type' => 'crop', 'value' => 'wheat', 'label' => 'wheat'],
            crop: 'wheat',
            cropId: 'wheat',
            scientificName: 'Triticum aestivum',
            topic: $familyKey,
            subtopic: null,
            requestedInformation: [$familyKey],
            constraints: [
                'question_type' => 'range',
                'requested_property' => $propertySurface,
                'requested_property_surface' => $propertySurface,
                'scientific_factors' => [$familyKey],
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
            topics: [$query->topic],
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
