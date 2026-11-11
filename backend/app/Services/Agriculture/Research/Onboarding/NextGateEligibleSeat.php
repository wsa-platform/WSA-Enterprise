<?php

namespace App\Services\Agriculture\Research\Onboarding;

use App\Services\Agriculture\Research\Search\Family\ScientificSourceFamilyProfile;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceAccessFamily;

/**
 * One of the R7 next-gate eligible seats (ADR-023 §8.41.2): seventeen seats that may
 * proceed to identity verification, authorized endpoint tests, and — when this mandate
 * is satisfied — selective activation.
 */
final readonly class NextGateEligibleSeat
{
    /**
     * @param  list<string>  $candidateFamilies
     * @param  list<string>  $blockers  machine-readable prerequisite codes still open
     */
    public function __construct(
        public string $adrId,
        public string $displayName,
        public string $sourceKey,
        public ScientificSourceAccessFamily $primaryFamily,
        public array $candidateFamilies,
        public ?string $dualPeerAdrId,
        public SeatActivationState $intendedActivation,
        public array $blockers,
        public ?ScientificSourceFamilyProfile $familyProfile,
        public ?ScientificSourceOnboardingContract $onboardingContract,
        public string $accessNotes,
        public string $licenceNotes,
        public ?string $endpointEvidenceUrl,
    ) {}

    public function isDualPeerDeferred(): bool
    {
        return in_array('dual_peer_runtime_deferred', $this->blockers, true);
    }

    public function isRuntimeActive(): bool
    {
        return $this->intendedActivation === SeatActivationState::ACTIVE
            && $this->onboardingContract?->activationState === SeatActivationState::ACTIVE
            && $this->blockers === [];
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'adr_id' => $this->adrId,
            'display_name' => $this->displayName,
            'source_key' => $this->sourceKey,
            'primary_family' => $this->primaryFamily->value,
            'candidate_families' => $this->candidateFamilies,
            'dual_peer_adr_id' => $this->dualPeerAdrId,
            'intended_activation' => $this->intendedActivation->value,
            'blockers' => $this->blockers,
            'is_runtime_active' => $this->isRuntimeActive(),
            'access_notes' => $this->accessNotes,
            'licence_notes' => $this->licenceNotes,
            'endpoint_evidence_url' => $this->endpointEvidenceUrl,
            'family_profile' => $this->familyProfile?->toArray(),
            'onboarding_decision_id' => $this->onboardingContract?->onboardingDecisionId,
            'onboarding_activation_state' => $this->onboardingContract?->activationState->value,
        ];
    }
}
