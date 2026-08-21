<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

class JobTalentProfile extends Model
{
    use BelongsToUser;

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

    protected $hidden = [
        'user_id',
        'cv_path',
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

    public function storedCvDiskPath(): ?string
    {
        if (! is_string($this->cv_path) || $this->cv_path === '') {
            return null;
        }

        $prefix = 'job-cvs/'.$this->id.'/';
        if (str_contains($this->cv_path, '..') || ! str_starts_with($this->cv_path, $prefix)) {
            return null;
        }

        if (! Storage::disk('local')->exists($this->cv_path)) {
            return null;
        }

        return $this->cv_path;
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
            'is_public',
            'created_at',
            'updated_at',
        ]);
    }

    /** @return array<string, mixed> */
    public function toOwnerArray(): array
    {
        $data = $this->toPublicArray();
        $data['has_cv'] = filled($this->cv_path);
        $data['cv_filename'] = $this->cv_path ? basename($this->cv_path) : null;
        $data['cv_parse_status'] = $this->cv_parse_status;
        if ($this->relationLoaded('contact') && $this->contact) {
            $this->contact->makeVisible(['email', 'phone', 'whatsapp', 'other_channels']);
            $data['contact'] = $this->contact->only(['email', 'phone', 'whatsapp', 'other_channels']);
        }

        return $data;
    }
}
