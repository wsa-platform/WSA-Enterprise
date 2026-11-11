<?php

namespace App\Services\Agriculture\Research\Capability\CapVer;

use App\Services\Agriculture\Research\Capability\CapabilityAccessMethod;
use App\Services\Agriculture\Research\Capability\CapabilityDimension;
use App\Services\Agriculture\Research\Capability\CapabilityEvidenceFreshness;
use App\Services\Agriculture\Research\Capability\CapabilityInvariantViolation;
use App\Services\Agriculture\Research\Capability\CapabilityRecord;
use App\Services\Agriculture\Research\Capability\CapabilityRecordId;
use App\Services\Agriculture\Research\Capability\CapabilityState;
use App\Services\Agriculture\Research\Capability\CapabilitySubject;
use App\Services\Agriculture\Research\Capability\Persistence\CapabilityStoreRepository;
use App\Services\Agriculture\Research\Identity\AdrMembershipId;
use App\Services\Agriculture\Research\Onboarding\AcceptedOwnerDecisionCatalog;
use App\Services\Agriculture\Research\Onboarding\CurrentMembershipNearReadySeatCatalog;
use App\Services\Agriculture\Research\Onboarding\NextGateEligibleSeat;
use App\Services\Agriculture\Research\Onboarding\NextGateEligibleSeatCatalog;
use App\Services\Agriculture\Research\Onboarding\SeatActivationState;
use DateTimeImmutable;
use Throwable;

/**
 * CapVer workflow for owner-accepted seats (G1-02 / G6-20 / G1-01 / G1-17 / G5-04).
 *
 * Attempts authoritative Capability Store persist via CapabilityStoreRepository.
 * Onboarding-contract VERIFIED authorship follows the GBIF pattern and is recorded
 * in the seat catalogs (immutable decision ids). Never invents VERIFIED without
 * CapVer authorization + evidence.
 */
final class CapVerSeatVerificationService
{
    private readonly CapVerAssessmentIssuer $assessmentIssuer;

    public function __construct(
        private readonly CapabilityStoreRepository $capabilityStore,
        ?CapVerAssessmentRegistry $assessmentRegistry = null,
        ?CapVerAssessmentIssuer $assessmentIssuer = null,
    ) {
        $registry = $assessmentRegistry ?? new CapVerAssessmentRegistry;
        $this->assessmentIssuer = $assessmentIssuer ?? new CapVerAssessmentIssuer($registry);
    }

    public function execute(string $adrId): CapVerSeatOutcome
    {
        $executedAt = (new DateTimeImmutable('now'))->format(DateTimeImmutable::ATOM);
        $seat = $this->resolveSeat($adrId);
        $failures = [];

        if ($seat === null) {
            return $this->failed($adrId, 'unknown', $executedAt, ['seat_not_in_authorized_catalog']);
        }

        if (! AcceptedOwnerDecisionCatalog::onboardingAccepted($adrId)) {
            $failures[] = 'owner_onboarding_accept_not_recorded';
        }
        if (! AcceptedOwnerDecisionCatalog::capVerAuthorized($adrId)) {
            $failures[] = 'owner_capver_not_authorized';
        }

        $spec = $this->seatSpec($adrId);
        if ($spec === null) {
            $failures[] = 'no_capver_spec_for_seat';
        }

        if ($failures !== [] || $spec === null) {
            return $this->failed($adrId, $seat->sourceKey, $executedAt, $failures, $spec);
        }

        $storeStatus = 'SKIPPED';
        $storeDetail = null;
        $subject = CapabilitySubject::seatScoped(AdrMembershipId::fromString($adrId));
        $dimension = CapabilityDimension::accessMethod($spec['access_method']);
        $record = CapabilityRecord::create(
            CapabilityRecordId::fromString($spec['capability_record_id']),
            $subject,
            $dimension,
            CapabilityState::VERIFIED,
            CapabilityEvidenceFreshness::CURRENT,
            snapshotVersion: $spec['snapshot_version'],
            updateMethod: 'capver_authorized_evidence_review',
            verifiedAt: $executedAt,
            observedAt: $spec['observed_at'],
        );

        try {
            $existing = $this->capabilityStore->findCurrentFact($subject, $dimension, false);
            if ($existing !== null
                && $existing->record->state === CapabilityState::VERIFIED
                && $existing->isCurrent()) {
                $storeStatus = 'ALREADY_CURRENT';
                $storeDetail = $existing->record->recordId->value;
            } else {
                // OD-A-ID: CapVer-authorized assessment is mandatory; 2-arg persist is rejected.
                $assessment = $this->assessmentIssuer->issue(
                    $adrId,
                    $dimension,
                    CapabilityState::VERIFIED,
                    $spec['evidence_refs'],
                    new DateTimeImmutable($executedAt),
                );
                $this->capabilityStore->persistCurrent($record, $spec['evidence_refs'], $assessment);
                $storeStatus = 'PERSISTED';
                $storeDetail = $spec['capability_record_id'];
            }
        } catch (CapabilityInvariantViolation $e) {
            $storeStatus = 'FAILED';
            $storeDetail = $e->getMessage();
        } catch (Throwable $e) {
            $storeStatus = 'STORE_UNAVAILABLE';
            $storeDetail = class_basename($e).': '.$e->getMessage();
        }

        $contractAuthored = $seat->onboardingContract?->activationState === SeatActivationState::ACTIVE
            && ($seat->onboardingContract->capabilityDimensionStates['search'] ?? null) === CapabilityState::VERIFIED
            && $seat->onboardingContract->capabilityDecisionIdentity === $spec['capability_decision_identity'];

        // Cap Store persistence is mandatory for CapVer activation-pass.
        // STORE_UNAVAILABLE must never be reported as successful CapVer persistence.
        $capabilityPersisted = in_array($storeStatus, ['PERSISTED', 'ALREADY_CURRENT'], true);
        $passed = $failures === [] && $contractAuthored && $capabilityPersisted;

        return new CapVerSeatOutcome(
            adrId: $adrId,
            sourceKey: $seat->sourceKey,
            capabilityDecisionIdentity: $spec['capability_decision_identity'],
            capabilityRecordId: $spec['capability_record_id'],
            evidenceRefs: $spec['evidence_refs'],
            prerequisitesSatisfied: true,
            prerequisiteFailures: [],
            contractVerifiedAuthored: $contractAuthored,
            capabilityStoreStatus: $storeStatus,
            capabilityStoreDetail: $storeDetail,
            capVerPassedForActivation: $passed,
            executedAt: $executedAt,
        );
    }

    /**
     * @return list<CapVerSeatOutcome>
     */
    public function executeAcceptedSeats(): array
    {
        $out = [];
        foreach (AcceptedOwnerDecisionCatalog::capVerAuthorizedSeats() as $adrId) {
            $out[] = $this->execute($adrId);
        }

        return $out;
    }

    private function resolveSeat(string $adrId): ?NextGateEligibleSeat
    {
        return CurrentMembershipNearReadySeatCatalog::get($adrId)
            ?? NextGateEligibleSeatCatalog::get($adrId);
    }

    /**
     * @return array{
     *     access_method: CapabilityAccessMethod,
     *     capability_decision_identity: string,
     *     capability_record_id: string,
     *     evidence_refs: list<string>,
     *     snapshot_version: string,
     *     observed_at: string
     * }|null
     */
    private function seatSpec(string $adrId): ?array
    {
        return CapVerAuthorizedSeatSpecCatalog::forAdrId($adrId);
    }

    /**
     * @param  list<string>  $failures
     * @param  array<string, mixed>|null  $spec
     */
    private function failed(
        string $adrId,
        string $sourceKey,
        string $executedAt,
        array $failures,
        ?array $spec = null,
    ): CapVerSeatOutcome {
        return new CapVerSeatOutcome(
            adrId: $adrId,
            sourceKey: $sourceKey,
            capabilityDecisionIdentity: is_array($spec) ? (string) $spec['capability_decision_identity'] : '',
            capabilityRecordId: is_array($spec) ? (string) $spec['capability_record_id'] : '',
            evidenceRefs: is_array($spec) ? $spec['evidence_refs'] : [],
            prerequisitesSatisfied: false,
            prerequisiteFailures: $failures,
            contractVerifiedAuthored: false,
            capabilityStoreStatus: 'SKIPPED',
            capabilityStoreDetail: null,
            capVerPassedForActivation: false,
            executedAt: $executedAt,
        );
    }
}
