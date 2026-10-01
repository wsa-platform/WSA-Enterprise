<?php

namespace App\Services\Agriculture\Research\Identity;

/**
 * IU-01 Source Identity Domain Contract — documentation + invariant helpers.
 *
 * Authority: WHO / WHAT the ADR source resource / seat is.
 *
 * Does NOT own:
 * - CSQ / scientific entity meaning
 * - Capability states (VERIFIED|PARTIAL|UNVERIFIED|UNAVAILABLE|NOT_APPLICABLE)
 * - Path eligibility/status
 * - Projection / C9 fidelity
 * - Stage-3 sourceKey selection or adapter execution
 * - Answer / R6 / Composer
 *
 * Scientific Identity Safety:
 * Source Identity ≠ Scientific Entity Identity ≠ CSQ.
 * This contract must never mutate CSQ facets (species, dose, disease, …).
 */
final class SourceIdentityDomainContract
{
    /** @var list<string> */
    public const FORBIDDEN_SCIENTIFIC_FACET_KEYS = [
        'entity',
        'species',
        'genus',
        'cultivar',
        'breed',
        'strain',
        'genotype',
        'phenotype',
        'qtl',
        'germplasm',
        'plant_part',
        'life_stage',
        'disease',
        'pathogen',
        'pest',
        'comparator',
        'dose',
        'concentration',
        'duration',
        'frequency',
        'application_method',
        'measurement',
        'unit',
        'geography',
        'environment',
        'experimental_condition',
        'treatment',
        'outcome',
    ];

    /** @var list<string> */
    public const FORBIDDEN_CAPABILITY_STATE_KEYS = [
        'VERIFIED',
        'PARTIAL',
        'UNVERIFIED',
        'UNAVAILABLE',
        'NOT_APPLICABLE',
    ];

    /**
     * Reject payloads that smuggle Cap states or CSQ scientific facets into identity.
     *
     * @param  array<string, mixed>  $payload
     */
    public static function assertIdentityPayloadClean(array $payload): void
    {
        foreach (array_keys($payload) as $key) {
            $normalized = strtolower((string) $key);
            foreach (self::FORBIDDEN_SCIENTIFIC_FACET_KEYS as $facet) {
                if ($normalized === $facet || $normalized === str_replace('_', '', $facet)) {
                    throw new SourceIdentityInvariantViolation(
                        "Source Identity payload must not carry scientific facet key [{$key}]."
                    );
                }
            }
        }

        foreach ($payload as $key => $value) {
            if (! is_string($value)) {
                continue;
            }
            $upper = strtoupper(trim($value));
            if (in_array($upper, self::FORBIDDEN_CAPABILITY_STATE_KEYS, true)
                && in_array(strtolower((string) $key), ['capability_state', 'cap_state', 'state', 'status'], true)
            ) {
                throw new SourceIdentityInvariantViolation(
                    'Source Identity must not encode Capability Store states.'
                );
            }
        }
    }

    /**
     * Identity layers remain distinct — no automatic equivalence.
     */
    public static function assertDistinctNamespaces(
        AdrMembershipId $adrId,
        ?CanonicalSourceIdentityId $canonicalId,
        ?ExternalIdentityRef $external,
        ?string $stage3SourceKey,
    ): void {
        if ($canonicalId !== null && $adrId->value === $canonicalId->value) {
            throw new SourceIdentityInvariantViolation(
                'adr_id and canonical_identity_id must remain distinct namespaces.'
            );
        }

        if ($stage3SourceKey !== null && trim($stage3SourceKey) !== '') {
            $key = trim($stage3SourceKey);
            if ($key === $adrId->value) {
                throw new SourceIdentityInvariantViolation(
                    'Stage-3 sourceKey must not equal adr_id.'
                );
            }
            if ($canonicalId !== null && $key === $canonicalId->value) {
                throw new SourceIdentityInvariantViolation(
                    'Stage-3 sourceKey must not equal canonical_identity_id.'
                );
            }
            if ($external !== null && $key === $external->identifier) {
                throw new SourceIdentityInvariantViolation(
                    'Stage-3 sourceKey must not be equated to external identity identifier by contract helpers.'
                );
            }
        }
    }
}
