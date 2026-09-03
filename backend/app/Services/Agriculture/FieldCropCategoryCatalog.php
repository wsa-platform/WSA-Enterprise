<?php

namespace App\Services\Agriculture;

/**
 * Public plant-production / field-crop taxonomy for clients.
 * Categories match the existing field-crop selector; scientific names come from FieldCropTaxonomyCatalog.
 */
class FieldCropCategoryCatalog
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(): array
    {
        return [
            'source' => 'field_crop_category_catalog',
            'plant_production_sections' => self::plantProductionSections(),
            'library_categories' => self::libraryCategories(),
            'categories' => self::categories(),
        ];
    }

    /**
     * @return list<array{id: string, name: string, library_category_ids: list<string>}>
     */
    public static function plantProductionSections(): array
    {
        return [
            ['id' => 'field-crops', 'name' => 'محاصيل الحقل', 'library_category_ids' => ['grains', 'sugar', 'forage', 'oil', 'legumes', 'fiber', 'other']],
            ['id' => 'vegetable-crops', 'name' => 'محاصيل الخضر', 'library_category_ids' => ['vegetables']],
            ['id' => 'fruit-trees', 'name' => 'أشجار الفاكهة', 'library_category_ids' => ['fruit-trees', 'palm']],
            ['id' => 'ornamental-plants', 'name' => 'نباتات و زهور الزينة', 'library_category_ids' => ['ornamental']],
            ['id' => 'medicinal-aromatic-plants', 'name' => 'النباتات الطبية و العطرية', 'library_category_ids' => ['medicinal-aromatic']],
        ];
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    public static function libraryCategories(): array
    {
        return [
            ['id' => 'grains', 'name' => 'محاصيل الحبوب'],
            ['id' => 'sugar', 'name' => 'المحاصيل السكرية'],
            ['id' => 'forage', 'name' => 'محاصيل الأعلاف'],
            ['id' => 'oil', 'name' => 'المحاصيل الزيتية'],
            ['id' => 'vegetables', 'name' => 'محاصيل الخضر'],
            ['id' => 'legumes', 'name' => 'المحاصيل البقولية'],
            ['id' => 'fruit-trees', 'name' => 'أشجار الفاكهة'],
            ['id' => 'palm', 'name' => 'النخيل'],
            ['id' => 'medicinal-aromatic', 'name' => 'النباتات الطبية والعطرية'],
            ['id' => 'ornamental', 'name' => 'نباتات الزينة'],
            ['id' => 'fiber', 'name' => 'محاصيل الألياف'],
            ['id' => 'other', 'name' => 'محاصيل أخرى'],
        ];
    }

    /**
     * @return list<array{id: string, name: string, crops: list<array{id: string, name: string, scientific_name: string}>}>
     */
    public static function categories(): array
    {
        $named = [
            'grains' => [
                'name' => 'محاصيل الحبوب',
                'crops' => [
                    ['id' => 'wheat', 'name' => 'القمح'],
                    ['id' => 'corn', 'name' => 'الذرة'],
                    ['id' => 'rice', 'name' => 'الأرز'],
                    ['id' => 'barley', 'name' => 'الشعير'],
                    ['id' => 'oats', 'name' => 'الشوفان'],
                    ['id' => 'sorghum', 'name' => 'الذرة الرفيعة'],
                    ['id' => 'millet', 'name' => 'الدخن'],
                    ['id' => 'rye', 'name' => 'الجاودار'],
                    ['id' => 'triticale', 'name' => 'التريتيكال'],
                ],
            ],
            'sugar' => [
                'name' => 'المحاصيل السكرية',
                'crops' => [
                    ['id' => 'sugarcane', 'name' => 'قصب السكر'],
                    ['id' => 'sugar-beet', 'name' => 'بنجر السكر'],
                ],
            ],
            'forage' => [
                'name' => 'محاصيل الأعلاف',
                'crops' => [
                    ['id' => 'alfalfa', 'name' => 'البرسيم'],
                    ['id' => 'clover', 'name' => 'الفصة'],
                    ['id' => 'fodder-corn', 'name' => 'الذرة العلفية'],
                    ['id' => 'fodder-sorghum', 'name' => 'السورجم العلفي'],
                    ['id' => 'sudan-grass', 'name' => 'حشيشة السودان'],
                ],
            ],
            'oil' => [
                'name' => 'المحاصيل الزيتية',
                'crops' => [
                    ['id' => 'sunflower', 'name' => 'دوار الشمس'],
                    ['id' => 'soybean', 'name' => 'فول الصويا'],
                    ['id' => 'sesame', 'name' => 'السمسم'],
                    ['id' => 'peanut', 'name' => 'الفول السوداني'],
                    ['id' => 'canola', 'name' => 'الكانولا'],
                    ['id' => 'castor', 'name' => 'الخروع'],
                ],
            ],
            'legumes' => [
                'name' => 'المحاصيل البقولية',
                'crops' => [
                    ['id' => 'fava-bean', 'name' => 'الفول'],
                    ['id' => 'lentil', 'name' => 'العدس'],
                    ['id' => 'chickpea', 'name' => 'الحمص'],
                    ['id' => 'pea', 'name' => 'البازلاء'],
                    ['id' => 'cowpea', 'name' => 'اللوبيا'],
                ],
            ],
            'fiber' => [
                'name' => 'محاصيل الألياف',
                'crops' => [
                    ['id' => 'cotton', 'name' => 'القطن'],
                    ['id' => 'flax', 'name' => 'الكتان'],
                    ['id' => 'hemp', 'name' => 'القنب'],
                    ['id' => 'jute', 'name' => 'الجوت'],
                ],
            ],
            'other' => [
                'name' => 'محاصيل أخرى',
                'crops' => [
                    ['id' => 'tobacco', 'name' => 'التبغ'],
                ],
            ],
            'vegetables' => ['name' => 'محاصيل الخضر', 'crops' => []],
            'fruit-trees' => ['name' => 'أشجار الفاكهة', 'crops' => []],
            'palm' => ['name' => 'النخيل', 'crops' => []],
            'medicinal-aromatic' => ['name' => 'النباتات الطبية والعطرية', 'crops' => []],
            'ornamental' => ['name' => 'نباتات الزينة', 'crops' => []],
        ];

        $categories = [];
        foreach ($named as $id => $entry) {
            $crops = [];
            foreach ($entry['crops'] as $crop) {
                $taxonomy = FieldCropTaxonomyCatalog::entryFor($crop['id']);
                $crops[] = [
                    'id' => $crop['id'],
                    'name' => $crop['name'],
                    'scientific_name' => (string) ($taxonomy['scientific_name'] ?? ''),
                ];
            }
            $categories[] = [
                'id' => $id,
                'name' => $entry['name'],
                'crops' => $crops,
            ];
        }

        return $categories;
    }
}
