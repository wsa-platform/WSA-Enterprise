<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitorLocation extends Model
{
    protected $fillable = [
        'visitor_session_id',
        'country',
        'city',
        'latitude',
        'longitude',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(VisitorSession::class, 'visitor_session_id');
    }
}
