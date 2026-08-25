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

    public const SELLER_BOTH = 'both';

    public const AVAILABILITY_AVAILABLE_NOW = 'available_now';

    public const AVAILABILITY_SEASONAL = 'seasonal';

    public const AVAILABILITY_MADE_TO_ORDER = 'made_to_order';

    /** Legacy alias stored on older rows; writes are normalized to made_to_order. */
    public const AVAILABILITY_ON_DEMAND = 'on_demand';

    public const AVAILABILITY_UNAVAILABLE = 'unavailable';

    /** @var list<string> */
    public const AVAILABILITIES = [
        self::AVAILABILITY_AVAILABLE_NOW,
        self::AVAILABILITY_SEASONAL,
        self::AVAILABILITY_MADE_TO_ORDER,
        self::AVAILABILITY_UNAVAILABLE,
    ];

    /** @var list<string> */
    public const AVAILABILITY_INPUTS = [
        self::AVAILABILITY_AVAILABLE_NOW,
        self::AVAILABILITY_SEASONAL,
        self::AVAILABILITY_MADE_TO_ORDER,
        self::AVAILABILITY_ON_DEMAND,
        self::AVAILABILITY_UNAVAILABLE,
    ];

    /** @var list<string> */
    public const SELLER_TYPE_FLAGS = [
        self::SELLER_LOCAL,
        self::SELLER_INTERNATIONAL,
    ];

    /** @var list<string> */
    public const SELLER_TYPES = [
        self::SELLER_LOCAL,
        self::SELLER_INTERNATIONAL,
        self::SELLER_BOTH,
    ];

    protected $fillable = [
        'seller_user_id',
        'organization_id',
        'category_id',
        'product_type',
        'title',
        'brand',
        'description',
        'seller_type',
        'status',
        'availability',
        'unit_id',
        'price',
        'currency',
        'country',
        'origin_country',
        'city',
        'seller_region',
        'seller_display_name',
        'seller_email',
        'seller_phone',
        'seller_verified',
        'export_ready',
        'export_destination',
        'export_metadata',
        'contact_access_price',
        'contact_access_currency',
        'min_order_quantity',
        'available_quantity',
        'production_capacity',
        'wholesale',
        'retail',
        'packaging',
        'shipping_terms',
        'lead_time_days',
        'specifications',
        'video_url',
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
            'wholesale' => 'boolean',
            'retail' => 'boolean',
            'specifications' => 'array',
            'min_order_quantity' => 'decimal:3',
            'available_quantity' => 'decimal:3',
            'production_capacity' => 'decimal:3',
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

    public function unit(): BelongsTo
    {
        return $this->belongsTo(MarketplaceUnit::class, 'unit_id');
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
            'brand' => $this->brand,
            'description' => $this->description,
            'product_type' => $this->product_type,
            'seller_type' => $this->seller_type,
            'seller_types' => self::sellerTypesFromStored($this->seller_type),
            'availability' => $this->availability,
            'price' => $this->price,
            'currency' => $this->currency,
            'country' => $this->country,
            'seller_country' => $this->country,
            'origin_country' => $this->origin_country,
            'city' => $this->city,
            'seller_region' => $this->seller_region,
            'export_ready' => $this->export_ready,
            'min_order_quantity' => $this->min_order_quantity,
            'available_quantity' => $this->available_quantity,
            'production_capacity' => $this->production_capacity,
            'wholesale' => $this->wholesale,
            'retail' => $this->retail,
            'packaging' => $this->packaging,
            'specifications' => $this->specifications,
            'video_url' => $this->video_url,
            'unit' => $this->unit?->only(['id', 'slug', 'name', 'name_ar']),
            'contact_access_price' => $this->contact_access_price,
            'contact_access_currency' => $this->contact_access_currency,
            'published_at' => $this->published_at,
            'category' => $this->category?->only(['id', 'slug', 'name', 'name_ar']),
            'seller' => [
                'display_name' => $this->seller_display_name,
                'country' => $this->country,
                'city' => $this->city,
                'region' => $this->seller_region,
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

    /**
     * @param  list<mixed>  $types
     */
    public static function sellerTypeFromTypes(array $types): ?string
    {
        $normalized = [];
        foreach ($types as $type) {
            if (! is_string($type) && ! is_int($type)) {
                continue;
            }
            $value = strtolower(trim((string) $type));
            if ($value === '') {
                continue;
            }
            $normalized[] = $value;
        }
        $normalized = array_values(array_unique($normalized));
        $hasLocal = in_array(self::SELLER_LOCAL, $normalized, true);
        $hasInternational = in_array(self::SELLER_INTERNATIONAL, $normalized, true);
        $unexpected = array_diff($normalized, self::SELLER_TYPE_FLAGS);
        if ($unexpected !== []) {
            return null;
        }
        if ($hasLocal && $hasInternational) {
            return self::SELLER_BOTH;
        }
        if ($hasLocal) {
            return self::SELLER_LOCAL;
        }
        if ($hasInternational) {
            return self::SELLER_INTERNATIONAL;
        }

        return null;
    }

    /** @return list<string> */
    public static function sellerTypesFromStored(?string $sellerType): array
    {
        return match ($sellerType) {
            self::SELLER_BOTH => [self::SELLER_LOCAL, self::SELLER_INTERNATIONAL],
            self::SELLER_LOCAL => [self::SELLER_LOCAL],
            self::SELLER_INTERNATIONAL => [self::SELLER_INTERNATIONAL],
            default => [],
        };
    }

    public static function canonicalizeAvailability(?string $availability): ?string
    {
        if ($availability === null || $availability === '') {
            return null;
        }
        $value = strtolower(trim($availability));
        if ($value === self::AVAILABILITY_ON_DEMAND) {
            return self::AVAILABILITY_MADE_TO_ORDER;
        }

        return $value;
    }
}
