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
            ['slug' => 'fruits', 'name' => 'Fruits', 'name_ar' => 'الفاكهة', 'sort_order' => 11],
            ['slug' => 'vegetables', 'name' => 'Vegetables', 'name_ar' => 'الخضروات', 'sort_order' => 12],
            ['slug' => 'grains-legumes', 'name' => 'Grains and legumes', 'name_ar' => 'الحبوب والبقوليات', 'sort_order' => 13],
            ['slug' => 'seeds', 'name' => 'Seeds', 'name_ar' => 'البذور', 'sort_order' => 14],
            ['slug' => 'plants-seedlings', 'name' => 'Plants and seedlings', 'name_ar' => 'النباتات والشتلات', 'sort_order' => 15],
            ['slug' => 'bee-products', 'name' => 'Bee products', 'name_ar' => 'منتجات النحل', 'sort_order' => 16],
            ['slug' => 'dairy-products', 'name' => 'Dairy products', 'name_ar' => 'منتجات الألبان', 'sort_order' => 17],
            ['slug' => 'animal-products', 'name' => 'Animal products', 'name_ar' => 'المنتجات الحيوانية', 'sort_order' => 18],
            ['slug' => 'fish-products', 'name' => 'Fish products', 'name_ar' => 'المنتجات السمكية', 'sort_order' => 19],
            ['slug' => 'processed-food', 'name' => 'Processed agricultural products', 'name_ar' => 'المنتجات الزراعية المصنعة', 'sort_order' => 20],
            ['slug' => 'herbs-spices', 'name' => 'Herbs and spices', 'name_ar' => 'الأعشاب والتوابل', 'sort_order' => 21],
            ['slug' => 'fertilizers-agri-products', 'name' => 'Fertilizers and agricultural products', 'name_ar' => 'الأسمدة والمنتجات الزراعية', 'sort_order' => 22],
            ['slug' => 'agricultural-supplies', 'name' => 'Agricultural production supplies', 'name_ar' => 'مستلزمات الإنتاج الزراعي', 'sort_order' => 23],
            ['slug' => 'grains', 'name' => 'Grains', 'name_ar' => 'الحبوب', 'sort_order' => 24],
            ['slug' => 'legumes', 'name' => 'Legumes', 'name_ar' => 'البقوليات', 'sort_order' => 25],
            ['slug' => 'feed', 'name' => 'Feed', 'name_ar' => 'الأعلاف', 'sort_order' => 26],
            ['slug' => 'fertilizers', 'name' => 'Fertilizers and nutrients', 'name_ar' => 'الأسمدة والمخصبات', 'sort_order' => 27],
            ['slug' => 'seeds-seedlings', 'name' => 'Seeds and seedlings', 'name_ar' => 'البذور والشتلات', 'sort_order' => 28],
            ['slug' => 'meat-poultry', 'name' => 'Meat and poultry', 'name_ar' => 'اللحوم والدواجن', 'sort_order' => 29],
            ['slug' => 'fish-seafood', 'name' => 'Fish and seafood', 'name_ar' => 'الأسماك والمأكولات البحرية', 'sort_order' => 30],
            ['slug' => 'honey-bee-products', 'name' => 'Honey and bee products', 'name_ar' => 'العسل ومنتجات النحل', 'sort_order' => 31],
            ['slug' => 'food-beverages', 'name' => 'Food and beverages', 'name_ar' => 'الأغذية والمشروبات', 'sort_order' => 32],
            ['slug' => 'livestock-supplies', 'name' => 'Livestock supplies', 'name_ar' => 'مستلزمات الثروة الحيوانية', 'sort_order' => 33],
            ['slug' => 'beekeeping-supplies', 'name' => 'Beekeeping supplies', 'name_ar' => 'مستلزمات تربية النحل', 'sort_order' => 34],
            ['slug' => 'aquaculture-supplies', 'name' => 'Aquaculture supplies', 'name_ar' => 'مستلزمات الاستزراع السمكي', 'sort_order' => 35],
            ['slug' => 'agricultural-equipment', 'name' => 'Agricultural equipment', 'name_ar' => 'المعدات الزراعية', 'sort_order' => 36],
            ['slug' => 'seedlings', 'name' => 'Seedlings', 'name_ar' => 'الشتلات', 'sort_order' => 37],
            ['slug' => 'plants', 'name' => 'Plants', 'name_ar' => 'النباتات', 'sort_order' => 38],
            ['slug' => 'pesticides', 'name' => 'Pesticides and agricultural materials', 'name_ar' => 'المبيدات والمواد الزراعية', 'sort_order' => 39],
            ['slug' => 'food-products', 'name' => 'Food products', 'name_ar' => 'المنتجات الغذائية', 'sort_order' => 40],
            ['slug' => 'foodstuffs', 'name' => 'Foodstuffs', 'name_ar' => 'المواد الغذائية', 'sort_order' => 46],
            ['slug' => 'meat', 'name' => 'Meat', 'name_ar' => 'اللحوم', 'sort_order' => 41],
            ['slug' => 'poultry', 'name' => 'Poultry', 'name_ar' => 'الدواجن', 'sort_order' => 42],
            ['slug' => 'oils', 'name' => 'Oils', 'name_ar' => 'الزيوت', 'sort_order' => 43],
            ['slug' => 'dates', 'name' => 'Dates', 'name_ar' => 'التمور', 'sort_order' => 44],
            ['slug' => 'other', 'name' => 'Other agriculture and food products', 'name_ar' => 'منتجات أخرى مرتبطة بالزراعة والغذاء', 'sort_order' => 45],
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
            ['slug' => 'gram', 'name' => 'Gram', 'name_ar' => 'جرام', 'sort_order' => 3],
            ['slug' => 'liter', 'name' => 'Liter', 'name_ar' => 'لتر', 'sort_order' => 4],
            ['slug' => 'box', 'name' => 'Box', 'name_ar' => 'صندوق', 'sort_order' => 5],
            ['slug' => 'carton', 'name' => 'Carton', 'name_ar' => 'كرتون', 'sort_order' => 6],
            ['slug' => 'pack', 'name' => 'Pack', 'name_ar' => 'عبوة', 'sort_order' => 7],
            ['slug' => 'package', 'name' => 'Package', 'name_ar' => 'طرد', 'sort_order' => 8],
            ['slug' => 'bag', 'name' => 'Bag', 'name_ar' => 'كيس', 'sort_order' => 9],
            ['slug' => 'bottle', 'name' => 'Bottle', 'name_ar' => 'زجاجة', 'sort_order' => 10],
            ['slug' => 'piece', 'name' => 'Piece', 'name_ar' => 'قطعة', 'sort_order' => 11],
            ['slug' => 'crate', 'name' => 'Crate', 'name_ar' => 'قفص', 'sort_order' => 12],
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
