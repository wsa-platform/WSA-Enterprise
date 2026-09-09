<?php

namespace App\Services\Agriculture\Intelligence\DTO;

/**
 * Separates architectural vs provider/model vs scientific confidence.
 */
final class FusedEvidenceBundle
{
    /**
     * @param  list<CanonicalAgriculturalResult>  $results
     * @param  list<array<string, mixed>>  $dedupedEvidence
     * @param  list<array<string, mixed>>  $conflicts
     * @param  list<string>  $providersUsed
     * @param  array<string, mixed>  $confidence
     * @param  array<string, mixed>  $summary
     */
    public function __construct(
        public readonly array $results,
        public readonly array $dedupedEvidence = [],
        public readonly array $conflicts = [],
        public readonly array $providersUsed = [],
        public readonly array $confidence = [],
        public readonly array $summary = [],
        public readonly ?WebConsensusResult $webConsensus = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'results' => array_map(
                static fn (CanonicalAgriculturalResult $r): array => $r->toArray(),
                $this->results,
            ),
            'deduped_evidence' => $this->dedupedEvidence,
            'conflicts' => $this->conflicts,
            'providers_used' => $this->providersUsed,
            'confidence' => $this->confidence,
            'summary' => $this->summary,
            'web_consensus' => $this->webConsensus?->toArray(),
        ];
    }
}
