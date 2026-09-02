<?php

namespace App\Services\Agriculture;

class CropKnowledgeScientificResearchSectionCatalog
{
    /** @return list<array{key: string, title: string, search_terms_en: string}> */
    public static function sections(): array
    {
        return [
            ['key' => 'research_overview', 'title' => 'نظرة عامة على الأبحاث العلمية', 'search_terms_en' => 'agricultural research review'],
            ['key' => 'breeding_genetics', 'title' => 'الأبحاث الوراثية والتربية', 'search_terms_en' => 'breeding genetics cultivar'],
            ['key' => 'agronomy_trials', 'title' => 'تجارب الإرشاد الزراعي والزراعة', 'search_terms_en' => 'agronomy field trials cultivation'],
            ['key' => 'pest_disease_research', 'title' => 'أبحاث الآفات والأمراض', 'search_terms_en' => 'pest disease pathology'],
            ['key' => 'climate_sustainability', 'title' => 'التكيف المناخي والاستدامة', 'search_terms_en' => 'climate adaptation sustainability'],
            ['key' => 'post_harvest_research', 'title' => 'أبحاث ما بعد الحصاد والجودة', 'search_terms_en' => 'post harvest quality storage'],
            ['key' => 'recent_publications', 'title' => 'منشورات علمية بارزة', 'search_terms_en' => 'peer reviewed publication'],
        ];
    }

    /** @return list<string> */
    public static function keys(): array
    {
        return array_column(self::sections(), 'key');
    }
}
