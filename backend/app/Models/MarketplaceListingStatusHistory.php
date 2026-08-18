<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceListingStatusHistory extends Model
{
    protected $table = 'marketplace_listing_status_history';

    protected $fillable = [
        'listing_id',
        'status',
        'changed_by_user_id',
        'reason',
    ];

    public function listing(): BelongsTo
    {
        return $this->belongsTo(MarketplaceListing::class, 'listing_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }
}
