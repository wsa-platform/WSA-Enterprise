<?php

namespace App\Services\Agriculture\Research\Synthesis;

use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\RetrievalSemanticContract;
use App\Services\Agriculture\Research\RetrievalSpecification;
use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use App\Services\Agriculture\Research\Search\ScientificStructuredObservation;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use App\Services\Agriculture\Research\Validation\EvidenceVerificationLayer;
use App\Services\Agriculture\Research\Validation\ScientificEvidenceItem;

/**
 * Phase 5 Unit B3 — Catalog-free answer-expression accuracy gate.
 *
 * Consumes Phase-4 signals and QuestionClaim metadata. Does not own relevance,
 * directness, claim_relation, FAOSTAT alignment, Catalog, persistence, or feedback.
 * Canonical property identity is CSQ/RS. This gate projects measurement class,
 * unit compatibility, and answerability from that frozen family.
 */
final class AnswerExpressionAccuracyGate
{
    /**
     * @param  array<string, mixed>  $questionClaim
     * @return array{
     *     allowed: bool,
     *     reasons: list<string>,
     *     claim_text: string,
     *     numerical_values: list<string>
     * }
     */
    public function evaluate(
        ScientificEvidenceItem $item,
        KnowledgeQueryPlan $plan,
        array $questionClaim,
        string $groundedText,
        string $relationship,
        callable $evidenceIdentityCompatible,
        callable $resolveDirectness,
        callable $extractSupportedPropertyValues,
        callable $requiresSupportedMeasurement,
    ): array {
        $reasons = [];
        $property = $this->frozenMeasurementFamily($plan);
        $askedLocation = trim((string) ($questionClaim['location'] ?? $plan->normalizedQuery->location ?? ''));
        $askedTime = trim((string) ($questionClaim['time'] ?? ''));
        if ($askedTime === '') {
            $askedTime = trim((string) ($plan->normalizedQuery->constraints['year']
                ?? $plan->normalizedQuery->constraints['year_code']
                ?? $plan->normalizedQuery->constraints['time']
                ?? ''));
        }

        $directness = (string) $resolveDirectness($item, $plan);
        if (! (bool) $evidenceIdentityCompatible($item, $plan, $directness)) {
            $reasons[] = 'accuracy_entity_incompatible';
        }

        $speciesRelation = (string) ($item->qualityFactors['species_relation'] ?? '');
        if (in_array($speciesRelation, ['different_species', 'entity_less', 'genus_only'], true)) {
            $reasons[] = 'accuracy_entity_incompatible';
        }
        if (array_key_exists('entity_matched', $item->qualityFactors)
            && $item->qualityFactors['entity_matched'] === false
            && $plan->normalizedQuery->isEntityDependent()) {
            $reasons[] = 'accuracy_entity_incompatible';
        }

        $verification = strtoupper(trim((string) ($item->qualityFactors['verification_label']
            ?? $item->sourceAttribution['verification_label']
            ?? '')));
        if ($verification === EvidenceVerificationLayer::LABEL_GEOGRAPHIC_MISMATCH
            || $directness === ScientificEvidenceDirectnessAssessor::GEOGRAPHIC_MISMATCH) {
            $reasons[] = 'accuracy_geography_unsupported';
        }

        // Geography may still use structured observation location when explicitly present.
        $observation = ScientificStructuredObservation::fromEvidenceItem($item);
        if ($askedLocation !== '' && $observation !== null && trim($observation->location) !== '') {
            $askedNorm = mb_strtolower($askedLocation);
            $obsNorm = mb_strtolower(trim($observation->location));
            if ($obsNorm !== '' && $askedNorm !== '' && $obsNorm !== $askedNorm
                && ! str_contains($obsNorm, $askedNorm) && ! str_contains($askedNorm, $obsNorm)) {
                $reasons[] = 'accuracy_geography_unsupported';
            }
        }

        // Temporal expression gate: explicit observation year only.
        // Never treat publicationYear / title / citation year as observation year.
        if ($askedTime !== '' && preg_match('/^(?:19|20)\d{2}$/', $askedTime) === 1) {
            $obsYear = $this->explicitObservationYear($item);
            if ($obsYear === '' || (int) $obsYear !== (int) $askedTime) {
                $reasons[] = 'accuracy_period_unsupported';
            }
        }

        if ($property !== '' && $this->isMeasurementProperty($property)) {
            $hay = mb_strtolower(trim(implode(' ', array_filter([
                (string) $item->publicationTitle,
                (string) $item->evidenceText,
                $groundedText,
            ]))));
            $terms = $this->propertyAddressTerms($property);
            if ($terms !== [] && ! $this->haystackAddressesPropertyTerms($hay, $terms)) {
                $reasons[] = 'accuracy_property_unsupported';
            }
        }

        $numericPlan = $this->planWithClaimProperty($plan, $property);
        $numericalValues = array_values(array_filter(
            (array) $extractSupportedPropertyValues($groundedText, $numericPlan),
            static fn ($v): bool => is_string($v) && trim($v) !== '',
        ));

        if ($this->hasAmbiguousNumericMeasurements($groundedText, $numericalValues)) {
            $reasons[] = 'accuracy_numeric_ambiguous';
            $numericalValues = [];
        }

        $claimText = $groundedText;
        // Incidental numbers in non-measurement questions (cultivation, literature)
        // are not a numeric-answer contract. Measurement questions still require
        // supported values via requiresSupportedMeasurement().
        $needsMeasurement = (bool) $requiresSupportedMeasurement($numericPlan)
            || ($this->isMeasurementProperty($property)
                && $this->textContainsMeasurementCandidate($groundedText));

        if ($numericalValues !== [] && $this->findingHasUncoveredMeasurement($groundedText, $numericalValues)) {
            // Keep supported values only when uncovered tokens are a different unit class;
            // do not blank an otherwise supported single-measurement statement.
            if ($this->hasAmbiguousNumericMeasurements($groundedText, $numericalValues)) {
                $reasons[] = 'accuracy_numeric_ambiguous';
                $claimText = '';
                $numericalValues = [];
            }
        } elseif ($numericalValues === [] && $needsMeasurement) {
            // PARTIAL qualitative support may omit numbers; do not force a numeric gate.
            if ($relationship === ClaimEvidenceRelationship::PARTIALLY_SUPPORTED
                && ! $this->textContainsMeasurementCandidate($groundedText)) {
                // keep grounded qualitative partial prose
            } else {
                if ($reasons === []) {
                    $reasons[] = 'accuracy_numeric_unsupported';
                }
                $claimText = '';
            }
        }

        $blocking = array_values(array_unique($reasons));
        if ($claimText === '' && $blocking === []) {
            $blocking[] = 'accuracy_numeric_unsupported';
        }
        if ($blocking !== []) {
            return [
                'allowed' => false,
                'reasons' => $blocking,
                'claim_text' => '',
                'numerical_values' => [],
            ];
        }

        if ($relationship === ClaimEvidenceRelationship::PARTIALLY_SUPPORTED && $claimText === '') {
            return [
                'allowed' => false,
                'reasons' => ['accuracy_numeric_unsupported'],
                'claim_text' => '',
                'numerical_values' => [],
            ];
        }

        return [
            'allowed' => $claimText !== '',
            'reasons' => [],
            'claim_text' => $claimText,
            'numerical_values' => $numericalValues,
        ];
    }

    /**
     * Public read of the B3 observation-year rule for other Stage-5 consumers.
     * Does not use publicationYear. Empty when no explicit observation year exists.
     */
    public function observationYearForItem(ScientificEvidenceItem $item): string
    {
        return $this->explicitObservationYear($item);
    }

    /**
     * Explicit observation / statistical / structured year only.
     * Never falls back to publicationYear or surface publication metadata.
     */
    private function explicitObservationYear(ScientificEvidenceItem $item): string
    {
        foreach ([$item->qualityFactors, $item->sourceAttribution] as $meta) {
            if (! is_array($meta)) {
                continue;
            }

            foreach (['observation', 'statistical', 'structured'] as $bagKey) {
                $bag = $meta[$bagKey] ?? null;
                if (! is_array($bag)) {
                    continue;
                }
                foreach (['year', 'year_code', 'time'] as $yearKey) {
                    $value = trim((string) ($bag[$yearKey] ?? ''));
                    if ($value !== '' && preg_match('/^(?:19|20)\d{2}$/', $value) === 1) {
                        return $value;
                    }
                }
            }

            if (array_key_exists('observation_year', $meta)) {
                $value = trim((string) $meta['observation_year']);
                if ($value !== '' && preg_match('/^(?:19|20)\d{2}$/', $value) === 1) {
                    return $value;
                }
            }
        }

        return '';
    }

    /**
     * Catalog-free property-term address check (Phase 5 B3).
     * Alphabetic terms are token-safe. Unit glyphs use normalized containment.
     * Does not call AgriculturalEntityCatalog. Does not decide canonical identity.
     *
     * @param  list<mixed>  $propertyTerms
     */
    public function haystackAddressesPropertyTerms(string $haystack, array $propertyTerms): bool
    {
        $haystack = mb_strtolower(trim($haystack));
        $haystack = preg_replace('/[\x{00A0}\x{202F}]/u', ' ', $haystack) ?? $haystack;
        if ($haystack === '') {
            return false;
        }

        foreach ($propertyTerms as $term) {
            $normalized = mb_strtolower(trim((string) $term));
            if ($normalized === '' || in_array($normalized, ['general_knowledge', 'agriculture', 'farming', 'general', 'definition'], true)) {
                continue;
            }
            if (! $this->haystackContainsAddressTerm($haystack, $normalized)) {
                continue;
            }
            if ($this->propertyMentionIsNegated($haystack, $normalized)) {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * Alphabetic family terms match at word boundaries. Unit glyphs (°, /, digits)
     * use normalized containment. Short English synonyms cannot match inside
     * unrelated tokens (heat ⊂ wheat).
     */
    private function haystackContainsAddressTerm(string $haystack, string $term): bool
    {
        if (preg_match('/[^\p{L}\s]/u', $term) === 1) {
            return mb_strpos($haystack, $term) !== false;
        }

        $quoted = preg_quote($term, '/');
        if (str_contains($term, ' ') || mb_strlen($term) <= 4) {
            return preg_match('/\b'.$quoted.'\b/u', $haystack) === 1;
        }

        return preg_match('/\b'.$quoted.'\w*\b/u', $haystack) === 1;
    }

    /**
     * Catalog-free negation guard for property mentions ("without reporting yield").
     */
    private function propertyMentionIsNegated(string $haystack, string $term): bool
    {
        $quoted = preg_quote($term, '/');

        return preg_match(
            '/\b(?:without|no|not|never|lacking|absent|excluding)\b(?:\s+\w+){0,4}\s+'.$quoted.'\b/u',
            $haystack,
        ) === 1
            || preg_match(
                '/\b'.$quoted.'\b(?:\s+\w+){0,3}\s+\b(?:not|never)\s+(?:reported|present|found|measured)\b/u',
                $haystack,
            ) === 1;
    }

    public function measurementWindowAddressesProperty(string $text, string $number, string $propertyKey): bool
    {
        $propertyKey = mb_strtolower(trim($propertyKey));
        if ($propertyKey === '' || in_array($propertyKey, ['general', 'definition'], true)) {
            return true;
        }

        $terms = $this->propertyAddressTerms($propertyKey);
        if ($terms === []) {
            return true;
        }

        $hay = mb_strtolower(preg_replace('/[\x{00A0}\x{202F}]/u', ' ', $text) ?? $text);
        if ($this->haystackAddressesPropertyTerms($hay, $terms)) {
            return true;
        }

        $needles = array_values(array_filter([
            mb_strtolower(trim($number)),
            preg_replace('/[^\d.,]/', '', $number) ?? '',
            ...preg_split('/[\s\x{00A0}\x{202F}]*[-–—\/][\s\x{00A0}\x{202F}]*/u', $number) ?: [],
        ], static fn (string $part): bool => trim($part) !== ''));
        $pos = false;
        foreach ($needles as $needle) {
            $found = mb_strpos($hay, mb_strtolower(trim((string) $needle)));
            if ($found !== false) {
                $pos = $found;
                break;
            }
        }
        if ($pos === false) {
            return false;
        }

        $start = max(0, $pos - 120);
        $window = mb_substr($hay, $start, 240);

        return $this->haystackAddressesPropertyTerms($window, $terms);
    }

    /**
     * @return list<string>
     */
    public function propertyAddressTerms(string $property): array
    {
        $aliases = [
            'quantity' => ['quantity', 'production', 'produced', 'output', 'tonnage', 'tons', 'tonnes'],
            'yield' => ['yield', 'productivity', 'productive', 't/ha', 'kg/ha'],
            'area' => ['area', 'harvested', 'hectare', 'hectares', 'ha'],
            'rate' => ['rate', 'dosage', 'seed rate', 'seeding'],
            'irrigation' => ['irrigation', 'water requirement', 'watering'],
            'temperature' => ['temperature', 'celsius', '°c', 'deg c'],
            'production' => ['production', 'quantity', 'produced', 'output', 'tons', 'tonnes'],
            'concentration' => ['concentration', 'ppm', 'mg/l', 'salinity'],
        ];

        $terms = $aliases[$property] ?? [];
        $terms[] = $property;

        return array_values(array_unique(array_map(
            static fn (string $term): string => mb_strtolower(trim($term)),
            $terms,
        )));
    }

    /**
     * Sense-bearing measurement-class terms: units and synonyms, not the bare class key.
     * The bare class label remains a weak topic keyword at the relevance layer.
     *
     * @return list<string>
     */
    public function measurementExpressionTerms(string $property): array
    {
        $property = mb_strtolower(trim($property));
        $bare = [$property];

        return array_values(array_filter(
            $this->propertyAddressTerms($property),
            static fn (string $term): bool => $term !== '' && ! in_array($term, $bare, true),
        ));
    }

    /**
     * Topic-sense check for a measurement class: expressed unit of that class,
     * or morphological class form (temperatures). Bare class keywords and
     * ambiguous synonyms (heat/thermal in "thermal sensor") are not sense.
     * Does not decide numeric claim expressibility.
     */
    public function haystackHasMeasurementClassSense(string $haystack, string $property): bool
    {
        $property = mb_strtolower(trim($property));
        $allowed = $this->requestedPropertyUnitClasses($property);
        if ($property === '' || $allowed === null || $allowed === []) {
            return false;
        }

        $hay = mb_strtolower(preg_replace('/[\x{00A0}\x{202F}]/u', ' ', $haystack) ?? $haystack);
        foreach ($this->measurementUnitClassesInText($hay) as $class) {
            if (in_array($class, $allowed, true)) {
                return true;
            }
        }
        foreach ($this->measurementExpressionTerms($property) as $term) {
            if (! $this->isMeasurementUnitTerm($term)) {
                continue;
            }
            if ($this->measurementTermAddressesHaystack($hay, $term)) {
                return true;
            }
        }

        return preg_match('/\b'.preg_quote($property, '/').'\w+\b/u', $hay) === 1;
    }

    private function isMeasurementUnitTerm(string $term): bool
    {
        $term = mb_strtolower(trim($term));
        if ($term === '') {
            return false;
        }
        if (preg_match('/[^\p{L}\s]/u', $term) === 1) {
            return true;
        }

        return in_array($term, ['celsius', 'kelvin', 'tonnage', 'tonnes', 'tons', 'hectares'], true);
    }

    /**
     * @return list<string>
     */
    public function measurementUnitClassesInText(string $text): array
    {
        $text = preg_replace('/[\x{00A0}\x{202F}]/u', ' ', $text) ?? $text;
        $pattern = '/(?<![\/.\w])(\d+(?:[.,]\d+)?(?:\s*[-–—\/]\s*\d+(?:[.,]\d+)?)?)\s*(?:million|billion)?\s*(%|kg\/ha|kg\s*ha-1|t\/ha|mg\/l|mg\/kg|kg\/day|kg\s*d-1|l\/day|mm\/day|m3\/ha|°c|deg c|celsius|kelvin|ppm|ph|tons?|tonnes?|hectares?|t|kg|g|mg|l|ml|mm|cm|m|ha|days?|weeks?|months?|c)\b/iu';
        preg_match_all($pattern, $text, $matches, PREG_SET_ORDER);
        $classes = [];
        foreach (is_array($matches) ? $matches : [] as $match) {
            $unit = mb_strtolower(trim((string) ($match[2] ?? '')));
            if ($unit === '') {
                continue;
            }
            $classes[$this->measurementUnitClass($unit)] = true;
        }

        return array_keys($classes);
    }

    public function measurementTermAddressesHaystack(string $haystack, string $term): bool
    {
        $haystack = mb_strtolower(trim($haystack));
        $term = mb_strtolower(trim($term));
        if ($haystack === '' || $term === '') {
            return false;
        }
        if (preg_match('/[^\p{L}\s]/u', $term) === 1) {
            return mb_strpos($haystack, $term) !== false;
        }
        $quoted = preg_quote($term, '/');
        if (mb_strlen($term) <= 4) {
            return preg_match('/\b'.$quoted.'\b/u', $haystack) === 1;
        }

        return preg_match('/\b'.$quoted.'\w*\b/u', $haystack) === 1;
    }

    public function measurementUnitClass(string $unit): string
    {
        $unit = mb_strtolower(trim($unit));

        return match (true) {
            in_array($unit, ['°c', 'deg c', 'celsius', 'kelvin', 'c'], true) => 'temperature',
            in_array($unit, ['ppm', 'mg/l', 'mg/kg', '%', 'ph'], true) => 'concentration',
            in_array($unit, ['kg/ha', 'kg ha-1', 't/ha', 'kg/day', 'kg d-1', 'l/day', 'mm/day', 'm3/ha', 'mm'], true) => 'rate',
            in_array($unit, ['ha', 'hectare', 'hectares'], true) => 'area',
            in_array($unit, ['kg', 'g', 'mg', 'l', 'ml', 'tons', 'ton', 'tonnes', 'tonne', 't'], true) => 'quantity',
            in_array($unit, ['days', 'day', 'weeks', 'week', 'months', 'month'], true) => 'time',
            default => 'quantity',
        };
    }

    /**
     * Frozen CSQ/RS measurement family for this plan. Not a second identity resolver:
     * surfaces, question_type, and factors are not votes. The $property argument is
     * ignored so leftover callers cannot promote user-language text to identity.
     */
    public function canonicalMeasurementProperty(string $property, KnowledgeQueryPlan $plan): string
    {
        return $this->frozenMeasurementFamily($plan);
    }

    /**
     * Canonical measurement family already frozen on CSQ / RetrievalSpecification.
     */
    public function frozenMeasurementFamily(KnowledgeQueryPlan $plan): string
    {
        $query = $plan->normalizedQuery;
        $csq = $query->canonicalQuestion;
        if ($csq !== null) {
            $family = RetrievalSpecification::canonicalFamilyKey(
                $csq->property->key,
                $csq->property->surface,
                $csq->context->scientificSense,
            );
            if ($this->isFrozenMeasurementFamily($family)) {
                return mb_strtolower(trim((string) $family));
            }
        }

        $spec = $csq !== null ? RetrievalSpecification::fromCanonical($csq) : null;
        $raw = $query->constraints['retrieval_specification'] ?? null;
        if ($spec === null && is_array($raw) && $raw !== []) {
            $spec = RetrievalSpecification::fromArray($raw);
        }
        if ($spec !== null) {
            $fromRole = match ($spec->scholarlyPropertyRole()) {
                RetrievalSemanticContract::ROLE_TEMPERATURE => 'temperature',
                RetrievalSemanticContract::ROLE_IRRIGATION_WATER => 'irrigation',
                RetrievalSemanticContract::ROLE_CLASSIFICATION => 'classification',
                RetrievalSemanticContract::ROLE_QUANTITY, RetrievalSemanticContract::ROLE_PRODUCTIVITY => 'quantity',
                default => '',
            };
            if ($this->isFrozenMeasurementFamily($fromRole)) {
                return $fromRole;
            }
        }

        return '';
    }

    private function isFrozenMeasurementFamily(?string $family): bool
    {
        $family = mb_strtolower(trim((string) $family));
        if ($family === '') {
            return false;
        }

        return in_array($family, [
            ...RetrievalSpecification::FAMILY_KEYS,
            'concentration', 'yield', 'rate', 'production',
        ], true);
    }

    private function isMeasurementProperty(string $property): bool
    {
        $property = mb_strtolower(trim($property));
        if ($property === '' || in_array($property, ['general', 'definition'], true)) {
            return false;
        }

        $classes = $this->requestedPropertyUnitClasses($property);

        return is_array($classes) && $classes !== [];
    }

    /**
     * @return list<string>|null
     */
    public function requestedPropertyUnitClasses(string $target): ?array
    {
        return match ($target) {
            'temperature', 'range' => ['temperature'],
            'concentration' => ['concentration'],
            'quantity', 'yield', 'rate', 'production', 'irrigation', 'requirements' => ['quantity', 'rate'],
            'area' => ['area'],
            'classification', 'species', 'definition', 'causes', 'symptoms', 'comparison' => [],
            default => null,
        };
    }

    private function planWithClaimProperty(KnowledgeQueryPlan $plan, string $property): KnowledgeQueryPlan
    {
        $family = $this->frozenMeasurementFamily($plan);
        if ($family === '') {
            return $plan;
        }

        $constraints = is_array($plan->normalizedQuery->constraints) ? $plan->normalizedQuery->constraints : [];
        $constraints['requested_property'] = $family;
        $constraints['requested_property_query_terms'] = $this->propertyAddressTerms($family);
        $constraints['requested_property_surface'] = $constraints['requested_property_surface']
            ?? $family;

        $q = $plan->normalizedQuery;

        return new KnowledgeQueryPlan(
            normalizedQuery: $q->copyPreservingCanonical($constraints, $q->crop, $q->cropId),
            researchIntent: $plan->researchIntent,
            agriculturalDomain: $plan->agriculturalDomain,
            subjectEntity: $plan->subjectEntity,
            topics: $plan->topics,
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

    private function textContainsMeasurementCandidate(string $text): bool
    {
        return preg_match(
            '/\d+(?:[.,]\d+)?\s*(?:million|billion)?\s*(?:%|kg\/ha|t\/ha|kg|ha|hectares?|mm|cm|m|l|ml|°c|ph|ppm|mg|g|tons?|tonnes?|t|days?|weeks?|months?)\b/iu',
            $text,
        ) === 1;
    }

    /**
     * @param  list<string>  $supportedValues
     */
    private function findingHasUncoveredMeasurement(string $text, array $supportedValues): bool
    {
        $pattern = '/(?<![\/.\w])(\d+(?:[.,]\d+)?(?:\s*[-–]\s*\d+(?:[.,]\d+)?)?)\s*(?:million|billion)?\s*(%|kg\/ha|kg\s*ha-1|t\/ha|mg\/l|mg\/kg|kg\/day|kg\s*d-1|l\/day|mm\/day|m3\/ha|°c|deg c|celsius|kelvin|ppm|ph|tons?|tonnes?|hectares?|t|kg|g|mg|l|ml|mm|cm|m|ha|days?|weeks?|months?|c)\b/iu';
        preg_match_all($pattern, $text, $matches, PREG_SET_ORDER);
        if ($matches === []) {
            return false;
        }

        foreach ($matches as $match) {
            $raw = trim((string) ($match[0] ?? ''));
            $number = trim((string) ($match[1] ?? ''));
            if ($raw === '') {
                continue;
            }
            $digits = preg_replace('/[^\d]/', '', $number) ?? '';
            if (preg_match('/^(?:19|20)\d{2}$/', $digits) === 1) {
                continue;
            }
            $covered = false;
            foreach ($supportedValues as $value) {
                if (str_contains(mb_strtolower($value), mb_strtolower($number))
                    || str_contains(mb_strtolower($raw), mb_strtolower($value))) {
                    $covered = true;
                    break;
                }
            }
            if (! $covered) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $supported
     */
    private function hasAmbiguousNumericMeasurements(string $text, array $supported): bool
    {
        $pattern = '/(?<![\/.\w])(\d+(?:[.,]\d+)?(?:\s*[-–]\s*\d+(?:[.,]\d+)?)?)\s*(?:million|billion)?\s*(%|kg\/ha|kg\s*ha-1|t\/ha|mg\/l|mg\/kg|kg\/day|kg\s*d-1|l\/day|mm\/day|m3\/ha|°c|deg c|celsius|kelvin|ppm|ph|tons?|tonnes?|hectares?|t|kg|g|mg|l|ml|mm|cm|m|ha|days?|weeks?|months?|c)\b/iu';
        preg_match_all($pattern, $text, $matches, PREG_SET_ORDER);
        $classes = [];
        foreach ($matches as $match) {
            $unit = mb_strtolower(trim((string) ($match[2] ?? '')));
            if ($unit === '') {
                continue;
            }
            $classes[$this->measurementUnitClass($unit)] = true;
        }

        if (count($classes) <= 1) {
            return false;
        }

        // Any extra unit class in the evidence prose beyond what was accepted as
        // supported for the claim property is treated as unresolved ambiguity.
        return true;
    }
}
