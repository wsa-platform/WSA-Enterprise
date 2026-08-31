<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImage extends Model
{
    protected $fillable = [
        'organization_id',
        'product_id',
        'media_upload_id',
        'storage_disk',
        'storage_path',
        'sort_order',
    ];

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function mediaUpload(): BelongsTo
    {
        return $this->belongsTo(MediaUpload::class);
    }
}
