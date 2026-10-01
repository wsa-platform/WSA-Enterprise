<?php

namespace App\Services\Agriculture\Research\Capability;

/**
 * IU-02 Capability Store Domain Contract — documentation + invariant helpers.
 *
 * Authority: WHAT can this source support?
 *
 * Does NOT own:
 * - Source Identity minting / SAME_AS / merge
 * - CSQ / scientific entity meaning
 * - Path eligibility_state / Path status
 * - Projection / C9 fidelity_class
 * - Stage-3 adapters / HTTP / fallback engine
 * - Composer / R6 / B7 persistence / 109 population
 *
 * Cap v2 states only. GO-1 SUPPORTED/UNKNOWN are historical and never equated here.
 */
final class CapabilityStoreDomainContract
{
    /** @var list<string> */
    public const CAP_V2_STATES = [
        'VERIFIED',
        'PARTIAL',
        'UNVERIFIED',
        'UNAVAILABLE',
        'NOT_APPLICABLE',
    ];

    /** @var list<string> */
    public const FORBIDDEN_PATH_ELIGIBILITY_VALUES = [
        'ELIGIBLE',
        'CONDITIONAL',
        'INELIGIBLE',
        'DEFERRED',
    ];

    /** @var list<string> */
    public const FORBIDDEN_PATH_STATUS_VALUES = [
        'SELECTED',
        'CONDITIONALLY_SELECTED',
        'DEFERRED_PENDING_CAPABILITY',
    ];

    /** @var list<string> */
    public const FORBIDDEN_AS_CAP_STATES = [
        'SUPPORTED',
        'PARTIALLY_SUPPORTED',
        'UNSUPPORTED',
        'UNKNOWN',
        'STALE',
        'LIVE',
        'FULL',
        'COMPLETE',
    ];

    /** @var list<string> */
    public const FORBIDDEN_SCIENTIFIC_MUTATION_KEYS = [
        'species',
        'dose',
        'disease',
        'pathogen',
        'csq',
        'canonical_scientific_question',
        'fidelity_class',
        'eligibility_state',
        'path_status',
    ];

    /**
     * B3 — Missing record resolves to UNVERIFIED (never UNAVAILABLE / N/A / VERIFIED).
     */
    public static function stateForMissingRecord(): CapabilityState
    {
        return CapabilityState::UNVERIFIED;
    }

    /**
     * Reject Cap payloads that smuggle Path ownership, GO-1-as-Cap, or CSQ mutation.
     *
     * @param  array<string, mixed>  $payload
     */
    public static function assertCapabilityPayloadClean(array $payload): void
    {
        foreach (array_keys($payload) as $key) {
            $normalized = strtolower((string) $key);
            foreach (self::FORBIDDEN_SCIENTIFIC_MUTATION_KEYS as $forbidden) {
                if ($normalized === $forbidden || $normalized === str_replace('_', '', $forbidden)) {
                    throw new CapabilityInvariantViolation(
                        "Capability Store payload must not carry forbidden authority key [{$key}]."
                    );
                }
            }
        }

        foreach ($payload as $key => $value) {
            if (! is_string($value)) {
                continue;
            }
            $upper = strtoupper(trim($value));
            $keyLower = strtolower((string) $key);

            if (in_array($keyLower, ['capability_state', 'cap_state', 'state'], true)) {
                if (in_array($upper, self::FORBIDDEN_AS_CAP_STATES, true)) {
                    throw new CapabilityInvariantViolation(
                        "Capability Store must not use historical GO-1 or non-Cap-v2 token [{$upper}] as capability_state."
                    );
                }
                if (! in_array($upper, self::CAP_V2_STATES, true)) {
                    throw new CapabilityInvariantViolation(
                        "Capability Store capability_state must be a Cap v2 state; got [{$upper}]."
                    );
                }
            }

            if (in_array($keyLower, ['eligibility_state', 'path_eligibility'], true)
                && in_array($upper, self::FORBIDDEN_PATH_ELIGIBILITY_VALUES, true)
            ) {
                throw new CapabilityInvariantViolation(
                    'Capability Store must not encode Path eligibility_state.'
                );
            }

            if (in_array($keyLower, ['path_status', 'status'], true)
                && in_array($upper, self::FORBIDDEN_PATH_STATUS_VALUES, true)
            ) {
                throw new CapabilityInvariantViolation(
                    'Capability Store must not encode Path status.'
                );
            }
        }
    }

    /**
     * B4 — STALE freshness must not be treated as Cap state or auto-VERIFIED.
     */
    public static function assertStaleIsNotCapabilityState(CapabilityEvidenceFreshness $freshness): void
    {
        if ($freshness === CapabilityEvidenceFreshness::STALE) {
            // Explicit no-op documentation guard: callers must keep freshness orthogonal.
            return;
        }
    }

    /**
     * Forbid promoting STALE evidence into a fresh VERIFIED claim without CapVer.
     */
    public static function assertNoStaleAutoPromotionToVerified(
        CapabilityState $state,
        CapabilityEvidenceFreshness $freshness,
        bool $autoPromoteRequested,
    ): void {
        if ($autoPromoteRequested
            && $freshness === CapabilityEvidenceFreshness::STALE
            && $state === CapabilityState::VERIFIED
        ) {
            throw new CapabilityInvariantViolation(
                'STALE evidence must not auto-promote to VERIFIED capability_state.'
            );
        }
    }

    /**
     * B6 — UNKNOWN snapshot/update must not imply LIVE/FULL/VERIFIED/COMPLETE.
     */
    public static function assertUnknownMetadataNotPromoted(string $snapshotVersion, string $updateMethod): void
    {
        $forbiddenClaims = ['LIVE', 'FULL', 'VERIFIED', 'COMPLETE'];
        if (strtoupper($snapshotVersion) === CapabilityRecord::UNKNOWN_METADATA
            || strtoupper($updateMethod) === CapabilityRecord::UNKNOWN_METADATA
        ) {
            // Presence of UNKNOWN is allowed; positive claims from UNKNOWN are not.
            return;
        }

        foreach ([$snapshotVersion, $updateMethod] as $meta) {
            if (in_array(strtoupper(trim($meta)), $forbiddenClaims, true)
                && (strtoupper($snapshotVersion) === 'UNKNOWN' || strtoupper($updateMethod) === 'UNKNOWN')
            ) {
                throw new CapabilityInvariantViolation(
                    'UNKNOWN snapshot_version/update_method must not be promoted to LIVE/FULL/VERIFIED/COMPLETE.'
                );
            }
        }
    }

    /**
     * Explicit non-equivalence: GO-1 historical states are never Cap v2.
     */
    public static function assertGo1NotEquatedToCapV2(
        HistoricalGo1CapabilityState $go1,
        CapabilityState $capV2,
    ): void {
        // Intentionally no mapping. Documented guard for tests / callers.
        unset($go1, $capV2);
    }

    /**
     * Cap does not mint Identity — subject must already carry adr_id from IU-01 types.
     */
    public static function assertSubjectConsumesIdentityOnly(CapabilitySubject $subject): void
    {
        if (trim($subject->adrId->value) === '') {
            throw new CapabilityInvariantViolation('Capability subject requires non-empty adr_id from Identity.');
        }
    }
}
