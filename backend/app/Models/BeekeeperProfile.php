<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BeekeeperProfile extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'user_id',
        'display_name',
        'country',
        'region',
        'location',
        'hive_count',
        'colony_count',
        'experience_years',
        'production_types',
        'goals',
        'seasonal_activity',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'production_types' => 'array',
            'goals' => 'array',
            'seasonal_activity' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function apiaries(): HasMany
    {
        return $this->hasMany(Apiary::class);
    }
}
