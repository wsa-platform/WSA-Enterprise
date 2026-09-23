<?php

namespace App\Services\Agriculture\Research;

/**
 * Generic question-type → knowledge-target contract.
 *
 * Crop identity is DATA supplied by the caller. This class never branches on
 * crop_id, scientific name, common name, or a specific user-question string.
 * Home and Crop both consume the same methods.
 */
final class ScientificQuestionSemantics
{
    public const SENSE_AGRONOMIC_REQUIREMENTS = 'agronomic_requirements';

    public const SENSE_SOIL_REQUIREMENTS = 'soil_requirements';

    public const SENSE_PLANT_DISEASE = 'plant_disease';

    public const SENSE_CROP_PEST = 'crop_pest';

    /**
     * Qualitative / descriptive question types that specify knowledge, not a
     * numeric measurement that must be extracted to compose an answer.
     *
     * @return list<string>
     */
    public static function qualitativeQuestionTypes(): array
    {
        return [
            'requirements',
            'recommendation',
            'symptoms',
            'classification',
            'species',
            'definition',
            'causes',
        ];
    }

    /**
     * Quantitative / value-sensitive question types.
     *
     * @return list<string>
     */
    public static function quantitativeQuestionTypes(): array
    {
        return [
            'quantity',
            'range',
            'timing',
        ];
    }

    public static function isRequirementSpecification(
        string $questionType,
        string $requiredEvidenceType = '',
        string $intentQualifier = '',
    ): bool {
        return $questionType === 'requirements'
            || $requiredEvidenceType === 'requirement_specification'
            || $intentQualifier === 'requirement';
    }

    /**
     * @param  list<string>  $topicFactors
     */
    public static function knowledgeTargetTopics(
        string $questionType,
        string $requiredEvidenceType,
        array $topicFactors,
        string $scientificSense,
        string $intentQualifier,
        string $normalizedQuestion = '',
        string $requestedProperty = '',
    ): array {
        $family = self::knowledgeFamily(
            $questionType,
            $requiredEvidenceType,
            $topicFactors,
            $scientificSense,
            $intentQualifier,
            $normalizedQuestion,
            $requestedProperty,
        );

        return match ($family) {
            'water_requirements' => [
                'water requirements',
                'irrigation requirement',
                'crop water requirement',
            ],
            'soil_requirements' => [
                'soil requirements',
                'soil suitability',
                'soil fertility',
            ],
            'temperature_range' => [
                'temperature range',
                'optimal temperature',
                'temperature requirement',
            ],
            'plant_diseases' => [
                'plant diseases',
                'crop diseases',
                'pathogens',
            ],
            'crop_pests' => [
                'crop pests',
                'insect pests',
                'pest management',
            ],
            'cultivation_practices' => [
                'cultivation practices',
                'crop management',
                'agronomic practices',
            ],
            'agronomic_requirements' => [
                'agronomic requirements',
                'cultivation practices',
                'crop management',
            ],
            default => [],
        };
    }

    /**
     * Entity-agnostic variant tails. Callers join the resolved entity as DATA.
     *
     * @param  list<string>  $topicFactors
     * @return list<string>
     */
    public static function searchVariantTails(
        string $questionType,
        string $requiredEvidenceType,
        array $topicFactors,
        string $scientificSense,
        string $intentQualifier,
        string $normalizedQuestion = '',
        string $requestedProperty = '',
    ): array {
        return self::knowledgeTargetTopics(
            $questionType,
            $requiredEvidenceType,
            $topicFactors,
            $scientificSense,
            $intentQualifier,
            $normalizedQuestion,
            $requestedProperty,
        );
    }

    /**
     * Property needles for the shared relevance gate. Empty means "do not
     * turn the topic gate on via property terms."
     *
     * @param  list<string>  $topicFactors
     * @return list<string>
     */
    public static function propertyQueryTerms(
        string $questionType,
        string $requiredEvidenceType,
        array $topicFactors,
        string $scientificSense,
        string $intentQualifier,
        string $normalizedQuestion = '',
        string $requestedProperty = '',
    ): array {
        return self::knowledgeTargetTopics(
            $questionType,
            $requiredEvidenceType,
            $topicFactors,
            $scientificSense,
            $intentQualifier,
            $normalizedQuestion,
            $requestedProperty,
        );
    }

    /**
     * Additional sense needles so requirement-specification is not scored as
     * plant-growth physiology.
     *
     * @return list<string>
     */
    public static function senseQueryTerms(string $sense): array
    {
        return match ($sense) {
            self::SENSE_AGRONOMIC_REQUIREMENTS => [
                'agronomic requirements',
                'cultivation practices',
                'crop management',
                'agronomy',
            ],
            self::SENSE_SOIL_REQUIREMENTS => [
                'soil requirements',
                'soil suitability',
                'soil fertility',
                'soil',
            ],
            self::SENSE_PLANT_DISEASE => [
                'disease',
                'pathogen',
                'plant disease',
                'crop disease',
            ],
            self::SENSE_CROP_PEST => [
                'pest',
                'insect pest',
                'pest management',
            ],
            default => [],
        };
    }

    /**
     * Prefer agronomic-requirement sense over plant_growth physiology when the
     * qualifier is requirement and no more specific factor sense applies.
     *
     * @param  list<string>  $topicFactors
     */
    public static function preferredSenseForRequirement(
        string $currentSense,
        string $intentQualifier,
        array $topicFactors,
        string $normalizedQuestion = '',
    ): string {
        if ($intentQualifier !== 'requirement') {
            return $currentSense;
        }
        if (in_array($currentSense, [
            'seed_germination',
            'crop_water_requirement',
            'plant_nutrition',
            'salinity_physiology',
            'planting_timing',
            'land_classification',
            'plant_family_members',
            'drying_processing',
            'storage',
        ], true)) {
            return $currentSense;
        }
        if (in_array('temperature', $topicFactors, true)
            || in_array('germination', $topicFactors, true)
            || in_array('water', $topicFactors, true)
            || in_array('salinity', $topicFactors, true)) {
            return $currentSense;
        }
        if (self::haystackMentionsSoil($normalizedQuestion)) {
            return self::SENSE_SOIL_REQUIREMENTS;
        }

        return self::SENSE_AGRONOMIC_REQUIREMENTS;
    }

    /**
     * When temperature/range signals dominate a "requirements" keyword hit,
     * the question is quantitative, not agronomic-requirement specification.
     *
     * @param  list<string>  $topicFactors
     */
    public static function preferRangeOverRequirements(
        string $questionType,
        string $intentQualifier,
        array $topicFactors,
        string $scientificSense,
        string $normalizedQuestion,
    ): bool {
        if ($questionType !== 'requirements') {
            return false;
        }
        $hasTemperature = in_array('temperature', $topicFactors, true)
            || $scientificSense === 'seed_germination';
        if (! $hasTemperature) {
            return false;
        }
        if (self::haystackMentionsAgronomicPractice($normalizedQuestion)) {
            return false;
        }

        return $intentQualifier === 'optimal_range'
            || $scientificSense === 'seed_germination'
            || self::haystackMentionsRange($normalizedQuestion);
    }

    /**
     * @param  list<string>  $topicFactors
     */
    public static function prefersDiseaseInventory(string $haystack, string $researchIntent = ''): bool
    {
        return $researchIntent === 'disease'
            || self::haystackMentionsDisease($haystack);
    }

    public static function hasSpecializedSearchTails(
        string $questionType,
        string $requiredEvidenceType,
        array $topicFactors,
        string $scientificSense,
        string $intentQualifier,
        string $normalizedQuestion = '',
        string $requestedProperty = '',
    ): bool {
        return self::searchVariantTails(
            $questionType,
            $requiredEvidenceType,
            $topicFactors,
            $scientificSense,
            $intentQualifier,
            $normalizedQuestion,
            $requestedProperty,
        ) !== [];
    }

    /**
     * @param  list<string>  $topicFactors
     */
    private static function knowledgeFamily(
        string $questionType,
        string $requiredEvidenceType,
        array $topicFactors,
        string $scientificSense,
        string $intentQualifier,
        string $normalizedQuestion,
        string $requestedProperty,
    ): string {
        $hay = mb_strtolower(trim($normalizedQuestion));
        $property = mb_strtolower(trim($requestedProperty));

        if (in_array($questionType, self::quantitativeQuestionTypes(), true)
            && $questionType !== 'timing') {
            if ($questionType === 'range' || $property === 'temperature' || in_array('temperature', $topicFactors, true)) {
                return 'temperature_range';
            }

            return '';
        }

        if ($scientificSense === 'crop_water_requirement'
            || $property === 'irrigation'
            || in_array('water', $topicFactors, true)
            || self::haystackMentionsWater($hay)) {
            if (self::isRequirementSpecification($questionType, $requiredEvidenceType, $intentQualifier)
                || $scientificSense === 'crop_water_requirement'
                || $property === 'irrigation') {
                return 'water_requirements';
            }
        }

        if ($scientificSense === self::SENSE_SOIL_REQUIREMENTS
            || $property === 'soil'
            || self::haystackMentionsSoil($hay)) {
            if (self::isRequirementSpecification($questionType, $requiredEvidenceType, $intentQualifier)
                || $property === 'soil') {
                return 'soil_requirements';
            }
        }

        if ($questionType === 'symptoms'
            || $scientificSense === 'disease'
            || $scientificSense === self::SENSE_PLANT_DISEASE
            || self::haystackMentionsDisease($hay)) {
            if ($questionType === 'symptoms' || self::haystackMentionsDisease($hay)) {
                return 'plant_diseases';
            }
        }

        if ($scientificSense === 'pest'
            || $scientificSense === self::SENSE_CROP_PEST
            || self::haystackMentionsPest($hay)) {
            return 'crop_pests';
        }

        if (self::haystackMentionsAgronomicPractice($hay)
            && ! self::isRequirementSpecification($questionType, $requiredEvidenceType, $intentQualifier)) {
            return 'cultivation_practices';
        }

        if (self::isRequirementSpecification($questionType, $requiredEvidenceType, $intentQualifier)) {
            return 'agronomic_requirements';
        }

        return '';
    }

    private static function haystackMentionsSoil(string $haystack): bool
    {
        $hay = mb_strtolower(trim($haystack));
        if ($hay === '') {
            return false;
        }

        foreach (['soil', 'تربة', 'تربه', 'toprak', 'sol '] as $marker) {
            if (AgriculturalEntityCatalog::containsTerm($hay, $marker)
                || mb_strpos($hay, $marker) !== false) {
                return true;
            }
        }

        return false;
    }

    private static function haystackMentionsWater(string $haystack): bool
    {
        $hay = mb_strtolower(trim($haystack));
        if ($hay === '') {
            return false;
        }

        foreach (['water requirement', 'irrigation', 'ري', 'مياه', 'sulama', 'eau'] as $marker) {
            if (AgriculturalEntityCatalog::containsTerm($hay, $marker)
                || mb_strpos($hay, $marker) !== false) {
                return true;
            }
        }

        return false;
    }

    private static function haystackMentionsDisease(string $haystack): bool
    {
        $hay = mb_strtolower(trim($haystack));
        if ($hay === '') {
            return false;
        }

        foreach (['disease', 'diseases', 'pathogen', 'مرض', 'أمراض', 'امراض'] as $marker) {
            if (AgriculturalEntityCatalog::containsTerm($hay, $marker)
                || mb_strpos($hay, $marker) !== false) {
                return true;
            }
        }

        return false;
    }

    private static function haystackMentionsPest(string $haystack): bool
    {
        $hay = mb_strtolower(trim($haystack));
        if ($hay === '') {
            return false;
        }

        foreach (['pest', 'pests', 'آفة', 'آفات', 'افات'] as $marker) {
            if (AgriculturalEntityCatalog::containsTerm($hay, $marker)
                || mb_strpos($hay, $marker) !== false) {
                return true;
            }
        }

        return false;
    }

    private static function haystackMentionsAgronomicPractice(string $haystack): bool
    {
        $hay = mb_strtolower(trim($haystack));
        if ($hay === '') {
            return false;
        }

        foreach ([
            'cultivation practices', 'agronomic practices', 'crop management',
            'farming practices', 'practices', 'ممارسات',
        ] as $marker) {
            if (AgriculturalEntityCatalog::containsTerm($hay, $marker)
                || mb_strpos($hay, $marker) !== false) {
                return true;
            }
        }

        return false;
    }

    private static function haystackMentionsRange(string $haystack): bool
    {
        $hay = mb_strtolower(trim($haystack));
        if ($hay === '') {
            return false;
        }

        foreach (['optimal', 'optimum', 'range', 'suitable', 'ideal', 'مثلى', 'مناسبة'] as $marker) {
            if (AgriculturalEntityCatalog::containsTerm($hay, $marker)
                || mb_strpos($hay, $marker) !== false) {
                return true;
            }
        }

        return false;
    }
}
