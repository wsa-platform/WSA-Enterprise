<?php

namespace App\Services\Agriculture\Research;

/**
 * Retrieval-layer semantic contract applied immediately before Stage 3 search.
 *
 * Does not parse questions and does not replace Query Understanding. It preserves
 * semantic roles that must not be corrupted by stop-token emptying or generic
 * productivity fallbacks, and it never invents an agricultural entity.
 */
final class RetrievalSemanticContract
{
    public const ROLE_IRRIGATION_WATER = 'irrigation_water_measurement';

    public const ROLE_TEMPERATURE = 'temperature_measurement';

    public const ROLE_PRODUCTIVITY = 'productivity_measurement';

    public const ROLE_CLASSIFICATION = 'classification';

    public const ROLE_QUANTITY = 'quantity_measurement';

    public const ROLE_UNRESOLVED = 'unresolved';

    public const ROLE_OTHER = 'other';

    /** @var list<string> */
    private const PRODUCTIVITY_FALLBACK_TERMS = ['yield', 'production'];

    /** @var list<string> */
    private const WATER_MEASUREMENT_FAMILY = [
        'water', 'irrigation', 'moisture', 'rainfall', 'rain', 'humidity',
        'evapotranspiration', 'water requirement', 'water use', 'water uptake',
        'مياه', 'ماء', 'ري', 'الري', 'رطوبة',
    ];

    /** @var list<string> */
    private const TEMPERATURE_MEASUREMENT_FAMILY = [
        'temperature', 'heat', 'thermal', 'germination',
        'حرارة', 'درجة', 'إنبات', 'انبات',
    ];

    /** @var list<string> */
    private const GENERIC_PROCESS_TOKENS = [
        'physiology', 'water', 'uptake', 'germination', 'production', 'yield',
        'growth', 'stress', 'temperature', 'salinity', 'irrigation', 'fertilization',
        'quantity', 'rate', 'classification', 'types', 'inventory', 'requirement',
        'moisture', 'humidity', 'evapotranspiration', 'concentration',
        'فسيولوجيا', 'مياه', 'ماء', 'ري', 'إنتاج', 'غلة', 'نمو', 'حرارة',
        'ملوحة', 'كمية', 'إنبات',
    ];

    public function apply(KnowledgeQueryPlan $plan): KnowledgeQueryPlan
    {
        $query = $plan->normalizedQuery;
        $constraints = is_array($query->constraints) ? $query->constraints : [];
        $surface = $this->requestedPropertySurface($constraints);
        $key = strtolower(trim((string) ($constraints['requested_property_key'] ?? '')));
        $sense = strtolower(trim((string) ($constraints['scientific_sense'] ?? '')));
        $intent = strtolower(trim((string) $plan->researchIntent));
        $role = $this->propertyRole($key, $sense, $intent, $surface);

        $incomingTerms = $this->stringList($constraints['requested_property_query_terms'] ?? []);
        $terms = $this->materializePropertyTerms($role, $surface, $incomingTerms);
        $factors = $this->stringList($constraints['scientific_factors'] ?? []);
        $terms = $this->interleaveRequiredFactors($terms, $factors);
        $unresolved = $role === self::ROLE_UNRESOLVED && $terms === [];

        $constraints['requested_property_query_terms'] = $unresolved ? [] : $terms;
        $constraints['retrieval_property_role'] = $role;
        $constraints['requested_property_unresolved'] = $unresolved;
        $constraints['mandatory_retrieval_terms'] = $this->mandatoryComponents($plan, $role, $constraints['requested_property_query_terms']);

        $suppressEntity = $this->shouldSuppressGenericProcessEntity($query);
        $constraints['retrieval_entity_suppressed'] = $suppressEntity;

        $nextQuery = $this->cloneQuery(
            $query,
            $constraints,
            $suppressEntity ? null : $query->crop,
            $suppressEntity ? null : $query->cropId,
        );

        return $this->clonePlan($plan, $nextQuery, $this->mergeTopics($plan->topics, $constraints['mandatory_retrieval_terms']));
    }

    /**
     * @param  array<string, mixed>  $constraints
     */
    private function requestedPropertySurface(array $constraints): string
    {
        foreach (['requested_property_surface', 'property_surface', 'requested_property'] as $key) {
            $value = trim((string) ($constraints[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        $target = $constraints['semantic_target'] ?? $constraints['requested_target'] ?? null;
        if (is_array($target)) {
            $value = trim((string) ($target['property_surface'] ?? $target['property'] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function propertyRole(string $key, string $sense, string $intent, string $surface): string
    {
        if ($this->isIrrigationWaterRole($key, $sense, $intent, $surface)) {
            return self::ROLE_IRRIGATION_WATER;
        }
        if ($this->isTemperatureRole($key, $sense, $intent, $surface)) {
            return self::ROLE_TEMPERATURE;
        }
        if ($this->isClassificationRole($key, $sense, $intent)) {
            return self::ROLE_CLASSIFICATION;
        }
        if ($this->isProductivityRole($key, $sense, $intent)) {
            return self::ROLE_PRODUCTIVITY;
        }
        if (in_array($key, ['quantity', 'rate', 'dose'], true) || $this->inFamily($surface, ['quantity', 'rate', 'dose', 'كمية'])) {
            return self::ROLE_QUANTITY;
        }
        if ($key === '' && $surface === '' && $sense === '') {
            return self::ROLE_UNRESOLVED;
        }

        return self::ROLE_OTHER;
    }

    private function isIrrigationWaterRole(string $key, string $sense, string $intent, string $surface): bool
    {
        if ($key === 'irrigation') {
            return true;
        }
        if (str_contains($sense, 'water') || str_contains($sense, 'irrigation') || str_contains($sense, 'evapotranspiration')) {
            return true;
        }
        if (in_array($intent, ['irrigation', 'water_management', 'crop_water_requirement'], true)) {
            return true;
        }

        return $this->inFamily($surface, self::WATER_MEASUREMENT_FAMILY);
    }

    private function isTemperatureRole(string $key, string $sense, string $intent, string $surface): bool
    {
        if (in_array($key, ['temperature', 'range'], true)) {
            return true;
        }
        if (str_contains($sense, 'germinat') || str_contains($sense, 'thermal') || $sense === 'seed_germination') {
            return true;
        }
        if (in_array($intent, ['thermal_requirement', 'germination'], true)) {
            return true;
        }

        return $this->inFamily($surface, self::TEMPERATURE_MEASUREMENT_FAMILY);
    }

    private function isClassificationRole(string $key, string $sense, string $intent): bool
    {
        return in_array($key, ['classification', 'types', 'inventory'], true)
            || in_array($sense, ['varieties', 'breeds', 'classification', 'plant_family_members', 'land_classification'], true)
            || in_array($intent, ['classification', 'inventory'], true);
    }

    private function isProductivityRole(string $key, string $sense, string $intent): bool
    {
        return in_array($key, ['yield', 'production'], true)
            || in_array($sense, ['production_quantity', 'yield'], true)
            || in_array($intent, ['statistical_lookup', 'productivity'], true);
    }

    /**
     * @param  list<string>  $incomingTerms
     * @return list<string>
     */
    private function materializePropertyTerms(string $role, string $surface, array $incomingTerms): array
    {
        $terms = $incomingTerms;
        if ($surface !== '' && ! $this->containsTerm($terms, $surface)) {
            array_unshift($terms, $surface);
        }

        $terms = match ($role) {
            self::ROLE_IRRIGATION_WATER => $this->uniqueTerms([
                ...$terms,
                'irrigation',
                'water requirement',
                'evapotranspiration',
                'quantity',
            ]),
            self::ROLE_TEMPERATURE => $this->uniqueTerms([
                ...$terms,
                'temperature',
                'germination',
            ]),
            self::ROLE_CLASSIFICATION => $this->uniqueTerms([
                ...$terms,
                'classification',
                'types',
            ]),
            self::ROLE_PRODUCTIVITY => $this->uniqueTerms([
                ...$terms,
                'production',
                'quantity',
            ]),
            self::ROLE_QUANTITY => $this->uniqueTerms([...$terms, 'quantity', 'rate']),
            default => $this->uniqueTerms($terms),
        };

        if (in_array($role, [
            self::ROLE_IRRIGATION_WATER,
            self::ROLE_TEMPERATURE,
            self::ROLE_CLASSIFICATION,
            self::ROLE_QUANTITY,
            self::ROLE_UNRESOLVED,
        ], true)) {
            $terms = array_values(array_filter(
                $terms,
                fn (string $term): bool => ! $this->isProductivityFallbackTerm($term),
            ));
        }

        return $terms;
    }

    /**
     * Required factors must occupy an early property-term slot so the existing
     * primary-variant joiner cannot drop them behind optional expansions.
     *
     * @param  list<string>  $terms
     * @param  list<string>  $factors
     * @return list<string>
     */
    private function interleaveRequiredFactors(array $terms, array $factors): array
    {
        if ($factors === []) {
            return $terms;
        }
        $head = array_slice($terms, 0, 1);
        $rest = array_slice($terms, 1);

        return $this->uniqueTerms([...$head, ...$factors, ...$rest]);
    }

    /**
     * @param  list<string>  $propertyTerms
     * @return list<string>
     */
    private function mandatoryComponents(KnowledgeQueryPlan $plan, string $role, array $propertyTerms): array
    {
        $query = $plan->normalizedQuery;
        $parts = [];
        $entity = $query->namedEntitySurface();
        if (is_string($entity) && trim($entity) !== '' && ! $this->isGenericProcessToken($entity)) {
            $parts[] = trim($entity);
        }
        foreach (array_slice($propertyTerms, 0, 4) as $term) {
            $parts[] = $term;
        }
        $sense = trim((string) ($query->constraints['scientific_sense'] ?? ''));
        if ($sense !== '') {
            $parts[] = str_replace('_', ' ', $sense);
        }
        $factors = $query->constraints['scientific_factors'] ?? [];
        if (is_array($factors)) {
            foreach ($factors as $factor) {
                $label = trim((string) $factor);
                if ($label !== '') {
                    $parts[] = $label;
                }
            }
        }
        if (is_string($query->location) && trim($query->location) !== '') {
            $parts[] = trim($query->location);
        }
        $year = trim((string) ($query->constraints['year'] ?? ''));
        if ($year !== '') {
            $parts[] = $year;
        }

        return $this->uniqueTerms($parts);
    }

    private function shouldSuppressGenericProcessEntity(AgriculturalKnowledgeQuery $query): bool
    {
        $crop = trim((string) $query->crop);
        if ($crop === '' || ! $this->isGenericProcessToken($crop)) {
            return false;
        }

        $subjectType = is_array($query->subject) ? strtolower(trim((string) ($query->subject['type'] ?? ''))) : '';
        if (in_array($subjectType, ['crop', 'named_entity', 'animal', 'plant_family'], true)) {
            return false;
        }

        $named = trim((string) ($query->constraints['named_entity_surface'] ?? ''));

        return $named === '' || $this->isGenericProcessToken($named);
    }

    private function isGenericProcessToken(string $token): bool
    {
        $folded = mb_strtolower(trim($token));
        if ($folded === '') {
            return false;
        }

        return in_array($folded, self::GENERIC_PROCESS_TOKENS, true);
    }

    private function isProductivityFallbackTerm(string $term): bool
    {
        return in_array(mb_strtolower(trim($term)), self::PRODUCTIVITY_FALLBACK_TERMS, true);
    }

    /**
     * @param  list<string>  $family
     */
    private function inFamily(string $surface, array $family): bool
    {
        $folded = mb_strtolower(trim($surface));
        if ($folded === '') {
            return false;
        }
        foreach ($family as $token) {
            if ($folded === mb_strtolower($token) || str_contains($folded, mb_strtolower($token))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $terms
     */
    private function containsTerm(array $terms, string $needle): bool
    {
        $folded = mb_strtolower(trim($needle));
        foreach ($terms as $term) {
            if (mb_strtolower(trim($term)) === $folded) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>|mixed  $values
     * @return list<string>
     */
    private function stringList(mixed $values): array
    {
        if (! is_array($values)) {
            return [];
        }
        $out = [];
        foreach ($values as $value) {
            $label = trim((string) $value);
            if ($label !== '') {
                $out[] = $label;
            }
        }

        return $out;
    }

    /**
     * @param  list<string>  $terms
     * @return list<string>
     */
    private function uniqueTerms(array $terms): array
    {
        $out = [];
        $seen = [];
        foreach ($terms as $term) {
            $label = trim((string) $term);
            if ($label === '') {
                continue;
            }
            $fold = mb_strtolower($label);
            if (isset($seen[$fold])) {
                continue;
            }
            $seen[$fold] = true;
            $out[] = $label;
        }

        return $out;
    }

    /**
     * @param  list<string>  $topics
     * @param  list<string>  $mandatory
     * @return list<string>
     */
    private function mergeTopics(array $topics, array $mandatory): array
    {
        return $this->uniqueTerms([...$mandatory, ...$topics]);
    }

    /**
     * @param  array<string, mixed>  $constraints
     */
    private function cloneQuery(
        AgriculturalKnowledgeQuery $query,
        array $constraints,
        ?string $crop,
        ?string $cropId,
    ): AgriculturalKnowledgeQuery {
        return new AgriculturalKnowledgeQuery(
            originalQuestion: $query->originalQuestion,
            normalizedQuestion: $query->normalizedQuestion,
            language: $query->language,
            agriculturalDomain: $query->agriculturalDomain,
            subject: $query->subject,
            crop: $crop,
            cropId: $cropId,
            scientificName: $query->scientificName,
            topic: $query->topic,
            subtopic: $query->subtopic,
            requestedInformation: $query->requestedInformation,
            constraints: $constraints,
            location: $query->location,
            researchRequired: $query->researchRequired,
            ambiguityState: $query->ambiguityState,
            clarificationRequirements: $query->clarificationRequirements,
            researchIntent: $query->researchIntent,
        );
    }

    /**
     * @param  list<string>  $topics
     */
    private function clonePlan(
        KnowledgeQueryPlan $plan,
        AgriculturalKnowledgeQuery $query,
        array $topics,
    ): KnowledgeQueryPlan {
        return new KnowledgeQueryPlan(
            normalizedQuery: $query,
            researchIntent: $plan->researchIntent,
            agriculturalDomain: $plan->agriculturalDomain,
            subjectEntity: $plan->subjectEntity,
            topics: $topics,
            subtopics: $plan->subtopics,
            requestedInformation: $plan->requestedInformation,
            evidenceRequirements: $plan->evidenceRequirements,
            sourcePriorities: $plan->sourcePriorities,
            primaryResearchStrategy: $plan->primaryResearchStrategy,
            researchSequence: $plan->researchSequence,
            ambiguityState: $plan->ambiguityState,
            clarificationRequirements: $plan->clarificationRequirements,
            contextInput: $plan->contextInput,
            readyForStage3: $plan->readyForStage3,
        );
    }
}
