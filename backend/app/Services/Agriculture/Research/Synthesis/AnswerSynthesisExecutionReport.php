<?php

namespace App\Services\Agriculture\Research\Synthesis;

/**
 * Structured Stage 5 answer synthesis execution report.
 */
final class AnswerSynthesisExecutionReport
{
    /**
     * @param  list<ResearchAnswerClaim>  $claims
     * @param  list<ResearchAnswerCitation>  $citations
     * @param  list<string>  $keyFindings
     * @param  list<string>  $limitations
     * @param  list<array<string, mixed>>  $conflicts
     * @param  list<array<string, mixed>>  $evidenceReferences
     * @param  array<string, mixed>  $researchMetadata
     * @param  array<string, mixed>  $observability
     */
    public function __construct(
        public readonly string $status,
        public readonly bool $performed,
        public readonly ?string $answer,
        public readonly ?string $conciseSummary,
        public readonly ?string $detailedExplanation,
        public readonly array $keyFindings,
        public readonly array $claims,
        public readonly array $citations,
        public readonly array $evidenceReferences,
        public readonly float $confidence,
        public readonly array $limitations,
        public readonly ?string $uncertainty,
        public readonly array $conflicts,
        public readonly string $language,
        public readonly array $researchMetadata,
        public readonly array $observability,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'stage' => 5,
            'synthesis' => [
                'performed' => $this->performed,
                'stage' => 5,
                'confidence' => $this->confidence,
                'language' => $this->language,
                'claims_count' => count($this->claims),
                'citations_count' => count($this->citations),
                'conflicts_count' => count($this->conflicts),
            ],
            'answer' => $this->answer,
            'concise_summary' => $this->conciseSummary,
            'detailed_explanation' => $this->detailedExplanation,
            'key_findings' => $this->keyFindings,
            'claims' => array_map(
                static fn (ResearchAnswerClaim $claim): array => $claim->toArray(),
                $this->claims,
            ),
            'citations' => array_map(
                static fn (ResearchAnswerCitation $citation): array => $citation->toArray(),
                $this->citations,
            ),
            'evidence_references' => $this->evidenceReferences,
            'confidence' => $this->confidence,
            'limitations' => $this->limitations,
            'uncertainty' => $this->uncertainty,
            'conflicts' => $this->conflicts,
            'research_metadata' => $this->researchMetadata,
            'observability' => $this->observability,
        ];
    }
}
