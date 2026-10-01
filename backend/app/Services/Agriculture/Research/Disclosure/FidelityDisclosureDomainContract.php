<?php

namespace App\Services\Agriculture\Research\Disclosure;

use App\Services\Agriculture\Research\Projection\ProjectionFidelityClass;

/**
 * IU-08 B8 domain contract — invariant helpers for fidelity disclosure handoff.
 */
final class FidelityDisclosureDomainContract
{
    /** @var list<string> */
    public const FORBIDDEN_HANDOFF_AUTHORITY_KEYS = [
        'confidence',
        'directness',
        'claim_relation',
        'claim_relationship',
        'sufficiency',
        'overall_confidence',
        'evidence_score',
        'capability_state',
        'eligibility_state',
        'path_status',
        'source_query',
        'answer',
    ];

    public static function assertValidHandoff(FidelityDisclosureHandoff $handoff): void
    {
        if ($handoff->schemaVersion !== FidelityDisclosureHandoff::SCHEMA_VERSION) {
            throw new FidelityDisclosureInvariantViolation(
                'FidelityDisclosureHandoff schema_version must be '.FidelityDisclosureHandoff::SCHEMA_VERSION.'.'
            );
        }

        if (trim($handoff->projectionIdentity->value) === '') {
            throw new FidelityDisclosureInvariantViolation('projection_identity must be non-empty.');
        }

        $payload = $handoff->toArray();
        self::assertObservabilityHasNoR6AuthorityKeys($payload);

        $hasIdentityCriticalMandatory = false;
        foreach ($handoff->disclosures as $record) {
            FidelityDisclosureCodes::assertKnown($record->code);
            FidelityDisclosureCodes::assertNonExactClass($record->c9Class);

            if ($record->code === FidelityDisclosureCodes::AGGREGATE_NON_EXACT) {
                if ($record->facetCode !== null) {
                    throw new FidelityDisclosureInvariantViolation(
                        'C9_AGGREGATE_NON_EXACT must not carry facet_code.'
                    );
                }
                if (! $record->mandatory || ! $record->qualificationRequired) {
                    throw new FidelityDisclosureInvariantViolation(
                        'C9_AGGREGATE_NON_EXACT must be mandatory with qualification_required.'
                    );
                }
            } else {
                if ($record->facetCode === null || trim($record->facetCode) === '') {
                    throw new FidelityDisclosureInvariantViolation(
                        "Facet disclosure [{$record->code}] requires facet_code."
                    );
                }
            }

            if ($record->code === FidelityDisclosureCodes::IDENTITY_CRITICAL_LOSS) {
                if (! $record->identityCritical || ! $record->mandatory || ! $record->qualificationRequired) {
                    throw new FidelityDisclosureInvariantViolation(
                        'C9_IDENTITY_CRITICAL_LOSS must be identity-critical, mandatory, qualification_required.'
                    );
                }
                $hasIdentityCriticalMandatory = true;
            }

            if ($record->code === FidelityDisclosureCodes::NON_IDENTITY_FACET_LOSS) {
                if ($record->identityCritical || $record->mandatory || $record->qualificationRequired) {
                    throw new FidelityDisclosureInvariantViolation(
                        'C9_NON_IDENTITY_FACET_LOSS must be non-identity-critical advisory (non-qualifying).'
                    );
                }
            }

            if ($record->projectionIdentity->value !== $handoff->projectionIdentity->value) {
                throw new FidelityDisclosureInvariantViolation(
                    'Disclosure projection_identity must match handoff projection_identity.'
                );
            }
        }

        $aggregateNonExact = $handoff->aggregateFidelityClass !== ProjectionFidelityClass::EXACT;
        if ($aggregateNonExact) {
            $hasAggregate = false;
            foreach ($handoff->disclosures as $record) {
                if ($record->code === FidelityDisclosureCodes::AGGREGATE_NON_EXACT) {
                    $hasAggregate = true;
                    if ($record->c9Class !== $handoff->aggregateFidelityClass) {
                        throw new FidelityDisclosureInvariantViolation(
                            'C9_AGGREGATE_NON_EXACT c9_class must equal aggregate_fidelity_class.'
                        );
                    }
                    break;
                }
            }
            if (! $hasAggregate) {
                throw new FidelityDisclosureInvariantViolation(
                    'Aggregate fidelity non-EXACT requires C9_AGGREGATE_NON_EXACT disclosure.'
                );
            }
            if (! $handoff->qualificationRequired || ! $handoff->unqualifiedExactScientificClaimForbidden) {
                throw new FidelityDisclosureInvariantViolation(
                    'Aggregate non-EXACT requires qualification_required and unqualified_exact_scientific_claim_forbidden.'
                );
            }
        }

        if ($hasIdentityCriticalMandatory) {
            if (! $handoff->qualificationRequired || ! $handoff->unqualifiedExactScientificClaimForbidden) {
                throw new FidelityDisclosureInvariantViolation(
                    'Identity-critical non-EXACT requires qualification_required and unqualified_exact_scientific_claim_forbidden.'
                );
            }
        }

        if ($handoff->qualificationRequired !== $handoff->unqualifiedExactScientificClaimForbidden) {
            throw new FidelityDisclosureInvariantViolation(
                'qualification_required and unqualified_exact_scientific_claim_forbidden must agree under B8.'
            );
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function assertObservabilityHasNoR6AuthorityKeys(array $payload): void
    {
        foreach (array_keys($payload) as $key) {
            $normalized = strtolower((string) $key);
            foreach (self::FORBIDDEN_HANDOFF_AUTHORITY_KEYS as $forbidden) {
                if ($normalized === $forbidden || $normalized === str_replace('_', '', $forbidden)) {
                    throw new FidelityDisclosureInvariantViolation(
                        "Fidelity disclosure payload must not carry forbidden authority key [{$key}]."
                    );
                }
            }
        }
    }
}
