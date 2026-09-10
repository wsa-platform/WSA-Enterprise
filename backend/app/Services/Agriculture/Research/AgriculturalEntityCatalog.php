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
            'plant_family_members',
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
            'plant_family',
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
            'aquaculture' => ['aquaculture', 'fish farming', 'استزراع', 'أسماك', 'اسماك'],
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
            'greenhouse' => [
                'greenhouse', 'greenhouses', 'polyhouse', 'polyhouses',
                'protected cultivation', 'protected agriculture', 'glasshouse',
                'صوبة', 'بيوت محمية', 'زراعة محمية',
            ],
            'open_field' => [
                'open field', 'open-field', 'openfield', 'field cultivation',
                'field-grown', 'outdoor cultivation', 'rainfed', 'rain-fed',
                'حقل مفتوح', 'الحقل المفتوح', 'زراعة مكشوفة', 'زراعة حقلية',
                'الزراعة الحقلية', 'الأرض المكشوفة', 'الارض المكشوفة',
                'الأراضي المكشوفة', 'الاراضي المكشوفة',
                'الزراعة في الأرض المكشوفة', 'الزراعة في الارض المكشوفة',
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
            'السعودية' => 'Saudi Arabia',
            'السعوديه' => 'Saudi Arabia',
            'saudi arabia' => 'Saudi Arabia',
            'saudi' => 'Saudi Arabia',
            'ksa' => 'Saudi Arabia',
            'تركيا' => 'Turkey',
            'turkey' => 'Turkey',
            'türkiye' => 'Turkey',
            'turkiye' => 'Turkey',
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
            'الأردن' => 'Jordan',
            'jordan' => 'Jordan',
            'الإمارات' => 'United Arab Emirates',
            'uae' => 'United Arab Emirates',
            'india' => 'India',
            'الهند' => 'India',
        ];
    }

    /**
     * User asks how soil/land classification is done (methods/models/frameworks),
     * not for a types/classes inventory.
     */
    public static function isLandOrSoilClassificationMethodQuestion(string $haystack): bool
    {
        $haystack = mb_strtolower(trim($haystack));
        if ($haystack === '') {
            return false;
        }

        $mentionsClassification = preg_match(
            '/(?:soil|land)\s*classification|classification\s+of\s+(?:soils?|lands?)|تصنيف\s*(?:ال)?(?:تربة|اراضي|أراضي)/u',
            $haystack,
        ) === 1;
        if (! $mentionsClassification) {
            return false;
        }

        // Inventory/types questions win over method framing.
        if (self::asksLandOrSoilTypesInventory($haystack)) {
            return false;
        }

        return preg_match(
            '/\b(?:what\s+methods?|which\s+methods?|methods?\s+(?:are\s+)?used|methods?\s+for|'
            .'methodology|techniques?\s+for|approaches?\s+for|frameworks?\s+for|'
            .'how\s+(?:do(?:es)?\s+one|to|is|are)\s+(?:classify|classify(?:ing)?))\b|'
            .'(?:ما\s+هي\s+)?(?:طرق|منهج(?:يات)?|اساليب|أساليب)\s*.{0,40}تصنيف|'
            .'كيفية\s+تصنيف/u',
            $haystack,
        ) === 1;
    }

    /**
     * User asks for land/soil types or a classification inventory (not methods).
     */
    public static function asksLandOrSoilTypesInventory(string $haystack): bool
    {
        $haystack = mb_strtolower(trim($haystack));
        if ($haystack === '') {
            return false;
        }

        return preg_match(
            '/(?:types?\s+of\s+(?:agricultural\s+)?(?:land|soil)|(?:agricultural\s+)?land\s+types?|'
            .'soil\s+types?|types?\s+de\s+terres?\s+agricoles|'
            .'tar[ıi]m\s+arazilerinin\s+t[üu]rleri|'
            .'انواع\s*(?:ال)?(?:اراضي|أراضي|تربه|تربة)|أنواع\s*(?:ال)?(?:أراضي|اراضي|تربة)|'
            .'تصنيف\s*(?:ال)?(?:اراضي|أراضي|تربة).{0,20}(?:انواع|أنواع|اصناف|أصناف))/u',
            $haystack,
        ) === 1;
    }

    /**
     * Map catalog location labels to ISO 3166-1 alpha-2 for Consensus study-country filter.
     */
    public static function locationToIsoCountryCode(string $location): ?string
    {
        $normalized = mb_strtolower(trim($location));
        if ($normalized === '') {
            return null;
        }

        $canonical = self::locationAliases()[$normalized] ?? null;
        if ($canonical === null) {
            foreach (self::locationAliases() as $alias => $label) {
                if (mb_strtolower($label) === $normalized) {
                    $canonical = $label;
                    break;
                }
            }
        }
        $canonical = $canonical ?? $location;

        return match (mb_strtolower(trim($canonical))) {
            'egypt' => 'eg',
            'saudi arabia' => 'sa',
            'turkey', 'türkiye', 'turkiye' => 'tr',
            'libya' => 'ly',
            'sudan' => 'sd',
            'tunisia' => 'tn',
            'algeria' => 'dz',
            'morocco' => 'ma',
            'jordan' => 'jo',
            'united arab emirates' => 'ae',
            'india' => 'in',
            default => null,
        };
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
            'varieties' => ['varieties', 'cultivars', 'cultivar classification', 'variety classification'],
            'plant_family_members' => [
                'family members', 'species', 'taxonomy', 'classification',
                'botanical family', 'plants of', 'genera', 'genus',
            ],
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
            'plant_family_members' => [
                'species', 'family members', 'taxonomy', 'botanical family',
                'classification', 'agriculture',
            ],
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

    // --- CURRENT TASK: Semantic Scholar / QueryBuilder family+potato ---

    // --- Typed factual question / evidence-type helpers (entity-family + varieties) ---
    /**
     * Question-type keyword signals (classification / quantity / timing…).
     *
     * @return array<string, list<string>>
     */
    public static function questionTypeSignals(): array
    {
        return [
            'classification' => [
                'types', 'type', 'classification', 'classify', 'categories', 'kinds',
                'varieties', 'variety', 'cultivars', 'cultivar',
                'أنواع', 'انواع', 'تصنيف', 'صنف', 'أصناف', 'اصناف',
                'types de', 'classification des', 'catégories',
                'türleri', 'türler', 'sınıflandırma', 'siniflandirma',
            ],
            'quantity' => [
                'quantity', 'rate', 'amount', 'dose', 'dosage', 'kg/ha', 'how much',
                'كمية', 'معدل', 'جرعة', 'كم',
                'quantité', 'dose', 'combien',
                'miktar', 'oran', 'ne kadar',
            ],
            'range' => [
                'optimal', 'optimum', 'best temperature', 'range', 'between',
                'أفضل درجة', 'النطاق', 'مثلى',
                'plage', 'optimale', 'température optimale',
                'en uygun', 'aralık', 'optimal',
            ],
            'timing' => [
                'when', 'timing', 'season', 'period', 'schedule', 'planting date',
                'متى', 'موعد', 'موسم', 'توقيت',
                'quand', 'saison', 'période',
                'ne zaman', 'mevsim', 'dönem',
            ],
            'causes' => [
                'why', 'cause', 'causes', 'reason', 'because',
                'لماذا', 'سبب', 'أسباب',
                'pourquoi', 'cause', 'raisons',
                'neden', 'sebep', 'nedenleri',
            ],
            'symptoms' => [
                'symptom', 'symptoms', 'signs', 'turn yellow', 'yellowing',
                'أعراض', 'اصفرار', 'تصفر',
                'symptômes', 'jaunissement',
                'belirtiler', 'sararma',
            ],
            'comparison' => [
                'compare', 'comparison', 'versus', 'vs', 'difference between',
                'مقارنة', 'مقابل', 'الفرق بين',
                'comparer', 'différence',
                'karşılaştır', 'fark',
            ],
            'species' => [
                'species', 'species of', 'kinds of fish', 'freshwater fish',
                'أنواع أسماك', 'انواع اسماك', 'أسماك', 'اسماك',
                'espèces', 'poissons',
                'balık türleri', 'türler',
            ],
            'definition' => [
                'define', 'definition', 'meaning of',
                'تعريف',
                "qu'est-ce", 'définition',
                'nedir', 'tanım',
            ],
            'recommendation' => [
                'best method', 'recommend', 'recommendation', 'how to', 'best way',
                'أفضل طريقة', 'توصية', 'كيف',
                'meilleure méthode', 'recommandation', 'comment',
                'en iyi yöntem', 'öneri', 'nasıl',
            ],
            'requirements' => [
                'requirement', 'requirements', 'needs', 'conditions required',
                'متطلبات', 'احتياجات', 'شروط',
                'exigences', 'besoins',
                'gereksinimler', 'şartlar',
            ],
        ];
    }
    /**
     * What DIRECT evidence must contain for each question type.
     *
     * @return array<string, string>
     */
    public static function requiredEvidenceTypeForQuestionType(string $questionType): string
    {
        return match ($questionType) {
            'classification' => 'classification_or_types_inventory',
            'quantity' => 'numeric_rate_or_quantity',
            'range' => 'numeric_range_or_optimal_value',
            'timing' => 'temporal_window_or_season',
            'causes' => 'causal_relationship',
            'symptoms' => 'symptom_description',
            'comparison' => 'comparative_evidence',
            'species' => 'species_list_or_taxonomy',
            'definition' => 'definitional_statement',
            'recommendation' => 'recommendation_or_best_practice',
            'requirements' => 'requirement_specification',
            default => 'topic_aligned_scientific_claim',
        };
    }
    /**
     * English search terms that encode required evidence content.
     *
     * @return list<string>
     */
    public static function requiredEvidenceQueryTerms(string $requiredEvidenceType): array
    {
        return match ($requiredEvidenceType) {
            'classification_or_types_inventory' => [
                'types', 'classification', 'categories', 'inventory',
                'varieties', 'cultivars',
            ],
            'numeric_rate_or_quantity' => ['rate', 'kg/ha', 'quantity', 'dosage'],
            'numeric_range_or_optimal_value' => ['optimal', 'temperature range', 'optimum'],
            'temporal_window_or_season' => ['planting date', 'season', 'timing'],
            'causal_relationship' => ['cause', 'effect', 'because'],
            'symptom_description' => ['symptoms', 'yellowing', 'chlorosis'],
            'comparative_evidence' => ['comparison', 'versus', 'compared'],
            'species_list_or_taxonomy' => [
                'species', 'taxonomy', 'family members', 'botanical family', 'genera',
            ],
            'definitional_statement' => ['definition', 'defined as'],
            'recommendation_or_best_practice' => ['recommended', 'best practice', 'method'],
            'requirement_specification' => ['requirements', 'required', 'needs'],
            default => [],
        };
    }
    /**
     * What the answer body must contain for a required evidence type (generic, not geography-specific).
     *
     * @return list<string>
     */
    public static function requiredEvidenceCharacteristics(string $requiredEvidenceType): array
    {
        return match ($requiredEvidenceType) {
            'classification_or_types_inventory' => [
                'explicit_types_or_classes_inventory',
                'named_classes_or_types',
                'classification_system_with_listed_types',
            ],
            'numeric_rate_or_quantity' => ['numeric_quantity_or_rate'],
            'numeric_range_or_optimal_value' => ['numeric_range_or_optimum'],
            'temporal_window_or_season' => ['temporal_window_or_season'],
            'causal_relationship' => ['stated_cause_or_mechanism'],
            'symptom_description' => ['described_symptoms'],
            'species_list_or_taxonomy' => ['species_or_taxa_list'],
            'recommendation_or_best_practice' => ['stated_recommendation_or_practice'],
            'requirement_specification' => ['stated_requirement'],
            'comparative_evidence' => ['comparative_statement'],
            'definitional_statement' => ['definitional_statement'],
            default => ['topic_aligned_claim'],
        };
    }
    /**
     * Negative constraints: evidence shapes that must not alone satisfy DIRECT.
     *
     * @return list<string>
     */
    public static function negativeConstraintsForEvidenceType(string $requiredEvidenceType): array
    {
        return match ($requiredEvidenceType) {
            'classification_or_types_inventory' => [
                'methodology_only',
                'machine_learning_model_only',
                'gis_mapping_only',
                'remote_sensing_only',
                'land_evaluation_without_types',
                'cultivation_without_inventory',
                'greenhouse_or_protected_culture',
                'groundwater_or_microbial_focus',
                'crop_production_without_types',
            ],
            'species_list_or_taxonomy' => [
                'methodology_only',
                'cultivation_without_inventory',
                'greenhouse_or_protected_culture',
            ],
            default => [],
        };
    }

    /**
     * Botanical family aliases for QueryBuilder / understanding (country-agnostic).
     * QUS and QueryBuilder both resolve through resolveBotanicalFamily() — do not duplicate.
     *
     * @return array<string, list<string>> canonical Latin family => aliases
     */
    public static function botanicalFamilyAliases(): array
    {
        return [
            'Cucurbitaceae' => [
                'cucurbitaceae',
                'cucurbit',
                'cucurbits',
                'cucurbit family',
                'القرعية',
                'العائلة القرعية',
                'عائلة القرعيات',
                'نبات العائلة القرعية',
                'نباتات العائلة القرعية',
            ],
            // Second catalog entry proves resolution is table-driven (not cucurbit-only).
            'Fabaceae' => [
                'fabaceae',
                'leguminosae',
                'legume family',
                'legumes family',
                'bean family',
                'العائلة البقولية',
                'عائلة البقوليات',
                'النباتات البقولية',
            ],
        ];
    }
    /**
     * Resolve a botanical family from free text via botanicalFamilyAliases().
     * Longest alias wins so nested phrases prefer the most specific match.
     */
    public static function resolveBotanicalFamily(string $text): ?string
    {
        $haystack = mb_strtolower(trim($text));
        if ($haystack === '') {
            return null;
        }

        $bestFamily = null;
        $bestLength = 0;
        foreach (self::botanicalFamilyAliases() as $family => $aliases) {
            foreach ($aliases as $alias) {
                $alias = mb_strtolower(trim((string) $alias));
                if ($alias === '') {
                    continue;
                }
                if (! self::containsTerm($haystack, $alias) && mb_strpos($haystack, $alias) === false) {
                    continue;
                }
                $length = mb_strlen($alias);
                if ($length > $bestLength) {
                    $bestLength = $length;
                    $bestFamily = $family;
                }
            }
        }

        return $bestFamily;
    }
    /**
     * Labels / aliases usable for entity matching against evidence text.
     *
     * @return list<string>
     */
    public static function recognitionLabelsForBotanicalFamily(string $family): array
    {
        $canonical = trim($family);
        if ($canonical === '') {
            return [];
        }

        $aliases = self::botanicalFamilyAliases()[$canonical] ?? [];
        $labels = array_merge([$canonical], array_map(
            static fn ($alias): string => (string) $alias,
            $aliases,
        ));

        return array_values(array_unique(array_filter(
            array_map(static fn (string $label): string => trim($label), $labels),
            static fn (string $label): bool => $label !== '',
        )));
    }
    /**
     * True when the question asks for members/plants/species of a botanical family
     * (inventory), not merely a one-line definition of the family name.
     */
    public static function asksPlantFamilyMemberInventory(string $normalizedQuestion): bool
    {
        $hay = mb_strtolower(trim($normalizedQuestion));
        if ($hay === '') {
            return false;
        }

        if (preg_match(
            '/\b(?:plants?\s+(?:belong(?:ing)?\s+to|of|in)|members?\s+of|species\s+(?:of|in)|'
            .'family\s+members?|kinds?\s+of\s+plants?|types?\s+of\s+plants?)\b/u',
            $hay,
        ) === 1) {
            return true;
        }

        foreach ([
            'نبات العائلة', 'نباتات العائلة', 'اعضاء العائلة', 'أعضاء العائلة',
            'انواع نباتات', 'أنواع نباتات', 'نباتات من العائلة',
        ] as $marker) {
            if (self::containsTerm($hay, $marker) || mb_strpos($hay, $marker) !== false) {
                return true;
            }
        }

        // "ما هي نبات/نباتات العائلة …" / Latin family + members/plants framing.
        return preg_match('/(?:ما\s+هي\s+نبات|ما\s+هي\s+نباتات)/u', $hay) === 1
            || (
                self::resolveBotanicalFamily($hay) !== null
                && preg_match('/\b(?:plants?|species|members?|taxonomy|genera|genus)\b/u', $hay) === 1
            );
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
            'tomato', 'potato', 'sweet-potato', 'pepper', 'ginger',
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
            'potato' => ['البطاطا', 'بطاطا', 'البطاطس', 'بطاطس'],
            // Longer than bare potato aliases so "بطاطا الحلوة" resolves to sweet potato.
            'sweet-potato' => [
                'البطاطا الحلوة', 'بطاطا الحلوة', 'بطاطا حلوة',
                'البطاطس الحلوة', 'بطاطس حلوة',
            ],
            'pepper' => ['الفلفل', 'فلفل'],
            'ginger' => ['الزنجبيل', 'زنجبيل'],
        ];
    }

    // --- END CURRENT TASK: Semantic Scholar / QueryBuilder family+potato ---

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
