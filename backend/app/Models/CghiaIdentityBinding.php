<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FS-01-ID Eloquent row for cghia_identity_binding_records.
 *
 * Storage encoding: bigint auto-increment `id` = persistence_record_id (NOT a domain identity).
 * Thin persistence representation — does not decide identity, mint SAME_AS, or activate sources.
 */
class CghiaIdentityBinding extends Model
{
    protected $table = 'cghia_identity_binding_records';

    protected $fillable = [
        'adr_id',
        'canonical_identity_id',
        'identity_status',
        'identity_decision_identity',
        'identity_evidence_fingerprint',
        'identity_evidence_refs',
        'original_source_identifier',
        'aggregator_record_identifier',
        'lifecycle_state',
        'schema_version',
        'idempotency_key',
        'superseded_by',
        'decision_actor',
        'decision_timestamp',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'identity_evidence_refs' => 'array',
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
