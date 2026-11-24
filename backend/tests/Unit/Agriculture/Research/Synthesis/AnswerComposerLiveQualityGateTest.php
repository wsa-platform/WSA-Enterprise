<?php

namespace Tests\Unit\Agriculture\Research\Synthesis;

use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use App\Services\Agriculture\Research\Search\ScientificEvidenceRelevanceGate;
use App\Services\Agriculture\Research\Search\ScientificStatisticalClaimAligner;
use App\Services\Agriculture\Research\Synthesis\AnswerComposer;
use App\Services\Agriculture\Research\Synthesis\AnswerExpressionAccuracyGate;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use App\Services\Agriculture\Research\Validation\EvidenceVerificationLayer;
use App\Services\Agriculture\ScientificSourceValidator;
use Mockery;
use ReflectionMethod;
use Tests\Feature\Phase5UnitATestFixtures;
use Tests\TestCase;

/**
 * Live-quality gates: reject chrome findings, require deficit-act match, block exotic domains.
 */
class AnswerComposerLiveQualityGateTest extends TestCase
{
    use Phase5UnitATestFixtures;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_low_quality_fragments_are_rejected(): void
    {
        $method = new ReflectionMethod(AnswerComposer::class, 'isLowQualityFindingFragment');
        $method->setAccessible(true);
        $composer = $this->composer();

        $this->assertTrue($method->invoke($composer, 'However,'));
        $this->assertTrue($method->invoke($composer, 'Understanding the effects of water stress on wheat growth is essential for good irrigation management.'));
        $this->assertTrue($method->invoke($composer, 'The aim of this study was to evaluate irrigation.'));
        $this->assertFalse($method->invoke(
            $composer,
            'Deficit irrigation reduced wheat grain yield by 18% compared with full irrigation in a two-year field trial.',
        ));
    }

    public function test_deficit_question_rejects_wastewater_only_finding(): void
    {
        $method = new ReflectionMethod(AnswerComposer::class, 'findingAddressesAskedAct');
        $method->setAccessible(true);
        $composer = $this->composer();
        $plan = $this->phase5Plan(
            question: 'What are the effects of deficit irrigation on wheat yield?',
            requested: ['quantity'],
        );

        $this->assertFalse($method->invoke(
            $composer,
            'Raw wastewater irrigation substantially reduced fertilizer use for wheat biomass production.',
            $plan,
        ));
        $this->assertTrue($method->invoke(
            $composer,
            'Deficit irrigation reduced wheat grain yield by 18% compared with full irrigation.',
            $plan,
        ));
    }

    public function test_exotic_domain_without_matching_evidence_is_insufficient(): void
    {
        $composer = $this->composer();
        $plan = $this->phase5Plan(
            question: 'What is the exact molecular mechanism of deficit irrigation in Martian soil?',
            requested: ['quantity'],
        );
        $evidence = $this->withEvidenceText(
            $this->phase5Evidence(
                'e-earth',
                ClaimEvidenceRelationship::SUPPORTED,
                ScientificEvidenceDirectnessAssessor::DIRECT,
            ),
            'Deficit irrigation reduced wheat grain yield by 12% under arid field conditions in Iraq.',
        );

        $report = $composer->compose($plan, $this->phase5Validation([$evidence], true));

        $this->assertSame('insufficient_evidence', $report->status);
        $this->assertStringContainsStringIgnoringCase('insufficient', $report->answer);
    }

    public function test_cwr_question_rejects_treatment_inventory_only_finding(): void
    {
        $method = new ReflectionMethod(AnswerComposer::class, 'findingAddressesAskedAct');
        $method->setAccessible(true);
        $composer = $this->composer();
        $plan = $this->phase5Plan(
            question: 'Buğday sulama suyu ihtiyacı nedir?',
            requested: ['quantity'],
            constraints: [
                'answer_language' => 'tr',
                'scientific_sense' => 'crop_water_requirement',
            ],
            language: 'tr',
            intent: 'irrigation',
        );

        $this->assertFalse($method->invoke(
            $composer,
            'The irrigation treatments included 50% MAD (DI), 55% MAD (DI), 60% MAD (DI), and local farmer\'s field replication (FI).',
            $plan,
        ));
        $this->assertTrue($method->invoke(
            $composer,
            'Seasonal crop water requirement of wheat was 412 mm under full irrigation, with ETc peaking at flowering.',
            $plan,
        ));
    }

    public function test_preferred_sentence_favors_quantitative_cwr_over_treatment_list(): void
    {
        $method = new ReflectionMethod(AnswerComposer::class, 'preferredFindingSentence');
        $method->setAccessible(true);
        $composer = $this->composer();
        $plan = $this->phase5Plan(
            question: 'Quel est le besoin en eau du blé?',
            requested: ['quantity'],
            constraints: [
                'answer_language' => 'fr',
                'scientific_sense' => 'crop_water_requirement',
            ],
            language: 'fr',
            intent: 'irrigation',
        );

        $text = 'The irrigation treatments included 50% MAD (DI) and well-watered treatment (FI). '
            .'Seasonal crop water requirement of wheat averaged 450 mm with peak ETc at heading.';

        $chosen = $method->invoke($composer, $text, $plan);
        $this->assertStringContainsString('450 mm', $chosen);
        $this->assertStringNotContainsString('treatments included', mb_strtolower($chosen));
    }

    public function test_cwr_range_answer_rejects_yield_kg_as_water_requirement(): void
    {
        $method = new ReflectionMethod(AnswerComposer::class, 'extractMeasurementAssertions');
        $method->setAccessible(true);
        $composer = $this->composer();
        $plan = $this->phase5Plan(
            question: 'Quel est le besoin en eau du blé?',
            requested: ['quantity'],
            constraints: [
                'answer_language' => 'fr',
                'scientific_sense' => 'crop_water_requirement',
            ],
            language: 'fr',
            intent: 'irrigation',
        );

        $yieldOnly = $method->invoke(
            $composer,
            'The highest grain yield (5,085 kg ha−1) was obtained from 100% ETc irrigation application.',
            $plan,
        );
        $this->assertSame([], $yieldOnly);

        $water = $method->invoke(
            $composer,
            'Seasonal crop water requirement of wheat was 412 mm under full irrigation.',
            $plan,
        );
        $this->assertNotEmpty($water);
        $this->assertTrue(
            (bool) preg_match('/412\s*mm/iu', implode(' ', $water)),
            'Expected water-depth measurement, got: '.implode(', ', $water),
        );
    }

    public function test_cwr_motivation_sentence_is_low_quality_fragment(): void
    {
        $method = new ReflectionMethod(AnswerComposer::class, 'isLowQualityFindingFragment');
        $method->setAccessible(true);
        $composer = $this->composer();

        $this->assertTrue($method->invoke(
            $composer,
            'Efficient water management of crop requires accurate irrigation scheduling which, in turn, requires the accurate measurement of crop water requirement.',
        ));
    }

    private function composer(): AnswerComposer
    {
        $relevance = Mockery::mock(ScientificEvidenceRelevanceGate::class);
        $relevance->shouldReceive('isRelevant')->andReturn(true);
        $relevance->shouldReceive('assess')->andReturn([
            'species_relation' => 'same_species',
            'entity_matched' => true,
            'topic_matched' => true,
            'sense_coverage' => true,
            'factor_coverage' => 1.0,
        ]);

        return new AnswerComposer(
            app(ScientificSourceValidator::class),
            $relevance,
            app(ScientificEvidenceDirectnessAssessor::class),
            app(EvidenceVerificationLayer::class),
            new ScientificStatisticalClaimAligner,
            expressionAccuracyGate: new AnswerExpressionAccuracyGate,
        );
    }

    private function withEvidenceText(
        \App\Services\Agriculture\Research\Validation\ScientificEvidenceItem $item,
        string $text,
    ): \App\Services\Agriculture\Research\Validation\ScientificEvidenceItem {
        return new \App\Services\Agriculture\Research\Validation\ScientificEvidenceItem(
            evidenceId: $item->evidenceId,
            sourceId: $item->sourceId,
            sourceKey: $item->sourceKey,
            sourceType: $item->sourceType,
            publicationTitle: $item->publicationTitle,
            authors: $item->authors,
            institution: $item->institution,
            journal: $item->journal,
            doi: $item->doi,
            url: $item->url,
            publicationYear: $item->publicationYear,
            retrievedAt: $item->retrievedAt,
            agriculturalDomain: $item->agriculturalDomain,
            claimTopic: $item->claimTopic,
            evidenceText: $text,
            validationStatus: $item->validationStatus,
            validationFailures: $item->validationFailures,
            claimRelationship: $item->claimRelationship,
            confidence: $item->confidence,
            qualityScore: $item->qualityScore,
            qualityFactors: $item->qualityFactors,
            sourceAttribution: $item->sourceAttribution,
            hasConflict: $item->hasConflict,
            conditions: $item->conditions,
            cropOrEntity: $item->cropOrEntity,
        );
    }
}
