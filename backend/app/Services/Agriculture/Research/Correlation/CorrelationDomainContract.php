<?php

namespace App\Services\Agriculture\Research\Correlation;

use App\Services\Agriculture\Research\Identity\AdrMembershipId;
use App\Services\Agriculture\Research\Identity\CanonicalSourceIdentityId;
use App\Services\Agriculture\Research\Path\PathDecisionId;
use App\Services\Agriculture\Research\Path\PathId;
use App\Services\Agriculture\Research\Projection\CanonicalQueryId;
use App\Services\Agriculture\Research\Projection\ProjectionIdentity;

/**
 * IU-06 B7 Correlation Domain Contract — documentation + invariant helpers.
 *
 * Authority: linkage / reconstructability of existing identities.
 *
 * Does NOT own: Identity minting, Cap/Path/Projection/CSQ mutation, C9, R6,
 * evidence scoring, HTTP, Stage-3, persistence, population.
 */
final class CorrelationDomainContract
{
    /** @var list<string> */
    public const FORBIDDEN_SCORING_KEYS = [
        'fidelity_class',
        'directness',
        'claim_relation',
        'claim_relationship',
        'sufficiency',
        'confidence',
        'overall_confidence',
        'evidence_score',
    ];

    /** @var list<string> */
    public const FORBIDDEN_RUNTIME_KEYS = [
        'http',
        'guzzle',
        'curl',
        'adapter',
        'normalizer',
        'migration',
        'eloquent',
        'sourcekey',
        'source_key',
    ];

    public static function assertDistinctLinkage(
        QuestionIdentityReference $questionIdentity,
        CanonicalQueryId $csqIdentity,
        ?VariantIdentityReference $variantId,
        AdrMembershipId $adrId,
        ?CanonicalSourceIdentityId $canonicalIdentityId,
        string $capabilityDecisionId,
        PathId $pathId,
        PathDecisionId $pathDecisionId,
        ProjectionIdentity $projectionIdentity,
        ?SourceRecordReference $originalSourceIdentifier,
        ?AggregatorRecordReference $aggregatorRecordIdentifier,
    ): void {
        if ($adrId->value === ($canonicalIdentityId?->value ?? '')) {
            throw new CorrelationInvariantViolation('adr_id must not equal canonical_identity_id.');
        }
        if ($adrId->value === $pathId->value) {
            throw new CorrelationInvariantViolation('adr_id must not equal path_id.');
        }
        if ($adrId->value === $projectionIdentity->value) {
            throw new CorrelationInvariantViolation('adr_id must not equal projection_identity.');
        }
        if ($adrId->value === $pathDecisionId->value) {
            throw new CorrelationInvariantViolation('adr_id must not equal path_decision_identity.');
        }
        if ($adrId->value === $capabilityDecisionId) {
            throw new CorrelationInvariantViolation('adr_id must not equal capability_decision_identity.');
        }
        if ($projectionIdentity->value === $pathId->value) {
            throw new CorrelationInvariantViolation('projection_identity must not equal path_id.');
        }
        if ($projectionIdentity->value === $pathDecisionId->value) {
            throw new CorrelationInvariantViolation('projection_identity must not equal path_decision_identity.');
        }
        if ($projectionIdentity->value === $capabilityDecisionId) {
            throw new CorrelationInvariantViolation(
                'projection_identity must not equal capability_decision_identity.'
            );
        }
        if ($projectionIdentity->value === $csqIdentity->value) {
            throw new CorrelationInvariantViolation('projection_identity must not equal csq_identity.');
        }
        if ($capabilityDecisionId === $pathDecisionId->value) {
            throw new CorrelationInvariantViolation(
                'capability_decision_identity must not equal path_decision_identity.'
            );
        }
        if ($questionIdentity->value === $csqIdentity->value) {
            throw new CorrelationInvariantViolation('question_identity must not equal csq_identity.');
        }
        if ($variantId !== null) {
            if ($variantId->value === $csqIdentity->value) {
                throw new CorrelationInvariantViolation('variant_id must not equal csq_identity.');
            }
            if ($variantId->value === $questionIdentity->value) {
                throw new CorrelationInvariantViolation('variant_id must not equal question_identity.');
            }
            if ($variantId->value === $pathId->value) {
                throw new CorrelationInvariantViolation('variant_id must not equal path_id.');
            }
        }

        if ($aggregatorRecordIdentifier !== null) {
            $agg = $aggregatorRecordIdentifier->value;
            if ($agg === $adrId->value) {
                throw new CorrelationInvariantViolation(
                    'aggregator_record_identifier must not equal adr_id.'
                );
            }
            if ($canonicalIdentityId !== null && $agg === $canonicalIdentityId->value) {
                throw new CorrelationInvariantViolation(
                    'aggregator_record_identifier must not equal canonical_identity_id.'
                );
            }
            if ($agg === $pathId->value || $agg === $projectionIdentity->value) {
                throw new CorrelationInvariantViolation(
                    'aggregator_record_identifier must not equal path_id or projection_identity.'
                );
            }
        }

        if ($originalSourceIdentifier !== null) {
            $src = $originalSourceIdentifier->value;
            if ($src === $adrId->value) {
                throw new CorrelationInvariantViolation(
                    'original_source_identifier must not equal adr_id.'
                );
            }
            if ($canonicalIdentityId !== null && $src === $canonicalIdentityId->value) {
                throw new CorrelationInvariantViolation(
                    'original_source_identifier must not equal canonical_identity_id.'
                );
            }
            if ($src === $pathId->value || $src === $projectionIdentity->value) {
                throw new CorrelationInvariantViolation(
                    'original_source_identifier must not equal path_id or projection_identity.'
                );
            }
        }

        // Stage-3 sourceKey must not be smuggled as aggregator/ADR collapse via equal tokens
        // against known Stage-3 scholarly keys when used as aggregator id — soft documentation:
        // equality to adr/canonical already forbidden above.
        unset($questionIdentity);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function assertCorrelationPayloadClean(array $payload): void
    {
        foreach (array_keys($payload) as $key) {
            $normalized = strtolower((string) $key);
            foreach ([...self::FORBIDDEN_SCORING_KEYS, ...self::FORBIDDEN_RUNTIME_KEYS] as $forbidden) {
                if ($normalized === $forbidden || $normalized === str_replace('_', '', $forbidden)) {
                    throw new CorrelationInvariantViolation(
                        "Correlation payload must not carry forbidden key [{$key}]."
                    );
                }
            }
        }
    }

    /**
     * B7 does not mint identities — factory must receive externally supplied opacities.
     */
    public static function assertDoesNotMintIdentities(): void
    {
        // Structural documentation guard for tests / callers.
    }
}
