<?php

namespace App\Services\Agriculture\Diagnosis\KnowledgeBase;

use App\Services\Agriculture\Diagnosis\DiagnosisEvidence;

/**
 * Result of matching a knowledge record against observations/context.
 */
final class DiagnosisKnowledgeMatchResult
{
    /**
     * @param  list<string>  $matchedObservationIds
     * @param  list<string>  $supportingEvidence
     * @param  list<string>  $contradictingEvidence
     * @param  list<DiagnosisEvidence>  $evidence
     * @param  list<string>  $matchReasons
     */
    public function __construct(
        public readonly DiagnosisKnowledgeRecord $record,
        public readonly float $matchScore,
        public readonly string $confidenceBand,
        public readonly string $safetyStatus,
        public readonly array $matchedObservationIds = [],
        public readonly array $supportingEvidence = [],
        public readonly array $contradictingEvidence = [],
        public readonly array $evidence = [],
        public readonly array $matchReasons = [],
        public readonly bool $insufficientEvidence = false,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'record' => $this->record->toArray(),
            'match_score' => round($this->matchScore, 4),
            'confidence_band' => $this->confidenceBand,
            'safety_status' => $this->safetyStatus,
            'matched_observation_ids' => $this->matchedObservationIds,
            'supporting_evidence' => $this->supportingEvidence,
            'contradicting_evidence' => $this->contradictingEvidence,
            'evidence' => array_map(
                static fn (DiagnosisEvidence $item): array => $item->toArray(),
                $this->evidence,
            ),
            'match_reasons' => $this->matchReasons,
            'insufficient_evidence' => $this->insufficientEvidence,
        ];
    }
}
