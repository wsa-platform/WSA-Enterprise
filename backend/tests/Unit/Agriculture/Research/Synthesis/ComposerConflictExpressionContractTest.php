<?php

namespace Tests\Unit\Agriculture\Research\Synthesis;

use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use App\Services\Agriculture\Research\Search\ScientificEvidenceRelevanceGate;
use App\Services\Agriculture\Research\Search\ScientificStatisticalClaimAligner;
use App\Services\Agriculture\Research\Synthesis\AnswerComposer;
use App\Services\Agriculture\Research\Synthesis\AnswerExpressionAccuracyGate;
use App\Services\Agriculture\Research\Synthesis\ScientificUserPresentation;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use App\Services\Agriculture\Research\Validation\EvidenceVerificationLayer;
use App\Services\Agriculture\Research\Validation\ScientificEvidenceItem;
use App\Services\Agriculture\ScientificSourceValidator;
use Mockery;
use ReflectionMethod;
use Tests\Feature\Phase5UnitATestFixtures;
use Tests\TestCase;

/**
 * Composer Unit B: conflict expression + non-conflicting sibling support.
 * Pure conflict stays empty; mixed conflict may express supported sibling prose
 * while preserving CONFLICTING identity and limitation.
 * Does not reopen Unit A measurement consume or Unit C overallConfidence.
 */
class ComposerConflictExpressionContractTest extends TestCase
{
    use Phase5UnitATestFixtures;

    private AnswerComposer $composer;

    private ReflectionMethod $traceHasNonConflictingSupport;

    protected function setUp(): void
    {
        parent::setUp();
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
            expressionAccuracyGate: new AnswerExpressionAccuracyGate,
        );
        $this->traceHasNonConflictingSupport = new ReflectionMethod(
            AnswerComposer::class,
            'traceHasNonConflictingSupport',
        );
        $this->traceHasNonConflictingSupport->setAccessible(true);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_pure_conflict_keeps_empty_prose_and_conflict_axis(): void
    {
        $plan = $this->phase5Plan(requested: ['quantity']);
        $conflict = $this->withEvidenceText(
            $this->phase5Evidence(
                'e-conflict',
                ClaimEvidenceRelationship::CONFLICTING,
                ScientificEvidenceDirectnessAssessor::DIRECT,
                conflict: true,
            ),
            'Wheat production quantity was 9 million tonnes according to source A.',
        );

        $report = $this->composer->compose($plan, $this->phase5Validation([$conflict], true));

        $this->assertSame(ClaimEvidenceRelationship::CONFLICTING, $report->claims[0]->claimRelationship);
        $this->assertSame('', trim($report->claims[0]->claimText));
        $this->assertContains('conflicting_evidence_for_question_claim', $report->claims[0]->limitations);
        $this->assertNotSame(ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE, $report->claims[0]->claimRelationship);
    }

    public function test_mixed_conflict_expresses_supported_sibling_and_keeps_conflict_label(): void
    {
        $plan = $this->phase5Plan(requested: ['quantity']);
        $supported = $this->withEvidenceText(
            $this->phase5Evidence(
                'e-s',
                ClaimEvidenceRelationship::SUPPORTED,
                ScientificEvidenceDirectnessAssessor::DIRECT,
            ),
            'Wheat production quantity in Egypt reached 9 million tonnes.',
        );
        $conflict = $this->withEvidenceText(
            $this->phase5Evidence(
                'e-c',
                ClaimEvidenceRelationship::CONFLICTING,
                ScientificEvidenceDirectnessAssessor::DIRECT,
                conflict: true,
            ),
            'Wheat production quantity was 4 million tonnes according to a conflicting source.',
        );

        $report = $this->composer->compose(
            $plan,
            $this->phase5Validation([$supported, $conflict], true),
        );

        $claim = $report->claims[0];
        $this->assertSame(ClaimEvidenceRelationship::CONFLICTING, $claim->claimRelationship);
        $this->assertNotSame('', trim($claim->claimText));
        $this->assertContains('conflicting_evidence_for_question_claim', $claim->limitations);
        $this->assertNotSame(ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE, $claim->claimRelationship);
        $this->assertNotSame(ClaimEvidenceRelationship::SUPPORTED, $claim->claimRelationship);
    }

    public function test_conflicting_evidence_is_never_chosen_as_factual_prose(): void
    {
        $plan = $this->phase5Plan(requested: ['quantity']);
        // Conflict listed first so ordering alone cannot explain skipping it.
        $conflict = $this->withEvidenceText(
            $this->phase5Evidence(
                'e-c-first',
                ClaimEvidenceRelationship::CONFLICTING,
                ScientificEvidenceDirectnessAssessor::DIRECT,
                conflict: true,
            ),
            'Wheat production quantity was 4 million tonnes according to a conflicting source.',
        );
        $supported = $this->withEvidenceText(
            $this->phase5Evidence(
                'e-s-second',
                ClaimEvidenceRelationship::SUPPORTED,
                ScientificEvidenceDirectnessAssessor::DIRECT,
            ),
            'Wheat production quantity in Egypt reached 9 million tonnes.',
        );

        $report = $this->composer->compose(
            $plan,
            $this->phase5Validation([$conflict, $supported], true),
        );

        $joined = mb_strtolower($report->claims[0]->claimText.' '.implode(' ', $report->claims[0]->numericalValues));
        $this->assertSame(ClaimEvidenceRelationship::CONFLICTING, $report->claims[0]->claimRelationship);
        $this->assertNotSame('', trim($report->claims[0]->claimText));
        $this->assertStringContainsString('9', $joined);
        $this->assertStringNotContainsString('4 million', $joined);
    }

    public function test_partially_supported_sibling_counts_as_non_conflicting_support(): void
    {
        $plan = $this->phase5Plan(requested: ['quantity']);
        $partial = $this->withEvidenceText(
            $this->phase5Evidence(
                'e-partial',
                ClaimEvidenceRelationship::PARTIALLY_SUPPORTED,
                ScientificEvidenceDirectnessAssessor::DIRECT,
            ),
            'Wheat production quantity in Egypt reached about 9 million tonnes under partial reporting.',
        );
        $conflict = $this->withEvidenceText(
            $this->phase5Evidence(
                'e-c',
                ClaimEvidenceRelationship::CONFLICTING,
                ScientificEvidenceDirectnessAssessor::DIRECT,
                conflict: true,
            ),
            'Wheat production quantity was 4 million tonnes according to a conflicting source.',
        );

        $itemsById = [
            $partial->evidenceId => $partial,
            $conflict->evidenceId => $conflict,
        ];
        $this->assertTrue($this->hasNonConflictingSupport(
            [$partial->evidenceId, $conflict->evidenceId],
            $itemsById,
        ));

        $report = $this->composer->compose(
            $plan,
            $this->phase5Validation([$partial, $conflict], true),
        );

        $claim = $report->claims[0];
        $this->assertSame(ClaimEvidenceRelationship::CONFLICTING, $claim->claimRelationship);
        $this->assertNotSame('', trim($claim->claimText));
        $this->assertContains('conflicting_evidence_for_question_claim', $claim->limitations);
        $this->assertContains('partial_evidence_support', $claim->limitations);
    }

    public function test_empty_snippet_on_mixed_conflict_does_not_collapse_to_insufficient(): void
    {
        $plan = $this->phase5Plan(requested: ['quantity']);
        // Sibling relationship qualifies support, but empty text yields no prose.
        $supportedEmpty = $this->withEvidenceText(
            $this->phase5Evidence(
                'e-empty',
                ClaimEvidenceRelationship::SUPPORTED,
                ScientificEvidenceDirectnessAssessor::DIRECT,
            ),
            '   ',
        );
        $conflict = $this->withEvidenceText(
            $this->phase5Evidence(
                'e-c',
                ClaimEvidenceRelationship::CONFLICTING,
                ScientificEvidenceDirectnessAssessor::DIRECT,
                conflict: true,
            ),
            'Wheat production quantity was 4 million tonnes according to a conflicting source.',
        );

        $report = $this->composer->compose(
            $plan,
            $this->phase5Validation([$supportedEmpty, $conflict], true),
        );

        $claim = $report->claims[0];
        $this->assertSame(ClaimEvidenceRelationship::CONFLICTING, $claim->claimRelationship);
        $this->assertSame('', trim($claim->claimText));
        $this->assertContains('conflicting_evidence_for_question_claim', $claim->limitations);
        $this->assertNotContains('insufficient_validated_evidence_for_question_claim', $claim->limitations);
        $this->assertNotSame(ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE, $claim->claimRelationship);
    }

    public function test_helper_rejects_only_conflicting_and_accepts_supported_siblings(): void
    {
        $supported = $this->phase5Evidence('e-s', ClaimEvidenceRelationship::SUPPORTED);
        $partial = $this->phase5Evidence('e-p', ClaimEvidenceRelationship::PARTIALLY_SUPPORTED);
        $conflict = $this->phase5Evidence(
            'e-c',
            ClaimEvidenceRelationship::CONFLICTING,
            conflict: true,
        );

        $this->assertFalse($this->hasNonConflictingSupport(
            [$conflict->evidenceId],
            [$conflict->evidenceId => $conflict],
        ));
        $this->assertTrue($this->hasNonConflictingSupport(
            [$supported->evidenceId, $conflict->evidenceId],
            [
                $supported->evidenceId => $supported,
                $conflict->evidenceId => $conflict,
            ],
        ));
        $this->assertTrue($this->hasNonConflictingSupport(
            [$partial->evidenceId, $conflict->evidenceId],
            [
                $partial->evidenceId => $partial,
                $conflict->evidenceId => $conflict,
            ],
        ));
        $this->assertFalse($this->hasNonConflictingSupport(
            ['missing-id', $conflict->evidenceId],
            [$conflict->evidenceId => $conflict],
        ));
    }

    public function test_nonempty_conflict_labeled_claim_contributes_to_closed_confidence_input(): void
    {
        $plan = $this->phase5Plan(requested: ['quantity']);
        $supported = $this->withEvidenceText(
            $this->phase5Evidence(
                'e-s',
                ClaimEvidenceRelationship::SUPPORTED,
                ScientificEvidenceDirectnessAssessor::DIRECT,
            ),
            'Wheat production quantity in Egypt reached 9 million tonnes.',
        );
        $conflict = $this->withEvidenceText(
            $this->phase5Evidence(
                'e-c',
                ClaimEvidenceRelationship::CONFLICTING,
                ScientificEvidenceDirectnessAssessor::DIRECT,
                conflict: true,
            ),
            'Wheat production quantity was 4 million tonnes according to a conflicting source.',
        );

        $report = $this->composer->compose(
            $plan,
            $this->phase5Validation([$supported, $conflict], true),
        );

        $this->assertSame(ClaimEvidenceRelationship::CONFLICTING, $report->claims[0]->claimRelationship);
        $this->assertNotSame('', trim($report->claims[0]->claimText));
        // Unit C (closed): nonempty claimText enters denominator — do not change overallConfidence.
        $this->assertGreaterThan(0.0, $report->confidence);
    }

    public function test_final_answer_confidence_threshold_remains_exactly_0_50(): void
    {
        $this->assertSame(0.50, ScientificUserPresentation::FINAL_ANSWER_CONFIDENCE_THRESHOLD);
    }

    /**
     * @param  list<string>  $evidenceIds
     * @param  array<string, ScientificEvidenceItem>  $itemsById
     */
    private function hasNonConflictingSupport(array $evidenceIds, array $itemsById): bool
    {
        return (bool) $this->traceHasNonConflictingSupport->invoke(
            $this->composer,
            $evidenceIds,
            $itemsById,
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
