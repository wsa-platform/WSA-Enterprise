<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceAttributeDefinition extends Model
{
    public const TYPE_STRING = 'string';

    public const TYPE_NUMBER = 'number';

    public const TYPE_TEXT = 'text';

    public const TYPE_BOOLEAN = 'boolean';

    protected $fillable = [
        'slug',
        'name',
        'name_ar',
        'data_type',
        'category_id',
        'product_type',
        'is_required',
        'is_active',
        'sort_order',
        'options',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'is_active' => 'boolean',
            'options' => 'array',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(MarketplaceCategory::class, 'category_id');
    }
}
