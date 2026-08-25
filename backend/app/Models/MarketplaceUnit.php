<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketplaceUnit extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'name_ar',
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
        return $this->hasMany(MarketplaceListing::class, 'unit_id');
    }

    /** @return list<array{slug: string, name: string, name_ar: string, sort_order: int}> */
    public static function canonicalUnits(): array
    {
        return [
            ['slug' => 'ton', 'name' => 'Ton', 'name_ar' => 'طن', 'sort_order' => 1],
            ['slug' => 'metric_ton', 'name' => 'Metric ton', 'name_ar' => 'طن متري', 'sort_order' => 2],
            ['slug' => 'kg', 'name' => 'Kilogram', 'name_ar' => 'كجم', 'sort_order' => 3],
            ['slug' => 'gram', 'name' => 'Gram', 'name_ar' => 'جرام', 'sort_order' => 4],
            ['slug' => 'liter', 'name' => 'Liter', 'name_ar' => 'لتر', 'sort_order' => 5],
            ['slug' => 'ml', 'name' => 'Milliliter', 'name_ar' => 'مل', 'sort_order' => 6],
            ['slug' => 'meter', 'name' => 'Meter', 'name_ar' => 'متر', 'sort_order' => 7],
            ['slug' => 'square_meter', 'name' => 'Square meter', 'name_ar' => 'متر مربع', 'sort_order' => 8],
            ['slug' => 'cubic_meter', 'name' => 'Cubic meter', 'name_ar' => 'متر مكعب', 'sort_order' => 9],
            ['slug' => 'cm', 'name' => 'Centimeter', 'name_ar' => 'سم', 'sort_order' => 10],
            ['slug' => 'piece', 'name' => 'Piece', 'name_ar' => 'قطعة', 'sort_order' => 11],
            ['slug' => 'each', 'name' => 'Each', 'name_ar' => 'حبة', 'sort_order' => 12],
            ['slug' => 'bag', 'name' => 'Bag', 'name_ar' => 'كيس', 'sort_order' => 13],
            ['slug' => 'box', 'name' => 'Box', 'name_ar' => 'صندوق', 'sort_order' => 14],
            ['slug' => 'pack', 'name' => 'Pack', 'name_ar' => 'عبوة', 'sort_order' => 15],
            ['slug' => 'bottle', 'name' => 'Bottle', 'name_ar' => 'زجاجة', 'sort_order' => 16],
            ['slug' => 'barrel', 'name' => 'Barrel', 'name_ar' => 'برميل', 'sort_order' => 17],
            ['slug' => 'other', 'name' => 'Other', 'name_ar' => 'أخرى', 'sort_order' => 18],
        ];
    }

    /** Extra units already used in the catalog; kept so existing listings keep their unit ids. */
    /** @return list<array{slug: string, name: string, name_ar: string, sort_order: int}> */
    public static function additionalUnits(): array
    {
        return [
            ['slug' => 'carton', 'name' => 'Carton', 'name_ar' => 'كرتون', 'sort_order' => 19],
            ['slug' => 'package', 'name' => 'Package', 'name_ar' => 'طرد', 'sort_order' => 20],
            ['slug' => 'crate', 'name' => 'Crate', 'name_ar' => 'قفص', 'sort_order' => 21],
        ];
    }

    public static function ensureCanonicalUnits(): void
    {
        $existing = self::query()->pluck('id', 'slug');
        foreach (array_merge(self::canonicalUnits(), self::additionalUnits()) as $unit) {
            if ($existing->has($unit['slug'])) {
                continue;
            }
            self::query()->create($unit + ['is_active' => true]);
        }
    }
}
