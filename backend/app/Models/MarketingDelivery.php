<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingDelivery extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'campaign_id',
        'recipient_type',
        'recipient_id',
        'channel',
        'status',
        'provider',
        'provider_message_id',
        'error_code',
        'error_message',
        'queued_at',
        'sent_at',
        'delivered_at',
        'failed_at',
    ];

    protected function casts(): array
    {
        return [
            'queued_at' => 'datetime',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(MarketingCampaign::class, 'campaign_id');
    }
}
