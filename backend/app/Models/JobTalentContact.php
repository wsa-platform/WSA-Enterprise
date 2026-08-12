<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobTalentContact extends Model
{
    protected $fillable = [
        'talent_profile_id',
        'email',
        'phone',
        'whatsapp',
        'other_channels',
    ];

    protected $hidden = [
        'email',
        'phone',
        'whatsapp',
        'other_channels',
    ];

    protected function casts(): array
    {
        return ['other_channels' => 'array'];
    }

    public function talentProfile(): BelongsTo
    {
        return $this->belongsTo(JobTalentProfile::class, 'talent_profile_id');
    }

    /** @return array<string, mixed> */
    public function toExchangeArray(): array
    {
        return [
            'email' => $this->email,
            'phone' => $this->phone,
            'whatsapp' => $this->whatsapp,
            'other_channels' => $this->other_channels,
        ];
    }
}
