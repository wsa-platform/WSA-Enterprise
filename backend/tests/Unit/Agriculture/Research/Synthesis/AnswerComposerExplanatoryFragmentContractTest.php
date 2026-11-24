<?php

namespace Tests\Unit\Agriculture\Research\Synthesis;

use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use App\Services\Agriculture\Research\Search\ScientificEvidenceRelevanceGate;
use App\Services\Agriculture\Research\Search\ScientificStatisticalClaimAligner;
use App\Services\Agriculture\Research\Synthesis\AnswerComposer;
use App\Services\Agriculture\Research\Synthesis\AnswerExpressionAccuracyGate;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use App\Services\Agriculture\Research\Validation\EvidenceVerificationLayer;
use App\Services\Agriculture\Research\Validation\ScientificEvidenceItem;
use App\Services\Agriculture\ScientificSourceValidator;
use Mockery;
use ReflectionMethod;
use Tests\Feature\Phase5UnitATestFixtures;
use Tests\TestCase;

/**
 * Live answer quality: explanatory mode must not comma-split abstracts into "However".
 */
class AnswerComposerExplanatoryFragmentContractTest extends TestCase
{
    use Phase5UnitATestFixtures;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_fact_items_keep_scientific_prose_and_reject_however_fragments(): void
    {
        $composer = $this->composer();
        $method = new ReflectionMethod(AnswerComposer::class, 'factItemsFromFindings');
        $method->setAccessible(true);

        $items = $method->invoke(
            $composer,
            [
                'However, its evaluation across different irrigation methods showed deficit irrigation reduced wheat yield under arid conditions.',
                'Thus',
                'its stage-specific response',
            ],
            'en',
        );

        $joined = mb_strtolower(implode(' || ', $items));
        $this->assertNotEmpty($items);
        $this->assertFalse(in_array('However', $items, true));
        $this->assertFalse(in_array('Thus', $items, true));
        $this->assertFalse(in_array('its stage-specific response', $items, true));
        $this->assertTrue(
            str_contains($joined, 'deficit irrigation')
            || str_contains($joined, 'wheat yield')
            || str_contains($joined, 'irrigation methods'),
            $joined,
        );
    }

    public function test_rejects_meta_gap_attributed_excerpt_and_prefers_empirical_sentence(): void
    {
        $composer = $this->composer();
        $factItems = new ReflectionMethod(AnswerComposer::class, 'factItemsFromFindings');
        $factItems->setAccessible(true);
        $preferred = new ReflectionMethod(AnswerComposer::class, 'preferredFindingSentence');
        $preferred->setAccessible(true);

        $metaAr = 'مقتطف من المصدر (باللغة الأصلية): However, its evaluation across different irrigation methods and its stage‐specific response to crop yield is rarely evaluated.';
        $items = $factItems->invoke($composer, [$metaAr], 'ar');
        $this->assertSame([], $items);

        $abstract = 'However, its evaluation across different irrigation methods and its stage-specific response to crop yield is rarely evaluated. Deficit irrigation reduced wheat grain yield by 18% under arid field conditions compared with full irrigation.';
        $chosen = $preferred->invoke($composer, $abstract);
        $this->assertStringContainsString('Deficit irrigation reduced wheat grain yield', $chosen);
        $this->assertStringNotContainsString('rarely evaluated', $chosen);
    }

    public function test_causal_deficit_question_uses_generic_answer_heading_not_water_requirement(): void
    {
        $composer = $this->composer();
        $plan = $this->phase5Plan(
            question: 'ما تأثير الري الناقص على إنتاجية القمح؟',
            requested: ['irrigation'],
            constraints: [
                'answer_language' => 'ar',
                'scientific_sense' => 'crop_water_requirement',
                'question_type' => 'causes',
            ],
            intent: 'irrigation',
        );

        $evidence = $this->withEvidenceText(
            $this->phase5Evidence(
                'e1',
                ClaimEvidenceRelationship::SUPPORTED,
                ScientificEvidenceDirectnessAssessor::DIRECT,
            ),
            'However, its evaluation across different irrigation methods and its stage-specific response to crop yield is rarely evaluated. Deficit irrigation reduced wheat grain yield under arid conditions compared with full irrigation.',
        );

        $report = $composer->compose($plan, $this->phase5Validation([$evidence], true));
        $answer = $report->answer;

        $this->assertStringNotContainsString('الاحتياج المائي', $answer);
        $this->assertStringNotContainsString('rarely evaluated', mb_strtolower($answer));
        $this->assertStringNotContainsString('أدلة علمية محدودة أو متعارضة', $answer);
        $this->assertTrue(
            str_contains(mb_strtolower($answer), 'deficit irrigation')
            || str_contains(mb_strtolower($answer), 'wheat')
            || str_contains($answer, 'إنتاج'),
            $answer,
        );
    }

    public function test_explanatory_compose_avoids_chrome_only_however_bullets(): void
    {
        $composer = $this->composer();
        $plan = $this->phase5Plan(
            question: 'What are the effects of deficit irrigation on wheat yield?',
            requested: ['irrigation'],
            constraints: [
                'answer_language' => 'en',
                'scientific_sense' => 'crop_water_requirement',
                'question_type' => 'causes',
            ],
            intent: 'irrigation',
        );

        $evidence = $this->withEvidenceText(
            $this->phase5Evidence(
                'e1',
                ClaimEvidenceRelationship::SUPPORTED,
                ScientificEvidenceDirectnessAssessor::DIRECT,
            ),
            'However, its evaluation across different irrigation methods showed that deficit irrigation reduced wheat yield under arid field conditions while full irrigation increased biomass.',
        );

        $report = $composer->compose($plan, $this->phase5Validation([$evidence], true));
        $answer = mb_strtolower($report->answer);

        $this->assertStringNotContainsString("1. **however**", $answer);
        $this->assertStringNotContainsString("1. **thus**", $answer);
        $this->assertTrue(
            str_contains($answer, 'deficit irrigation')
            || str_contains($answer, 'wheat yield')
            || str_contains($answer, 'irrigation'),
            $report->answer,
        );
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

    private function withEvidenceText(ScientificEvidenceItem $item, string $text): ScientificEvidenceItem
    {
        return new ScientificEvidenceItem(
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
