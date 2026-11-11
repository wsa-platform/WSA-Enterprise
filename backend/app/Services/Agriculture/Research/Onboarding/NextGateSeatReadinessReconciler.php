<?php

namespace App\Services\Agriculture\Research\Onboarding;

use App\Services\Agriculture\Research\Capability\CapabilityState;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceOperation;

/**
 * Reconciles the seventeen R7 next-gate seats against the live onboarding catalog.
 *
 * Does not activate seats. Does not invent identity verification.
 */
final class NextGateSeatReadinessReconciler
{
    /**
     * @return list<NextGateSeatReadinessDisposition>
     */
    public function reconcile(): array
    {
        $out = [];
        foreach (NextGateEligibleSeatCatalog::ADR_IDS as $adrId) {
            $seat = NextGateEligibleSeatCatalog::get($adrId);
            if ($seat === null) {
                continue;
            }
            $out[] = $this->dispose($seat);
        }

        return $out;
    }

    /** @return array<string, mixed> */
    public function summary(): array
    {
        $rows = $this->reconcile();
        $byClass = [];
        foreach ($rows as $row) {
            $byClass[$row->readinessClass] = ($byClass[$row->readinessClass] ?? 0) + 1;
        }

        return [
            'schema_id' => 'adr023.next_gate_17_readiness_reconciliation.v1',
            'authority' => NextGateEligibleSeatCatalog::AUTHORITY,
            'seat_count' => count($rows),
            'runtime_active_count' => count(array_filter($rows, static fn (NextGateSeatReadinessDisposition $d): bool => $d->runtimeActive)),
            'ready_for_activation_count' => count(array_filter($rows, static fn (NextGateSeatReadinessDisposition $d): bool => $d->readyForActivation)),
            'by_readiness_class' => $byClass,
            'seats' => array_map(static fn (NextGateSeatReadinessDisposition $d): array => $d->toArray(), $rows),
            'policy' => [
                'http_200_is_not_readiness' => true,
                'unresolved_licence_is_not_permission' => true,
                'automatic_activation_when_all_gates_pass' => true,
                'local_index_empty_is_not_activation' => true,
            ],
        ];
    }

    private function dispose(NextGateEligibleSeat $seat): NextGateSeatReadinessDisposition
    {
        $contract = $seat->onboardingContract;
        $licensing = $contract?->licensing;
        $licenceDeclared = $licensing !== null && ! $licensing->isFullyUnresolved();
        $searchState = $contract?->capabilityDimensionStates[ScientificSourceOperation::SEARCH->value] ?? null;
        $capabilityVerified = $searchState === CapabilityState::VERIFIED;
        // Owner-bound canonical identity is minted only on ACTIVE contracts (currently G5-04).
        $identityOwnerVerified = $seat->isRuntimeActive()
            && ($contract?->canonicalIdentityId !== null);

        $ready = $seat->isRuntimeActive()
            || (
                $identityOwnerVerified
                && $licenceDeclared
                && $capabilityVerified
                && $seat->blockers === []
                && $seat->familyProfile !== null
            );

        $class = $this->classify($seat, $ready, $licenceDeclared, $capabilityVerified);
        $action = $this->smallestAction($seat, $licenceDeclared, $capabilityVerified, $identityOwnerVerified);
        $owner = [];
        if (! $identityOwnerVerified && ! $seat->isRuntimeActive()) {
            // Documentary identity can be sufficient for engineering; formal SAME_AS binding remains owner-gated when blockers cite identity_not_verified.
            if (in_array('identity_not_verified', $seat->blockers, true)) {
                $owner[] = 'owner_identity_binding';
            }
        }
        if (! $licenceDeclared) {
            $owner[] = 'licence_acceptance_for_intended_usage';
        }
        if (in_array('endpoint_not_tested', $seat->blockers, true) || in_array('endpoint_blocked', $seat->blockers, true)) {
            $owner[] = 'authorize_bounded_endpoint_probe';
        }
        if (
            in_array('local_index_not_built', $seat->blockers, true)
            || in_array('ods_bulk_ingest_pending', $seat->blockers, true)
            || in_array('ods_official_artifact_unresolvable', $seat->blockers, true)
            || in_array('full_ods_corpus_ingest_pending', $seat->blockers, true)
            || in_array('static_dump_ingest_pending', $seat->blockers, true)
            || in_array('full_relational_csv_projection_pending', $seat->blockers, true)
            || in_array('local_index_store_not_deployed', $seat->blockers, true)
        ) {
            $owner[] = 'authorize_local_index_store_deploy_and_ingest';
        }

        $evidence = array_values(array_filter([
            $seat->endpointEvidenceUrl,
            $contract?->onboardingDecisionId,
            NextGateEligibleSeatCatalog::AUTHORITY,
        ]));

        return new NextGateSeatReadinessDisposition(
            adrId: $seat->adrId,
            displayName: $seat->displayName,
            sourceKey: $seat->sourceKey,
            activation: $seat->intendedActivation,
            runtimeActive: $seat->isRuntimeActive(),
            identityOwnerVerified: $identityOwnerVerified,
            licenceDeclared: $licenceDeclared,
            licenceFullyUnresolved: $licensing?->isFullyUnresolved() ?? true,
            capabilityVerifiedForSearch: $capabilityVerified,
            adapterProfilePresent: $seat->familyProfile !== null,
            readyForActivation: $ready,
            blockers: $seat->blockers,
            candidateFamilies: $seat->candidateFamilies,
            readinessClass: $class,
            smallestRemainingAction: $action,
            evidenceRefs: $evidence,
            ownerDecisionsRequired: array_values(array_unique($owner)),
        );
    }

    private function classify(
        NextGateEligibleSeat $seat,
        bool $ready,
        bool $licenceDeclared,
        bool $capabilityVerified,
    ): string {
        if ($seat->isRuntimeActive()) {
            return 'ACTIVE_RUNTIME';
        }
        if ($seat->intendedActivation === SeatActivationState::DEFERRED) {
            return 'DEFERRED_DUAL_PEER';
        }
        if ($ready) {
            return 'READY_AWAITING_ACTIVATION_MANDATE';
        }
        $awaitingIndex = $licenceDeclared
            && $seat->familyProfile !== null
            && (
                in_array('local_index_not_built', $seat->blockers, true)
                || in_array('ods_bulk_ingest_pending', $seat->blockers, true)
                || in_array('ods_official_artifact_unresolvable', $seat->blockers, true)
                || in_array('full_ods_corpus_ingest_pending', $seat->blockers, true)
                || in_array('static_dump_ingest_pending', $seat->blockers, true)
                || in_array('full_relational_csv_projection_pending', $seat->blockers, true)
                || in_array('local_index_store_not_deployed', $seat->blockers, true)
            );
        if ($awaitingIndex) {
            return 'LICENCE_CLEARED_AWAITING_LOCAL_INDEX';
        }
        if ($seat->familyProfile !== null && $licenceDeclared) {
            return 'SCAFFOLDED_LICENCE_DECLARED_NOT_READY';
        }
        if ($seat->familyProfile !== null) {
            return 'SCAFFOLDED_NOT_READY';
        }
        if (! $capabilityVerified) {
            return 'BLOCKED_CAPABILITY_OR_ACCESS';
        }

        return 'BLOCKED';
    }

    private function smallestAction(
        NextGateEligibleSeat $seat,
        bool $licenceDeclared,
        bool $capabilityVerified,
        bool $identityOwnerVerified,
    ): string {
        if ($seat->isRuntimeActive()) {
            return 'Preserve ACTIVE; no further activation in this mission.';
        }
        if ($seat->intendedActivation === SeatActivationState::DEFERRED) {
            return 'Keep dual-peer DEFERRED unless owner changes dual-runtime policy.';
        }
        if (in_array('local_index_store_not_deployed', $seat->blockers, true)) {
            return 'Deploy durable scientific-record-store migration under authorized change control, then ingest.';
        }
        if (in_array('full_ods_corpus_ingest_pending', $seat->blockers, true)) {
            return 'Continue crawling AGRIS.ODS.xml provider downloadURLs into LocalIndex under operator-approved budget.';
        }
        if (in_array('ods_official_artifact_unresolvable', $seat->blockers, true)) {
            return 'Obtain current official ODS artifact URL from FAO.';
        }
        if (
            in_array('local_index_not_built', $seat->blockers, true)
            || in_array('ods_bulk_ingest_pending', $seat->blockers, true)
            || in_array('static_dump_ingest_pending', $seat->blockers, true)
            || in_array('full_relational_csv_projection_pending', $seat->blockers, true)
        ) {
            return 'Ingest authorized bulk/static/OAI snapshot into local index (empty index must not be activated as live search).';
        }
        if (! $licenceDeclared) {
            return 'Owner accepts licence text for intended retrieval/storage model.';
        }
        if (in_array('endpoint_blocked', $seat->blockers, true)) {
            return 'Resolve WAF/access block or choose alternate authorized access path.';
        }
        if (! $capabilityVerified) {
            return 'Authorize bounded capability probe and record VERIFIED search evidence.';
        }
        if (! $identityOwnerVerified && in_array('identity_not_verified', $seat->blockers, true)) {
            return 'Owner binds canonical identity for '.$seat->adrId.'.';
        }

        return 'Clear remaining blockers: '.implode(', ', $seat->blockers);
    }
}
