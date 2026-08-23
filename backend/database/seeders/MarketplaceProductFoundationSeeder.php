<?php

namespace Database\Seeders;

use App\Models\MarketplaceAttributeDefinition;
use App\Models\MarketplaceCategory;
use App\Models\MarketplaceUnit;
use Illuminate\Database\Seeder;

class MarketplaceProductFoundationSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['slug' => 'agricultural-crops', 'name' => 'Agricultural crops', 'name_ar' => 'المحاصيل الزراعية', 'sort_order' => 10],
            ['slug' => 'fruits', 'name' => 'Fruits', 'name_ar' => 'الفواكه', 'sort_order' => 11],
            ['slug' => 'vegetables', 'name' => 'Vegetables', 'name_ar' => 'الخضروات', 'sort_order' => 12],
            ['slug' => 'grains-legumes', 'name' => 'Grains and legumes', 'name_ar' => 'الحبوب والبقوليات', 'sort_order' => 13],
            ['slug' => 'seeds', 'name' => 'Seeds', 'name_ar' => 'البذور', 'sort_order' => 14],
            ['slug' => 'plants-seedlings', 'name' => 'Plants and seedlings', 'name_ar' => 'النباتات والشتلات', 'sort_order' => 15],
            ['slug' => 'bee-products', 'name' => 'Bee products', 'name_ar' => 'منتجات النحل', 'sort_order' => 16],
            ['slug' => 'dairy-products', 'name' => 'Dairy products', 'name_ar' => 'منتجات الألبان', 'sort_order' => 17],
            ['slug' => 'animal-products', 'name' => 'Animal products', 'name_ar' => 'المنتجات الحيوانية', 'sort_order' => 18],
            ['slug' => 'fish-products', 'name' => 'Fish products', 'name_ar' => 'المنتجات السمكية', 'sort_order' => 19],
            ['slug' => 'processed-food', 'name' => 'Processed food', 'name_ar' => 'المنتجات الغذائية المصنعة', 'sort_order' => 20],
            ['slug' => 'herbs-spices', 'name' => 'Herbs and spices', 'name_ar' => 'الأعشاب والتوابل', 'sort_order' => 21],
            ['slug' => 'fertilizers-agri-products', 'name' => 'Fertilizers and agricultural products', 'name_ar' => 'الأسمدة والمنتجات الزراعية', 'sort_order' => 22],
            ['slug' => 'agricultural-supplies', 'name' => 'Agricultural production supplies', 'name_ar' => 'مستلزمات الإنتاج الزراعي', 'sort_order' => 23],
        ];

        foreach ($categories as $category) {
            MarketplaceCategory::updateOrCreate(
                ['slug' => $category['slug']],
                $category + ['is_active' => true],
            );
        }

        $units = [
            ['slug' => 'ton', 'name' => 'Ton', 'name_ar' => 'طن', 'sort_order' => 1],
            ['slug' => 'kg', 'name' => 'Kilogram', 'name_ar' => 'كجم', 'sort_order' => 2],
            ['slug' => 'box', 'name' => 'Box', 'name_ar' => 'صندوق', 'sort_order' => 3],
            ['slug' => 'carton', 'name' => 'Carton', 'name_ar' => 'كرتون', 'sort_order' => 4],
            ['slug' => 'pack', 'name' => 'Pack', 'name_ar' => 'عبوة', 'sort_order' => 5],
            ['slug' => 'liter', 'name' => 'Liter', 'name_ar' => 'لتر', 'sort_order' => 6],
        ];

        foreach ($units as $unit) {
            MarketplaceUnit::updateOrCreate(
                ['slug' => $unit['slug']],
                $unit + ['is_active' => true],
            );
        }

        $cropId = MarketplaceCategory::where('slug', 'agricultural-crops')->value('id');
        $foodId = MarketplaceCategory::where('slug', 'processed-food')->value('id');

        $attributes = [
            ['slug' => 'variety', 'name' => 'Variety', 'name_ar' => 'الصنف', 'category_id' => $cropId, 'product_type' => 'crop', 'sort_order' => 1],
            ['slug' => 'grade', 'name' => 'Grade', 'name_ar' => 'درجة الجودة', 'category_id' => $cropId, 'product_type' => 'crop', 'sort_order' => 2],
            ['slug' => 'size_weight', 'name' => 'Size / weight', 'name_ar' => 'الحجم / الوزن', 'category_id' => $cropId, 'product_type' => 'crop', 'sort_order' => 3],
            ['slug' => 'harvest_season', 'name' => 'Harvest season', 'name_ar' => 'موسم الإنتاج', 'category_id' => $cropId, 'product_type' => 'crop', 'sort_order' => 4],
            ['slug' => 'storage_conditions', 'name' => 'Storage conditions', 'name_ar' => 'شروط التخزين', 'category_id' => null, 'product_type' => null, 'sort_order' => 5],
            ['slug' => 'ingredients', 'name' => 'Ingredients', 'name_ar' => 'المكونات', 'category_id' => $foodId, 'product_type' => 'food', 'sort_order' => 6],
            ['slug' => 'production_date', 'name' => 'Production date', 'name_ar' => 'تاريخ الإنتاج', 'category_id' => $foodId, 'product_type' => 'food', 'sort_order' => 7],
            ['slug' => 'expiry_date', 'name' => 'Expiry date', 'name_ar' => 'تاريخ انتهاء الصلاحية', 'category_id' => $foodId, 'product_type' => 'food', 'sort_order' => 8],
            ['slug' => 'certifications', 'name' => 'Certifications', 'name_ar' => 'الشهادات', 'category_id' => $foodId, 'product_type' => 'food', 'sort_order' => 9],
        ];

        foreach ($attributes as $attribute) {
            MarketplaceAttributeDefinition::updateOrCreate(
                ['slug' => $attribute['slug']],
                $attribute + [
                    'data_type' => MarketplaceAttributeDefinition::TYPE_STRING,
                    'is_required' => false,
                    'is_active' => true,
                ],
            );
        }
    }
}
