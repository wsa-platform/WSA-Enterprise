<?php

namespace Tests\Feature;

use App\Services\Agriculture\Research\ResearchPlanner;
use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use App\Services\Agriculture\Research\Synthesis\AnswerComposer;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use App\Services\Agriculture\Research\Validation\EvidenceValidationExecutionReport;
use App\Services\Agriculture\Research\Validation\EvidenceValidationStatus;
use App\Services\Agriculture\Research\Validation\ScientificEvidenceItem;
use Tests\TestCase;

/**
 * Phase 4 — claim ↔ evidence ↔ citation traceability.
 */
class Phase4ClaimEvidenceTraceabilityTest extends TestCase
{
    public function test_composed_claims_reference_validated_evidence_ids(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'What irrigation methods improve wheat yield?',
        ]);

        $item = new ScientificEvidenceItem(
            evidenceId: 'trace-ev-1',
            sourceId: 'src-trace-1',
            sourceKey: 'openalex',
            sourceType: 'peer_reviewed_journal',
            publicationTitle: 'Wheat drip irrigation improved yield',
            authors: ['Fixture'],
            institution: 'Fixture',
            journal: 'Agronomy Journal',
            doi: '10.9999/trace-1',
            url: 'https://example.test/trace-1',
            publicationYear: 2020,
            retrievedAt: '2026-09-21T00:00:00+00:00',
            agriculturalDomain: 'field_crops',
            claimTopic: 'irrigation',
            evidenceText: 'Wheat drip irrigation improved grain yield in multi-year field trials in arid regions.',
            validationStatus: EvidenceValidationStatus::EVIDENCE_USABLE,
            validationFailures: [],
            claimRelationship: ClaimEvidenceRelationship::SUPPORTED,
            confidence: 0.9,
            qualityScore: 90.0,
            qualityFactors: [
                'evidence_directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
                'answer_eligible' => true,
            ],
            sourceAttribution: [
                'evidence_directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
            ],
            cropOrEntity: 'wheat',
        );

        $validation = new EvidenceValidationExecutionReport(
            status: 'validation_completed',
            validatedEvidence: [$item],
            rejectedEvidence: [],
            sourcesReceived: 1,
            validatedCount: 1,
            rejectedCount: 0,
            duplicateCount: 0,
            conflictingCount: 0,
            evidenceSufficient: true,
            validatorsUsed: [],
            qualityDistribution: [],
            searchSummary: ['search_status' => 'search_completed'],
            observability: [],
        );

        $synthesis = app(AnswerComposer::class)->compose($plan, $validation);

        if ($synthesis->claims !== []) {
            foreach ($synthesis->claims as $claim) {
                $this->assertContains('trace-ev-1', $claim->evidenceIds);
            }
        }

        if ($synthesis->citations !== []) {
            $this->assertTrue(collect($synthesis->citations)->contains(
                fn ($citation): bool => $citation->evidenceId === 'trace-ev-1',
            ));
            $this->assertTrue(collect($synthesis->citations)->contains(
                fn ($citation): bool => $citation->url === 'https://example.test/trace-1'
                    || $citation->doi === '10.9999/trace-1',
            ));
        }

        $this->assertTrue(
            $synthesis->claims !== [] || $synthesis->status === 'insufficient_evidence'
            || str_contains((string) $synthesis->status, 'insufficient'),
            'Composer must either bind claims to evidence or explicitly mark insufficiency',
        );
    }
}
