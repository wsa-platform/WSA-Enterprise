<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmploymentStatusHistory extends Model
{
    protected $table = 'employment_status_history';

    protected $fillable = [
        'job_seeker_profile_id',
        'status',
        'changed_by_user_id',
        'notes',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(JobSeekerProfile::class, 'job_seeker_profile_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }
}
