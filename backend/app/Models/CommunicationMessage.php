<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommunicationMessage extends Model
{
    protected $fillable = [
        'organization_id',
        'created_by_user_id',
        'subject',
        'body',
        'channel',
        'status',
        'is_bulk',
        'is_newsletter',
        'mailing_list_id',
        'scheduled_at',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'is_bulk' => 'boolean',
            'is_newsletter' => 'boolean',
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function mailingList(): BelongsTo
    {
        return $this->belongsTo(MailingList::class);
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(CommunicationRecipient::class);
    }
}
