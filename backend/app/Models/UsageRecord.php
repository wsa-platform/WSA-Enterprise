<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsageRecord extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'metric',
        'quantity',
        'period_start',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'metadata' => 'array',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
