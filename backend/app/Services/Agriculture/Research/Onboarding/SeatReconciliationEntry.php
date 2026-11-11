<?php

namespace App\Services\Agriculture\Research\Onboarding;

use App\Services\Agriculture\Research\Search\Registry\ScientificSourceAccessFamily;

/**
 * The reconciled onboarding state of one membership seat against the repository.
 *
 * Every dimension in DIMENSIONS is present, so a seat cannot be reported with a silently
 * missing dimension. The entry carries the seat's onboarding decision record; it never
 * binds, registers or activates anything.
 */
final readonly class SeatReconciliationEntry
{
    public const DIMENSIONS = [
        'identity', 'membership', 'family', 'adapter_compatibility',
        'search', 'pagination', 'filtering', 'full_text', 'bulk_harvest',
        'query_mapping', 'authentication', 'credential_settings', 'access_method',
        'licence', 'retention', 'indexing', 'excerpt_display', 'user_facing_use',
        'provenance', 'record_identity', 'dedupe', 'ingestion', 'freshness', 'deletion_withdrawal',
        'implementation', 'test_coverage',
    ];

    /** The dimensions that describe what the source can do, as opposed to who or whose terms. */
    public const CAPABILITY_DIMENSIONS = ['search', 'pagination', 'filtering', 'full_text', 'bulk_harvest', 'query_mapping'];

    /**
     * @param  array<string, SeatOnboardingDimension>  $dimensions  exactly the DIMENSIONS keys
     * @param  list<ScientificSourceAccessFamily>  $candidateFamilies  families named by documented modalities
     * @param  list<OnboardingBlocker>  $blockers
     * @param  list<string>  $profileNameCorrespondences  query-profile keys whose name matches the seat; not SAME_AS
     */
    public function __construct(
        public string $adrId,
        public string $groupId,
        public int $ordinal,
        public string $displayName,
        public ?string $dualPeerAdrId,
        public string $evidenceBasis,
        public string $evidenceAdrId,
        public array $dimensions,
        public array $candidateFamilies,
        public array $blockers,
        public OnboardingEvidenceStatus $status,
        public ScientificSourceOnboardingContract $contract,
        public array $profileNameCorrespondences = [],
        public ?string $nextAction = null,
    ) {
        $keys = array_keys($dimensions);
        if (array_diff(self::DIMENSIONS, $keys) !== [] || array_diff($keys, self::DIMENSIONS) !== []) {
            throw new OnboardingContractViolation("Seat '{$adrId}' must carry exactly the reconciliation dimensions.");
        }
        foreach ($dimensions as $dimension) {
            if (! $dimension instanceof SeatOnboardingDimension) {
                throw new OnboardingContractViolation("Seat '{$adrId}' dimensions must be SeatOnboardingDimension values.");
            }
        }
        if ($contract->adrId->value !== $adrId) {
            throw new OnboardingContractViolation("Onboarding decision for '{$contract->adrId->value}' cannot describe seat '{$adrId}'.");
        }
        if ($status === OnboardingEvidenceStatus::VERIFIED && $blockers !== []) {
            throw new OnboardingContractViolation("Seat '{$adrId}' cannot be VERIFIED while it has blockers.");
        }
    }

    public function dimension(string $key): SeatOnboardingDimension
    {
        return $this->dimensions[$key] ?? throw new OnboardingContractViolation("Unknown dimension '{$key}'.");
    }

    public function hasBlocker(OnboardingBlocker $blocker): bool
    {
        return in_array($blocker, $this->blockers, true);
    }

    public function familyState(): SeatFamilyState
    {
        return SeatFamilyState::of($this->candidateFamilies);
    }

    /**
     * Evidence about the source, kept apart from whether it may be onboarded and whether it runs.
     * Documented evidence never implies readiness, and readiness never implies activation.
     *
     * @return array<string, string>
     */
    public function evidenceAxes(): array
    {
        $capability = array_map(fn (string $key): OnboardingEvidenceStatus => $this->dimensions[$key]->status, self::CAPABILITY_DIMENSIONS);

        return [
            'identity_evidence' => $this->dimensions['identity']->status->value,
            'access_evidence' => $this->dimensions['access_method']->status->value,
            'licensing_evidence' => $this->dimensions['licence']->status->value,
            'licensing_declaration' => $this->contract->toArray()['license_state'],
            'capability_evidence' => in_array(OnboardingEvidenceStatus::PARTIALLY_EVIDENCED, $capability, true)
                ? OnboardingEvidenceStatus::PARTIALLY_EVIDENCED->value
                : OnboardingEvidenceStatus::UNVERIFIED->value,
            'capability_decision' => $this->contract->capabilityDecisionIdentity ?? 'NONE',
            'documentary_evidence_status' => $this->status->value,
            'onboarding_readiness' => $this->contract->activationState->value,
            'operational_activation' => $this->contract->activationState === SeatActivationState::ACTIVE ? 'ACTIVE' : 'INACTIVE',
        ];
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $dimensions = [];
        foreach (self::DIMENSIONS as $key) {
            $dimensions[$key] = $this->dimensions[$key]->toArray();
        }

        return [
            'adr_id' => $this->adrId,
            'group_id' => $this->groupId,
            'ordinal' => $this->ordinal,
            'display_name' => $this->displayName,
            'dual_peer_adr_id' => $this->dualPeerAdrId,
            'evidence_basis' => $this->evidenceBasis,
            'evidence_adr_id' => $this->evidenceAdrId,
            'documentary_evidence_status' => $this->status->value,
            'evidence_axes' => $this->evidenceAxes(),
            'family_state' => $this->familyState()->value,
            'candidate_families' => array_map(static fn (ScientificSourceAccessFamily $f): string => $f->value, $this->candidateFamilies),
            'blockers' => array_map(static fn (OnboardingBlocker $b): string => $b->value, $this->blockers),
            'profile_name_correspondences' => $this->profileNameCorrespondences,
            'dimensions' => $dimensions,
            'onboarding_decision' => $this->contract->toArray(),
            'next_action' => $this->nextAction,
        ];
    }
}
