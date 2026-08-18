<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MailingListMember extends Model
{
    protected $fillable = [
        'mailing_list_id',
        'user_id',
        'email',
        'phone',
    ];

    public function mailingList(): BelongsTo
    {
        return $this->belongsTo(MailingList::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
