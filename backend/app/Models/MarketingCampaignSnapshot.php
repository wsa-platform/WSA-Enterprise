<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingCampaignSnapshot extends Model
{
    protected $fillable = [
        'organization_id',
        'campaign_id',
        'metrics',
        'captured_at',
    ];

    protected function casts(): array
    {
        return [
            'metrics' => 'array',
            'captured_at' => 'datetime',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(MarketingCampaign::class, 'campaign_id');
    }
}
