<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserIdentity extends Model
{
    public const PROVIDER_EMAIL = 'email';

    public const PROVIDER_GOOGLE = 'google';

    public const PROVIDER_FACEBOOK = 'facebook';

    public const PROVIDER_PHONE = 'phone';

    protected $fillable = [
        'user_id',
        'provider',
        'provider_id',
        'email',
        'phone',
        'verified_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
