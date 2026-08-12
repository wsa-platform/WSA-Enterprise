<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class JobContactRequest extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'talent_profile_id',
        'requested_by_user_id',
        'employer_contact_name',
        'employer_contact_email',
        'employer_contact_phone',
        'employer_contact_whatsapp',
        'status',
        'job_reference',
        'notes',
        'idempotency_key',
    ];

    public function talentProfile(): BelongsTo
    {
        return $this->belongsTo(JobTalentProfile::class, 'talent_profile_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function transaction(): HasOne
    {
        return $this->hasOne(JobContactTransaction::class, 'contact_request_id');
    }

    /** @return array<string, mixed> */
    public function employerContactArray(): array
    {
        return [
            'name' => $this->employer_contact_name,
            'email' => $this->employer_contact_email,
            'phone' => $this->employer_contact_phone,
            'whatsapp' => $this->employer_contact_whatsapp,
        ];
    }
}
