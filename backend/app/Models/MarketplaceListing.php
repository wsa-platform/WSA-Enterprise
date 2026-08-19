<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MarketplaceListing extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING_REVIEW = 'pending_review';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUS_UNPUBLISHED = 'unpublished';

    public const SELLER_LOCAL = 'local';

    public const SELLER_INTERNATIONAL = 'international';

    protected $fillable = [
        'seller_user_id',
        'organization_id',
        'category_id',
        'title',
        'description',
        'seller_type',
        'status',
        'price',
        'currency',
        'country',
        'city',
        'seller_display_name',
        'seller_email',
        'seller_phone',
        'seller_verified',
        'export_ready',
        'export_destination',
        'export_metadata',
        'contact_access_price',
        'contact_access_currency',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'contact_access_price' => 'decimal:2',
            'seller_verified' => 'boolean',
            'export_ready' => 'boolean',
            'export_metadata' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_user_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(MarketplaceCategory::class, 'category_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(MarketplaceListingImage::class, 'listing_id')->orderBy('sort_order');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(MarketplaceListingStatusHistory::class, 'listing_id');
    }

    public function entitlements(): HasMany
    {
        return $this->hasMany(MarketplaceEntitlement::class, 'listing_id');
    }

    /** @return array<string, mixed> */
    public function toPublicArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'seller_type' => $this->seller_type,
            'price' => $this->price,
            'currency' => $this->currency,
            'country' => $this->country,
            'city' => $this->city,
            'export_ready' => $this->export_ready,
            'export_destination' => $this->export_destination,
            'contact_access_price' => $this->contact_access_price,
            'contact_access_currency' => $this->contact_access_currency,
            'published_at' => $this->published_at,
            'category' => $this->category?->only(['id', 'slug', 'name', 'name_ar']),
            'seller' => [
                'display_name' => $this->seller_display_name,
                'country' => $this->country,
                'city' => $this->city,
                'seller_type' => $this->seller_type,
                'verified' => $this->seller_verified,
            ],
            'images' => $this->images->map(fn (MarketplaceListingImage $img) => $img->only(['id', 'path', 'alt_text', 'sort_order']))->values(),
        ];
    }

    /** @return array<string, mixed> */
    public function toOwnerArray(): array
    {
        return array_merge($this->toPublicArray(), [
            'status' => $this->status,
            'seller_email' => $this->seller_email,
            'seller_phone' => $this->seller_phone,
            'export_metadata' => $this->export_metadata,
            'organization_id' => $this->organization_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]);
    }
}
