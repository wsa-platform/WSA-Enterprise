<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class JobTalentProfile extends Model
{
    public const STATUS_AVAILABLE = 'available';

    public const STATUS_HIRED = 'hired';

    protected $fillable = [
        'user_id',
        'professional_name',
        'specialization',
        'biography',
        'country',
        'region',
        'city',
        'skills',
        'experience',
        'education',
        'certificates',
        'languages',
        'disciplines',
        'work_preferences',
        'availability',
        'employment_status',
        'cv_path',
        'cv_parse_status',
        'cv_parsed_at',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'skills' => 'array',
            'experience' => 'array',
            'education' => 'array',
            'certificates' => 'array',
            'languages' => 'array',
            'disciplines' => 'array',
            'work_preferences' => 'array',
            'availability' => 'array',
            'is_public' => 'boolean',
            'cv_parsed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contact(): HasOne
    {
        return $this->hasOne(JobTalentContact::class, 'talent_profile_id');
    }

    public function contactRequests(): HasMany
    {
        return $this->hasMany(JobContactRequest::class, 'talent_profile_id');
    }

    public function employmentRecords(): HasMany
    {
        return $this->hasMany(JobEmploymentRecord::class, 'talent_profile_id');
    }

    public function isContactExchangeAvailable(): bool
    {
        return $this->employment_status === self::STATUS_AVAILABLE && $this->is_public;
    }

    /** @return array<string, mixed> */
    public function toPublicArray(): array
    {
        return $this->only([
            'id',
            'professional_name',
            'specialization',
            'biography',
            'country',
            'region',
            'city',
            'skills',
            'experience',
            'education',
            'certificates',
            'languages',
            'disciplines',
            'work_preferences',
            'availability',
            'employment_status',
            'cv_parse_status',
            'is_public',
            'created_at',
            'updated_at',
        ]);
    }
}
