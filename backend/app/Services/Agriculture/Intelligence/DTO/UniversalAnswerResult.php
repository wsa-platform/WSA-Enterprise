<?php

namespace App\Services\Agriculture\Intelligence\DTO;

/**
 * Backward-compatible extended final answer contract.
 */
final class UniversalAnswerResult
{
    /**
     * @param  list<string>  $providersUsed
     * @param  list<string>  $limitations
     * @param  list<array<string, mixed>>  $citations
     * @param  list<array<string, mixed>>  $webCitations
     * @param  list<array<string, mixed>>  $scientificCitations
     * @param  array<string, mixed>  $evidenceSummary
     * @param  array<string, mixed>  $legacy
     * @param  array<string, mixed>  $observability
     */
    public function __construct(
        public readonly AnswerEligibility $eligibility,
        public readonly ?string $answer,
        public readonly ?string $conciseSummary,
        public readonly array $providersUsed = [],
        public readonly array $limitations = [],
        public readonly array $citations = [],
        public readonly array $webCitations = [],
        public readonly array $scientificCitations = [],
        public readonly array $evidenceSummary = [],
        public readonly ?FusedEvidenceBundle $fusion = null,
        public readonly array $legacy = [],
        public readonly array $observability = [],
        public readonly string $status = 'completed',
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $eligibility = $this->eligibility->toArray();

        return array_merge($this->legacy, [
            'status' => $this->status,
            'answer' => $this->answer,
            'concise_summary' => $this->conciseSummary,
            'answer_status' => $eligibility['answer_status'],
            'web_answer_eligible' => $eligibility['web_answer_eligible'],
            'scientific_answer_eligible' => $eligibility['scientific_answer_eligible'],
            'overall_answer_eligible' => $eligibility['overall_answer_eligible'],
            'eligibility' => $eligibility,
            'providers_used' => $this->providersUsed,
            'limitations' => $this->limitations,
            'citations' => $this->citations,
            'web_citations' => $this->webCitations,
            'scientific_citations' => $this->scientificCitations,
            'web_evidence_summary' => $this->evidenceSummary['web'] ?? [],
            'scientific_evidence_summary' => $this->evidenceSummary['scientific'] ?? [],
            'evidence_summary' => $this->evidenceSummary,
            'fusion' => $this->fusion?->toArray(),
            'universal_orchestrator' => [
                'enabled' => true,
                'version' => '1.0.0',
                'observability' => $this->observability,
            ],
        ]);
    }
}
