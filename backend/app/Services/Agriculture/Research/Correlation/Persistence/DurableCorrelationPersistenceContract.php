<?php

namespace App\Services\Agriculture\Research\Correlation\Persistence;

/**
 * IU-07 persistence package contract documentation + static guards for tests.
 */
final class DurableCorrelationPersistenceContract
{
    public const SCHEMA_VERSION = DurableCorrelationRecord::CURRENT_SCHEMA_VERSION;

    public const TABLE = 'durable_correlation_records';

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
                    throw new DurableCorrelationInvariantViolation(
                        "Durable correlation metadata must not carry authoritative key [{$key}]."
                    );
                }
            }
        }
    }
}
