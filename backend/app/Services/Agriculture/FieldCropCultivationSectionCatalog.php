<?php

namespace App\Services\Agriculture;

class FieldCropCultivationSectionCatalog
{
    /** @return list<array{key: string, title: string}> */
    public static function sections(): array
    {
        return [
            ['key' => 'commercial_scientific_name', 'title' => 'اسم المحصول التجاري والاسم العلمي'],
            ['key' => 'seed_rate', 'title' => 'كمية التقاوي'],
            ['key' => 'planting_season', 'title' => 'ميعاد الزراعة المناسب'],
            ['key' => 'soil', 'title' => 'الأرض والتربة المناسبة'],
            ['key' => 'land_preparation', 'title' => 'كيفية إعداد الأرض للزراعة'],
            ['key' => 'planting_methods', 'title' => 'طرق الزراعة'],
            ['key' => 'irrigation', 'title' => 'طرق الري المناسبة'],
            ['key' => 'fertilization', 'title' => 'التسميد وطرق إضافته'],
            ['key' => 'environmental_requirements', 'title' => 'الاحتياجات البيئية'],
            ['key' => 'vegetative_growth_duration', 'title' => 'مدة النمو الخضري'],
            ['key' => 'reproductive_phase', 'title' => 'المرحلة التكاثرية'],
            ['key' => 'harvest', 'title' => 'موعد الحصاد أو جمع المحصول'],
            ['key' => 'diseases', 'title' => 'الأمراض التي تصيب المحصول'],
        ];
    }

    /** @return list<string> */
    public static function keys(): array
    {
        return array_column(self::sections(), 'key');
    }

    public static function titleFor(string $key): string
    {
        foreach (self::sections() as $section) {
            if ($section['key'] === $key) {
                return $section['title'];
            }
        }

        return $key;
    }
}
