<?php

namespace App\Services\Agriculture\Research\Identity\Persistence;

/**
 * FS-01-ID persistence package contract documentation + static guards.
 */
final class IdentityBindingPersistenceContract
{
    public const SCHEMA_VERSION = IdentityBindingRecord::CURRENT_SCHEMA_VERSION;

    public const TABLE = 'cghia_identity_binding_records';

    /** @var list<string> */
    public const FORBIDDEN_AUTHORITY_KEYS = [
        'fidelity_class',
        'directness',
        'claim_relation',
        'sufficiency',
        'confidence',
        'overall_confidence',
        'evidence_score',
        'mapped_facets',
        'unsupported_facets',
        'omitted_facets',
        'unresolved_facets',
        'capability_state',
        'eligibility_state',
        'path_status',
        'activation_state',
        'display_name',
        'same_as',
        'sameas',
    ];

    /**
     * @param  array<string, mixed>  $metadata
     */
    public static function assertMetadataNonAuthoritative(array $metadata): void
    {
        foreach (array_keys($metadata) as $key) {
            $normalized = strtolower((string) $key);
            foreach (self::FORBIDDEN_AUTHORITY_KEYS as $forbidden) {
                if ($normalized === $forbidden || $normalized === str_replace('_', '', $forbidden)) {
                    throw new IdentityBindingInvariantViolation(
                        "Identity binding metadata must not carry authoritative key [{$key}]."
                    );
                }
            }
        }

        // Cap states must not appear under identity status-like metadata keys.
        foreach ($metadata as $key => $value) {
            if (! is_string($value)) {
                continue;
            }
            $upper = strtoupper(trim($value));
            if (in_array($upper, ['VERIFIED', 'PARTIAL', 'UNVERIFIED', 'UNAVAILABLE', 'NOT_APPLICABLE'], true)
                && in_array(strtolower((string) $key), ['capability_state', 'cap_state', 'state', 'status'], true)
            ) {
                throw new IdentityBindingInvariantViolation(
                    'Identity binding metadata must not encode Capability Store states.'
                );
            }
        }
    }
}
