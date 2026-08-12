<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BeeCalendarTask extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'apiary_id',
        'hive_id',
        'task_type',
        'severity',
        'title',
        'description',
        'scheduled_for',
        'due_at',
        'status',
        'context',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_for' => 'datetime',
            'due_at' => 'datetime',
            'context' => 'array',
        ];
    }

    public function apiary(): BelongsTo
    {
        return $this->belongsTo(Apiary::class);
    }

    public function hive(): BelongsTo
    {
        return $this->belongsTo(Hive::class);
    }
}
