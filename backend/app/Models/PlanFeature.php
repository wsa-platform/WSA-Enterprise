<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanFeature extends Model
{
    protected $fillable = [
        'plan_id',
        'feature_key',
        'limit_value',
        'limit_period',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
