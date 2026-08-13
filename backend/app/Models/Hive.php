<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\BelongsToOwner;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hive extends Model
{
    use BelongsToOrganization;
    use BelongsToOwner;

    protected $fillable = [
        'organization_id',
        'owner_user_id',
        'apiary_id',
        'code',
        'colony_status',
        'queen_info',
        'frame_count',
        'notes',
    ];

    protected function casts(): array
    {
        return ['queen_info' => 'array'];
    }

    public function apiary(): BelongsTo
    {
        return $this->belongsTo(Apiary::class);
    }

    public function inspections(): HasMany
    {
        return $this->hasMany(HiveInspection::class);
    }

    public function treatments(): HasMany
    {
        return $this->hasMany(HiveTreatment::class);
    }

    public function feedings(): HasMany
    {
        return $this->hasMany(HiveFeeding::class);
    }

    public function productionRecords(): HasMany
    {
        return $this->hasMany(HiveProductionRecord::class);
    }
}
