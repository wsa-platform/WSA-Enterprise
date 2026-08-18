<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MarketplaceCategory extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'slug',
        'name',
        'name_ar',
        'description',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function listings(): HasMany
    {
        return $this->hasMany(MarketplaceListing::class, 'category_id');
    }
}
