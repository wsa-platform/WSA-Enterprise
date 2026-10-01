<?php

namespace App\Services\Agriculture\Research\Capability;

/**
 * Domain evaluator: RequiredCapabilities + records → CapabilityDecisionIdentity.
 *
 * AND semantics only. Does not select paths, execute HTTP, or run fallback.
 */
final class CapabilityRequirementEvaluator
{
    /**
     * @param  list<CapabilityRecord>  $records
     */
    public function evaluate(
        CapabilitySubject $subject,
        CapabilityRequirementSet $requirements,
        array $records,
        string $decisionId,
        string $decidedAt,
    ): CapabilityDecisionIdentity {
        $index = $this->indexRecordsForSubject($subject, $records);

        $outcomes = [];
        $hasUnavailable = false;
        $hasUnverified = false;
        $hasPartial = false;
        $hasStale = false;
        $seatOverrideApplied = false;
        $snapshotVersion = CapabilityRecord::UNKNOWN_METADATA;

        foreach ($requirements->required as $dimension) {
            $record = $index[$dimension->key()] ?? null;
            if ($record === null) {
                // B3: Missing capability record = UNVERIFIED
                $state = CapabilityState::UNVERIFIED;
                $missing = true;
                $limitation = null;
                $freshness = CapabilityEvidenceFreshness::UNKNOWN->value;
            } else {
                $state = $record->state;
                $missing = false;
                $limitation = $record->limitationText;
                $freshness = $record->evidenceFreshness->value;
                if ($record->isStaleEvidence()) {
                    $hasStale = true;
                }
                if ($record->seatOverrideApplied) {
                    $seatOverrideApplied = true;
                }
                if ($snapshotVersion === CapabilityRecord::UNKNOWN_METADATA
                    && ! $record->hasUnknownSnapshotVersion()
                ) {
                    $snapshotVersion = $record->snapshotVersion;
                }
            }

            if ($state === CapabilityState::UNAVAILABLE) {
                $hasUnavailable = true;
            } elseif ($state === CapabilityState::UNVERIFIED) {
                $hasUnverified = true;
            } elseif ($state === CapabilityState::PARTIAL) {
                $hasPartial = true;
            }

            $outcomes[] = [
                'dimension_key' => $dimension->key(),
                'family' => $dimension->family->value,
                'code' => $dimension->code,
                'state' => $state->value,
                'limitation_text' => $limitation,
                'missing_record' => $missing,
                'evidence_freshness_class' => $freshness,
            ];
        }

        $andClass = match (true) {
            $hasUnavailable => CapabilityAndResultClass::HAS_UNAVAILABLE,
            $hasUnverified => CapabilityAndResultClass::HAS_UNVERIFIED,
            $hasPartial => CapabilityAndResultClass::HAS_PARTIAL,
            default => CapabilityAndResultClass::ALL_VERIFIED,
        };

        $optionalKeys = array_map(
            static fn (CapabilityDimension $d): string => $d->key(),
            $requirements->optionalEnrichments,
        );

        return CapabilityDecisionIdentity::create(
            $decisionId,
            $subject,
            $outcomes,
            $optionalKeys,
            $andClass,
            $hasStale,
            $snapshotVersion,
            $decidedAt,
            $seatOverrideApplied,
        );
    }

    /**
     * Resolve effective Cap state for a dimension (missing → UNVERIFIED).
     *
     * @param  list<CapabilityRecord>  $records
     */
    public function resolveState(
        CapabilitySubject $subject,
        CapabilityDimension $dimension,
        array $records,
    ): CapabilityState {
        foreach ($records as $record) {
            if (! $record instanceof CapabilityRecord) {
                continue;
            }
            if (! $this->subjectMatches($subject, $record->subject)) {
                continue;
            }
            if ($record->dimension->equals($dimension)) {
                return $record->state;
            }
        }

        return CapabilityState::UNVERIFIED;
    }

    /**
     * @param  list<CapabilityRecord>  $records
     * @return array<string, CapabilityRecord>
     */
    private function indexRecordsForSubject(CapabilitySubject $subject, array $records): array
    {
        $index = [];
        foreach ($records as $record) {
            if (! $record instanceof CapabilityRecord) {
                throw new CapabilityInvariantViolation('Capability records list must contain CapabilityRecord only.');
            }
            if (! $this->subjectMatches($subject, $record->subject)) {
                continue;
            }
            $key = $record->dimension->key();
            if (isset($index[$key])) {
                // Restrictive-wins for duplicate cells (seat override vs shared).
                $winnerState = CapabilityState::moreRestrictive($index[$key]->state, $record->state);
                $index[$key] = $winnerState === $record->state ? $record : $index[$key];
            } else {
                $index[$key] = $record;
            }
        }

        return $index;
    }

    private function subjectMatches(CapabilitySubject $expected, CapabilitySubject $actual): bool
    {
        if (! $expected->adrId->equals($actual->adrId)) {
            return false;
        }

        $a = $expected->canonicalIdentityId?->value;
        $b = $actual->canonicalIdentityId?->value;

        return $a === $b;
    }
}
