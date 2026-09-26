<?php

namespace Tests\Feature;

use App\Services\Agriculture\Research\AgriculturalResearchAgent;
use App\Services\Agriculture\Research\Synthesis\AnswerSynthesisExecutionReport;
use App\Services\Agriculture\Research\Synthesis\ResearchAnswerCitation;
use App\Services\Agriculture\Research\Synthesis\ResearchAnswerClaim;
use App\Services\Agriculture\Research\Synthesis\ScientificAnswerCandidatePresenter;
use App\Services\Agriculture\Research\Synthesis\ScientificUserPresentation;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use App\Services\Agriculture\Research\Validation\EvidenceValidationStatus;
use App\Services\Agriculture\Research\Validation\ScientificEvidenceItem;
use ReflectionMethod;
use Tests\TestCase;

class ScientificUserPresentationContractTest extends TestCase
{
    public function test_candidate_threshold_remains_fifty_percent(): void
    {
        $this->assertSame(0.50, ScientificAnswerCandidatePresenter::PRESENTATION_THRESHOLD);
        $this->assertSame(0.50, ScientificUserPresentation::FINAL_ANSWER_CONFIDENCE_THRESHOLD);
        $this->assertSame(0.50, ScientificUserPresentation::SEARCH_RESULT_CONFIDENCE_THRESHOLD);
    }

    public function test_original_url_accepts_http_and_doi_but_not_google(): void
    {
        $this->assertSame(
            'https://example.org/paper',
            ScientificUserPresentation::originalUrl('https://example.org/paper', '10.1000/x'),
        );
        $this->assertSame(
            'https://doi.org/10.1000/valid',
            ScientificUserPresentation::originalUrl(null, '10.1000/valid'),
        );
        $this->assertNull(ScientificUserPresentation::originalUrl('https://www.google.com/search?q=wheat', '10.1000/x'));
        $this->assertNull(ScientificUserPresentation::originalUrl('/research/result/x', null));
    }

    public function test_library_search_implementations_remain_deleted(): void
    {
        $this->assertFileDoesNotExist(app_path('Services/Agriculture/Discoverers/LibraryKeywordSectionDiscoverer.php'));
        $this->assertFileDoesNotExist(app_path('Services/Agriculture/Discoverers/LibraryStructuredSectionDiscoverer.php'));
        $this->assertFileDoesNotExist(app_path('Services/Agriculture/Discoverers/LibraryRagSectionDiscoverer.php'));
        $this->assertFileDoesNotExist(app_path('Services/Agriculture/Discoverers/LibraryCropFilesSectionDiscoverer.php'));
        $this->assertFileDoesNotExist(app_path('Services/Agriculture/Research/AgriculturalScientificKnowledgeEngine.php'));
    }

    public function test_case_a_direct_with_citations_presents_answer_and_sources(): void
    {
        $synthesis = $this->synthesisReport(
            answer: 'Documented primary answer.',
            gate: 'PASSED',
            citations: [$this->citation()],
        );
        $this->assertTrue($this->isSufficient($synthesis));
        $presentation = $this->present($synthesis);
        $this->assertSame('Documented primary answer.', $presentation['primary_answer']);
        $this->assertSame(ScientificUserPresentation::HUMAN_ANSWERED, $presentation['human_status']);
        $this->assertCount(1, $presentation['sources']);
        $this->assertSame('https://example.org/paper', $presentation['sources'][0]['original_url']);
    }

    public function test_case_b_direct_with_empty_citations_keeps_answer_and_does_not_fabricate_source(): void
    {
        $synthesis = $this->synthesisReport(
            answer: 'Supported value is 6609520 t.',
            gate: 'PASSED',
            citations: [],
            metadata: [
                'direct_evidence_gate' => 'PASSED',
                'evidence_sufficient' => true,
                'sufficiency_mode' => 'sufficient_direct_evidence',
            ],
        );
        $this->assertTrue($this->isSufficient($synthesis));
        $presentation = $this->present($synthesis);
        $this->assertSame('Supported value is 6609520 t.', $presentation['primary_answer']);
        $this->assertSame(ScientificUserPresentation::HUMAN_ANSWERED, $presentation['human_status']);
        $this->assertSame([], $presentation['sources']);
        $this->assertStringNotContainsString('doi.org', json_encode($presentation));
        $this->assertStringNotContainsString('google.', json_encode($presentation));
    }

    public function test_case_c_supporting_only_below_display_threshold_is_hidden(): void
    {
        $synthesis = $this->synthesisReport(
            answer: 'Supporting-only narrative.',
            gate: 'INSUFFICIENT_DIRECT_EVIDENCE',
            citations: [$this->citation()],
            metadata: [
                'direct_evidence_gate' => 'INSUFFICIENT_DIRECT_EVIDENCE',
                'evidence_sufficient' => false,
            ],
            confidence: 0.42,
        );
        $this->assertFalse($this->isSufficient($synthesis));
        $presentation = $this->present($synthesis);
        $this->assertNull($presentation['primary_answer']);
        $this->assertSame(ScientificUserPresentation::HUMAN_INSUFFICIENT, $presentation['human_status']);
        $this->assertSame('insufficient_direct_evidence', $presentation['user_notice_code']);
    }

    public function test_case_d_empty_answer_is_not_fabricated(): void
    {
        $synthesis = $this->synthesisReport(
            answer: '   ',
            gate: 'PASSED',
            citations: [],
        );
        $this->assertFalse($this->isSufficient($synthesis));
        $presentation = $this->present($synthesis);
        $this->assertNull($presentation['primary_answer']);
    }

    public function test_case_e_no_direct_evidence_remains_insufficient(): void
    {
        $synthesis = $this->synthesisReport(
            answer: 'Insufficient direct scientific evidence was found for a definitive answer.',
            gate: 'INSUFFICIENT_DIRECT_EVIDENCE',
            citations: [],
            confidence: 0.0,
        );
        $this->assertFalse($this->isSufficient($synthesis));
        $presentation = $this->present($synthesis);
        $this->assertNull($presentation['primary_answer']);
        $this->assertSame(ScientificUserPresentation::HUMAN_INSUFFICIENT, $presentation['human_status']);
        $this->assertSame([], $presentation['sources']);
    }

    public function test_case_f_citationless_direct_statistical_answer_is_visible(): void
    {
        $synthesis = $this->synthesisReport(
            answer: 'Wheat Production 2022. Value: 6609520 t',
            gate: 'PASSED',
            citations: [],
            metadata: [
                'direct_evidence_gate' => 'PASSED',
                'evidence_sufficient' => true,
                'required_evidence_type' => 'numeric_rate_or_quantity',
            ],
        );
        $this->assertTrue($this->isSufficient($synthesis));
        $presentation = $this->present($synthesis);
        $this->assertSame('Wheat Production 2022. Value: 6609520 t', $presentation['primary_answer']);
        $this->assertSame([], $presentation['sources']);
        $this->assertFalse($presentation['candidate_selection']['confidence_exposed']);
        $this->assertTrue($presentation['candidate_selection']['directness_unchanged']);
    }

    public function test_final_answer_hidden_when_overall_confidence_is_0_49(): void
    {
        $this->assertFinalAnswerDisplay(0.49, expectDisplayed: false);
    }

    public function test_final_answer_shown_when_overall_confidence_is_exactly_0_50(): void
    {
        $this->assertFinalAnswerDisplay(0.50, expectDisplayed: true);
    }

    public function test_final_answer_shown_when_overall_confidence_is_0_51(): void
    {
        $this->assertFinalAnswerDisplay(0.51, expectDisplayed: true);
    }

    public function test_final_answer_hidden_when_overall_confidence_is_0_40_even_if_direct_gate_passed(): void
    {
        $synthesis = $this->synthesisReport(
            answer: 'Synthesized answer that must stay hidden below the display threshold.',
            gate: 'PASSED',
            citations: [$this->citation()],
            confidence: 0.40,
        );
        $this->assertFalse($this->isSufficient($synthesis));
        $presentation = $this->present($synthesis);
        $this->assertNull($presentation['primary_answer']);
        $this->assertSame(ScientificUserPresentation::HUMAN_INSUFFICIENT, $presentation['human_status']);
    }

    public function test_final_answer_shown_when_overall_confidence_is_0_80_even_if_direct_gate_failed(): void
    {
        $synthesis = $this->synthesisReport(
            answer: 'Valid synthesized final answer at high confidence.',
            gate: 'INSUFFICIENT_DIRECT_EVIDENCE',
            citations: [$this->citation()],
            metadata: [
                'direct_evidence_gate' => 'INSUFFICIENT_DIRECT_EVIDENCE',
                'evidence_sufficient' => false,
            ],
            confidence: 0.80,
        );
        $this->assertTrue($this->isSufficient($synthesis));
        $presentation = $this->present($synthesis);
        $this->assertSame('Valid synthesized final answer at high confidence.', $presentation['primary_answer']);
        $this->assertSame(ScientificUserPresentation::HUMAN_ANSWERED, $presentation['human_status']);
    }

    public function test_search_result_hidden_when_evidence_confidence_is_0_49(): void
    {
        $this->assertResultEligibility(0.49, expectVisible: false);
    }

    public function test_search_result_shown_when_evidence_confidence_is_exactly_0_50(): void
    {
        $this->assertResultEligibility(0.50, expectVisible: true);
    }

    public function test_search_result_shown_when_evidence_confidence_is_0_51(): void
    {
        $this->assertResultEligibility(0.51, expectVisible: true);
    }

    public function test_results_list_uses_stage4_confidence_not_overall_confidence(): void
    {
        $synthesis = $this->synthesisReport(
            answer: 'Hidden final answer.',
            gate: 'PASSED',
            citations: [],
            confidence: 0.40,
        );
        $this->assertFalse($this->isSufficient($synthesis));
        $presentation = ScientificUserPresentation::fromSynthesis($synthesis, false, [
            $this->evidenceItem('https://openalex.org/W111', 'Eligible paper', 0.50),
            $this->evidenceItem('https://openalex.org/W222', 'Ineligible paper', 0.49),
        ]);
        $this->assertNull($presentation['primary_answer']);
        $this->assertCount(1, $presentation['results']);
        $this->assertSame('https://openalex.org/W111', $presentation['results'][0]['result_id']);
        $this->assertSame('Eligible paper', $presentation['results'][0]['title']);
    }

    public function test_results_list_keeps_multiple_eligible_stage4_papers(): void
    {
        $synthesis = $this->synthesisReport(
            answer: 'Documented primary answer.',
            gate: 'PASSED',
            citations: [$this->citation()],
        );
        $presentation = ScientificUserPresentation::fromSynthesis($synthesis, true, [
            $this->evidenceItem('https://openalex.org/W1', 'Paper One', 0.80),
            $this->evidenceItem('https://openalex.org/W2', 'Paper Two', 0.70),
            $this->evidenceItem('https://openalex.org/W3', 'Paper Three', 0.50),
        ]);
        $this->assertSame('Documented primary answer.', $presentation['primary_answer']);
        $this->assertSame(
            ['https://openalex.org/W1', 'https://openalex.org/W2', 'https://openalex.org/W3'],
            array_column($presentation['results'], 'result_id'),
        );
    }

    public function test_result_identity_stays_stable_when_title_changes(): void
    {
        $first = $this->evidenceItem('https://openalex.org/W999', 'Original title', 0.80);
        $renamed = $this->evidenceItem('https://openalex.org/W999', 'Changed title', 0.80);
        $this->assertSame(
            ScientificUserPresentation::canonicalResultId($first),
            ScientificUserPresentation::canonicalResultId($renamed),
        );
        $this->assertSame('https://openalex.org/W999', ScientificUserPresentation::canonicalResultId($first));
        $this->assertNotSame('Original title', ScientificUserPresentation::canonicalResultId($first));
    }

    public function test_doi_and_publisher_url_are_not_used_as_internal_result_identity(): void
    {
        $doiOnly = $this->evidenceItem('10.1000/paper-id', 'DOI paper', 0.80, '10.1000/paper-id');
        $urlOnly = $this->evidenceItem('https://publisher.example/paper', 'URL paper', 0.80, 'https://publisher.example/paper');
        $this->assertSame('srcid-'.md5('10.1000/paper-id'), ScientificUserPresentation::canonicalResultId($doiOnly));
        $this->assertSame('srcid-'.md5('https://publisher.example/paper'), ScientificUserPresentation::canonicalResultId($urlOnly));
        $this->assertFalse(ScientificUserPresentation::isInternalIdentity('10.1000/paper-id'));
        $this->assertFalse(ScientificUserPresentation::isInternalIdentity('https://publisher.example/paper'));
    }

    public function test_unusable_stage4_items_are_not_listed(): void
    {
        $usable = $this->evidenceItem('https://openalex.org/W80', 'Usable paper', 0.80);
        $rejected = $this->evidenceItem(
            'https://openalex.org/W10',
            'Rejected paper',
            0.90,
            'https://openalex.org/W10',
            EvidenceValidationStatus::REJECTED,
        );
        $rows = ScientificUserPresentation::researchResultsFromValidatedEvidence([$usable, $rejected]);
        $this->assertCount(1, $rows);
        $this->assertSame('https://openalex.org/W80', $rows[0]['result_id']);
    }

    public function test_invalid_and_duplicate_stage4_items_are_rejected_deterministically(): void
    {
        $first = $this->evidenceItem('https://openalex.org/Wdup', 'First paper', 0.80);
        $duplicate = $this->evidenceItem('https://openalex.org/Wdup', 'Second paper same id', 0.90);
        $rows = ScientificUserPresentation::researchResultsFromValidatedEvidence([
            'not-an-item',
            $first,
            $duplicate,
        ]);
        $this->assertCount(1, $rows);
        $this->assertSame('https://openalex.org/Wdup', $rows[0]['result_id']);
        $this->assertSame('First paper', $rows[0]['title']);
    }

    public function test_high_overall_confidence_does_not_include_ineligible_result(): void
    {
        $synthesis = $this->synthesisReport(
            answer: 'Visible final answer.',
            gate: 'PASSED',
            citations: [$this->citation()],
            confidence: 0.80,
        );
        $presentation = ScientificUserPresentation::fromSynthesis($synthesis, true, [
            $this->evidenceItem('https://openalex.org/Wlow', 'Low result', 0.40),
            $this->evidenceItem('https://openalex.org/Whigh', 'High result', 0.80),
        ]);
        $this->assertSame('Visible final answer.', $presentation['primary_answer']);
        $this->assertSame(['https://openalex.org/Whigh'], array_column($presentation['results'], 'result_id'));
    }

    public function test_high_claim_confidence_cannot_substitute_for_low_overall_confidence(): void
    {
        $highClaim = new ResearchAnswerClaim(
            claimId: 'c-high',
            claimText: 'High-confidence claim that is not the final-answer gate.',
            evidenceIds: ['ev-1'],
            sourceIds: ['src-1'],
            validationStatus: 'validated',
            claimRelationship: ClaimEvidenceRelationship::SUPPORTED,
            confidence: 0.90,
        );
        $synthesis = $this->synthesisReport(
            answer: 'Non-empty final synthesized answer that must stay hidden.',
            gate: 'PASSED',
            citations: [$this->citation()],
            confidence: 0.40,
            claims: [$highClaim],
        );

        $this->assertLessThan(ScientificUserPresentation::FINAL_ANSWER_CONFIDENCE_THRESHOLD, $synthesis->confidence);
        $this->assertGreaterThanOrEqual(ScientificAnswerCandidatePresenter::PRESENTATION_THRESHOLD, $highClaim->confidence);
        $this->assertFalse($this->isSufficient($synthesis));
        $this->assertFalse(ScientificUserPresentation::isEligibleForFinalDisplay($synthesis));

        $presentation = $this->present($synthesis);
        $this->assertNull($presentation['primary_answer']);
        $this->assertSame(ScientificUserPresentation::HUMAN_INSUFFICIENT, $presentation['human_status']);
        $this->assertSame(
            ['High-confidence claim that is not the final-answer gate.'],
            array_column($presentation['candidates'], 'answer'),
        );
    }

    private function assertFinalAnswerDisplay(float $confidence, bool $expectDisplayed): void
    {
        $synthesis = $this->synthesisReport(
            answer: 'Boundary synthesized final answer.',
            gate: 'PASSED',
            citations: [$this->citation()],
            confidence: $confidence,
        );
        $this->assertSame($expectDisplayed, $this->isSufficient($synthesis));
        $presentation = $this->present($synthesis);
        if ($expectDisplayed) {
            $this->assertSame('Boundary synthesized final answer.', $presentation['primary_answer']);
            $this->assertSame(ScientificUserPresentation::HUMAN_ANSWERED, $presentation['human_status']);
        } else {
            $this->assertNull($presentation['primary_answer']);
            $this->assertSame(ScientificUserPresentation::HUMAN_INSUFFICIENT, $presentation['human_status']);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function present(AnswerSynthesisExecutionReport $synthesis): array
    {
        return ScientificUserPresentation::fromSynthesis($synthesis, $this->isSufficient($synthesis));
    }

    private function isSufficient(AnswerSynthesisExecutionReport $synthesis): bool
    {
        $method = new ReflectionMethod(AgriculturalResearchAgent::class, 'hasSufficientScientificSynthesis');
        $method->setAccessible(true);

        return (bool) $method->invoke(app(AgriculturalResearchAgent::class), $synthesis);
    }

    /**
     * @param  list<ResearchAnswerCitation>  $citations
     * @param  array<string, mixed>  $metadata
     * @param  list<ResearchAnswerClaim>  $claims
     */
    private function synthesisReport(
        string $answer,
        string $gate,
        array $citations,
        array $metadata = [],
        float $confidence = 0.8,
        array $claims = [],
    ): AnswerSynthesisExecutionReport {
        return new AnswerSynthesisExecutionReport(
            status: 'scientific_generated',
            performed: true,
            answer: $answer,
            conciseSummary: null,
            detailedExplanation: null,
            keyFindings: [],
            claims: $claims,
            citations: $citations,
            evidenceReferences: [],
            confidence: $confidence,
            limitations: [],
            uncertainty: null,
            conflicts: [],
            language: 'en',
            researchMetadata: array_merge(['direct_evidence_gate' => $gate], $metadata),
            observability: [],
        );
    }

    private function assertResultEligibility(float $confidence, bool $expectVisible): void
    {
        $rows = ScientificUserPresentation::researchResultsFromValidatedEvidence([
            $this->evidenceItem('https://openalex.org/W50', 'Boundary paper', $confidence),
        ]);
        if ($expectVisible) {
            $this->assertCount(1, $rows);
            $this->assertSame('https://openalex.org/W50', $rows[0]['result_id']);
            $this->assertSame($confidence, $rows[0]['confidence']);
        } else {
            $this->assertSame([], $rows);
        }
    }

    private function evidenceItem(
        string $sourceIdentifier,
        string $title,
        float $confidence,
        ?string $sourceId = null,
        string $validationStatus = EvidenceValidationStatus::EVIDENCE_USABLE,
    ): ScientificEvidenceItem {
        return new ScientificEvidenceItem(
            evidenceId: md5($sourceIdentifier.'|'.$title),
            sourceId: $sourceId ?? $sourceIdentifier,
            sourceKey: 'openalex',
            sourceType: 'peer_reviewed_journal',
            publicationTitle: $title,
            authors: ['Author'],
            institution: 'Org',
            journal: 'Journal',
            doi: str_starts_with($sourceIdentifier, '10.') ? $sourceIdentifier : '10.1000/demo',
            url: str_starts_with($sourceIdentifier, 'http') ? $sourceIdentifier : 'https://example.org/paper',
            publicationYear: 2022,
            retrievedAt: '2026-09-25T00:00:00+00:00',
            agriculturalDomain: 'field_crops',
            claimTopic: 'irrigation',
            evidenceText: 'Documented scientific evidence.',
            validationStatus: $validationStatus,
            validationFailures: [],
            claimRelationship: ClaimEvidenceRelationship::SUPPORTED,
            confidence: $confidence,
            qualityScore: 80.0,
            qualityFactors: [],
            sourceAttribution: [
                'provenance' => [
                    'source_identifier' => $sourceIdentifier,
                ],
            ],
        );
    }

    private function citation(): ResearchAnswerCitation
    {
        return new ResearchAnswerCitation(
            citationId: 'cite-1',
            sourceId: 'src-1',
            evidenceId: 'ev-1',
            title: 'Paper',
            authors: ['Author'],
            organization: null,
            journal: null,
            doi: null,
            url: 'https://example.org/paper',
            publicationYear: 2022,
            sourceType: 'peer_reviewed_journal',
        );
    }
}
