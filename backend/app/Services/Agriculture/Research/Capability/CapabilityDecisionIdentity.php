<?php

namespace App\Services\Agriculture\Research\Capability;

/**
 * capability_decision_identity — Cap evaluation snapshot for later Path/B7 (Design §20).
 *
 * Cap owns content; Path eligibility/status remain Path-owned (B1).
 */
final readonly class CapabilityDecisionIdentity
{
    /**
     * @param  list<array{dimension_key: string, family: string, code: string, state: string, limitation_text: ?string, missing_record: bool, evidence_freshness_class: string}>  $perCellOutcomes
     * @param  list<string>  $optionalEnrichmentKeys
     */
    private function __construct(
        public string $decisionId,
        public CapabilitySubject $subject,
        public array $perCellOutcomes,
        public array $optionalEnrichmentKeys,
        public CapabilityAndResultClass $andResultClass,
        public bool $hasStaleFlags,
        public string $snapshotVersion,
        public string $decidedAt,
        public bool $seatOverrideApplied,
    ) {}

    /**
     * @param  list<array{dimension_key: string, family: string, code: string, state: string, limitation_text: ?string, missing_record: bool, evidence_freshness_class: string}>  $perCellOutcomes
     * @param  list<string>  $optionalEnrichmentKeys
     */
    public static function create(
        string $decisionId,
        CapabilitySubject $subject,
        array $perCellOutcomes,
        array $optionalEnrichmentKeys,
        CapabilityAndResultClass $andResultClass,
        bool $hasStaleFlags,
        string $snapshotVersion,
        string $decidedAt,
        bool $seatOverrideApplied = false,
    ): self {
        $id = trim($decisionId);
        if ($id === '') {
            throw new CapabilityInvariantViolation('capability_decision_identity decision_id must be non-empty.');
        }

        $snap = trim($snapshotVersion);
        if ($snap === '') {
            $snap = CapabilityRecord::UNKNOWN_METADATA;
        }

        $at = trim($decidedAt);
        if ($at === '') {
            throw new CapabilityInvariantViolation('capability_decision_identity decided_at must be non-empty.');
        }

        return new self(
            $id,
            $subject,
            $perCellOutcomes,
            $optionalEnrichmentKeys,
            $andResultClass,
            $hasStaleFlags,
            $snap,
            $at,
            $seatOverrideApplied,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'decision_id' => $this->decisionId,
            'subject_adr_id' => $this->subject->adrId->value,
            'subject_canonical_identity_id' => $this->subject->canonicalIdentityId?->value,
            'required_cell_outcomes' => $this->perCellOutcomes,
            'optional_enrichments' => $this->optionalEnrichmentKeys,
            'and_result_class' => $this->andResultClass->value,
            'stale_flags' => $this->hasStaleFlags,
            'snapshot_version' => $this->snapshotVersion,
            'decided_at' => $this->decidedAt,
            'seat_override_applied' => $this->seatOverrideApplied,
        ];
    }
}
