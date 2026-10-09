<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Cap Store Persistence Eloquent row for cghia_capability_cell_records.
 *
 * Storage encoding: bigint auto-increment `id` is NOT CapabilityRecordId.
 * Thin persistence representation — does not assign VERIFIED, populate cells,
 * or decide Path/Projection/D-10.
 */
class CghiaCapabilityCell extends Model
{
    protected $table = 'cghia_capability_cell_records';

    protected $fillable = [
        'capability_record_id',
        'adr_id',
        'canonical_identity_id',
        'dimension_family',
        'dimension_code',
        'seat_override_applied',
        'capability_state',
        'evidence_freshness_class',
        'snapshot_version',
        'update_method',
        'limitation_text',
        'verified_at',
        'observed_at',
        'capability_evidence_refs',
        'cell_lifecycle',
    ];

    protected function casts(): array
    {
        return [
            'seat_override_applied' => 'boolean',
            'capability_evidence_refs' => 'array',
        ];
    }
}
