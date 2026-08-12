<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Apiary extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'beekeeper_profile_id',
        'name',
        'country',
        'region',
        'location',
        'latitude',
        'longitude',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'is_active' => 'boolean',
        ];
    }

    public function beekeeperProfile(): BelongsTo
    {
        return $this->belongsTo(BeekeeperProfile::class);
    }

    public function hives(): HasMany
    {
        return $this->hasMany(Hive::class);
    }
}
