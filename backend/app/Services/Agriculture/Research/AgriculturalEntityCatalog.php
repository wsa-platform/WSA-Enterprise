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
            'cultivation' => [
                'cultivation', 'cultivate', 'grow', 'planting', 'sowing', 'germination', 'إنبات',
                'زراعة', 'أزرع', 'زرع', 'زراع',
            ],
            'environmental_requirements' => [
                'climate requirement', 'temperature requirement', 'environmental', 'temperature',
                'درجة الحرارة', 'حرارة', 'salinity', 'ملوحة', 'climate', 'متطلبات بيئية', 'مناخ',
            ],
            'irrigation' => [
                'irrigation', 'water scheduling', 'drip', 'water requirement', 'الماء', 'ري', 'مياه',
            ],
            'fertilization' => ['fertilizer', 'fertilization', 'fertiliser', 'سماد', 'تسميد'],
            'soil_management' => [
                'soil management', 'soil preparation', 'soil fertility', 'soil health',
                'إدارة التربة', 'تحضير التربة', 'خصوبة التربة',
            ],
            'plant_nutrition' => [
                'plant nutrition', 'nutrient deficiency', 'potassium', 'بوتاسيوم',
                'نقص البوتاسيوم', 'تغذية النبات', 'نقص عناصر',
            ],
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
            'agricultural_economics' => [
                'farm economics', 'profitability', 'agricultural economics', 'اقتصاد زراعي',
                'economic feasibility', 'feasibility', 'جدوى', 'اقتصادية', 'اقتصادي',
            ],
            'agricultural_industry' => [
                'processing', 'value chain', 'agricultural industry', 'صناعة',
                'drying', 'storage', 'extraction', 'تجفيف', 'تخزين', 'استخلاص',
            ],
            'scientific_literature' => ['scientific literature', 'peer reviewed', 'publication', 'أبحاث علمية', 'منشورات'],
            'general_knowledge' => ['agriculture', 'farming', 'زراعة'],
        ];
    }

    /**
     * Multilingual scientific topic/factor signals (normalized English keys).
     *
     * @return array<string, list<string>>
     */
    public static function topicFactorSignals(): array
    {
        return [
            'temperature' => [
                'temperature', 'thermal', 'heat stress', 'optimal temperature', 'growing temperature',
                'temperature requirement', 'temperature regime', 'درجة الحرارة', 'حرارة',
            ],
            'salinity' => [
                'salinity', 'salt stress', 'saline', 'ملوحة', 'ملح',
            ],
            'water' => [
                'water requirement', 'water use', 'moisture', 'drought', 'الماء', 'مياه', 'irrigation water',
            ],
            'potassium' => [
                'potassium', 'k deficiency', 'potassium deficiency', 'بوتاسيوم', 'نقص البوتاسيوم',
            ],
            'germination' => [
                'germination', 'seed germination', 'seedling emergence', 'إنبات',
            ],
            'nitrogen' => [
                'nitrogen', 'n deficiency', 'نيتروجين', 'نقص النيتروجين',
            ],
            'phosphorus' => [
                'phosphorus', 'phosphate', 'فوسفور', 'نقص الفوسفور',
            ],
            'drying' => [
                'drying', 'dehydration', 'dehydrated', 'oven drying', 'hot air drying', 'تجفيف',
            ],
            'storage' => [
                'storage', 'postharvest storage', 'post-harvest storage', 'shelf life', 'تخزين',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function topicFactorEnglishLabels(): array
    {
        return [
            'temperature' => 'temperature',
            'salinity' => 'salinity',
            'water' => 'water',
            'potassium' => 'potassium',
            'germination' => 'germination',
            'nitrogen' => 'nitrogen',
            'phosphorus' => 'phosphorus',
            'drying' => 'drying',
            'storage' => 'storage',
        ];
    }

    /**
     * Strong (sense-bearing) topic phrases — bare factor labels alone are weak.
     *
     * @return array<string, list<string>>
     */
    public static function strongTopicFactorSignals(): array
    {
        return [
            'temperature' => [
                'optimal temperature', 'growing temperature', 'temperature requirement',
                'temperature regime', 'heat stress', 'thermal regime', 'درجة الحرارة المناسبة',
            ],
            'salinity' => [
                'salt stress', 'salinity stress', 'salinity tolerance', 'saline irrigation', 'تأثير الملوحة',
            ],
            'water' => [
                'water requirement', 'water use', 'irrigation water', 'water stress', 'drought stress', 'احتياج',
            ],
            'potassium' => [
                'potassium deficiency', 'k deficiency', 'نقص البوتاسيوم',
            ],
            'germination' => [
                'seed germination', 'seedling emergence', 'germination temperature', 'إنبات',
            ],
            'nitrogen' => [
                'nitrogen deficiency', 'n deficiency', 'نقص النيتروجين',
            ],
            'phosphorus' => [
                'phosphorus deficiency', 'phosphate deficiency', 'نقص الفوسفور',
            ],
            'drying' => [
                'oven drying', 'hot air drying', 'drying temperature', 'تجفيف',
            ],
            'storage' => [
                'postharvest storage', 'post-harvest storage', 'storage temperature', 'shelf life', 'تخزين',
            ],
        ];
    }

    /**
     * Controlled agricultural growth/climate context (scoring bonus; not all required).
     *
     * @return list<string>
     */
    public static function agriculturalContextSignals(): array
    {
        return [
            'growth', 'growing', 'cultivation', 'crop production', 'crop growth', 'plant growth',
            'germination', 'seedling', 'planting', 'sowing', 'field conditions', 'field crop',
            'greenhouse', 'climate', 'irrigation', 'salinity stress', 'salt stress', 'drought',
            'yield', 'agronomic', 'agronomy', 'rhizome', 'farming', 'environmental requirements',
            'optimal growing', 'crop establishment', 'vegetative', 'phenology',
            'نمو', 'زراعة', 'إنبات', 'ري', 'محصول', 'حقل', 'مناخ',
        ];
    }

    /**
     * Intent-aware negative sense markers (not a global blacklist).
     *
     * @return array<string, list<string>>
     */
    public static function negativeSenseMarkers(): array
    {
        return [
            'extraction' => [
                'extraction', 'extract', 'microwave-assisted', 'microwave assisted',
                'solvent extraction', 'oleoresin', 'essential oil extraction', 'bioactive compound extraction',
                'mae', 'استخلاص',
            ],
            'processing' => [
                'food processing', 'industrial processing', 'processing temperature',
                'process optimization', 'معالجة صناعية',
            ],
            'drying' => [
                'drying', 'dehydration', 'dehydrated', 'oven drying', 'hot air drying', 'تجفيف',
            ],
            'storage' => [
                'storage', 'postharvest storage', 'post-harvest storage', 'shelf life', 'تخزين',
            ],
        ];
    }

    /**
     * Research intents that allow a given negative sense without penalty.
     *
     * @return list<string>
     */
    public static function intentsAllowingNegativeSense(string $sense): array
    {
        return match ($sense) {
            'extraction', 'processing' => ['agricultural_industry', 'scientific_literature'],
            'drying', 'storage' => ['agricultural_industry', 'productivity', 'scientific_literature'],
            default => [],
        };
    }

    public static function intentForTopicFactor(string $factor): ?string
    {
        return match ($factor) {
            'temperature', 'salinity' => 'environmental_requirements',
            'water' => 'irrigation',
            'potassium', 'nitrogen', 'phosphorus' => 'plant_nutrition',
            'germination' => 'cultivation',
            'drying', 'storage' => 'agricultural_industry',
            default => null,
        };
    }

    /**
     * Intent qualifiers (effect / optimal / requirement) — not crop-specific.
     *
     * @return array<string, list<string>>
     */
    public static function intentQualifierSignals(): array
    {
        return [
            'optimal_range' => [
                'optimal', 'optimum', 'optima', 'best', 'ideal', 'suitable', 'preferred',
                'temperature range', 'thermal range',
                'أفضل', 'مناسبة', 'مناسب', 'مثلى', 'مثالي',
            ],
            'effect' => [
                'effect', 'effects', 'impact', 'influence', 'affect', 'affects', 'affected',
                'response', 'responses', 'تأثير', 'اثر', 'أثر',
            ],
            'requirement' => [
                'requirement', 'requirements', 'need', 'needs', 'required', 'require',
                'احتياج', 'احتياجات', 'متطلبات',
            ],
            'economic_feasibility' => ['feasibility', 'profitability', 'جدوى', 'ربحية'],
            'extension_adoption' => ['extension', 'adoption', 'إرشاد', 'تبني'],
        ];
    }

    /**
     * Production-system signals (hydroponics, etc.) — not crop-specific.
     *
     * @return array<string, list<string>>
     */
    public static function productionSystemSignals(): array
    {
        return [
            'hydroponics' => [
                'hydroponics', 'hydroponic', 'soilless culture', 'soilless',
                'الزراعة المائية', 'زراعة مائية', 'هيدروبون', 'هيدروبونيك',
            ],
        ];
    }

    /**
     * Multilingual country/region aliases → English location labels for geo questions.
     *
     * @return array<string, string>
     */
    public static function locationAliases(): array
    {
        return [
            'مصر' => 'Egypt',
            'egyptian' => 'Egypt',
            'egypt' => 'Egypt',
            'ليبيا' => 'Libya',
            'libya' => 'Libya',
            'libyan' => 'Libya',
            'السودان' => 'Sudan',
            'sudan' => 'Sudan',
            'تونس' => 'Tunisia',
            'tunisia' => 'Tunisia',
            'الجزائر' => 'Algeria',
            'algeria' => 'Algeria',
            'المغرب' => 'Morocco',
            'morocco' => 'Morocco',
        ];
    }

    /**
     * Sense-aware scientific synonym expansions for controlled query variants.
     * Synonyms are not always equivalent — callers pick by sense.
     *
     * @return list<string>
     */
    public static function scientificSynonymsForFactor(string $factor, ?string $sense = null): array
    {
        return match ($factor) {
            'temperature' => match ($sense) {
                'seed_germination' => ['temperature', 'germination temperature', 'thermal'],
                'drying_processing' => ['drying temperature', 'temperature', 'thermal'],
                'storage' => ['storage temperature', 'temperature'],
                default => ['temperature', 'thermal', 'heat stress', 'thermal stress'],
            },
            'water' => ['water', 'crop water requirement', 'irrigation requirement', 'evapotranspiration', 'water use'],
            'salinity' => ['salinity', 'salt stress', 'salinity tolerance', 'saline'],
            'germination' => ['germination', 'seed germination', 'emergence', 'seedling emergence'],
            'potassium' => ['potassium', 'potassium deficiency', 'K deficiency'],
            'nitrogen' => ['nitrogen', 'nitrogen deficiency'],
            'phosphorus' => ['phosphorus', 'phosphate'],
            'drying' => ['drying', 'dehydration', 'hot air drying'],
            'storage' => ['storage', 'postharvest storage', 'shelf life'],
            default => $factor !== '' ? [$factor] : [],
        };
    }

    /**
     * Query terms that encode scientific sense (growth / germination / irrigation…).
     *
     * @return list<string>
     */
    public static function senseQueryTerms(string $sense): array
    {
        return match ($sense) {
            'plant_growth' => ['growth', 'physiology', 'cultivation', 'yield'],
            'seed_germination' => [
                'seed germination', 'germination', 'germination temperature',
                'germination rate', 'germination percentage', 'seedling emergence', 'emergence',
            ],
            'crop_water_requirement' => ['irrigation', 'evapotranspiration', 'water use'],
            'salinity_physiology' => ['growth', 'yield', 'physiology'],
            'drying_processing' => ['drying', 'dehydration'],
            'storage' => ['storage', 'postharvest'],
            'plant_nutrition' => ['plant nutrition', 'nutrient deficiency'],
            'agricultural_economics' => ['agricultural economics', 'farm profitability'],
            'agricultural_extension' => ['agricultural extension', 'farmer adoption'],
            'agricultural_industry' => ['processing', 'value chain'],
            default => ['agriculture'],
        };
    }

    /**
     * Evidence signals that directly answer seed-germination questions.
     *
     * @return list<string>
     */
    public static function germinationEvidenceSignals(): array
    {
        return [
            'seed germination', 'germination temperature', 'germination rate',
            'germination percentage', 'germination percent', 'seedling emergence',
            'emergence', 'seed temperature requirement', 'germination',
        ];
    }

    /**
     * Essential-oil / volatile-oil primary markers (demote for germination unless oils asked).
     *
     * @return list<string>
     */
    public static function essentialOilPrimaryMarkers(): array
    {
        return [
            'essential oil', 'essential oils', 'essential-oil', 'oil yield',
            'volatile oil', 'volatile oils', 'essential oil composition',
            'essential-oil composition', 'essential oil content',
        ];
    }

    /**
     * Secondary productivity metrics that must not lead temperature/germination answers.
     *
     * @return list<string>
     */
    public static function secondaryMetricMarkers(): array
    {
        return [
            'oil yield', 'essential oil', 'volatile oil', 'biomass', 'productivity',
            'yield', 'fruit yield', 'grain yield', 'weight yield',
        ];
    }

    /**
     * Temperature-answer signals preferred in grounded snippets for thermal questions.
     *
     * @return list<string>
     */
    public static function temperatureAnswerSignals(): array
    {
        return [
            'temperature', 'optimal temperature', 'optimum temperature',
            'germination temperature', 'thermal', 'heat stress', '°c', '° c',
        ];
    }

    /**
     * True when the user question explicitly asks about oils / essential oils.
     */
    public static function userAskedAboutOils(string $questionHaystack): bool
    {
        $hay = mb_strtolower(trim($questionHaystack));
        if ($hay === '') {
            return false;
        }

        foreach (['essential oil', 'essential oils', 'oil yield', 'volatile oil', 'زيوت عطرية', 'زيت عطري', 'زيت طيار'] as $marker) {
            if (self::containsTerm($hay, $marker) || mb_strpos($hay, $marker) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * English topic terms used for scientific query construction and evidence matching.
     *
     * @return list<string>
     */
    public static function englishTermsForIntent(string $intent): array
    {
        return match ($intent) {
            'cultivation' => ['cultivation', 'crop production', 'agriculture'],
            'environmental_requirements' => ['environmental requirements', 'climate', 'agriculture'],
            'irrigation' => ['irrigation', 'water management', 'agriculture'],
            'fertilization' => ['fertilization', 'nutrient management', 'agriculture'],
            'soil_management' => ['soil management', 'agriculture'],
            'plant_nutrition' => ['plant nutrition', 'nutrient deficiency', 'agriculture'],
            'disease' => ['plant disease', 'pathogen', 'agriculture'],
            'pest' => ['pest management', 'agriculture'],
            'productivity' => ['yield', 'productivity', 'agriculture'],
            'varieties' => ['cultivar', 'variety', 'agriculture'],
            'scientific_literature' => ['scientific literature', 'agriculture'],
            'agricultural_economics' => ['agricultural economics', 'farm economics', 'agriculture'],
            'agricultural_industry' => ['processing', 'postharvest', 'agriculture'],
            'general_knowledge' => ['agriculture', 'farming'],
            default => ['agriculture'],
        };
    }

    /**
     * Strong off-domain phrases: hard-reject unless an agricultural rescue applies.
     *
     * @return array<string, list<string>> family => markers
     */
    public static function strongIrrelevantDomainMarkers(): array
    {
        return [
            'psychology' => [
                'anxiety disorder', 'psychiatry', 'psychiatric', 'psychotherapy',
                'cognitive behavioral', 'neurosis',
            ],
            'economics' => [
                'stock market', 'macroeconomics', 'consumer behavior',
                'political economy',
            ],
            'sociology' => [
                'sociology of',
            ],
        ];
    }

    /**
     * Ambiguous domain words that are NOT sufficient alone for hard-reject.
     * Require context: agricultural rescue, or ag+entity+topic, else reject.
     *
     * @return array<string, list<string>> family => markers
     */
    public static function ambiguousDomainMarkers(): array
    {
        return [
            'economics' => ['economics', 'economy', 'economic'],
            'psychology' => ['psychology', 'psychological'],
        ];
    }

    /**
     * Phrases that place an ambiguous/strong family inside Agricultural Sciences.
     *
     * @return array<string, list<string>> family => rescue phrases
     */
    public static function agriculturalDomainRescuePhrases(): array
    {
        return [
            'economics' => [
                'agricultural economics', 'farm economics', 'crop economics',
                'farm profitability', 'production economics', 'economic analysis',
                'economic impact', 'farm income', 'crop production economics',
                'اقتصاد زراعي', 'ربحية',
            ],
            'psychology' => [
                'agricultural psychology', 'farmer behavior', 'farmer behaviour',
                'farmers behavior', 'farmers behaviour', 'agricultural extension',
                'extension adoption', 'adoption behavior', 'adoption behaviour',
                'farmer decision', 'farmers decision', 'decision making among farmers',
            ],
            'sociology' => [
                'rural sociology', 'agricultural sociology',
            ],
        ];
    }

    /**
     * Minimal agricultural-sciences branch labels for domain awareness
     * (complements AgriculturalDomainCatalog; not a second taxonomy).
     *
     * @return list<string>
     */
    public static function agriculturalScienceBranchSignals(): array
    {
        return [
            'crop science', 'horticulture', 'plant physiology', 'soil science',
            'plant nutrition', 'plant pathology', 'entomology', 'pest management',
            'agricultural engineering', 'agricultural economics', 'agricultural extension',
            'animal science', 'veterinary', 'aquaculture', 'fisheries',
            'food science', 'agronomy', 'agronomic',
        ];
    }

    /**
     * Legacy flat list retained for callers; prefer strong/ambiguous helpers.
     *
     * @return list<string>
     */
    public static function irrelevantDomainMarkers(): array
    {
        $markers = [];
        foreach (self::strongIrrelevantDomainMarkers() as $familyMarkers) {
            foreach ($familyMarkers as $marker) {
                $markers[] = $marker;
            }
        }
        foreach (self::ambiguousDomainMarkers() as $familyMarkers) {
            foreach ($familyMarkers as $marker) {
                $markers[] = $marker;
            }
        }

        return array_values(array_unique($markers));
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
            $labels = [$cropId, str_replace('-', ' ', $cropId)];
            if ($taxonomy !== null) {
                $labels[] = $taxonomy['scientific_name'];
                $labels = array_merge($labels, $taxonomy['synonyms']);
            }
            if (isset($arabicLabels[$cropId])) {
                foreach ((array) $arabicLabels[$cropId] as $arabicLabel) {
                    $labels[] = $arabicLabel;
                }
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
            'tomato', 'pepper', 'ginger',
        ];
    }

    /**
     * @return array<string, string|list<string>>
     */
    private static function arabicCropLabels(): array
    {
        return [
            'wheat' => ['القمح', 'قمح'],
            'corn' => ['الذرة', 'ذرة'],
            'rice' => ['الأرز', 'ارز', 'أرز'],
            'barley' => ['الشعير', 'شعير'],
            'oats' => ['الشوفان', 'شوفان'],
            'sorghum' => ['الذرة الرفيعة'],
            'millet' => ['الدخن', 'دخن'],
            'rye' => ['الجاودار'],
            'triticale' => ['التريتيكال'],
            'sugarcane' => ['قصب السكر'],
            'sugar-beet' => ['بنجر السكر'],
            'sunflower' => ['دوار الشمس'],
            'soybean' => ['فول الصويا'],
            'sesame' => ['السمسم', 'سمسم'],
            'peanut' => ['الفول السوداني'],
            'canola' => ['الكانولا'],
            'tomato' => ['الطماطم', 'طماطم'],
            'pepper' => ['الفلفل', 'فلفل'],
            'ginger' => ['الزنجبيل', 'زنجبيل'],
        ];
    }

    /**
     * @return list<string>
     */
    public static function recognitionLabelsForCrop(string $cropId): array
    {
        foreach (self::cropRecognitionEntries() as $entry) {
            if ($entry['crop_id'] === $cropId) {
                return $entry['labels'];
            }
        }

        return FieldCropTaxonomyCatalog::searchTermsFor($cropId);
    }

    /**
     * @return list<string>
     */
    public static function extractTopicFactors(string $normalizedQuestion): array
    {
        $matched = [];
        foreach (self::topicFactorSignals() as $factor => $keywords) {
            foreach ($keywords as $keyword) {
                if (self::containsTerm($normalizedQuestion, $keyword)) {
                    $matched[] = $factor;
                    break;
                }
            }
        }

        return array_values(array_unique($matched));
    }

    /**
     * @param  list<string>  $factors
     * @return list<string>
     */
    public static function englishLabelsForFactors(array $factors): array
    {
        $labels = self::topicFactorEnglishLabels();
        $out = [];
        foreach ($factors as $factor) {
            if (isset($labels[$factor])) {
                $out[] = $labels[$factor];
            }
        }

        return array_values(array_unique($out));
    }

    /**
     * @return array{crop_id: string, label: string}|null
     */
    public static function recognizeCrop(string $normalizedQuestion): ?array
    {
        $best = null;
        $bestLength = 0;

        foreach (self::cropRecognitionEntries() as $entry) {
            foreach ($entry['labels'] as $label) {
                if ($label === '') {
                    continue;
                }
                if (! self::containsTerm($normalizedQuestion, $label)) {
                    continue;
                }
                $length = mb_strlen($label);
                if ($length > $bestLength) {
                    $bestLength = $length;
                    $best = [
                        'crop_id' => $entry['crop_id'],
                        'label' => $label,
                    ];
                }
            }
        }

        return $best;
    }

    public static function containsTerm(string $haystack, string $needle): bool
    {
        $needle = mb_strtolower(trim($needle));
        if ($needle === '') {
            return false;
        }

        if (mb_strlen($needle) <= 4 && preg_match('/\p{L}/u', $needle) === 1) {
            return preg_match('/\b'.preg_quote($needle, '/').'\b/u', $haystack) === 1
                || (preg_match('/\p{Arabic}/u', $needle) === 1 && str_contains($haystack, $needle));
        }

        return str_contains($haystack, $needle);
    }
}
