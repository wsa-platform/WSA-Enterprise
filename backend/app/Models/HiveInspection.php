<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HiveInspection extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'hive_id',
        'inspector_user_id',
        'inspected_at',
        'overall_status',
        'findings',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'inspected_at' => 'datetime',
            'findings' => 'array',
        ];
    }

    public function hive(): BelongsTo
    {
        return $this->belongsTo(Hive::class);
    }
}
