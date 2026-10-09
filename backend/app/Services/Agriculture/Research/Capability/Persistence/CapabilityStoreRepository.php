<?php

namespace App\Services\Agriculture\Research\Capability\Persistence;

use App\Services\Agriculture\Research\Capability\CapabilityDimension;
use App\Services\Agriculture\Research\Capability\CapabilityRecord;
use App\Services\Agriculture\Research\Capability\CapabilityRecordId;
use App\Services\Agriculture\Research\Capability\CapabilitySubject;

/**
 * Cap Store Persistence repository — storage only (ADR-023 §8.16 CPD-A–D).
 *
 * Writer-neutral: persists already-authorized facts. Does not assign VERIFIED
 * (CapVer-only), populate cells, inherit shared → seat, or auto-insert UNVERIFIED
 * for missing cells. No delete, no in-place claim update, no latest-wins.
 */
interface CapabilityStoreRepository
{
    /**
     * Append a new CURRENT fact.
     * Rejects a duplicate CapabilityRecordId and an existing CURRENT fact for the
     * same (subject, dimension, seat_override_applied) — use supersedeCurrent().
     *
     * @param  list<string>  $evidenceRefs
     */
    public function persistCurrent(CapabilityRecord $record, array $evidenceRefs): CapabilityCellFact;

    /**
     * Atomically mark the prior CURRENT fact HISTORICAL and append the replacement
     * as CURRENT. Prior and replacement must share subject, dimension and override layer.
     *
     * @param  list<string>  $evidenceRefs
     */
    public function supersedeCurrent(
        CapabilityRecordId $priorRecordId,
        CapabilityRecord $replacement,
        array $evidenceRefs,
    ): CapabilityCellFact;

    public function findFact(CapabilityRecordId $recordId): ?CapabilityCellFact;

    /**
     * Null ⇒ no CURRENT fact for the exact tuple. More than one ⇒ fail closed.
     */
    public function findCurrentFact(
        CapabilitySubject $subject,
        CapabilityDimension $dimension,
        bool $seatOverrideApplied,
    ): ?CapabilityCellFact;

    /**
     * CURRENT facts for the exact subject (shared and override layers), as domain
     * records for CapabilityRequirementEvaluator. HISTORICAL facts are excluded.
     *
     * @return list<CapabilityRecord>
     */
    public function loadCurrentForSubject(CapabilitySubject $subject): array;
}
