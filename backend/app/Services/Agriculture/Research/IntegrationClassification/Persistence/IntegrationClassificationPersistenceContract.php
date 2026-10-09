<?php

namespace App\Services\Agriculture\Research\IntegrationClassification\Persistence;

/**
 * IC Persistence package contract documentation + static guards.
 *
 * Storage only — not CapVer, Path, Projection/C9, D-10, Stage-3, Membership, or B7.
 */
final class IntegrationClassificationPersistenceContract
{
    public const SCHEMA_VERSION = IntegrationClassificationRecord::CURRENT_SCHEMA_VERSION;

    public const TABLE = 'cghia_integration_classification_records';

    /** Partial unique index: at most one ACTIVE row per adr_id. */
    public const SINGLE_ACTIVE_INDEX = 'cghia_ic_records_single_active_uq';

    /**
     * Columns compared on idempotency replay. Excluded by design: free text
     * (rationale, source_specific_rationale), provenance/wall clock (decision_actor,
     * verified_at, decision_timestamp), non-authoritative metadata, and lifecycle/storage columns.
     *
     * @var list<string>
     */
    public const REPLAY_CONTRACT_FIELDS = [
        'adr_id',
        'classification_decision_identity',
        'classification_status',
        'canonical_identity_id',
        'identity_binding_ref',
        'access_modality_claims',
        'integration_nature',
        'integration_boundary',
        'protocol_family',
        'source_specific_requirement',
        'path_family_hint',
        'existing_adapter_reference',
        'external_dependency_reference',
        'evidence_references',
        'evidence_fingerprint',
        'license_reference',
        'access_reference',
        'reuse_reference',
    ];

    /**
     * Access modality claim code strings (intentional vocabulary overlap with Cap AccessMethod;
     * split ownership — IC claims ≠ Cap-verified methods).
     *
     * @var list<string>
     */
    public const ACCESS_MODALITY_CLAIMS = [
        'web_ui_search',
        'machine_access',
        'api',
        'oai_pmh',
        'rss',
        'atom',
        'bulk_download',
        'static_download',
        'metadata_access',
        'full_text_access',
    ];

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
        'cap_state',
        'eligibility_state',
        'path_status',
        'path_eligibility',
        'activation_state',
        'display_name',
        'same_as',
        'sameas',
        'source_query',
        'csq',
        'selected_path',
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
                    throw new IntegrationClassificationInvariantViolation(
                        "IC metadata must not carry authoritative key [{$key}]."
                    );
                }
            }
        }

        foreach ($metadata as $key => $value) {
            if (! is_string($value)) {
                continue;
            }
            $upper = strtoupper(trim($value));
            if (in_array($upper, ['VERIFIED', 'PARTIAL', 'UNVERIFIED', 'UNAVAILABLE', 'NOT_APPLICABLE'], true)
                && in_array(strtolower((string) $key), ['capability_state', 'cap_state', 'state', 'status'], true)
            ) {
                throw new IntegrationClassificationInvariantViolation(
                    'IC metadata must not encode Capability Store states.'
                );
            }
            if (in_array($upper, ['SELECTED', 'CONDITIONALLY_SELECTED', 'ELIGIBLE', 'ACTIVE'], true)
                && in_array(strtolower((string) $key), ['path_status', 'eligibility_state', 'activation_state', 'state', 'status'], true)
            ) {
                throw new IntegrationClassificationInvariantViolation(
                    'IC metadata must not encode Path/D-10 authority states.'
                );
            }
        }
    }
}
