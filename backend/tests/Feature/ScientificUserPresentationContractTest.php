<?php

namespace Tests\Feature;

use App\Services\Agriculture\Research\AgriculturalResearchAgent;
use App\Services\Agriculture\Research\Synthesis\AnswerSynthesisExecutionReport;
use App\Services\Agriculture\Research\Synthesis\ResearchAnswerCitation;
use App\Services\Agriculture\Research\Synthesis\ScientificAnswerCandidatePresenter;
use App\Services\Agriculture\Research\Synthesis\ScientificUserPresentation;
use ReflectionMethod;
use Tests\TestCase;

class ScientificUserPresentationContractTest extends TestCase
{
    public function test_candidate_threshold_remains_fifty_percent(): void
    {
        $this->assertSame(0.50, ScientificAnswerCandidatePresenter::PRESENTATION_THRESHOLD);
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
        $presentation = ScientificUserPresentation::fromSynthesis($synthesis, true);
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
        $presentation = ScientificUserPresentation::fromSynthesis($synthesis, true);
        $this->assertSame('Supported value is 6609520 t.', $presentation['primary_answer']);
        $this->assertSame(ScientificUserPresentation::HUMAN_ANSWERED, $presentation['human_status']);
        $this->assertSame([], $presentation['sources']);
        $this->assertStringNotContainsString('doi.org', json_encode($presentation));
        $this->assertStringNotContainsString('google.', json_encode($presentation));
    }

    public function test_case_c_supporting_only_does_not_present_a_direct_answer(): void
    {
        $synthesis = $this->synthesisReport(
            answer: 'Supporting-only narrative.',
            gate: 'INSUFFICIENT_DIRECT_EVIDENCE',
            citations: [$this->citation()],
            metadata: [
                'direct_evidence_gate' => 'INSUFFICIENT_DIRECT_EVIDENCE',
                'evidence_sufficient' => false,
            ],
        );
        $this->assertFalse($this->isSufficient($synthesis));
        $presentation = ScientificUserPresentation::fromSynthesis($synthesis, false);
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
        $presentation = ScientificUserPresentation::fromSynthesis($synthesis, false);
        $this->assertNull($presentation['primary_answer']);
    }

    public function test_case_e_no_direct_evidence_remains_insufficient(): void
    {
        $synthesis = $this->synthesisReport(
            answer: 'Insufficient direct scientific evidence was found for a definitive answer.',
            gate: 'INSUFFICIENT_DIRECT_EVIDENCE',
            citations: [],
        );
        $this->assertFalse($this->isSufficient($synthesis));
        $presentation = ScientificUserPresentation::fromSynthesis($synthesis, false);
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
        $presentation = ScientificUserPresentation::fromSynthesis($synthesis, true);
        $this->assertSame('Wheat Production 2022. Value: 6609520 t', $presentation['primary_answer']);
        $this->assertSame([], $presentation['sources']);
        $this->assertFalse($presentation['candidate_selection']['confidence_exposed']);
        $this->assertTrue($presentation['candidate_selection']['directness_unchanged']);
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
     */
    private function synthesisReport(
        string $answer,
        string $gate,
        array $citations,
        array $metadata = [],
    ): AnswerSynthesisExecutionReport {
        return new AnswerSynthesisExecutionReport(
            status: 'scientific_generated',
            performed: true,
            answer: $answer,
            conciseSummary: null,
            detailedExplanation: null,
            keyFindings: [],
            claims: [],
            citations: $citations,
            evidenceReferences: [],
            confidence: 0.8,
            limitations: [],
            uncertainty: null,
            conflicts: [],
            language: 'en',
            researchMetadata: array_merge(['direct_evidence_gate' => $gate], $metadata),
            observability: [],
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
