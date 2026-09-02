<?php

namespace App\Services\Agriculture\Research;

/**
 * Canonical agricultural domain taxonomy for query understanding and planning.
 * Single source of truth — do not duplicate elsewhere.
 */
final class AgriculturalDomainCatalog
{
    public const PLANT_PRODUCTION = 'plant_production';

    public const FIELD_CROPS = 'field_crops';

    public const VEGETABLES = 'vegetables';

    public const FRUIT_TREES = 'fruit_trees';

    public const ORNAMENTAL_PLANTS = 'ornamental_plants';

    public const MEDICINAL_AROMATIC_PLANTS = 'medicinal_aromatic_plants';

    public const SOIL = 'soil';

    public const IRRIGATION_WATER = 'irrigation_water';

    public const FERTILIZATION = 'fertilization';

    public const PLANT_NUTRITION = 'plant_nutrition';

    public const PESTS_DISEASES = 'pests_diseases';

    public const ANIMAL_PRODUCTION = 'animal_production';

    public const POULTRY = 'poultry';

    public const BEEKEEPING = 'beekeeping';

    public const AQUACULTURE = 'aquaculture';

    public const AGRICULTURAL_ECONOMICS = 'agricultural_economics';

    public const AGRICULTURAL_INDUSTRIES = 'agricultural_industries';

    public const AGRICULTURAL_RESEARCH = 'agricultural_research';

    public const GENERAL_AGRICULTURE = 'general_agriculture';

    /** @return list<string> */
    public static function all(): array
    {
        return [
            self::PLANT_PRODUCTION,
            self::FIELD_CROPS,
            self::VEGETABLES,
            self::FRUIT_TREES,
            self::ORNAMENTAL_PLANTS,
            self::MEDICINAL_AROMATIC_PLANTS,
            self::SOIL,
            self::IRRIGATION_WATER,
            self::FERTILIZATION,
            self::PLANT_NUTRITION,
            self::PESTS_DISEASES,
            self::ANIMAL_PRODUCTION,
            self::POULTRY,
            self::BEEKEEPING,
            self::AQUACULTURE,
            self::AGRICULTURAL_ECONOMICS,
            self::AGRICULTURAL_INDUSTRIES,
            self::AGRICULTURAL_RESEARCH,
            self::GENERAL_AGRICULTURE,
        ];
    }

    public static function isValid(string $domain): bool
    {
        return in_array($domain, self::all(), true);
    }

    /**
     * Maps legacy Stage 1 planner domain keys to canonical domains.
     *
     * @return array<string, string>
     */
    public static function legacyDomainMap(): array
    {
        return [
            'crop_cultivation' => self::FIELD_CROPS,
            'irrigation' => self::IRRIGATION_WATER,
            'fertilization' => self::FERTILIZATION,
            'soil' => self::SOIL,
            'plant_nutrition' => self::PLANT_NUTRITION,
            'pests' => self::PESTS_DISEASES,
            'diseases' => self::PESTS_DISEASES,
            'yield' => self::PLANT_PRODUCTION,
            'varieties' => self::PLANT_PRODUCTION,
            'animal_production' => self::ANIMAL_PRODUCTION,
            'poultry' => self::POULTRY,
            'beekeeping' => self::BEEKEEPING,
            'aquaculture' => self::AQUACULTURE,
            'feed' => self::ANIMAL_PRODUCTION,
            'agricultural_economics' => self::AGRICULTURAL_ECONOMICS,
            'agricultural_industries' => self::AGRICULTURAL_INDUSTRIES,
            'scientific_publications' => self::AGRICULTURAL_RESEARCH,
        ];
    }

    public static function normalize(string $domain): string
    {
        $domain = trim($domain);
        if ($domain === '') {
            return self::GENERAL_AGRICULTURE;
        }

        if (self::isValid($domain)) {
            return $domain;
        }

        return self::legacyDomainMap()[$domain] ?? self::GENERAL_AGRICULTURE;
    }

    /**
     * @return array<string, list<string>>
     */
    public static function keywordSignals(): array
    {
        return [
            self::FIELD_CROPS => ['field crop', 'field crops', 'cereal', 'grain crop', 'زراعة', 'محصول حقل', 'محاصيل حقل', 'قمح', 'wheat', 'cultivation'],
            self::VEGETABLES => ['vegetable', 'vegetables', 'خضار', 'طماطم', 'tomato', 'tomatoes'],
            self::FRUIT_TREES => ['fruit tree', 'orchard', 'أشجار مثمرة', 'فاكهة', 'fruit'],
            self::SOIL => ['soil', 'soil health', 'soil fertility', 'تربة', 'خصوبة التربة', 'soil management'],
            self::IRRIGATION_WATER => ['irrigation', 'water management', 'drip irrigation', 'ري', 'مياه', 'irrigation scheduling'],
            self::FERTILIZATION => ['fertilizer', 'fertilization', 'fertiliser', 'سماد', 'تسميد', 'nutrient application'],
            self::PLANT_NUTRITION => ['plant nutrition', 'micronutrient', 'macronutrient', 'تغذية النبات', 'عناصر غذائية'],
            self::PESTS_DISEASES => ['pest', 'pests', 'disease', 'pathology', 'آفة', 'آفات', 'مرض', 'أمراض', 'plant protection'],
            self::ANIMAL_PRODUCTION => ['livestock', 'animal production', 'cattle', 'إنتاج حيواني', 'ماشية'],
            self::POULTRY => ['poultry', 'broiler', 'layer hen', 'دواجن', 'فراخ'],
            self::BEEKEEPING => ['beekeeping', 'apiculture', 'pollination', 'نحل', 'تربية نحل'],
            self::AQUACULTURE => ['aquaculture', 'fish farming', 'fisheries', 'استزراع', 'أسماك'],
            self::AGRICULTURAL_ECONOMICS => ['agricultural economics', 'farm profitability', 'economics', 'اقتصاد زراعي', 'ربحية'],
            self::AGRICULTURAL_INDUSTRIES => ['agricultural industry', 'value chain', 'processing', 'صناعات زراعية', 'تصنيع'],
            self::AGRICULTURAL_RESEARCH => ['scientific literature', 'peer reviewed', 'research publication', 'أبحاث علمية', 'منشورات علمية'],
            self::GENERAL_AGRICULTURE => ['agriculture', 'farming', 'زراعة', 'مزارع'],
        ];
    }
}
