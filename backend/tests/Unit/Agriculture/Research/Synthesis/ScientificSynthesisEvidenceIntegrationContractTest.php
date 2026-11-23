<?php

namespace Tests\Unit\Agriculture\Research\Synthesis;

use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use App\Services\Agriculture\Research\Search\ScientificEvidenceRelevanceGate;
use App\Services\Agriculture\Research\Search\ScientificStatisticalClaimAligner;
use App\Services\Agriculture\Research\Synthesis\AnswerComposer;
use App\Services\Agriculture\Research\Synthesis\AnswerExpressionAccuracyGate;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use App\Services\Agriculture\Research\Validation\EvidenceConflictAssessmentOutcome;
use App\Services\Agriculture\Research\Validation\EvidenceConflictDetector;
use App\Services\Agriculture\Research\Validation\EvidenceVerificationLayer;
use App\Services\Agriculture\Research\Validation\ScientificEvidenceItem;
use App\Services\Agriculture\ScientificSourceValidator;
use Mockery;
use Tests\Feature\Phase5UnitATestFixtures;
use Tests\TestCase;

/**
 * Mission contract: contextual differences ≠ genuine conflict; synthesis packet present.
 */
class ScientificSynthesisEvidenceIntegrationContractTest extends TestCase
{
    use Phase5UnitATestFixtures;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_composer_exposes_synthesis_packet_and_context_note_for_contextual_differences(): void
    {
        $composer = $this->composer();
        $plan = $this->phase5Plan(requested: ['quantity']);

        $direct = $this->withEvidenceText(
            $this->phase5Evidence(
                'e-direct',
                ClaimEvidenceRelationship::SUPPORTED,
                ScientificEvidenceDirectnessAssessor::DIRECT,
            ),
            'Wheat irrigation water requirement under drip systems was quantified at 450 mm.',
        );
        $contextual = new \App\Services\Agriculture\Research\Validation\ScientificEvidenceItem(
            evidenceId: 'e-ctx',
            sourceId: $direct->sourceId,
            sourceKey: $direct->sourceKey,
            sourceType: $direct->sourceType,
            publicationTitle: $direct->publicationTitle,
            authors: $direct->authors,
            institution: $direct->institution,
            journal: $direct->journal,
            doi: $direct->doi,
            url: $direct->url,
            publicationYear: $direct->publicationYear,
            retrievedAt: $direct->retrievedAt,
            agriculturalDomain: $direct->agriculturalDomain,
            claimTopic: $direct->claimTopic,
            evidenceText: 'Under sprinkler irrigation in humid climates wheat water use differed.',
            validationStatus: $direct->validationStatus,
            validationFailures: [],
            claimRelationship: ClaimEvidenceRelationship::SUPPORTED,
            confidence: 0.7,
            qualityScore: 0.7,
            qualityFactors: [
                'evidence_directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
                'conflict_assessment' => EvidenceConflictAssessmentOutcome::RESOLVED_BY_CONTEXT,
                'contextual_difference' => true,
                'conflict_differing_keys' => ['irrigation_method', 'climate'],
            ],
            sourceAttribution: [
                'evidence_directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
            ],
            hasConflict: false,
            conditions: ['irrigation_method' => 'sprinkler', 'climate' => 'humid'],
            cropOrEntity: 'wheat',
        );

        $report = $composer->compose(
            $plan,
            $this->phase5Validation([$direct, $contextual], true),
        );

        $this->assertArrayHasKey('scientific_synthesis', $report->researchMetadata);
        $packet = $report->researchMetadata['scientific_synthesis'];
        $this->assertSame('synthesis-integrator-v1', $packet['matrix_version'] ?? null);
        $this->assertNotEmpty($packet['contextual_differences'] ?? []);
        $this->assertStringContainsString(
            'experimental conditions',
            mb_strtolower($report->detailedExplanation),
        );
        // Contextual difference must not force synthesis_completed_with_conflicts alone.
        $this->assertNotSame('synthesis_completed_with_conflicts', $report->status);
    }

    public function test_detector_to_composer_path_keeps_context_resolved_support(): void
    {
        $detector = new EvidenceConflictDetector;
        $a = $this->phase5Evidence(
            'e-a',
            ClaimEvidenceRelationship::SUPPORTED,
            ScientificEvidenceDirectnessAssessor::DIRECT,
        );
        $b = $this->phase5Evidence(
            'e-b',
            ClaimEvidenceRelationship::SUPPORTED,
            ScientificEvidenceDirectnessAssessor::DIRECT,
        );
        $a = $this->withEvidenceText(
            $a,
            'Drip irrigation improve increase enhance wheat yield under arid field trials.',
        );
        $b = $this->withEvidenceText(
            $b,
            'Flood irrigation reduce decrease harm wheat yield under humid field trials.',
        );
        $a = new \App\Services\Agriculture\Research\Validation\ScientificEvidenceItem(
            evidenceId: $a->evidenceId,
            sourceId: $a->sourceId,
            sourceKey: $a->sourceKey,
            sourceType: $a->sourceType,
            publicationTitle: $a->publicationTitle,
            authors: $a->authors,
            institution: $a->institution,
            journal: $a->journal,
            doi: $a->doi,
            url: $a->url,
            publicationYear: $a->publicationYear,
            retrievedAt: $a->retrievedAt,
            agriculturalDomain: $a->agriculturalDomain,
            claimTopic: 'irrigation yield',
            evidenceText: $a->evidenceText,
            validationStatus: $a->validationStatus,
            validationFailures: [],
            claimRelationship: ClaimEvidenceRelationship::SUPPORTED,
            confidence: $a->confidence,
            qualityScore: $a->qualityScore,
            qualityFactors: $a->qualityFactors,
            sourceAttribution: $a->sourceAttribution,
            hasConflict: false,
            conditions: ['irrigation_method' => 'drip', 'climate' => 'arid'],
            cropOrEntity: 'wheat',
        );
        $b = new \App\Services\Agriculture\Research\Validation\ScientificEvidenceItem(
            evidenceId: $b->evidenceId,
            sourceId: $b->sourceId,
            sourceKey: $b->sourceKey,
            sourceType: $b->sourceType,
            publicationTitle: $b->publicationTitle,
            authors: $b->authors,
            institution: $b->institution,
            journal: $b->journal,
            doi: $b->doi,
            url: $b->url,
            publicationYear: $b->publicationYear,
            retrievedAt: $b->retrievedAt,
            agriculturalDomain: $b->agriculturalDomain,
            claimTopic: 'irrigation yield',
            evidenceText: $b->evidenceText,
            validationStatus: $b->validationStatus,
            validationFailures: [],
            claimRelationship: ClaimEvidenceRelationship::SUPPORTED,
            confidence: $b->confidence,
            qualityScore: $b->qualityScore,
            qualityFactors: $b->qualityFactors,
            sourceAttribution: $b->sourceAttribution,
            hasConflict: false,
            conditions: ['irrigation_method' => 'flood', 'climate' => 'humid'],
            cropOrEntity: 'wheat',
        );

        $detected = $detector->detect([$a, $b]);
        foreach ($detected as $item) {
            $this->assertFalse($item->hasConflict);
            $this->assertSame(
                EvidenceConflictAssessmentOutcome::RESOLVED_BY_CONTEXT,
                $item->qualityFactors['conflict_assessment'] ?? null,
            );
        }

        $report = $this->composer()->compose(
            $this->phase5Plan(requested: ['quantity']),
            $this->phase5Validation($detected, true),
        );
        $this->assertNotEmpty($report->researchMetadata['scientific_synthesis']['contextual_differences'] ?? []);
        $this->assertSame([], $report->conflicts);
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
