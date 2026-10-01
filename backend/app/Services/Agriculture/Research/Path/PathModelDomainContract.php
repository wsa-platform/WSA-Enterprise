<?php

namespace App\Services\Agriculture\Research\Path;

use App\Services\Agriculture\Research\Capability\CapabilityDecisionIdentity;
use App\Services\Agriculture\Research\Identity\AdrMembershipId;
use App\Services\Agriculture\Research\Identity\CanonicalSourceIdentityId;

/**
 * IU-03 Path Model Domain Contract — documentation + invariant helpers.
 *
 * Authority: which integration route is permitted / selected under Cap constraints.
 *
 * Does NOT own: Cap states, Identity minting, Projection/C9, Stage-3, HTTP, CSQ, Composer.
 */
final class PathModelDomainContract
{
    /** @var list<string> */
    public const FORBIDDEN_RUNTIME_KEYS = [
        'http',
        'curl',
        'guzzle',
        'sourcekey',
        'source_key',
        'fidelity_class',
        'csq',
        'canonical_scientific_question',
        'projection_identity',
    ];

    public static function assertPathIdNotEqualAdrId(PathId $pathId, AdrMembershipId $adrId): void
    {
        if ($pathId->value === $adrId->value) {
            throw new PathInvariantViolation('path_id must not equal adr_id (PATH-INV-011).');
        }
    }

    public static function assertDecisionIdDistinct(
        PathDecisionId $decisionId,
        PathId $pathId,
        AdrMembershipId $adrId,
        string $capabilityDecisionId,
        ?CanonicalSourceIdentityId $canonicalIdentityId,
    ): void {
        $d = $decisionId->value;
        if ($d === $pathId->value) {
            throw new PathInvariantViolation('path_decision_identity must not equal path_id.');
        }
        if ($d === $adrId->value) {
            throw new PathInvariantViolation('path_decision_identity must not equal adr_id.');
        }
        if ($d === $capabilityDecisionId) {
            throw new PathInvariantViolation(
                'path_decision_identity must not equal capability_decision_identity.'
            );
        }
        if ($canonicalIdentityId !== null && $d === $canonicalIdentityId->value) {
            throw new PathInvariantViolation(
                'path_decision_identity must not equal canonical_identity_id.'
            );
        }
    }

    /**
     * B1: eligibility and status are orthogonal axes — both must be present as distinct enums.
     * Domain allows combinations; does not auto-select. Soft guidance only for INELIGIBLE+SELECTED.
     */
    public static function assertEligibilityOrthogonalToStatus(
        PathEligibilityState $eligibility,
        PathStatus $status,
    ): void {
        // Hard reject: INELIGIBLE cannot be SELECTED as verified automation (Design §25).
        if ($eligibility === PathEligibilityState::INELIGIBLE
            && $status === PathStatus::SELECTED
        ) {
            throw new PathInvariantViolation(
                'INELIGIBLE path cannot have status SELECTED for verified automation.'
            );
        }

        if ($eligibility === PathEligibilityState::INELIGIBLE
            && $status === PathStatus::CONDITIONALLY_SELECTED
        ) {
            throw new PathInvariantViolation(
                'INELIGIBLE path cannot have status CONDITIONALLY_SELECTED.'
            );
        }

        // Orthogonality is structural: types differ; no further merge.
        unset($eligibility, $status);
    }

    /**
     * Build Path decision from Cap decision via frozen Cap→eligibility mapping.
     * Does NOT auto-apply suggested status as Cap promotion — status is an explicit argument.
     *
     * @param  list<PathLimitation>  $limitations
     * @param  list<string>  $decisionReasons
     */
    public static function bindFromCapabilityDecision(
        PathDecisionId $decisionId,
        PathId $pathId,
        PathFamily $pathFamily,
        CapabilityDecisionIdentity $capabilityDecision,
        PathStatus $status,
        array $limitations = [],
        array $decisionReasons = [],
        string $decidedAt = '',
        string $decisionVersion = '1',
        PathFallbackLayer $fallbackLayer = PathFallbackLayer::L1,
    ): PathDecisionIdentity {
        $mapper = new CapabilityToPathEligibilityMapper();
        $eligibility = $mapper->map($capabilityDecision->andResultClass);

        if ($capabilityDecision->hasStaleFlags) {
            $limitations[] = PathLimitation::of(
                'CAP_STALE_EVIDENCE',
                'Capability decision carries stale evidence flags; STALE must not auto-promote to verified Cap.',
            );
        }

        return PathDecisionIdentity::create(
            $decisionId,
            $pathId,
            $pathFamily,
            $capabilityDecision->subject->adrId,
            $capabilityDecision->subject->canonicalIdentityId,
            $capabilityDecision,
            $eligibility,
            $status,
            $limitations,
            $decisionReasons,
            $decidedAt,
            $decisionVersion,
            $fallbackLayer,
            forbidsFidelityIncreaseOnFallback: true,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function assertPathPayloadClean(array $payload): void
    {
        foreach (array_keys($payload) as $key) {
            $normalized = strtolower((string) $key);
            foreach (self::FORBIDDEN_RUNTIME_KEYS as $forbidden) {
                if ($normalized === $forbidden || $normalized === str_replace('_', '', $forbidden)) {
                    throw new PathInvariantViolation(
                        "Path Model payload must not carry forbidden runtime/authority key [{$key}]."
                    );
                }
            }
        }
    }
}
