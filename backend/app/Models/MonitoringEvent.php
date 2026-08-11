<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitoringEvent extends Model
{
    public const STAGE_DETECTED = 'detected';

    public const STAGE_ANALYZED = 'analyzed';

    public const STAGE_REMEDIATION_ATTEMPTED = 'remediation_attempted';

    public const STAGE_VERIFIED = 'verified';

    public const STAGE_RESOLVED = 'resolved';

    public const STAGE_ESCALATED = 'escalated';

    public const STATUS_OPEN = 'open';

    public const STATUS_RESOLVED = 'resolved';

    protected $fillable = [
        'organization_id',
        'component',
        'status',
        'severity',
        'lifecycle_stage',
        'detected_at',
        'resolved_at',
        'details',
        'remediation_status',
        'remediation_action',
        'request_id',
        'correlation_id',
        'analysis_summary',
    ];

    protected function casts(): array
    {
        return [
            'details' => 'array',
            'detected_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
