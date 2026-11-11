<?php

namespace App\Services\Agriculture\Research\Onboarding;

use App\Services\Agriculture\Research\Search\Registry\ScientificSourceAccessFamily;

/**
 * The reconciliation of every membership seat, plus the integrity findings of the inputs.
 */
final readonly class ScientificSourceOnboardingReconciliation
{
    public const SCHEMA_ID = 'adr023.source_onboarding_reconciliation.v2';

    /** Blockers every seat carries today; they do not distinguish one seat from another. */
    public const UNIVERSAL_BLOCKERS = [
        OnboardingBlocker::IDENTITY_NOT_VERIFIED,
        OnboardingBlocker::INTEGRATION_UNCLASSIFIED,
        OnboardingBlocker::ENDPOINT_NOT_TESTED,
        OnboardingBlocker::LICENCE_UNRESOLVED,
        OnboardingBlocker::CAPABILITY_UNVERIFIED,
        OnboardingBlocker::ONBOARDING_DECISION_NOT_ACCEPTED,
    ];

    /**
     * @param  array<string, SeatReconciliationEntry>  $entries  keyed by adr_id, register order
     * @param  list<OnboardingIntegrityFinding>  $findings
     * @param  array<string, string>  $inputs  input name => sha256 or revision
     */
    public function __construct(
        public array $entries,
        public array $findings,
        public array $inputs,
        public string $reconciledAt,
    ) {}

    public function entry(string $adrId): ?SeatReconciliationEntry
    {
        return $this->entries[$adrId] ?? null;
    }

    /** @return list<OnboardingIntegrityFinding> */
    public function findingsWithCode(string $code): array
    {
        return array_values(array_filter($this->findings, static fn (OnboardingIntegrityFinding $f): bool => $f->code === $code));
    }

    /** @return list<OnboardingIntegrityFinding> */
    public function errors(): array
    {
        return array_values(array_filter($this->findings, static fn (OnboardingIntegrityFinding $f): bool => $f->severity === OnboardingIntegrityFinding::ERROR));
    }

    /** @return array<string, mixed> */
    public function summary(): array
    {
        $status = array_fill_keys(array_map(static fn (OnboardingEvidenceStatus $s): string => $s->value, OnboardingEvidenceStatus::cases()), 0);
        $familyStates = array_fill_keys(array_map(static fn (SeatFamilyState $s): string => $s->value, SeatFamilyState::cases()), 0);
        $activation = [];
        $operational = [];
        $blockers = [];
        $families = [];
        $seatsWithFamily = 0;
        $dimensions = [];
        foreach ($this->entries as $entry) {
            $status[$entry->status->value]++;
            $familyStates[$entry->familyState()->value]++;
            $activation[$entry->contract->activationState->value] = ($activation[$entry->contract->activationState->value] ?? 0) + 1;
            $axes = $entry->evidenceAxes();
            $operational[$axes['operational_activation']] = ($operational[$axes['operational_activation']] ?? 0) + 1;
            foreach ($entry->blockers as $blocker) {
                $blockers[$blocker->value] = ($blockers[$blocker->value] ?? 0) + 1;
            }
            $seatsWithFamily += $entry->candidateFamilies === [] ? 0 : 1;
            foreach ($entry->candidateFamilies as $family) {
                $families[$family->value] = ($families[$family->value] ?? 0) + 1;
            }
            foreach ($entry->dimensions as $key => $dimension) {
                $dimensions[$key][$dimension->status->value] = ($dimensions[$key][$dimension->status->value] ?? 0) + 1;
            }
        }
        ksort($activation);
        ksort($operational);
        ksort($blockers);
        ksort($families);
        $severities = [];
        foreach ($this->findings as $finding) {
            $severities[$finding->severity] = ($severities[$finding->severity] ?? 0) + 1;
        }

        return [
            'seats' => count($this->entries),
            'documentary_evidence_status' => $status,
            'family_state' => $familyStates,
            'onboarding_readiness' => $activation,
            'operational_activation' => $operational,
            'blockers' => $blockers,
            'candidate_family_assignments' => [
                'counting_unit' => 'seat-family pair: a seat may document several families or none, so this does not sum to the seat count',
                'seats_with_any_family' => $seatsWithFamily,
                'total' => array_sum($families),
                'by_family' => $families,
            ],
            'dimensions' => $dimensions,
            'findings_by_severity' => $severities,
        ];
    }

    /**
     * Why the local-index blocker count differs from the harvest/bulk family count, seat by seat.
     *
     * @return array<string, mixed>
     */
    public function localIndexReconciliation(): array
    {
        $assignments = [];
        $withLocal = [];
        $several = [];
        $remoteAlternative = [];
        $blocked = [];
        $nextGate = [];
        $held = [];
        foreach ($this->entries as $adrId => $entry) {
            $local = array_values(array_filter($entry->candidateFamilies, static fn (ScientificSourceAccessFamily $f): bool => in_array($f, SeatFamilyState::LOCAL_INDEX_FAMILIES, true)));
            if ($local === []) {
                continue;
            }
            foreach ($local as $family) {
                $assignments[$family->value] = ($assignments[$family->value] ?? 0) + 1;
            }
            $withLocal[] = $adrId;
            if (count($local) > 1) {
                $several[$adrId] = array_map(static fn (ScientificSourceAccessFamily $f): string => $f->value, $local);
            }
            if ($entry->familyState() === SeatFamilyState::REMOTE_API_AND_LOCAL_INDEX) {
                $remoteAlternative[] = $adrId;
            }
            if ($entry->hasBlocker(OnboardingBlocker::LOCAL_INDEX_STORE_NOT_DEPLOYED)) {
                $blocked[] = $adrId;
            }
            $distinct = array_values(array_filter(
                $entry->blockers,
                static fn (OnboardingBlocker $b): bool => ! in_array($b, self::UNIVERSAL_BLOCKERS, true) && $b !== OnboardingBlocker::LOCAL_INDEX_STORE_NOT_DEPLOYED,
            ));
            if ($distinct === []) {
                $nextGate[] = $adrId;
            } else {
                $held[$adrId] = array_map(static fn (OnboardingBlocker $b): string => $b->value, $distinct);
            }
        }
        ksort($assignments);

        return [
            'rule' => 'local_index_store_not_deployed applies only when every implemented search path of the seat needs the local index (family_state local_index); a documented REST API is an alternative path.',
            'family_assignments' => $assignments,
            'family_assignment_total' => array_sum($assignments),
            'seats_with_local_index_family' => $withLocal,
            'seats_with_several_local_index_families' => $several,
            'seats_with_remote_api_alternative' => $remoteAlternative,
            'store_not_deployed_blocked_seats' => $blocked,
            'next_gate_rule' => 'May proceed to the next gate (owner identity verification and an authorized endpoint test) when no blocker beyond the universal ones and the undeployed store remains. No seat may be activated.',
            'next_gate_candidates' => $nextGate,
            'held_by_seat_specific_blockers' => $held,
        ];
    }

    /** @return list<array<string, string>> */
    public function warningDispositions(): array
    {
        $dispositions = [];
        foreach ($this->findings as $finding) {
            if ($finding->severity === OnboardingIntegrityFinding::WARNING) {
                $dispositions[] = OnboardingWarningDisposition::for($finding)
                    ?? ['code' => $finding->code, 'subject' => $finding->subject, 'evidence' => $finding->detail, 'root_cause' => 'UNDISPOSITIONED'];
            }
        }

        return $dispositions;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'schema_id' => self::SCHEMA_ID,
            'status' => 'RECONCILIATION - OFFLINE - NOT PERSISTED - OWNER VERIFICATION PENDING',
            'reconciled_at' => $this->reconciledAt,
            'contract_version' => ScientificSourceOnboardingContract::CONTRACT_VERSION,
            'inputs' => $this->inputs,
            'summary' => $this->summary(),
            'local_index_reconciliation' => $this->localIndexReconciliation(),
            'warning_dispositions' => $this->warningDispositions(),
            'findings' => array_map(static fn (OnboardingIntegrityFinding $f): array => $f->toArray(), $this->findings),
            'seats' => array_values(array_map(static fn (SeatReconciliationEntry $e): array => $e->toArray(), $this->entries)),
        ];
    }
}
