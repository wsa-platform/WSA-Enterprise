<?php

namespace App\Services\Agriculture\Research;

use App\Services\Agriculture\FieldCropTaxonomyCatalog;

/**
 * Reusable agricultural entity recognition catalog.
 * Uses existing crop taxonomy — no crop-specific hard-coded logic in planners.
 */
final class AgriculturalEntityCatalog
{
    /** @return list<string> */
    public static function researchIntents(): array
    {
        return [
            'cultivation',
            'environmental_requirements',
            'irrigation',
            'fertilization',
            'soil_management',
            'plant_nutrition',
            'disease',
            'pest',
            'diagnosis_scientific',
            'productivity',
            'varieties',
            'animal_production',
            'poultry_production',
            'beekeeping',
            'aquaculture',
            'feed',
            'agricultural_economics',
            'agricultural_industry',
            'scientific_literature',
            'general_knowledge',
        ];
    }

    /** @return list<string> */
    public static function subjectTypes(): array
    {
        return [
            'crop',
            'animal',
            'insect',
            'fish',
            'soil',
            'disease',
            'pest',
            'nutrient',
            'agricultural_material',
            'production_system',
            'research_topic',
            'other',
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    public static function intentKeywordSignals(): array
    {
        return [
            'cultivation' => ['cultivation', 'cultivate', 'grow', 'planting', 'sowing', 'زراعة', 'أزرع', 'زرع', 'زراع'],
            'environmental_requirements' => ['climate requirement', 'temperature requirement', 'environmental', 'متطلبات بيئية', 'مناخ'],
            'irrigation' => ['irrigation', 'water scheduling', 'drip', 'ري', 'مياه', 'ري'],
            'fertilization' => ['fertilizer', 'fertilization', 'fertiliser', 'سماد', 'تسميد', 'سماد'],
            'soil_management' => ['soil management', 'soil preparation', 'soil fertility', 'soil health', 'إدارة التربة', 'تحضير التربة', 'خصوبة التربة'],
            'plant_nutrition' => ['plant nutrition', 'nutrient deficiency', 'تغذية النبات', 'نقص عناصر'],
            'disease' => ['disease', 'pathogen', 'blight', 'مرض', 'أمراض', 'فطري'],
            'pest' => ['pest', 'insect pest', 'آفة', 'آفات', 'حشر'],
            'diagnosis_scientific' => ['diagnosis', 'symptom identification', 'تشخيص'],
            'productivity' => ['yield', 'productivity', 'harvest', 'إنتاجية', 'محصول'],
            'varieties' => ['variety', 'cultivar', 'breeding', 'صنف', 'أصناف'],
            'animal_production' => ['livestock', 'animal production', 'cattle', 'إنتاج حيواني'],
            'poultry_production' => ['poultry', 'broiler', 'layer', 'دواجن'],
            'beekeeping' => ['beekeeping', 'apiculture', 'نحل', 'تربية نحل'],
            'aquaculture' => ['aquaculture', 'fish farming', 'استزراع', 'أسماك'],
            'feed' => ['animal feed', 'feed formulation', 'علف', 'تغذية'],
            'agricultural_economics' => ['farm economics', 'profitability', 'agricultural economics', 'اقتصاد'],
            'agricultural_industry' => ['processing', 'value chain', 'agricultural industry', 'صناعة'],
            'scientific_literature' => ['scientific literature', 'peer reviewed', 'publication', 'أبحاث علمية', 'منشورات'],
            'general_knowledge' => ['agriculture', 'farming', 'زراعة'],
        ];
    }

    /**
     * @return list<array{crop_id: string, labels: list<string>}>
     */
    public static function cropRecognitionEntries(): array
    {
        $entries = [];
        $arabicLabels = self::arabicCropLabels();

        foreach (self::cropIds() as $cropId) {
            $taxonomy = FieldCropTaxonomyCatalog::entryFor($cropId);
            $labels = [$cropId];
            if ($taxonomy !== null) {
                $labels[] = $taxonomy['scientific_name'];
                $labels = array_merge($labels, $taxonomy['synonyms']);
            }
            if (isset($arabicLabels[$cropId])) {
                $labels[] = $arabicLabels[$cropId];
            }

            $entries[] = [
                'crop_id' => $cropId,
                'labels' => array_values(array_unique(array_filter(array_map(
                    static fn (string $label): string => mb_strtolower(trim($label)),
                    $labels,
                )))),
            ];
        }

        return $entries;
    }

    /**
     * @return list<string>
     */
    private static function cropIds(): array
    {
        return [
            'wheat', 'corn', 'rice', 'barley', 'oats', 'sorghum', 'millet', 'rye', 'triticale',
            'sugarcane', 'sugar-beet', 'alfalfa', 'clover', 'fodder-corn', 'fodder-sorghum', 'sudan-grass',
            'sunflower', 'soybean', 'sesame', 'peanut', 'canola', 'castor',
            'fava-bean', 'lentil', 'chickpea', 'pea', 'cowpea',
            'cotton', 'flax', 'hemp', 'jute', 'tobacco',
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function arabicCropLabels(): array
    {
        return [
            'wheat' => 'القمح',
            'corn' => 'الذرة',
            'rice' => 'الأرز',
            'barley' => 'الشعير',
            'oats' => 'الشوفان',
            'sorghum' => 'الذرة الرفيعة',
            'millet' => 'الدخن',
            'rye' => 'الجاودار',
            'triticale' => 'التريتيكال',
            'sugarcane' => 'قصب السكر',
            'sugar-beet' => 'بنجر السكر',
            'sunflower' => 'دوار الشمس',
            'soybean' => 'فول الصويا',
            'sesame' => 'السمسم',
            'peanut' => 'الفول السوداني',
            'canola' => 'الكانولا',
            'tomato' => 'طماطم',
        ];
    }

    /**
     * @return array{crop_id: string, label: string}|null
     */
    public static function recognizeCrop(string $normalizedQuestion): ?array
    {
        foreach (self::cropRecognitionEntries() as $entry) {
            foreach ($entry['labels'] as $label) {
                if ($label === '') {
                    continue;
                }
                if (self::containsTerm($normalizedQuestion, $label)) {
                    return [
                        'crop_id' => $entry['crop_id'],
                        'label' => $label,
                    ];
                }
            }
        }

        if (self::containsTerm($normalizedQuestion, 'طماطم') || self::containsTerm($normalizedQuestion, 'tomato')) {
            return ['crop_id' => 'tomato', 'label' => 'tomato'];
        }

        return null;
    }

    public static function containsTerm(string $haystack, string $needle): bool
    {
        $needle = mb_strtolower(trim($needle));
        if ($needle === '') {
            return false;
        }

        if (mb_strlen($needle) <= 4 && preg_match('/\p{L}/u', $needle) === 1) {
            return preg_match('/\b'.preg_quote($needle, '/').'\b/u', $haystack) === 1;
        }

        return str_contains($haystack, $needle);
    }
}
