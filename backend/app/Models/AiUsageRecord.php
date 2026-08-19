<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiUsageRecord extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'user_id',
        'ai_request_id',
        'provider',
        'model',
        'provider_request_id',
        'tokens_used',
        'latency_ms',
        'status',
        'error_category',
        'retrieval',
    ];

    protected function casts(): array
    {
        return [
            'tokens_used' => 'integer',
            'latency_ms' => 'integer',
            'retrieval' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function aiRequest(): BelongsTo
    {
        return $this->belongsTo(AiRequest::class);
    }
}
