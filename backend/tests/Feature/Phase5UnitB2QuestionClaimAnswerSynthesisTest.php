<?php

namespace Tests\Feature;

use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use App\Services\Agriculture\Research\Synthesis\AnswerComposer;
use App\Services\Agriculture\Research\Synthesis\QuestionClaimSynthesisContract;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use Tests\TestCase;

/**
 * Phase 5 Unit B2 — QuestionClaim → Evidence → AnswerStatement synthesis.
 */
class Phase5UnitB2QuestionClaimAnswerSynthesisTest extends TestCase
{
    use Phase5UnitATestFixtures;

    public function test_b2_01_single_supported_claim_produces_traceable_statement(): void
    {
        $plan = $this->phase5Plan(
            question: 'What is wheat production quantity in Egypt?',
            requested: ['quantity'],
        );
        $evidence = [$this->phase5Evidence(
            'e-qty',
            ClaimEvidenceRelationship::SUPPORTED,
            ScientificEvidenceDirectnessAssessor::DIRECT,
        )];
        $evidence[0] = $this->withEvidenceText($evidence[0], 'Wheat production quantity in Egypt reached 9 million tonnes.');

        $report = app(AnswerComposer::class)->compose($plan, $this->phase5Validation($evidence, true));

        $this->assertNotEmpty($report->claims);
        $this->assertSame('qc-1', $report->claims[0]->questionClaimId);
        $this->assertSame(ClaimEvidenceRelationship::SUPPORTED, $report->claims[0]->claimRelationship);
        $this->assertSame(['e-qty'], $report->claims[0]->evidenceIds);
        $this->assertSame(['src-e-qty'], $report->claims[0]->sourceIds);
        $this->assertNotSame('', trim($report->claims[0]->claimText));
        $this->assertNotEmpty($report->observability['answer_statement_traces'] ?? []);
        $this->assertSame('qc-1', $report->observability['answer_statement_traces'][0]['question_claim_id']);
    }

    public function test_b2_02_two_supported_claims_remain_separate(): void
    {
        $plan = $this->phase5Plan(
            question: 'What is the production quantity and harvested area of wheat in Italy?',
            requested: ['quantity', 'area'],
            location: 'Italy',
        );
        $evidence = [
            $this->withEvidenceText(
                $this->phase5Evidence('e-q', ClaimEvidenceRelationship::SUPPORTED),
                'Wheat production quantity in Italy was 7.2 million tonnes.',
            ),
            $this->withEvidenceText(
                $this->phase5Evidence('e-a', ClaimEvidenceRelationship::SUPPORTED),
                'Harvested area of wheat in Italy covered 1.1 million hectares.',
            ),
        ];

        $report = app(AnswerComposer::class)->compose($plan, $this->phase5Validation($evidence, true));

        $byQc = [];
        foreach ($report->claims as $claim) {
            $byQc[$claim->questionClaimId] = $claim;
        }
        $this->assertArrayHasKey('qc-1', $byQc);
        $this->assertArrayHasKey('qc-2', $byQc);
        $this->assertSame(ClaimEvidenceRelationship::SUPPORTED, $byQc['qc-1']->claimRelationship);
        $this->assertSame(ClaimEvidenceRelationship::SUPPORTED, $byQc['qc-2']->claimRelationship);
        $this->assertSame(['e-q'], $byQc['qc-1']->evidenceIds);
        $this->assertSame(['e-a'], $byQc['qc-2']->evidenceIds);
        $this->assertNotSame($byQc['qc-1']->claimText, $byQc['qc-2']->claimText);
    }

    public function test_b2_03_unsupported_second_claim_is_explicit_not_omitted(): void
    {
        $plan = $this->phase5Plan(
            question: 'What is the production quantity and harvested area of wheat in Italy?',
            requested: ['quantity', 'area'],
            location: 'Italy',
        );
        $evidence = [
            $this->withEvidenceText(
                $this->phase5Evidence('e-q-only', ClaimEvidenceRelationship::SUPPORTED),
                'Wheat production quantity in Italy was 7.2 million tonnes.',
            ),
        ];

        $report = app(AnswerComposer::class)->compose($plan, $this->phase5Validation($evidence, true));

        $byQc = [];
        foreach ($report->claims as $claim) {
            $byQc[$claim->questionClaimId] = $claim;
        }
        $this->assertArrayHasKey('qc-1', $byQc);
        $this->assertArrayHasKey('qc-2', $byQc);
        $this->assertSame(ClaimEvidenceRelationship::SUPPORTED, $byQc['qc-1']->claimRelationship);
        $this->assertSame(ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE, $byQc['qc-2']->claimRelationship);
        $this->assertSame('', trim($byQc['qc-2']->claimText));
        $this->assertContains(
            'insufficient_validated_evidence_for_question_claim',
            $byQc['qc-2']->limitations,
        );
        $this->assertContains(
            'insufficient_validated_evidence_for_question_claim',
            $report->limitations,
        );
    }

    public function test_b2_04_partial_preserves_limitation_without_inventing_completion(): void
    {
        $plan = $this->phase5Plan(requested: ['quantity']);
        $evidence = [
            $this->withEvidenceText(
                $this->phase5Evidence(
                    'e-partial',
                    ClaimEvidenceRelationship::PARTIALLY_SUPPORTED,
                    ScientificEvidenceDirectnessAssessor::DIRECT,
                ),
                'Wheat production quantity estimates are incomplete for Egypt.',
            ),
        ];

        $report = app(AnswerComposer::class)->compose($plan, $this->phase5Validation($evidence, true));

        $this->assertCount(1, $report->claims);
        $this->assertSame('qc-1', $report->claims[0]->questionClaimId);
        $this->assertSame(ClaimEvidenceRelationship::PARTIALLY_SUPPORTED, $report->claims[0]->claimRelationship);
        $this->assertContains('partial_evidence_support', $report->claims[0]->limitations);
        $this->assertNotSame('', trim($report->claims[0]->claimText));
    }

    public function test_b2_05_conflict_preserves_state_without_arbitrary_fact(): void
    {
        $plan = $this->phase5Plan(requested: ['quantity']);
        $evidence = [
            $this->withEvidenceText(
                $this->phase5Evidence(
                    'e-conflict',
                    ClaimEvidenceRelationship::CONFLICTING,
                    ScientificEvidenceDirectnessAssessor::DIRECT,
                    conflict: true,
                ),
                'Wheat production quantity was 9 million tonnes according to source A.',
            ),
        ];

        $report = app(AnswerComposer::class)->compose($plan, $this->phase5Validation($evidence, true));

        $this->assertSame('qc-1', $report->claims[0]->questionClaimId);
        $this->assertSame(ClaimEvidenceRelationship::CONFLICTING, $report->claims[0]->claimRelationship);
        $this->assertSame('', trim($report->claims[0]->claimText));
        $this->assertContains('conflicting_evidence_for_question_claim', $report->claims[0]->limitations);
    }

    public function test_b2_06_supporting_only_not_promoted_to_direct_truth(): void
    {
        $plan = $this->phase5Plan(
            question: 'What is wheat production quantity in Egypt?',
            requested: ['quantity'],
            constraints: [
                'answer_language' => 'en',
                'question_type' => 'quantity',
                'required_evidence_type' => 'direct_measurement',
            ],
            intent: 'statistical_lookup',
        );
        $evidence = [
            $this->withEvidenceText(
                $this->phase5Evidence(
                    'e-sup',
                    ClaimEvidenceRelationship::SUPPORTED,
                    ScientificEvidenceDirectnessAssessor::SUPPORTING,
                ),
                'Background notes mention wheat production quantity trends in the region.',
            ),
            $this->withEvidenceText(
                $this->phase5Evidence(
                    'e-sup-2',
                    ClaimEvidenceRelationship::SUPPORTED,
                    ScientificEvidenceDirectnessAssessor::SUPPORTING,
                ),
                'Secondary wheat production quantity commentary without direct measurement.',
            ),
        ];

        $report = app(AnswerComposer::class)->compose($plan, $this->phase5Validation($evidence, true));

        $this->assertSame(0, (int) ($report->researchMetadata['direct_evidence_count'] ?? 0));
        $this->assertNotSame('sufficient_direct_evidence', $report->researchMetadata['sufficiency_mode'] ?? null);
        $this->assertTrue(
            in_array($report->status, ['insufficient_evidence', 'no_validated_evidence'], true)
            || str_contains(mb_strtolower($report->conciseSummary), 'insufficient')
            || ((float) $report->confidence) === 0.0,
        );
    }

    public function test_b2_07_no_orphan_factual_statements_without_question_claim_id(): void
    {
        $plan = $this->phase5Plan(requested: ['quantity']);
        $evidence = [
            $this->withEvidenceText(
                $this->phase5Evidence('e-1', ClaimEvidenceRelationship::SUPPORTED),
                'Wheat production quantity in Egypt reached 9 million tonnes.',
            ),
        ];

        $report = app(AnswerComposer::class)->compose($plan, $this->phase5Validation($evidence, true));

        foreach ($report->claims as $claim) {
            if (trim($claim->claimText) === '') {
                continue;
            }
            $this->assertNotNull($claim->questionClaimId);
            $this->assertNotSame('', trim((string) $claim->questionClaimId));
        }
    }

    public function test_b2_08_citation_provenance_remains_traceable(): void
    {
        $plan = $this->phase5Plan(requested: ['quantity']);
        $base = $this->phase5Evidence('e-cite', ClaimEvidenceRelationship::SUPPORTED);
        $evidence = [
            new \App\Services\Agriculture\Research\Validation\ScientificEvidenceItem(
                evidenceId: $base->evidenceId,
                sourceId: $base->sourceId,
                sourceKey: $base->sourceKey,
                sourceType: 'peer_reviewed_journal',
                publicationTitle: 'Wheat production quantity study',
                authors: $base->authors,
                institution: 'Fixture University',
                journal: 'Fixture Journal',
                doi: $base->doi,
                url: $base->url,
                publicationYear: $base->publicationYear,
                retrievedAt: $base->retrievedAt,
                agriculturalDomain: $base->agriculturalDomain,
                claimTopic: $base->claimTopic,
                evidenceText: 'Wheat production quantity in Egypt reached 9 million tonnes.',
                validationStatus: $base->validationStatus,
                validationFailures: $base->validationFailures,
                claimRelationship: $base->claimRelationship,
                confidence: $base->confidence,
                qualityScore: $base->qualityScore,
                qualityFactors: $base->qualityFactors,
                sourceAttribution: $base->sourceAttribution,
                hasConflict: $base->hasConflict,
                conditions: $base->conditions,
                cropOrEntity: $base->cropOrEntity,
            ),
        ];

        $report = app(AnswerComposer::class)->compose($plan, $this->phase5Validation($evidence, true));

        $this->assertSame(['e-cite'], $report->claims[0]->evidenceIds);
        $this->assertSame(['src-e-cite'], $report->claims[0]->sourceIds);
        $this->assertNotEmpty($report->citations);
        $this->assertSame('e-cite', $report->citations[0]->evidenceId);
        $this->assertSame('src-e-cite', $report->citations[0]->sourceId);
    }

    /**
     * @dataProvider multilingualMultiClaimProvider
     */
    public function test_b2_09_to_12_multilingual_multi_claim(string $question, string $lang): void
    {
        $plan = $this->phase5Plan(
            question: $question,
            requested: ['quantity', 'area'],
            constraints: ['answer_language' => $lang],
            language: $lang,
            location: 'Italy',
        );
        $evidence = [
            $this->withEvidenceText(
                $this->phase5Evidence('e-q-'.$lang, ClaimEvidenceRelationship::SUPPORTED),
                'Wheat production quantity in Italy was 7.2 million tonnes.',
            ),
            $this->withEvidenceText(
                $this->phase5Evidence('e-a-'.$lang, ClaimEvidenceRelationship::SUPPORTED),
                'Harvested area of wheat in Italy covered 1.1 million hectares.',
            ),
        ];

        $report = app(AnswerComposer::class)->compose($plan, $this->phase5Validation($evidence, true));

        $this->assertSame($lang, $report->language);
        $ids = array_map(static fn ($c) => $c->questionClaimId, $report->claims);
        $this->assertContains('qc-1', $ids);
        $this->assertContains('qc-2', $ids);
    }

    /** @return list<array{0: string, 1: string}> */
    public static function multilingualMultiClaimProvider(): array
    {
        return [
            'ar' => ['ما كمية الإنتاج والمساحة المحصودة للقمح في إيطاليا؟', 'ar'],
            'en' => ['What is the production quantity and harvested area of wheat in Italy?', 'en'],
            'fr' => ['Quelle est la quantité de production et la superficie récoltée du blé en Italie ?', 'fr'],
            'tr' => ['İtalya\'da buğday üretim miktarı ve hasat edilen alanı nedir?', 'tr'],
        ];
    }

    public function test_b2_mapper_multi_claim_does_not_cross_bind_unrelated_property(): void
    {
        $plan = $this->phase5Plan(
            requested: ['quantity', 'area'],
            location: 'Italy',
        );
        $evidence = [
            $this->withEvidenceText(
                $this->phase5Evidence('e-q', ClaimEvidenceRelationship::SUPPORTED),
                'Wheat production quantity in Italy was 7.2 million tonnes.',
            ),
        ];
        $matrix = (new QuestionClaimSynthesisContract)->build(
            $plan,
            $this->phase5Validation($evidence, true),
            $evidence,
        );

        $this->assertCount(1, $matrix['claim_evidence_bindings']);
        $this->assertSame('qc-1', $matrix['claim_evidence_bindings'][0]['question_claim_id']);
        $this->assertSame(
            ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE,
            $matrix['answer_statement_traces'][1]['aggregate_claim_relationship'],
        );
    }

    private function withEvidenceText(mixed $item, string $text): mixed
    {
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
            conditions: $item->conditions ?? null,
            cropOrEntity: $item->cropOrEntity ?? null,
        );
    }
}
