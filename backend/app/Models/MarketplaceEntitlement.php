<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceEntitlement extends Model
{
    protected $fillable = [
        'buyer_user_id',
        'listing_id',
        'order_id',
        'granted_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'granted_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_user_id');
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(MarketplaceListing::class, 'listing_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(ContactAccessOrder::class, 'order_id');
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null;
    }
}
