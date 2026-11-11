<?php

namespace App\Services\Agriculture\Research\Capability\CapVer;

/**
 * Real CapVer execution result for one seat. Authorization ≠ success.
 *
 * @phpstan-type CapVerStoreStatus 'PERSISTED'|'ALREADY_CURRENT'|'STORE_UNAVAILABLE'|'FAILED'|'SKIPPED'
 */
final readonly class CapVerSeatOutcome
{
    /**
     * @param  list<string>  $evidenceRefs
     * @param  list<string>  $prerequisiteFailures
     * @param  CapVerStoreStatus  $capabilityStoreStatus
     */
    public function __construct(
        public string $adrId,
        public string $sourceKey,
        public string $capabilityDecisionIdentity,
        public string $capabilityRecordId,
        public array $evidenceRefs,
        public bool $prerequisitesSatisfied,
        public array $prerequisiteFailures,
        public bool $contractVerifiedAuthored,
        public string $capabilityStoreStatus,
        public ?string $capabilityStoreDetail,
        public bool $capVerPassedForActivation,
        public string $executedAt,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'adr_id' => $this->adrId,
            'source_key' => $this->sourceKey,
            'capability_decision_identity' => $this->capabilityDecisionIdentity,
            'capability_record_id' => $this->capabilityRecordId,
            'evidence_refs' => $this->evidenceRefs,
            'prerequisites_satisfied' => $this->prerequisitesSatisfied,
            'prerequisite_failures' => $this->prerequisiteFailures,
            'contract_verified_authored' => $this->contractVerifiedAuthored,
            'capability_store_status' => $this->capabilityStoreStatus,
            'capability_store_detail' => $this->capabilityStoreDetail,
            'contract_verified_authored' => $this->contractVerifiedAuthored,
            'capability_persisted' => in_array($this->capabilityStoreStatus, ['PERSISTED', 'ALREADY_CURRENT'], true),
            'capver_passed_for_activation' => $this->capVerPassedForActivation,
            'executed_at' => $this->executedAt,
            'note' => 'Authorization ≠ CapVer success. contract_verified_authored is onboarding-contract VERIFIED authorship; capability_persisted requires Cap Store PERSISTED|ALREADY_CURRENT. STORE_UNAVAILABLE is never activation-pass.',
        ];
    }
}
