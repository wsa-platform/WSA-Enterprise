<?php

namespace Tests\Unit\Agriculture\Research\Synthesis;

use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use App\Services\Agriculture\Research\Synthesis\ResearchAnswerClaim;
use App\Services\Agriculture\Research\Synthesis\ScientificSynthesisIntegrator;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use App\Services\Agriculture\Research\Validation\EvidenceConflictAssessmentOutcome;
use App\Services\Agriculture\Research\Validation\ScientificEvidenceItem;
use Tests\TestCase;

class ScientificSynthesisIntegratorTest extends TestCase
{
    public function test_integrator_separates_supported_partial_and_contextual_differences(): void
    {
        $claims = [
            new ResearchAnswerClaim(
                claimId: 'c1',
                claimText: 'Deficit irrigation can increase water-use efficiency in wheat.',
                evidenceIds: ['e1'],
                sourceIds: ['src-e1'],
                validationStatus: 'validated',
                claimRelationship: ClaimEvidenceRelationship::SUPPORTED,
                confidence: 0.8,
                sourceKeys: ['openalex'],
            ),
            new ResearchAnswerClaim(
                claimId: 'c2',
                claimText: 'Yield response remains partially characterized under saline soils.',
                evidenceIds: ['e2'],
                sourceIds: ['src-e2'],
                validationStatus: 'validated',
                claimRelationship: ClaimEvidenceRelationship::PARTIALLY_SUPPORTED,
                confidence: 0.5,
                limitations: ['partial_evidence_support'],
                sourceKeys: ['openalex'],
            ),
            new ResearchAnswerClaim(
                claimId: 'c3',
                claimText: '',
                evidenceIds: [],
                sourceIds: [],
                validationStatus: 'insufficient',
                claimRelationship: ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE,
                confidence: 0.0,
                limitations: ['insufficient_validated_evidence_for_question_claim'],
            ),
        ];

        $usable = [
            $this->item('e1', contextual: false),
            $this->item('e2', contextual: true),
        ];

        $packet = (new ScientificSynthesisIntegrator())->integrate($claims, $usable, [], 'en');

        $this->assertNotEmpty($packet['supported_findings']);
        $this->assertNotEmpty($packet['partial_findings']);
        $this->assertNotEmpty($packet['contextual_differences']);
        $this->assertContains('c3', $packet['insufficient_claims']);
        $this->assertSame('synthesis-integrator-v1', $packet['matrix_version']);
        $this->assertNotEmpty($packet['synthesis_notes']);
    }

    public function test_integrator_preserves_genuine_conflicts_without_auto_resolution(): void
    {
        $packet = (new ScientificSynthesisIntegrator())->integrate(
            [],
            [],
            [[
                'evidence_id' => 'e-c',
                'conflict_assessment' => EvidenceConflictAssessmentOutcome::GENUINE_CONFLICT,
                'message' => 'conflict',
            ]],
            'en',
        );

        $this->assertCount(1, $packet['genuine_conflicts']);
        $joined = implode(' ', $packet['synthesis_notes']);
        $this->assertStringContainsString('comparable conditions', mb_strtolower($joined));
    }

    private function item(string $id, bool $contextual): ScientificEvidenceItem
    {
        return new ScientificEvidenceItem(
            evidenceId: $id,
            sourceId: 'src-'.$id,
            sourceKey: 'openalex',
            sourceType: 'scholarly',
            publicationTitle: 'Title '.$id,
            authors: ['Author'],
            institution: null,
            journal: null,
            doi: null,
            url: null,
            publicationYear: 2021,
            retrievedAt: now()->toIso8601String(),
            agriculturalDomain: 'irrigation',
            claimTopic: 'irrigation',
            evidenceText: 'Irrigation trial excerpt for '.$id,
            validationStatus: 'evidence_usable',
            validationFailures: [],
            claimRelationship: ClaimEvidenceRelationship::SUPPORTED,
            confidence: 0.7,
            qualityScore: 0.7,
            qualityFactors: $contextual ? [
                'evidence_directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
                'conflict_assessment' => EvidenceConflictAssessmentOutcome::RESOLVED_BY_CONTEXT,
                'contextual_difference' => true,
                'conflict_differing_keys' => ['irrigation_method'],
            ] : [
                'evidence_directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
            ],
            sourceAttribution: [],
            hasConflict: false,
            conditions: $contextual ? ['irrigation_method' => 'drip'] : null,
            cropOrEntity: 'wheat',
        );
    }
}
