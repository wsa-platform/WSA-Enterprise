<?php

namespace App\Services\Agriculture\Research\Capability\Persistence;

use App\Services\Agriculture\Research\Capability\CapabilityInvariantViolation;
use App\Services\Agriculture\Research\Capability\CapabilityRecord;
use App\Services\Agriculture\Research\Capability\CapabilityState;

/**
 * Immutable persisted Cap cell fact (ADR-023 §8.16 CPD-A/B).
 *
 * Wraps the domain CapabilityRecord with Cap-owned opaque evidence references
 * and the Cap-native lifecycle. Evidence refs are not CapabilityRecordId,
 * CapabilityDecisionIdentity, sourceKey, IC decision identity, or identity_binding_ref.
 */
final readonly class CapabilityCellFact
{
    /** States whose Persist writes require at least one evidence ref (§8.16.7). */
    private const EVIDENCE_REQUIRED_STATES = [
        CapabilityState::VERIFIED,
        CapabilityState::PARTIAL,
        CapabilityState::UNAVAILABLE,
    ];

    /**
     * @param  list<string>  $evidenceRefs
     */
    private function __construct(
        public CapabilityRecord $record,
        public array $evidenceRefs,
        public CapabilityCellLifecycle $lifecycle,
    ) {}

    /**
     * @param  list<mixed>  $evidenceRefs
     */
    public static function forWrite(CapabilityRecord $record, array $evidenceRefs): self
    {
        return new self(
            $record,
            self::normalizeEvidenceRefs($record->state, $evidenceRefs),
            CapabilityCellLifecycle::CURRENT,
        );
    }

    /**
     * @param  list<mixed>  $evidenceRefs
     */
    public static function fromPersisted(
        CapabilityRecord $record,
        array $evidenceRefs,
        CapabilityCellLifecycle $lifecycle,
    ): self {
        return new self(
            $record,
            self::normalizeEvidenceRefs($record->state, $evidenceRefs),
            $lifecycle,
        );
    }

    public function isCurrent(): bool
    {
        return $this->lifecycle === CapabilityCellLifecycle::CURRENT;
    }

    /**
     * Natural CURRENT uniqueness tuple (CPD-D); evidence refs are not part of it.
     */
    public function naturalKey(): string
    {
        return self::naturalKeyFor(
            $this->record->subject->adrId->value,
            $this->record->subject->canonicalIdentityId?->value,
            $this->record->dimension->family->value,
            $this->record->dimension->code,
            $this->record->seatOverrideApplied,
        );
    }

    public static function naturalKeyFor(
        string $adrId,
        ?string $canonicalIdentityId,
        string $dimensionFamily,
        string $dimensionCode,
        bool $seatOverrideApplied,
    ): string {
        return json_encode(
            [$adrId, $canonicalIdentityId, $dimensionFamily, $dimensionCode, $seatOverrideApplied],
            JSON_THROW_ON_ERROR,
        );
    }

    /**
     * @param  list<mixed>  $refs
     * @return list<string>
     */
    private static function normalizeEvidenceRefs(CapabilityState $state, array $refs): array
    {
        $out = [];
        foreach ($refs as $ref) {
            if (! is_string($ref)) {
                throw new CapabilityInvariantViolation('capability_evidence_refs must be a list of opaque strings.');
            }
            if (trim($ref) === '') {
                throw new CapabilityInvariantViolation('capability_evidence_refs entries must be non-empty strings.');
            }
            if (trim($ref) !== $ref) {
                throw new CapabilityInvariantViolation(
                    'capability_evidence_refs entries must not carry leading/trailing whitespace; refs are opaque and not rewritten.'
                );
            }
            if (isset($out[$ref])) {
                throw new CapabilityInvariantViolation("Duplicate capability_evidence_ref [{$ref}] within one Cap cell fact.");
            }
            $out[$ref] = true;
        }

        if ($out === [] && in_array($state, self::EVIDENCE_REQUIRED_STATES, true)) {
            throw new CapabilityInvariantViolation(
                "Cap cell facts with capability_state={$state->value} require at least one capability_evidence_ref."
            );
        }

        $refsOut = array_map('strval', array_keys($out));
        sort($refsOut, SORT_STRING);

        return $refsOut;
    }
}
