<?php

namespace Tests\Unit\Agriculture\Research\Synthesis;

use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use App\Services\Agriculture\Research\Search\ScientificEvidenceRelevanceGate;
use App\Services\Agriculture\Research\Search\ScientificStatisticalClaimAligner;
use App\Services\Agriculture\Research\Synthesis\AnswerComposer;
use App\Services\Agriculture\Research\Synthesis\AnswerExpressionAccuracyGate;
use App\Services\Agriculture\Research\Synthesis\ResearchAnswerClaim;
use App\Services\Agriculture\Research\Synthesis\ScientificUserPresentation;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use App\Services\Agriculture\Research\Validation\EvidenceValidationExecutionReport;
use App\Services\Agriculture\Research\Validation\EvidenceVerificationLayer;
use App\Services\Agriculture\ScientificSourceValidator;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Composer Unit C: overallConfidence denominator is nonempty claimText only.
 * Empty / unanswered claim rows must not occupy the denominator.
 * Does not require Unit B conflict-expression behavior.
 */
class ComposerConfidenceDenominatorContractTest extends TestCase
{
    private AnswerComposer $composer;

    private ReflectionMethod $overallConfidence;

    private EvidenceValidationExecutionReport $validationReport;

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
        $this->overallConfidence = new ReflectionMethod(AnswerComposer::class, 'overallConfidence');
        $this->overallConfidence->setAccessible(true);
        $this->validationReport = new EvidenceValidationExecutionReport(
            status: 'validation_completed',
            validatedEvidence: [],
            rejectedEvidence: [],
            sourcesReceived: 0,
            validatedCount: 0,
            rejectedCount: 0,
            duplicateCount: 0,
            conflictingCount: 0,
            evidenceSufficient: true,
            validatorsUsed: [],
            qualityDistribution: [],
            searchSummary: [],
            observability: [],
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_single_nonempty_claim_confidence_equals_claim_confidence(): void
    {
        $claims = [
            $this->claim('c1', 'Supported quantity reached 7.2 million tonnes.', 0.82),
        ];

        $this->assertSame(0.82, $this->confidence($claims, ['direct_count' => 1]));
    }

    public function test_empty_sibling_claim_does_not_drag_denominator(): void
    {
        $claims = [
            $this->claim('c1', 'Supported quantity reached 7.2 million tonnes.', 0.82),
            $this->claim('c2', '', 0.0),
        ];

        $this->assertSame(0.82, $this->confidence($claims, ['direct_count' => 1]));
    }

    public function test_all_empty_claim_text_returns_zero(): void
    {
        $claims = [
            $this->claim('c1', '', 0.9),
            $this->claim('c2', '   ', 0.8),
        ];

        $this->assertSame(0.0, $this->confidence($claims, ['direct_count' => 1]));
    }

    public function test_zero_claims_returns_zero(): void
    {
        $this->assertSame(0.0, $this->confidence([], ['direct_count' => 1]));
    }

    public function test_supporting_only_is_capped_at_0_42(): void
    {
        $claims = [
            $this->claim('c1', 'Supporting context notes related quantity trends.', 0.9),
        ];

        $score = $this->confidence($claims, ['direct_count' => 0]);
        $this->assertLessThanOrEqual(0.42, $score);
        $this->assertSame(0.42, $score);
    }

    public function test_confidence_is_capped_at_0_95(): void
    {
        $claims = [
            $this->claim('c1', 'High confidence measurement prose.', 0.99),
            $this->claim('c2', 'Second high confidence measurement prose.', 0.98),
        ];

        $score = $this->confidence($claims, ['direct_count' => 1]);
        $this->assertLessThanOrEqual(0.95, $score);
        $this->assertSame(0.95, $score);
    }

    public function test_final_answer_confidence_threshold_remains_exactly_0_50(): void
    {
        $this->assertSame(0.50, ScientificUserPresentation::FINAL_ANSWER_CONFIDENCE_THRESHOLD);
    }

    public function test_unit_c_does_not_require_unit_b_conflict_prose(): void
    {
        // Independent of Unit B: CONFLICTING with empty prose is simply non-expressible.
        $claims = [
            $this->claim(
                'c-conflict',
                '',
                0.8,
                ClaimEvidenceRelationship::CONFLICTING,
            ),
            $this->claim(
                'c-supported',
                'Supported quantity reached 9 million tonnes.',
                0.8,
                ClaimEvidenceRelationship::SUPPORTED,
            ),
        ];

        $this->assertSame(0.8, $this->confidence($claims, ['direct_count' => 1]));
    }

    /**
     * @param  list<ResearchAnswerClaim>  $claims
     * @param  array<string, mixed>  $sufficiency
     */
    private function confidence(array $claims, array $sufficiency): float
    {
        return (float) $this->overallConfidence->invoke(
            $this->composer,
            $claims,
            $this->validationReport,
            $sufficiency,
        );
    }

    private function claim(
        string $id,
        string $text,
        float $confidence,
        string $relationship = ClaimEvidenceRelationship::SUPPORTED,
    ): ResearchAnswerClaim {
        return new ResearchAnswerClaim(
            claimId: $id,
            claimText: $text,
            evidenceIds: [$id],
            sourceIds: ['src-'.$id],
            validationStatus: 'validated',
            claimRelationship: $relationship,
            confidence: $confidence,
            numericalValues: [],
            limitations: [],
            questionClaimId: 'qc-'.$id,
        );
    }
}
