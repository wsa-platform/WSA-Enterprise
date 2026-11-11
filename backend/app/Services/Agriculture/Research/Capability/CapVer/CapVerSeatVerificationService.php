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
        return match ($adrId) {
            'G1-02' => [
                'access_method' => CapabilityAccessMethod::OAI_PMH,
                'capability_decision_identity' => 'cap-r11-20261018-G1-02-search',
                'capability_record_id' => 'cap-rec-r11-20261018-G1-02-oai_pmh',
                'evidence_refs' => [
                    CurrentMembershipNearReadySeatCatalog::ENDPOINT_EVIDENCE_ORGANIC_EPRINTS,
                    'IC-ER 2026-10-13 G1-02',
                    'LocalIndex GEN=2 ACTIVE_ROWS=189 cross-process probe 2026-10-18',
                    'OD-G1-02-ONBOARDING-ACTIVE ACCEPT + OD-CAPVER-ACCEPTED-SEATS AUTHORIZE',
                ],
                'snapshot_version' => 'organic_eprints.oai_pmh.search.v1',
                'observed_at' => '2026-10-18T12:00:00+00:00',
            ],
            'G6-20' => [
                'access_method' => CapabilityAccessMethod::API,
                'capability_decision_identity' => 'cap-r11-20261018-G6-20-search',
                'capability_record_id' => 'cap-rec-r11-20261018-G6-20-api',
                'evidence_refs' => [
                    CurrentMembershipNearReadySeatCatalog::ENDPOINT_EVIDENCE_BHL,
                    'IC-ER 2026-10-13 G6-20',
                    'Live PublicationSearch Status=ok 2026-10-18 (key via config; value never archived)',
                    'OD-G6-20-ONBOARDING-ACTIVE ACCEPT + OD-CAPVER-ACCEPTED-SEATS AUTHORIZE',
                ],
                'snapshot_version' => 'bhl.rest_api.publication_search.v1',
                'observed_at' => '2026-10-18T12:00:00+00:00',
            ],
            'G1-01' => [
                'access_method' => CapabilityAccessMethod::BULK_DOWNLOAD,
                'capability_decision_identity' => 'cap-r12-20261018-G1-01-search',
                'capability_record_id' => 'cap-rec-r12-20261018-G1-01-bulk',
                'evidence_refs' => [
                    NextGateEligibleSeatCatalog::ENDPOINT_EVIDENCE_AGRIS,
                    'IC-ER 2026-10-13 G1-01',
                    'LocalIndex GEN=4 ACTIVE_ROWS=1317 bounded ODS subset 2026-10-18',
                    'OD-G1-01-SCOPE-BOUNDED-SUBSET + OD-G1-01-ONBOARDING-ACTIVE + OD-CAPVER-G1-01-G1-17 AUTHORIZE',
                ],
                'snapshot_version' => 'agris.bulk_dataset.bounded_ods.search.v1',
                'observed_at' => '2026-10-18T12:37:57+00:00',
            ],
            'G1-17' => [
                'access_method' => CapabilityAccessMethod::STATIC_DOWNLOAD,
                'capability_decision_identity' => 'cap-r12-20261018-G1-17-search',
                'capability_record_id' => 'cap-rec-r12-20261018-G1-17-static',
                'evidence_refs' => [
                    NextGateEligibleSeatCatalog::ENDPOINT_EVIDENCE_DR_DUKE,
                    'IC-ER 2026-10-13 G1-17',
                    'LocalIndex GEN=2 ACTIVE_ROWS=1500 limited projection 2026-10-18',
                    'FARMACY≠FARMACY_NEW; ETHNOBOT traditional-use only',
                    'OD-G1-17-SCOPE-LIMITED-PROJECTION + OD-G1-17-ONBOARDING-ACTIVE + OD-CAPVER-G1-01-G1-17 AUTHORIZE',
                ],
                'snapshot_version' => 'dr_duke.static_dataset.limited_projection.search.v1',
                'observed_at' => '2026-10-18T11:23:54+00:00',
            ],
            'G5-04' => [
                'access_method' => CapabilityAccessMethod::API,
                'capability_decision_identity' => 'cap-r9-20261016-G5-04-search',
                'capability_record_id' => 'cap-rec-r9-20261016-G5-04-api',
                'evidence_refs' => [
                    NextGateEligibleSeatCatalog::ENDPOINT_EVIDENCE_GBIF,
                    'IC-ER 2026-10-13 G5-04',
                    'onb-r9-20261016-G5-04 ACTIVE species-search contract',
                    'ADR-023 §8.66 scoped CapVer authorization 2026-10-22',
                    'Live GET /v1/species/search q=Triticum HTTP 200 2026-10-22T06:33:36Z usageKey=315051481',
                ],
                'snapshot_version' => 'gbif.rest_api.species_search.v1',
                'observed_at' => '2026-10-22T06:33:36+00:00',
            ],
            default => null,
        };
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
