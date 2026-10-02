<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * IC Persistence Eloquent row for cghia_integration_classification_records.
 *
 * Storage encoding: bigint auto-increment `id` = persistence_record_id (NOT a domain identity).
 * Thin persistence representation — does not decide Cap/Path/D-10, mint SAME_AS, or activate sources.
 */
class CghiaIntegrationClassification extends Model
{
    protected $table = 'cghia_integration_classification_records';

    protected $fillable = [
        'adr_id',
        'canonical_identity_id',
        'identity_binding_ref',
        'classification_decision_identity',
        'classification_status',
        'access_modality_claims',
        'integration_nature',
        'integration_boundary',
        'protocol_family',
        'source_specific_requirement',
        'source_specific_rationale',
        'path_family_hint',
        'existing_adapter_reference',
        'external_dependency_reference',
        'evidence_references',
        'evidence_fingerprint',
        'license_reference',
        'access_reference',
        'reuse_reference',
        'rationale',
        'decision_actor',
        'verified_at',
        'decision_timestamp',
        'lifecycle_state',
        'schema_version',
        'idempotency_key',
        'superseded_by',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'access_modality_claims' => 'array',
            'evidence_references' => 'array',
            'source_specific_requirement' => 'boolean',
            'schema_version' => 'integer',
            'metadata' => 'array',
            'superseded_by' => 'integer',
        ];
    }

    public function supersededByRecord(): BelongsTo
    {
        return $this->belongsTo(self::class, 'superseded_by');
    }
}
