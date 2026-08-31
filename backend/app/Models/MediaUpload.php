<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaUpload extends Model
{
    protected $fillable = [
        'organization_id',
        'user_id',
        'storage_disk',
        'storage_path',
        'mime_type',
        'size_bytes',
        'context',
        'metadata',
    ];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'size_bytes' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
