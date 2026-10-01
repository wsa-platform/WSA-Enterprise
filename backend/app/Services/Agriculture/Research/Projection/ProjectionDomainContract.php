<?php

namespace App\Services\Agriculture\Research\Projection;

use App\Services\Agriculture\Research\Capability\CapabilityDecisionIdentity;
use App\Services\Agriculture\Research\Identity\AdrMembershipId;
use App\Services\Agriculture\Research\Identity\CanonicalSourceIdentityId;
use App\Services\Agriculture\Research\Path\PathDecisionIdentity;
use App\Services\Agriculture\Research\Path\PathId;

/**
 * IU-04 Projection + C9 Domain Contract — documentation + invariant helpers.
 *
 * Authority: source-native representation of immutable CSQ under Cap + Path.
 * C9: fidelity_class of that representation — not Directness / claim_relation / sufficiency.
 *
 * Does NOT own: CSQ mutation, Cap/Path mutation, Builder, Compiler, Stage-3, HTTP,
 * adapters, normalizers, Composer, persistence, population.
 */
final class ProjectionDomainContract
{
    /** @var list<string> */
    public const FORBIDDEN_R6_KEYS = [
        'directness',
        'claim_relation',
        'claim_relationship',
        'sufficiency',
        'overall_confidence',
        'confidence',
        'evidence_score',
    ];

    /** @var list<string> */
    public const FORBIDDEN_RUNTIME_KEYS = [
        'http',
        'guzzle',
        'curl',
        'adapter',
        'normalizer',
        'sourcekey',
        'source_key',
        'migration',
        'eloquent',
    ];

    public static function assertProjectionIdentityDistinct(
        ProjectionIdentity $projectionIdentity,
        CanonicalQueryId $canonicalQueryId,
        AdrMembershipId $adrId,
        ?CanonicalSourceIdentityId $canonicalIdentityId,
        string $capabilityDecisionId,
        string $pathDecisionId,
        PathId $pathId,
    ): void {
        $p = $projectionIdentity->value;
        $forbiddenEquals = [
            'canonical_query_id' => $canonicalQueryId->value,
            'adr_id' => $adrId->value,
            'capability_decision_identity' => $capabilityDecisionId,
            'path_decision_identity' => $pathDecisionId,
            'path_id' => $pathId->value,
        ];
        if ($canonicalIdentityId !== null) {
            $forbiddenEquals['canonical_identity_id'] = $canonicalIdentityId->value;
        }

        foreach ($forbiddenEquals as $label => $value) {
            if ($p === $value) {
                throw new ProjectionInvariantViolation(
                    "projection_identity must not equal {$label}."
                );
            }
        }
    }

    public static function assertSubjectAlignment(
        CapabilityDecisionIdentity $capabilityDecision,
        PathDecisionIdentity $pathDecision,
    ): void {
        if (! $capabilityDecision->subject->adrId->equals($pathDecision->adrId)) {
            throw new ProjectionInvariantViolation(
                'Projection Cap subject adr_id must match Path Decision adr_id.'
            );
        }

        $capCanonical = $capabilityDecision->subject->canonicalIdentityId?->value;
        $pathCanonical = $pathDecision->canonicalIdentityId?->value;
        if ($capCanonical !== $pathCanonical) {
            throw new ProjectionInvariantViolation(
                'Projection Cap subject canonical must match Path Decision canonical.'
            );
        }

        if ($capabilityDecision->decisionId !== $pathDecision->capabilityDecisionId) {
            throw new ProjectionInvariantViolation(
                'Projection Cap decision id must match Path Decision capability_decision_identity.'
            );
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function assertProjectionPayloadClean(array $payload): void
    {
        foreach (array_keys($payload) as $key) {
            $normalized = strtolower((string) $key);
            foreach ([...self::FORBIDDEN_R6_KEYS, ...self::FORBIDDEN_RUNTIME_KEYS] as $forbidden) {
                if ($normalized === $forbidden || $normalized === str_replace('_', '', $forbidden)) {
                    throw new ProjectionInvariantViolation(
                        "Projection payload must not carry forbidden key [{$key}]."
                    );
                }
            }
        }
    }

    /**
     * B6 — UNKNOWN metadata must not be promoted to LIVE/CURRENT/FULL/COMPLETE/VERIFIED claims.
     */
    public static function assertUnknownMetadataNotPromoted(string $retrievalTimestamp, string $sourceVersion): void
    {
        $forbidden = ['LIVE', 'CURRENT', 'FULL', 'COMPLETE', 'VERIFIED'];
        foreach ([$retrievalTimestamp, $sourceVersion] as $meta) {
            if (strtoupper(trim($meta)) === ProjectionEnvelope::UNKNOWN_METADATA) {
                continue;
            }
            if (in_array(strtoupper(trim($meta)), $forbidden, true)
                && (strtoupper($retrievalTimestamp) === 'UNKNOWN' || strtoupper($sourceVersion) === 'UNKNOWN')
            ) {
                throw new ProjectionInvariantViolation(
                    'UNKNOWN retrieval_timestamp/source_version must not be promoted to LIVE/CURRENT/FULL/COMPLETE/VERIFIED.'
                );
            }
        }
    }

    /**
     * C9 ⊥ R6 — fidelity is not Directness/claim_relation/sufficiency/confidence.
     */
    public static function assertFidelityIndependentOfR6(ProjectionFidelityClass $fidelityClass): void
    {
        // Structural documentation guard; fidelity enum contains no R6 tokens.
        $value = $fidelityClass->value;
        foreach (['DIRECT', 'CLAIM', 'SUFFICIENCY', 'CONFIDENCE'] as $token) {
            if (str_contains($value, $token)) {
                throw new ProjectionInvariantViolation('fidelity_class must not encode R6 axes.');
            }
        }
    }
}
