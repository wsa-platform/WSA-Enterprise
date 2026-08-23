<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WelcomeDelivery extends Model
{
    protected $fillable = [
        'welcome_event_id',
        'channel',
        'status',
        'provider',
        'provider_message_id',
        'error_message',
        'payload',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'sent_at' => 'datetime',
        ];
    }

    public function welcomeEvent(): BelongsTo
    {
        return $this->belongsTo(WelcomeEvent::class);
    }
}
