<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * IU-07 Eloquent row for durable_correlation_records.
 *
 * Storage encoding: bigint auto-increment `id` = persistence_record_id (NOT a domain identity).
 */
class DurableCorrelation extends Model
{
    protected $table = 'durable_correlation_records';

    protected $fillable = [
        'question_identity',
        'csq_identity',
        'variant_id',
        'adr_id',
        'canonical_identity_id',
        'capability_decision_identity',
        'path_id',
        'path_decision_identity',
        'projection_identity',
        'retrieval_timestamp',
        'original_source_identifier',
        'aggregator_record_identifier',
        'composite_is_canonical_source_identity',
        'lifecycle_state',
        'schema_version',
        'idempotency_key',
        'superseded_by',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'composite_is_canonical_source_identity' => 'boolean',
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
