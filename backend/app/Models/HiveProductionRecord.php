<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\BelongsToOwner;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HiveProductionRecord extends Model
{
    use BelongsToOrganization;
    use BelongsToOwner;

    protected $fillable = [
        'organization_id',
        'owner_user_id',
        'hive_id',
        'product_type',
        'quantity',
        'unit',
        'recorded_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'recorded_at' => 'datetime',
        ];
    }

    public function hive(): BelongsTo
    {
        return $this->belongsTo(Hive::class);
    }
}
