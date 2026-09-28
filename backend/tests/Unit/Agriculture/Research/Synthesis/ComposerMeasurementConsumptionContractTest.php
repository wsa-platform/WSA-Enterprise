<?php

namespace Tests\Unit\Agriculture\Research\Synthesis;

use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\CanonicalScientificQuestion;
use App\Services\Agriculture\Research\CsqContext;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use App\Services\Agriculture\Research\Search\ScientificEvidenceRelevanceGate;
use App\Services\Agriculture\Research\Search\ScientificStatisticalClaimAligner;
use App\Services\Agriculture\Research\Synthesis\AnswerComposer;
use App\Services\Agriculture\Research\Synthesis\AnswerExpressionAccuracyGate;
use App\Services\Agriculture\Research\Synthesis\ScientificUserPresentation;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use App\Services\Agriculture\Research\Validation\EvidenceValidationStatus;
use App\Services\Agriculture\Research\Validation\EvidenceVerificationLayer;
use App\Services\Agriculture\Research\Validation\ScientificEvidenceItem;
use App\Services\Agriculture\ScientificSourceValidator;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Composer Unit A: consume frozen CSQ/RS measurement identity via AccuracyGate.
 * Surface wording, question_type, factors, and ranges are not identity.
 */
class ComposerMeasurementConsumptionContractTest extends TestCase
{
    private AnswerComposer $composer;

    private AnswerExpressionAccuracyGate $gate;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gate = new AnswerExpressionAccuracyGate;
        $relevance = Mockery::mock(ScientificEvidenceRelevanceGate::class);
        $relevance->shouldReceive('isRelevant')->andReturn(true);
        $relevance->shouldReceive('assess')->andReturn([
            'species_relation' => 'same_species',
            'entity_matched' => true,
            'topic_matched' => true,
            'sense_coverage' => true,
            'factor_coverage' => 1.0,
        ]);

        $this->composer = new AnswerComposer(
            app(ScientificSourceValidator::class),
            $relevance,
            app(ScientificEvidenceDirectnessAssessor::class),
            app(EvidenceVerificationLayer::class),
            new ScientificStatisticalClaimAligner,
            expressionAccuracyGate: $this->gate,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_frozen_csq_family_is_consumed_not_surface(): void
    {
        $plan = $this->temperaturePlan('fr', 'température optimale', 'barley');
        $surface = (string) $plan->normalizedQuery->constraints['requested_property'];

        $this->assertSame('temperature', $this->gate->frozenMeasurementFamily($plan));
        $this->assertSame('temperature', $this->gate->canonicalMeasurementProperty($surface, $plan));
        $this->assertSame(
            'temperature',
            $this->gate->canonicalMeasurementProperty('optimal temperature', $plan),
        );
        $this->assertNotSame($surface, $this->gate->canonicalMeasurementProperty($surface, $plan));
    }

    public function test_surface_wording_is_not_canonical_identity(): void
    {
        $plan = $this->temperaturePlan('fr', 'température optimale', 'barley');
        $needles = $this->invoke('groundingNeedles', $plan);

        $this->assertContains('temperature', $needles);
        $this->assertContains('température optimale', $needles);
        $this->assertSame('temperature', $this->gate->canonicalMeasurementProperty('température optimale', $plan));
    }

    public function test_no_frozen_identity_does_not_invent_temperature(): void
    {
        $plan = $this->planWithoutFrozenCsq();
        $this->assertNull($plan->normalizedQuery->canonicalQuestion);
        $this->assertSame('', $this->gate->frozenMeasurementFamily($plan));
        $this->assertSame('', $this->gate->canonicalMeasurementProperty('température optimale', $plan));

        $rainfall = 'Seasonal rainfall reached 450 mm in replicated field plots.';
        $this->assertTrue($this->isSynthesizable($this->directItem('e-rain', $rainfall), $plan));
        $this->assertFalse($this->findingUnsupported($rainfall, $plan));
    }

    public function test_celsius_18_is_accepted_for_frozen_temperature(): void
    {
        $plan = $this->temperaturePlan('en', 'temperature', 'barley');
        $text = 'Barley seedlings grew at 18 °C in growth chambers.';

        $this->assertTrue($this->isSynthesizable($this->directItem('e-18c', $text), $plan));
        $this->assertNotEmpty($this->extract($text, $plan));
        $this->assertFalse($this->findingUnsupported($text, $plan));
        $this->assertTrue($this->joinedContains($this->extract($text, $plan), '18'));
    }

    public function test_slash_range_24_14_is_accepted_for_frozen_temperature(): void
    {
        $plan = $this->temperaturePlan('en', 'optimal temperature', 'maize');
        $text = 'Grown at optimum temperatures (day/night, 24/14°C) for maize.';

        $values = $this->extract($text, $plan);
        $this->assertNotEmpty($values);
        $joined = mb_strtolower(implode(' ', $values));
        $this->assertTrue(str_contains($joined, '24') && str_contains($joined, '14'));
        $this->assertFalse($this->findingUnsupported($text, $plan));
    }

    public function test_nbsp_and_narrow_nbsp_celsius_are_accepted(): void
    {
        $plan = $this->temperaturePlan('en', 'temperature', 'barley');
        $nbsp = "Barley was grown at optimal (22\u{00A0}°C) night temperature.";
        $narrow = "Barley was grown at optimal (22\u{202F}°C) night temperature.";

        $this->assertNotEmpty($this->extract($nbsp, $plan));
        $this->assertNotEmpty($this->extract($narrow, $plan));
        $this->assertFalse($this->findingUnsupported($nbsp, $plan));
        $this->assertFalse($this->findingUnsupported($narrow, $plan));
    }

    public function test_rainfall_mm_does_not_satisfy_frozen_temperature(): void
    {
        $plan = $this->temperaturePlan('en', 'temperature', 'barley');
        $text = 'Barley yield increased under 450 mm seasonal rainfall in field trials.';

        $this->assertFalse($this->isSynthesizable($this->directItem('e-mm', $text), $plan));
        $this->assertSame([], $this->extract($text, $plan));
        $this->assertTrue($this->findingUnsupported($text, $plan));
    }

    public function test_yield_t_ha_does_not_satisfy_frozen_temperature(): void
    {
        $plan = $this->temperaturePlan('en', 'temperature', 'barley');
        $text = 'Barley grain yield reached 5 t/ha in irrigated field trials.';

        $this->assertFalse($this->isSynthesizable($this->directItem('e-tha', $text), $plan));
        $this->assertSame([], $this->extract($text, $plan));
        $this->assertTrue($this->findingUnsupported($text, $plan));
    }

    public function test_bare_number_is_not_a_temperature_expression(): void
    {
        $plan = $this->temperaturePlan('en', 'temperature', 'barley');
        $text = 'Barley growth was scored on 18 replicated field plots.';

        $this->assertSame([], $this->extract($text, $plan));
        $this->assertFalse($this->findingUnsupported($text, $plan));
        $this->assertFalse($this->gate->haystackHasMeasurementClassSense($text, 'temperature'));
    }

    public function test_surface_phrase_alone_does_not_bypass_canonical_identity(): void
    {
        $plan = $this->planWithoutFrozenCsq();
        $text = 'A temperature of 18 °C was recorded under controlled chambers.';

        $this->assertSame('', $this->gate->canonicalMeasurementProperty('température optimale', $plan));
        $this->assertTrue($this->isSynthesizable($this->directItem('e-surf', $text), $plan));

        $frozen = $this->temperaturePlan('fr', 'température optimale', 'barley');
        $this->assertSame('temperature', $this->gate->canonicalMeasurementProperty('température optimale', $frozen));
        $this->assertNotEmpty($this->extract($text, $frozen));
    }

    public function test_multilingual_prose_does_not_change_canonical_family(): void
    {
        $fr = $this->temperaturePlan('fr', 'température optimale', 'barley');
        $tr = $this->temperaturePlan('tr', 'optimum sıcaklık', 'barley');
        $ar = $this->temperaturePlan('ar', 'درجة الحرارة', 'barley');
        $text = 'Les plantules ont germé à 18 °C en chambre climatique.';

        foreach ([$fr, $tr, $ar] as $plan) {
            $this->assertSame('temperature', $this->gate->frozenMeasurementFamily($plan));
            $this->assertSame('temperature', $this->gate->canonicalMeasurementProperty(
                (string) $plan->normalizedQuery->constraints['requested_property'],
                $plan,
            ));
            $this->assertNotEmpty($this->extract($text, $plan));
            $this->assertFalse($this->findingUnsupported($text, $plan));
        }
    }

    public function test_heat_substring_in_wheat_is_not_temperature_address(): void
    {
        $plan = $this->temperaturePlan('en', 'temperature', 'wheat');
        $text = 'Wheat plant growth and physiology in field agronomy without thermal regimes.';

        $this->assertFalse($this->isSynthesizable($this->directItem('e-heat', $text), $plan));
        $this->assertNotContains('heat', $this->gate->propertyAddressTerms('temperature'));
        $this->assertFalse($this->gate->haystackAddressesPropertyTerms(
            mb_strtolower($text),
            $this->gate->propertyAddressTerms('temperature'),
        ));
    }

    public function test_presentation_threshold_remains_exactly_0_50(): void
    {
        $this->assertSame(0.50, ScientificUserPresentation::FINAL_ANSWER_CONFIDENCE_THRESHOLD);
    }

    /**
     * @param  list<string>  $values
     */
    private function joinedContains(array $values, string $needle): bool
    {
        return str_contains(mb_strtolower(implode(' ', $values)), mb_strtolower($needle));
    }

    /**
     * @return list<string>
     */
    private function extract(string $text, KnowledgeQueryPlan $plan): array
    {
        return $this->invoke('extractMeasurementAssertions', $text, $plan);
    }

    private function findingUnsupported(string $text, KnowledgeQueryPlan $plan): bool
    {
        return (bool) $this->invoke('findingContainsUnsupportedNumeric', $text, $plan);
    }

    private function isSynthesizable(ScientificEvidenceItem $item, KnowledgeQueryPlan $plan): bool
    {
        return (bool) $this->invoke('isSynthesizable', $item, $plan);
    }

    private function invoke(string $method, mixed ...$arguments): mixed
    {
        $ref = new ReflectionMethod(AnswerComposer::class, $method);
        $ref->setAccessible(true);

        return $ref->invoke($this->composer, ...$arguments);
    }

    private function temperaturePlan(string $language, string $propertySurface, string $cropId): KnowledgeQueryPlan
    {
        return $this->makePlan($language, $propertySurface, $cropId, freezeCanonicalQuestion: true);
    }

    private function planWithoutFrozenCsq(): KnowledgeQueryPlan
    {
        return $this->makePlan('fr', 'température optimale', 'barley', freezeCanonicalQuestion: false);
    }

    private function makePlan(
        string $language,
        string $propertySurface,
        string $cropId,
        bool $freezeCanonicalQuestion,
    ): KnowledgeQueryPlan {
        $csq = $freezeCanonicalQuestion
            ? new CanonicalScientificQuestion(
                originalQuestion: $propertySurface.' '.$cropId,
                language: $language,
                normalizedForm: $propertySurface.' '.$cropId,
                researchContext: CanonicalScientificQuestion::RESEARCH_CONTEXT_HOME,
                entity: CanonicalScientificQuestion::entity(
                    surface: $cropId,
                    canonicalId: $cropId,
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
                    requestedInformation: ['temperature'],
                ),
            )
            : null;
        $query = new AgriculturalKnowledgeQuery(
            originalQuestion: $propertySurface.' '.$cropId,
            normalizedQuestion: $propertySurface.' '.$cropId,
            language: $language,
            agriculturalDomain: 'agronomy',
            subject: ['type' => 'crop', 'value' => $cropId, 'label' => $cropId],
            crop: $cropId,
            cropId: $cropId,
            scientificName: null,
            topic: 'temperature',
            subtopic: null,
            requestedInformation: ['temperature'],
            constraints: [
                'answer_language' => $language,
                'requested_property' => $propertySurface,
                'requested_property_surface' => $propertySurface,
                'requested_property_query_terms' => [$propertySurface],
                'scientific_factors' => ['temperature'],
                'scientific_sense' => 'plant_growth',
                'scientific_topics' => ['temperature'],
                'scientific_intent_qualifier' => 'optimal_range',
                'question_type' => 'range',
                'entity_dependent' => true,
            ],
            location: null,
            researchRequired: true,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            researchIntent: 'environmental_requirements',
            canonicalQuestion: $csq,
        );

        return new KnowledgeQueryPlan(
            normalizedQuery: $query,
            researchIntent: 'environmental_requirements',
            agriculturalDomain: 'agronomy',
            subjectEntity: $query->subject,
            topics: ['temperature'],
            subtopics: [],
            requestedInformation: ['temperature'],
            evidenceRequirements: ['peer_reviewed'],
            sourcePriorities: ['openalex'],
            primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            readyForStage3: true,
        );
    }

    private function directItem(string $id, string $text): ScientificEvidenceItem
    {
        return new ScientificEvidenceItem(
            evidenceId: $id,
            sourceId: 'source-'.$id,
            sourceKey: 'openalex',
            sourceType: 'university_research',
            publicationTitle: 'Controlled growth chamber study',
            authors: ['Dr Researcher'],
            institution: 'University of Agriculture',
            journal: 'Journal of Agronomy',
            doi: '10.1000/'.$id,
            url: 'https://doi.org/10.1000/'.$id,
            publicationYear: 2023,
            retrievedAt: '2026-09-28T00:00:00+00:00',
            agriculturalDomain: 'field_crops',
            claimTopic: 'temperature',
            evidenceText: $text,
            validationStatus: EvidenceValidationStatus::EVIDENCE_USABLE,
            validationFailures: [],
            claimRelationship: ClaimEvidenceRelationship::SUPPORTED,
            confidence: 0.8,
            qualityScore: 75.0,
            qualityFactors: [
                'not_scientific_certainty' => true,
                'evidence_directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
                'answer_eligible' => false,
                'species_relation' => 'same_species',
                'entity_matched' => true,
                'topic_matched' => true,
            ],
            sourceAttribution: [
                'organization' => 'University of Agriculture',
                'source_type' => 'university_research',
                'evidence_directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
            ],
        );
    }
}
