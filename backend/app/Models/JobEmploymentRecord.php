<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobEmploymentRecord extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'talent_profile_id',
        'contact_transaction_id',
        'job_reference',
        'employment_status',
        'hired_at',
        'notes',
    ];

    protected function casts(): array
    {
        return ['hired_at' => 'datetime'];
    }

    public function talentProfile(): BelongsTo
    {
        return $this->belongsTo(JobTalentProfile::class, 'talent_profile_id');
    }

    public function contactTransaction(): BelongsTo
    {
        return $this->belongsTo(JobContactTransaction::class, 'contact_transaction_id');
    }
}
