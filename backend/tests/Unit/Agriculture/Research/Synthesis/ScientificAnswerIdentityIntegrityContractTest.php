<?php

namespace Tests\Unit\Agriculture\Research\Synthesis;

use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use App\Services\Agriculture\Research\Search\ScientificEvidenceRelevanceGate;
use App\Services\Agriculture\Research\Search\ScientificStatisticalClaimAligner;
use App\Services\Agriculture\Research\Synthesis\AnswerComposer;
use App\Services\Agriculture\Research\Synthesis\AnswerExpressionAccuracyGate;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use App\Services\Agriculture\Research\Validation\EvidenceValidationStatus;
use App\Services\Agriculture\Research\Validation\EvidenceVerificationLayer;
use App\Services\Agriculture\Research\Validation\ScientificEvidenceItem;
use App\Services\Agriculture\ScientificSourceValidator;
use Mockery;
use Tests\Feature\Phase5UnitATestFixtures;
use Tests\TestCase;

/**
 * Rev 11.0.8 §11 — Scientific Answer Identity Integrity (EXIT-09 / EXIT-10).
 *
 * Distinct from Answer Traceability and from C1→C3 bridge mirrors.
 * Does not grant SCIENTIFIC_INTEGRATION_VERIFIED.
 */
class ScientificAnswerIdentityIntegrityContractTest extends TestCase
{
    use Phase5UnitATestFixtures;

    private AnswerComposer $composer;

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
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_answer_preserves_original_question_identity_in_metadata(): void
    {
        $question = 'What is wheat production quantity in Egypt?';
        $plan = $this->phase5Plan(question: $question);
        $evidence = $this->identityEvidence(
            'e-align',
            'Wheat production quantity in Egypt reached 9 million tonnes in the reported year.',
        );

        $report = $this->composer->compose($plan, $this->phase5Validation([$evidence], true));

        $this->assertTrue($report->performed);
        $this->assertSame($question, $plan->normalizedQuery->originalQuestion);
        $this->assertSame('wheat', $plan->normalizedQuery->cropId);
        $this->assertSame('Egypt', $plan->normalizedQuery->location);
        $this->assertNotEmpty($report->claims);
        $this->assertNotSame('', trim((string) $report->claims[0]->claimText));
        $joined = mb_strtolower(implode(' ', array_filter([
            (string) $report->answer,
            (string) $report->claims[0]->claimText,
            implode(' ', $report->keyFindings),
        ])));
        $this->assertTrue(
            str_contains($joined, 'wheat') || str_contains($joined, 'egypt') || str_contains($joined, '9'),
            'Answer must remain aligned to wheat/Egypt production identity: '.$joined,
        );
    }

    public function test_different_species_evidence_does_not_become_entity_safe_answer(): void
    {
        $plan = $this->phase5Plan(question: 'What is wheat production quantity in Egypt?');
        $maize = $this->identityEvidence(
            'e-maize',
            'Maize production quantity in Egypt reached 7 million tonnes.',
            quality: [
                'evidence_directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
                'species_relation' => 'different_species',
                'entity_matched' => false,
                'topic_matched' => true,
            ],
            title: 'Maize production statistics Egypt',
            sourceKey: 'openalex',
        );

        $report = $this->composer->compose($plan, $this->phase5Validation([$maize], true));

        $this->assertTrue(
            in_array($report->status, [
                'insufficient_evidence',
                'no_validated_evidence',
                'synthesis_completed_with_conflicts',
                'synthesis_completed_with_partial_conflicts',
            ], true)
            || $report->confidence < 0.5
            || $report->claims === []
            || trim((string) ($report->claims[0]->claimText ?? '')) === '',
            'Wrong-entity maize evidence must not yield a confident wheat answer; status='.$report->status
                .' confidence='.$report->confidence,
        );
        $answer = mb_strtolower((string) $report->answer);
        if ($answer !== '' && ! str_contains($answer, 'insufficient')) {
            $this->assertStringNotContainsString('7 million', $answer);
        }
    }

    public function test_geographic_mismatch_evidence_is_not_synthesizable_as_direct_answer(): void
    {
        $plan = $this->phase5Plan(question: 'What is wheat production quantity in Egypt?', location: 'Egypt');
        $brazil = $this->identityEvidence(
            'e-br',
            'Wheat production quantity in Brazil reached 5 million tonnes.',
            quality: [
                'evidence_directness' => ScientificEvidenceDirectnessAssessor::GEOGRAPHIC_MISMATCH,
            ],
            title: 'Brazil wheat production',
        );

        $report = $this->composer->compose($plan, $this->phase5Validation([$brazil], true));

        $this->assertContains($report->status, [
            'insufficient_evidence',
            'no_validated_evidence',
        ]);
        $this->assertSame([], $report->citations);
    }

    public function test_missing_evidence_does_not_fabricate_scientific_claims(): void
    {
        $plan = $this->phase5Plan();
        $report = $this->composer->compose($plan, $this->phase5Validation([], false));

        $this->assertFalse($report->performed === true && $report->claims !== [] && $report->confidence >= 0.7);
        $this->assertContains($report->status, [
            'no_valid_evidence',
            'insufficient_evidence',
            'no_validated_evidence',
            'no_search_results',
        ]);
        $this->assertSame([], $report->citations);
        foreach ($report->claims as $claim) {
            $this->assertNotSame(ClaimEvidenceRelationship::SUPPORTED, $claim->claimRelationship);
        }
    }

    public function test_supporting_only_evidence_does_not_become_direct_confident_answer(): void
    {
        $plan = $this->phase5Plan();
        $supporting = $this->identityEvidence(
            'e-sup',
            'Background review mentions wheat production systems without a measured Egypt quantity.',
            quality: [
                'evidence_directness' => ScientificEvidenceDirectnessAssessor::SUPPORTING,
            ],
            relationship: ClaimEvidenceRelationship::PARTIALLY_SUPPORTED,
        );

        $report = $this->composer->compose($plan, $this->phase5Validation([$supporting], true));

        $this->assertTrue(
            in_array($report->status, ['insufficient_evidence', 'no_validated_evidence'], true)
            || ($report->observability['sufficiency']['mode'] ?? null) === 'insufficient_direct_evidence'
            || $report->confidence < 0.55,
            'SUPPORTING-only must not present as a high-confidence DIRECT answer; status='.$report->status,
        );
        $this->assertSame([], $report->citations);
    }

    public function test_conflict_preservation_keeps_conflicting_relationship(): void
    {
        $plan = $this->phase5Plan();
        $supported = $this->identityEvidence(
            'e-s',
            'Wheat production quantity in Egypt reached 9 million tonnes.',
        );
        $conflict = $this->identityEvidence(
            'e-c',
            'Wheat production quantity in Egypt was 4 million tonnes according to a conflicting source.',
            relationship: ClaimEvidenceRelationship::CONFLICTING,
            conflict: true,
        );

        $report = $this->composer->compose($plan, $this->phase5Validation([$supported, $conflict], true));

        $this->assertNotEmpty($report->claims);
        $this->assertSame(ClaimEvidenceRelationship::CONFLICTING, $report->claims[0]->claimRelationship);
        $this->assertContains('conflicting_evidence_for_question_claim', $report->claims[0]->limitations);
        $this->assertNotSame(ClaimEvidenceRelationship::SUPPORTED, $report->claims[0]->claimRelationship);
    }

    public function test_quantitative_values_survive_into_claim_text_when_supported(): void
    {
        $plan = $this->phase5Plan();
        $evidence = $this->identityEvidence(
            'e-qty',
            'Wheat production quantity in Egypt reached 9.2 million tonnes under irrigated systems.',
        );

        $report = $this->composer->compose($plan, $this->phase5Validation([$evidence], true));

        $this->assertNotEmpty($report->claims);
        $text = (string) $report->claims[0]->claimText;
        $this->assertNotSame('', trim($text));
        $this->assertTrue(
            str_contains($text, '9.2') || str_contains($text, '9') || str_contains(mb_strtolower($text), 'tonne'),
            'Quantitative payload must survive synthesis: '.$text,
        );
        $this->assertStringNotContainsString('causally proves', mb_strtolower($text));
    }

    public function test_citations_preserve_source_identity_and_evidence_links(): void
    {
        $plan = $this->phase5Plan();
        $evidence = $this->identityEvidence(
            'e-cite',
            'Wheat production quantity in Egypt reached 9 million tonnes.',
            sourceKey: 'agris',
        );

        $report = $this->composer->compose($plan, $this->phase5Validation([$evidence], true));

        $this->assertNotEmpty($report->citations, 'DIRECT usable evidence must produce citations');
        $this->assertSame('agris', $report->citations[0]->sourceKey);
        $this->assertSame('src-e-cite', $report->citations[0]->sourceId);
        $this->assertSame('e-cite', $report->citations[0]->evidenceId);
        $this->assertContains('agris', $report->citations[0]->contributingProviders);
        $this->assertNotEmpty($report->claims[0]->evidenceIds ?? $report->evidenceReferences);
    }

    public function test_uncertainty_surface_present_when_confidence_is_bounded(): void
    {
        $plan = $this->phase5Plan();
        $partial = $this->identityEvidence(
            'e-unc',
            'Wheat production quantity in Egypt may have been near 8 million tonnes in limited surveys.',
            relationship: ClaimEvidenceRelationship::PARTIALLY_SUPPORTED,
            quality: [
                'evidence_directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
            ],
            confidence: 0.45,
        );

        $report = $this->composer->compose($plan, $this->phase5Validation([$partial], true));

        $this->assertTrue(
            $report->uncertainty !== null
            || $report->limitations !== []
            || $report->confidence < 0.95
            || in_array($report->status, ['insufficient_evidence', 'synthesis_completed_with_partial_conflicts'], true),
            'Bounded evidence must not erase uncertainty/limitations; confidence='.$report->confidence,
        );
    }

    public function test_genus_level_evidence_does_not_answer_species_specific_question(): void
    {
        $plan = $this->phase5Plan(question: 'What is Triticum aestivum production quantity in Egypt?');
        $genusOnly = $this->identityEvidence(
            'e-genus',
            'Triticum genus production systems vary widely across North Africa without species-resolved Egypt quantities.',
            quality: [
                'evidence_directness' => ScientificEvidenceDirectnessAssessor::SUPPORTING,
                'species_relation' => 'related_genus',
                'entity_matched' => false,
                'topic_matched' => true,
            ],
            title: 'Triticum genus agronomy overview',
        );

        $report = $this->composer->compose($plan, $this->phase5Validation([$genusOnly], true));

        $this->assertTrue(
            in_array($report->status, [
                'insufficient_evidence',
                'no_validated_evidence',
                'synthesis_completed_with_partial_conflicts',
            ], true)
            || $report->confidence < 0.55
            || $report->citations === [],
            'Genus-only evidence must not become a species-resolved confident answer; status='.$report->status,
        );
    }

    public function test_correlational_evidence_is_not_upgraded_to_causal_claim_language(): void
    {
        $plan = $this->phase5Plan(question: 'Does deficit irrigation cause higher wheat yield in Egypt?');
        $correlational = $this->identityEvidence(
            'e-corr',
            'Wheat yield in Egypt was associated with deficit irrigation practices in observational surveys (correlation observed; causation not established).',
            relationship: ClaimEvidenceRelationship::PARTIALLY_SUPPORTED,
            quality: [
                'evidence_directness' => ScientificEvidenceDirectnessAssessor::SUPPORTING,
            ],
        );

        $report = $this->composer->compose($plan, $this->phase5Validation([$correlational], true));
        $joined = mb_strtolower(implode(' ', array_filter([
            (string) $report->answer,
            ...array_map(static fn ($c): string => (string) $c->claimText, $report->claims),
        ])));

        $this->assertTrue(
            in_array($report->status, ['insufficient_evidence', 'no_validated_evidence'], true)
            || $report->confidence < 0.6
            || $report->citations === []
            || ! str_contains($joined, 'causally proves')
            || str_contains($joined, 'correlation')
            || str_contains($joined, 'associated'),
            'Correlational evidence must not be upgraded to causal certainty: '.$joined,
        );
        $this->assertStringNotContainsString('causally proves', $joined);
    }

    /**
     * @param  array<string, mixed>  $quality
     */
    private function identityEvidence(
        string $id,
        string $text,
        string $relationship = ClaimEvidenceRelationship::SUPPORTED,
        array $quality = [],
        bool $conflict = false,
        string $title = '',
        string $sourceKey = 'openalex',
        float $confidence = 0.85,
    ): ScientificEvidenceItem {
        $directness = (string) ($quality['evidence_directness'] ?? ScientificEvidenceDirectnessAssessor::DIRECT);
        $factors = array_merge([
            'evidence_directness' => $directness,
            'species_relation' => 'same_species',
            'entity_matched' => true,
            'topic_matched' => true,
            'sense_coverage' => true,
            'factor_coverage' => 1.0,
        ], $quality);

        return new ScientificEvidenceItem(
            evidenceId: $id,
            sourceId: 'src-'.$id,
            sourceKey: $sourceKey,
            sourceType: 'peer_reviewed_journal',
            publicationTitle: $title !== '' ? $title : ('Title '.$id),
            authors: ['Author A'],
            institution: 'University Research Institute',
            journal: 'Agronomy Journal',
            doi: '10.1000/'.$id,
            url: 'https://example.org/'.$id,
            publicationYear: 2020,
            retrievedAt: now()->toIso8601String(),
            agriculturalDomain: 'agronomy',
            claimTopic: 'production',
            evidenceText: $text,
            validationStatus: EvidenceValidationStatus::EVIDENCE_USABLE,
            validationFailures: [],
            claimRelationship: $relationship,
            confidence: $confidence,
            qualityScore: $confidence,
            qualityFactors: $factors,
            sourceAttribution: [
                'organization' => 'University Research Institute',
                'source_type' => 'peer_reviewed_journal',
                'found_by_sources' => [$sourceKey],
                'evidence_directness' => $directness,
            ],
            hasConflict: $conflict,
        );
    }
}
