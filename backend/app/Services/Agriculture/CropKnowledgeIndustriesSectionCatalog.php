<?php

namespace App\Services\Agriculture;

class CropKnowledgeIndustriesSectionCatalog
{
    /** @return list<array{key: string, title: string, search_terms_en: string}> */
    public static function sections(): array
    {
        return [
            ['key' => 'food_products', 'title' => 'المنتجات الغذائية', 'search_terms_en' => 'food products human consumption'],
            ['key' => 'feed_livestock', 'title' => 'الأعلاف والثروة الحيوانية', 'search_terms_en' => 'animal feed livestock'],
            ['key' => 'industrial_processing', 'title' => 'المعالجة والصناعات التحويلية', 'search_terms_en' => 'industrial processing manufacturing'],
            ['key' => 'bioeconomy_applications', 'title' => 'تطبيقات الاقتصاد الحيوي', 'search_terms_en' => 'bioeconomy bioproducts'],
            ['key' => 'value_chain', 'title' => 'سلسلة القيمة والأسواق', 'search_terms_en' => 'value chain market trade'],
            ['key' => 'economic_importance', 'title' => 'الأهمية الاقتصادية', 'search_terms_en' => 'economic importance production'],
        ];
    }

    /** @return list<string> */
    public static function keys(): array
    {
        return array_column(self::sections(), 'key');
    }
}
