<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketingCampaign extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'name',
        'description',
        'channel',
        'audience_segment_id',
        'template_id',
        'content',
        'status',
        'scheduled_at',
        'started_at',
        'completed_at',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'content' => 'array',
            'scheduled_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function segment(): BelongsTo
    {
        return $this->belongsTo(MarketingAudienceSegment::class, 'audience_segment_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(MarketingTemplate::class, 'template_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(MarketingDelivery::class, 'campaign_id');
    }
}
