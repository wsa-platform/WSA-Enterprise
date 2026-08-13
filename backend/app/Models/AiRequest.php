<?php
namespace App\Models;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\BelongsToOwner;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiRequest extends Model {
    use BelongsToOrganization;
    use BelongsToOwner;

    protected $fillable=[
        'organization_id','owner_user_id','user_id','request_type','source_type','source_id',
        'provider','status','input','output','error_message','latency_ms',
        'tokens_used','cancelled_at',
    ];

    protected function casts(): array {
        return [
            'input'=>'array',
            'output'=>'array',
            'cancelled_at'=>'datetime',
        ];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public function isCancellable(): bool
    {
        return in_array($this->status, ['pending', 'processing'], true);
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, ['completed', 'failed', 'cancelled'], true);
    }
}
