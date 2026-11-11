<?php

namespace App\Services\Agriculture\Research\Onboarding;

/**
 * Evidence-backed disposition for one next-gate seat.
 *
 * Keeps availability, capability, licensing, identity, readiness, and activation distinct.
 * HTTP 200 alone never implies readiness or activation.
 */
final readonly class NextGateSeatReadinessDisposition
{
    /**
     * @param  list<string>  $blockers
     * @param  list<string>  $candidateFamilies
     * @param  list<string>  $evidenceRefs
     * @param  list<string>  $ownerDecisionsRequired
     */
    public function __construct(
        public string $adrId,
        public string $displayName,
        public string $sourceKey,
        public SeatActivationState $activation,
        public bool $runtimeActive,
        public bool $identityOwnerVerified,
        public bool $licenceDeclared,
        public bool $licenceFullyUnresolved,
        public bool $capabilityVerifiedForSearch,
        public bool $adapterProfilePresent,
        public bool $readyForActivation,
        public array $blockers,
        public array $candidateFamilies,
        public string $readinessClass,
        public string $smallestRemainingAction,
        public array $evidenceRefs,
        public array $ownerDecisionsRequired,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'adr_id' => $this->adrId,
            'display_name' => $this->displayName,
            'source_key' => $this->sourceKey,
            'activation' => $this->activation->value,
            'runtime_active' => $this->runtimeActive,
            'identity_owner_verified' => $this->identityOwnerVerified,
            'licence_declared' => $this->licenceDeclared,
            'licence_fully_unresolved' => $this->licenceFullyUnresolved,
            'capability_verified_for_search' => $this->capabilityVerifiedForSearch,
            'adapter_profile_present' => $this->adapterProfilePresent,
            'ready_for_activation' => $this->readyForActivation,
            'blockers' => $this->blockers,
            'candidate_families' => $this->candidateFamilies,
            'readiness_class' => $this->readinessClass,
            'smallest_remaining_action' => $this->smallestRemainingAction,
            'evidence_refs' => $this->evidenceRefs,
            'owner_decisions_required' => $this->ownerDecisionsRequired,
        ];
    }
}
