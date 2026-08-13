<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\BelongsToOwner;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HiveInspection extends Model
{
    use BelongsToOrganization;
    use BelongsToOwner;

    protected $fillable = [
        'organization_id',
        'owner_user_id',
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
