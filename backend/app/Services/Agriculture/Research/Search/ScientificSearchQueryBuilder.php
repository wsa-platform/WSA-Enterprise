<?php

namespace App\Services\Agriculture\Research\Search;

use App\Services\Agriculture\FieldCropTaxonomyCatalog;
use App\Services\Agriculture\Research\AgriculturalEntityCatalog;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;

/**
 * Builds deterministic scholarly search queries from Stage 2 planning output.
 *
 * Prefers scientific entity + English topic/factor terms over raw multilingual user text.
 * Emits multiple intent/sense-driven variants for multi-query retrieval.
 */
class ScientificSearchQueryBuilder
{
    private const MAX_VARIANTS = 5;

    public function buildFromPlan(KnowledgeQueryPlan $plan): string
    {
        $variants = $this->buildVariantsFromPlan($plan);

        return $variants[0] ?? 'agriculture';
    }

    /**
     * Controlled query variants (scientific entity + English topics + sense synonyms).
     *
     * @return list<string>
     */
    public function buildVariantsFromPlan(KnowledgeQueryPlan $plan): array
    {
        $query = $plan->normalizedQuery;
        $entity = $this->resolveEntityTerm($plan);
        $commonLabels = $this->resolveCommonEntityLabels($plan);
        $topics = $this->resolveTopicTerms($plan);
        $sense = trim((string) ($query->constraints['scientific_sense'] ?? ''));
        $qualifier = trim((string) ($query->constraints['scientific_intent_qualifier'] ?? ''));
        $factors = is_array($query->constraints['scientific_factors'] ?? null)
            ? $query->constraints['scientific_factors']
            : [];
        $intentTerms = AgriculturalEntityCatalog::englishTermsForIntent($plan->researchIntent);
        $senseTerms = $sense !== ''
            ? AgriculturalEntityCatalog::senseQueryTerms($sense)
            : [];
        $primaryCommon = $commonLabels[0] ?? null;
        $wantsCultivationProduction = $this->wantsCultivationProductionVariants($plan);
        $mentionsRhizome = $this->mentionsRhizome($plan);
        $location = $this->resolveAskedLocation($plan);
        $isLandClassification = $sense === 'land_classification';
        $landSuitabilityTerms = $this->resolveLandSuitabilityTerms($plan);
        $primaryTopic = $topics[0] ?? ($senseTerms[0] ?? null);

        // Land classification must keep land/soil topics — never degrade to bare cultivation.
        if ($isLandClassification) {
            $landTerms = $this->landClassificationTopicTerms($plan);
            $topics = $landTerms;
            $senseTerms = $landTerms;
            $intentTerms = ['agriculture'];
            $primaryTopic = $landTerms[0] ?? 'land classification';
            $wantsCultivationProduction = false;
        } elseif ($landSuitabilityTerms !== []) {
            // Crop + land suitability: lead with suitability terms, not bare cultivation-only.
            $wantsCultivationProduction = false;
            $primaryTopic = $landSuitabilityTerms[0];
        }

        $isMethodClassification = $this->isClassificationMethodQuestion($plan);
        $isInventoryClassification = ! $isMethodClassification && $this->isInventoryClassificationQuestion($plan);
        $isVarietyInventory = $this->isCropVarietyInventoryQuestion($plan);
        $plantFamily = $this->resolveBotanicalFamily($plan);

        $variants = [];

        // Plant-family member inventory (e.g. Cucurbitaceae) — keep family + species/members, not greenhouse crop culture.
        if ($plantFamily !== null) {
            foreach ($this->buildPlantFamilyMemberVariants($plantFamily) as $familyVariant) {
                $variants[] = $familyVariant;
            }
            $wantsCultivationProduction = false;
        }

        if ($entity !== null) {
            // Crop variety/cultivar inventory: prefer varieties/cultivars; do not drift to disease/oil/storage.
            if ($isVarietyInventory) {
                $wantsCultivationProduction = false;
                foreach ($this->buildCropVarietyInventoryVariants($entity, $primaryCommon, $location) as $varietyVariant) {
                    $variants[] = $varietyVariant;
                }
            }

            // National inventory for crop/livestock entities (varieties / breeds) before generic topics.
            if ($isInventoryClassification && $location !== null && $this->isCountryLocation($location)) {
                foreach ($this->buildEntityNationalInventoryVariants($plan, $entity, $primaryCommon, $location) as $inventoryVariant) {
                    $variants[] = $inventoryVariant;
                }
            }

            // Rhizome questions: lead with rhizome variants so ranking is not hijacked by
            // accidental irrigation topic matches from Arabic "ري" inside "ريزوم".
            if ($mentionsRhizome) {
                $variants[] = $this->joinTerms([$entity, 'rhizome']);
                if ($primaryCommon !== null) {
                    $variants[] = $this->joinTerms([$primaryCommon, 'rhizome']);
                }
                $topics = array_values(array_filter(
                    $topics,
                    static fn (string $topic): bool => ! in_array(mb_strtolower($topic), ['irrigation', 'water'], true),
                ));
                $factors = array_values(array_filter(
                    $factors,
                    static fn ($factor): bool => ! in_array(mb_strtolower((string) $factor), ['irrigation', 'water'], true),
                ));
                // Arabic "ريزوم" can false-match irrigation intent/sense; prefer growth terms.
                $senseTerms = array_values(array_filter(
                    $senseTerms,
                    static fn (string $term): bool => ! in_array(mb_strtolower($term), [
                        'irrigation', 'evapotranspiration', 'water use', 'water',
                    ], true),
                ));
                if ($senseTerms === []) {
                    $senseTerms = ['growth', 'physiology', 'cultivation'];
                }
                $primaryTopic = $topics[0] ?? ($senseTerms[0] ?? null);
            }

            // Entity + land/soil suitability (from plan topics or question markers).
            if ($landSuitabilityTerms !== []) {
                foreach (array_slice($landSuitabilityTerms, 0, 2) as $suitabilityTerm) {
                    $variants[] = $this->joinTerms([$entity, $suitabilityTerm, $location]);
                    if ($primaryCommon !== null && strcasecmp($primaryCommon, $entity) !== 0) {
                        $variants[] = $this->joinTerms([$primaryCommon, $suitabilityTerm, $location]);
                    }
                }
            }

            $variants[] = $this->joinTerms([$entity, $primaryTopic, $senseTerms[0] ?? null]);

            // Context-aware diversification: common crop labels + cultivation/production.
            if ($primaryCommon !== null && strcasecmp($primaryCommon, $entity) !== 0) {
                $variants[] = $this->joinTerms([$primaryCommon, $primaryTopic, $senseTerms[0] ?? null]);
            }

            if ($wantsCultivationProduction) {
                if ($primaryCommon !== null) {
                    $variants[] = $this->joinTerms([$primaryCommon, 'cultivation']);
                    $variants[] = $this->joinTerms([$primaryCommon, 'production']);
                }
                $variants[] = $this->joinTerms([$entity, 'cultivation']);
                $variants[] = $this->joinTerms([$entity, 'production']);

                $genus = $this->genusFromScientificName($entity);
                if ($genus !== null) {
                    $variants[] = $this->joinTerms([$genus, 'cultivation']);
                }
            }

            foreach ($this->synonymTopicPairs($factors, $sense) as $pair) {
                $variants[] = $this->joinTerms([$entity, $pair[0], $pair[1] ?? ($senseTerms[0] ?? null)]);
            }

            if ($qualifier === 'optimal_range' && $primaryTopic !== null) {
                $variants[] = $this->joinTerms([$entity, 'optimal', $primaryTopic, $senseTerms[0] ?? 'growth']);
            }

            if ($qualifier === 'effect' && $primaryTopic !== null) {
                $variants[] = $this->joinTerms([$entity, $primaryTopic, 'effect', $senseTerms[0] ?? 'physiology']);
            }

            if ($senseTerms !== []) {
                foreach (array_slice($senseTerms, 0, 3) as $senseTerm) {
                    $variants[] = $this->joinTerms([$entity, $primaryTopic, $senseTerm]);
                }
            }

            $variants[] = $this->joinTerms([$entity, ...array_slice($topics, 0, 2), ...array_slice($intentTerms, 0, 1)]);
            $variants[] = $this->joinTerms([$entity, $plan->researchIntent, 'agriculture']);
        } elseif ($isMethodClassification) {
            foreach ($this->buildClassificationMethodVariants($plan, $location) as $methodVariant) {
                $variants[] = $methodVariant;
            }
        } elseif ($this->isGeoAquacultureQuestion($plan)) {
            $variants[] = $this->joinTerms(['aquaculture', 'fish', $location]);
            $variants[] = $this->joinTerms(['fish farming', $location]);
            $variants[] = $this->joinTerms([
                $topics[0] ?? 'aquaculture',
                'aquaculture',
                $location,
            ]);
        } elseif ($isLandClassification || ($isInventoryClassification && $this->isLandSoilInventorySubject($plan))) {
            // National-first inventory when location is a country; regional stays regional-primary.
            foreach ($this->buildLandSoilInventoryVariants($plan, $location) as $inventoryVariant) {
                $variants[] = $inventoryVariant;
            }
        } else {
            $latinQuestion = $this->latinScientificFragment($query->normalizedQuestion);
            $variants[] = $this->joinTerms([...$topics, ...array_slice($senseTerms, 0, 2), ...array_slice($intentTerms, 0, 2)]);
            if ($latinQuestion !== null) {
                $variants[] = $this->joinTerms([$latinQuestion, 'agriculture']);
            }
            $variants[] = $this->joinTerms([$plan->researchIntent, $plan->agriculturalDomain, 'agriculture']);
        }

        // Hydroponics / production-system variants (no country filter).
        $productionSystem = trim((string) ($query->constraints['production_system'] ?? ''));
        if ($productionSystem === 'hydroponics') {
            $variants[] = $this->joinTerms([$entity, 'hydroponics', 'soilless culture']);
            $variants[] = $this->joinTerms(['hydroponics', 'agriculture', $primaryTopic ?? ($topics[0] ?? null)]);
        }

        // Aquaculture geo variants when location asked (study location — never publisher country).
        if ($location !== null && $this->isGeoAquacultureQuestion($plan) && $entity !== null) {
            $variants[] = $this->joinTerms([
                $entity,
                'aquaculture',
                $location,
            ]);
            $variants[] = $this->joinTerms(['fish farming', $location]);
        }

        $unique = [];
        foreach ($variants as $variant) {
            $trimmed = trim($variant);
            if ($trimmed === '') {
                continue;
            }
            // Retain explicit plan geography on every emitted variant when present.
            if ($location !== null && ! $this->variantMentionsLocation($trimmed, $location)) {
                $trimmed = $this->joinTerms([$trimmed, $location]);
            }
            if ($trimmed === '' || in_array($trimmed, $unique, true)) {
                continue;
            }
            $unique[] = $trimmed;
            if (count($unique) >= self::MAX_VARIANTS) {
                break;
            }
        }

        return $unique !== [] ? $unique : ['agriculture'];
    }

    /**
     * Consensus HTTP query options from the structured plan.
     * domain=agri for agricultural questions; country only when location asked (study country).
     * Never maps publisher to geo.
     *
     * @return array{domain?: string, country?: string, include_semantic_score: bool}
     */
    public function buildConsensusRequestOptions(KnowledgeQueryPlan $plan): array
    {
        $options = [
            'include_semantic_score' => true,
        ];

        if ($this->shouldUseAgriDomain($plan)) {
            $options['domain'] = 'agri';
        }

        $location = $this->resolveAskedLocation($plan);
        if ($location !== null) {
            $iso = AgriculturalEntityCatalog::locationToIsoCountryCode($location);
            if ($iso !== null) {
                $options['country'] = $iso;
            }
        }

        return $options;
    }

    private function shouldUseAgriDomain(KnowledgeQueryPlan $plan): bool
    {
        $domain = mb_strtolower(trim((string) $plan->agriculturalDomain));
        if ($domain !== '' && (
            str_contains($domain, 'agricultur')
            || str_contains($domain, 'crop')
            || str_contains($domain, 'plant')
            || str_contains($domain, 'horticult')
            || str_contains($domain, 'aquacultur')
            || str_contains($domain, 'livestock')
            || str_contains($domain, 'soil')
        )) {
            return true;
        }

        if (in_array($plan->researchIntent, [
            'cultivation', 'irrigation', 'fertilization', 'soil_management', 'plant_nutrition',
            'disease', 'pest', 'aquaculture', 'animal_production', 'poultry_production',
            'beekeeping', 'feed', 'general_knowledge',
        ], true)) {
            return true;
        }

        $branch = trim((string) ($plan->normalizedQuery->constraints['scientific_domain_branch'] ?? ''));

        return $branch !== '';
    }

    private function resolveAskedLocation(KnowledgeQueryPlan $plan): ?string
    {
        $fromQuery = trim((string) ($plan->normalizedQuery->location ?? ''));
        if ($fromQuery !== '') {
            return $fromQuery;
        }

        $fromConstraints = trim((string) ($plan->normalizedQuery->constraints['location'] ?? ''));

        return $fromConstraints !== '' ? $fromConstraints : null;
    }

    private function isGeoAquacultureQuestion(KnowledgeQueryPlan $plan): bool
    {
        if (in_array($plan->researchIntent, ['aquaculture'], true)) {
            return true;
        }

        $subjectType = is_array($plan->subjectEntity) ? (string) ($plan->subjectEntity['type'] ?? '') : '';
        if ($subjectType === 'fish') {
            return true;
        }

        $haystack = mb_strtolower(trim(
            $plan->normalizedQuery->originalQuestion.' '.$plan->normalizedQuery->normalizedQuestion
        ));

        foreach (['aquaculture', 'fish farming', 'استزراع', 'أسماك', 'اسماك', 'fish'] as $marker) {
            if ($marker !== '' && mb_strpos($haystack, mb_strtolower($marker)) !== false) {
                return true;
            }
        }

        return false;
    }

    private function isCountryLocation(string $location): bool
    {
        return AgriculturalEntityCatalog::locationToIsoCountryCode($location) !== null;
    }

    /**
     * Inventory/classification intent from plan semantics (and light land/soil phrasing fallback).
     * Method questions are excluded by the caller.
     */
    private function isInventoryClassificationQuestion(KnowledgeQueryPlan $plan): bool
    {
        $query = $plan->normalizedQuery;
        $sense = trim((string) ($query->constraints['scientific_sense'] ?? ''));
        $questionType = trim((string) ($query->constraints['question_type'] ?? ''));
        $required = trim((string) ($query->constraints['required_evidence_type'] ?? ''));

        if ($sense === 'land_classification') {
            return true;
        }

        if ($questionType === 'classification' && in_array($required, [
            'classification_or_types_inventory',
            'species_list_or_taxonomy',
        ], true)) {
            return true;
        }

        if ($required === 'classification_or_types_inventory') {
            return true;
        }

        if (in_array('classification_inventory', $plan->subtopics, true)
            || in_array('classification_inventory', $plan->requestedInformation, true)
            || in_array('types_or_classification', $plan->requestedInformation, true)) {
            return true;
        }

        return $this->questionImpliesLandSoilInventory($plan);
    }

    /**
     * True when the inventory question is about land/soil types (not fish species / generic lists).
     */
    private function isLandSoilInventorySubject(KnowledgeQueryPlan $plan): bool
    {
        $sense = trim((string) ($plan->normalizedQuery->constraints['scientific_sense'] ?? ''));
        if ($sense === 'land_classification' || $plan->researchIntent === 'land_classification') {
            return true;
        }

        $subjectType = is_array($plan->subjectEntity) ? (string) ($plan->subjectEntity['type'] ?? '') : '';
        if (in_array($subjectType, ['land', 'soil'], true)) {
            return true;
        }

        if ($this->isGeoAquacultureQuestion($plan)) {
            return false;
        }

        $domain = mb_strtolower(trim((string) $plan->agriculturalDomain));
        if (str_contains($domain, 'soil') || str_contains($domain, 'land')) {
            return true;
        }

        return $this->questionImpliesLandSoilInventory($plan);
    }

    private function isClassificationMethodQuestion(KnowledgeQueryPlan $plan): bool
    {
        $haystack = trim(
            $plan->normalizedQuery->originalQuestion.' '.$plan->normalizedQuery->normalizedQuestion
        );

        return AgriculturalEntityCatalog::isLandOrSoilClassificationMethodQuestion($haystack);
    }

    private function questionImpliesLandSoilInventory(KnowledgeQueryPlan $plan): bool
    {
        $haystack = mb_strtolower(trim(
            $plan->normalizedQuery->originalQuestion.' '.$plan->normalizedQuery->normalizedQuestion
        ));
        if ($haystack === '') {
            return false;
        }

        if (AgriculturalEntityCatalog::asksLandOrSoilTypesInventory($haystack)) {
            return true;
        }

        // Covers "soil classes" / "land categories" when Stage 2 under-tags question_type.
        return preg_match(
            '/\b(?:soil|land)\s+(?:classes?|categories|taxonomy|associations|inventory)\b|'
            .'soil\s+map\s+of|national\s+soil\s+classification|'
            .'أصناف\s*(?:ال)?(?:تربة|أراضي|اراضي)|فئات\s*(?:ال)?(?:تربة|أراضي|اراضي)/u',
            $haystack,
        ) === 1;
    }

    /**
     * National-first land/soil inventory variants, or regional-primary when location is not a country.
     *
     * @return list<string>
     */
    private function buildLandSoilInventoryVariants(KnowledgeQueryPlan $plan, ?string $location): array
    {
        if ($location !== null && $this->isCountryLocation($location)) {
            return $this->buildNationalLandSoilInventoryVariants($location);
        }

        return $this->buildRegionalOrGenericLandSoilInventoryVariants($location);
    }

    /**
     * NATIONAL INVENTORY → NATIONAL SYNONYMS → REGIONAL fallback → GENERIC.
     * Precise distinct variants; country preserved on every national variant.
     *
     * @return list<string>
     */
    private function buildNationalLandSoilInventoryVariants(string $country): array
    {
        $gentilic = $this->countryGentilicAdjective($country);
        $variants = [
            // 1. National inventory (primary emission window)
            $this->joinTerms(['agricultural land types', $country]),
            $this->joinTerms(['land types', 'agricultural', $country]),
            $this->joinTerms(['soil types', $country]),
            $this->joinTerms(['soil classification', $country]),
            $this->joinTerms(['land classification', $country]),
            $this->joinTerms(['soil classification', $country, 'agriculture']),
            $this->joinTerms(['land resources', $country, 'agriculture']),
            // 2. National synonyms / scientific terminology (still ahead of regional/generic)
            $this->joinTerms(['Soil Map of', $country]),
            $this->joinTerms([$gentilic, 'soil types', $country]),
            $this->joinTerms(['national soil classification', $country]),
            $this->joinTerms(['soil associations', $country]),
            $this->joinTerms([$gentilic, 'agricultural land classification', $country]),
            $this->joinTerms([$gentilic, 'Soil Taxonomy', $country]),
            $this->joinTerms(['types of soils', $country]),
            // 3. Regional within country (fallback — not primary)
            $this->joinTerms(['soil classification', 'regions', $country]),
            // 4. Generic scientific context (still country-scoped)
            $this->joinTerms(['soil taxonomy', 'agriculture', $country]),
        ];

        return array_values(array_filter($variants, static fn (string $v): bool => trim($v) !== ''));
    }

    /**
     * Regional inventory: preserve region as primary; do not force national-only strategy.
     *
     * @return list<string>
     */
    private function buildRegionalOrGenericLandSoilInventoryVariants(?string $location): array
    {
        $variants = [
            $this->joinTerms(['soil types', $location]),
            $this->joinTerms(['soil classification', $location]),
            $this->joinTerms(['land classification', $location]),
            $this->joinTerms(['agricultural land types', $location]),
            $this->joinTerms(['types of soils', $location]),
            $this->joinTerms(['soil associations', $location]),
            $this->joinTerms(['land types', 'soil types', $location]),
        ];

        return array_values(array_filter($variants, static fn (string $v): bool => trim($v) !== ''));
    }

    /**
     * Method-oriented variants; inventory must not be primary.
     *
     * @return list<string>
     */
    private function buildClassificationMethodVariants(KnowledgeQueryPlan $plan, ?string $location): array
    {
        $variants = [
            $this->joinTerms(['soil classification', 'method', $location]),
            $this->joinTerms(['soil classification', 'methodology', $location]),
            $this->joinTerms(['land classification', 'method', $location]),
            $this->joinTerms(['soil classification', 'technique', $location]),
            $this->joinTerms(['classification method', 'soils', $location]),
            $this->joinTerms(['soil mapping', 'method', $location]),
        ];

        return array_values(array_filter($variants, static fn (string $v): bool => trim($v) !== ''));
    }

    /**
     * Crop varieties / livestock breeds national inventory when entity + country + inventory intent.
     *
     * @return list<string>
     */
    private function buildEntityNationalInventoryVariants(
        KnowledgeQueryPlan $plan,
        string $entity,
        ?string $primaryCommon,
        string $country,
    ): array {
        $subjectType = is_array($plan->subjectEntity) ? (string) ($plan->subjectEntity['type'] ?? '') : '';
        $intent = $plan->researchIntent;
        $label = $primaryCommon ?? $entity;

        if (in_array($subjectType, ['livestock', 'animal', 'poultry'], true)
            || in_array($intent, ['animal_production', 'poultry_production', 'livestock'], true)) {
            return [
                $this->joinTerms([$label, 'breeds', $country]),
                $this->joinTerms([$entity, 'breeds', $country]),
                $this->joinTerms(['livestock breeds', $country]),
            ];
        }

        return [
            $this->joinTerms([$label, 'varieties', $country]),
            $this->joinTerms([$entity, 'cultivars', $country]),
            $this->joinTerms([$label, 'cultivars', $country]),
            $this->joinTerms([$entity, 'varieties', $country]),
        ];
    }

    /**
     * Crop variety questions without requiring a country (potato cultivars / Solanum tuberosum cultivars).
     *
     * @return list<string>
     */
    private function buildCropVarietyInventoryVariants(
        string $entity,
        ?string $primaryCommon,
        ?string $location,
    ): array {
        $label = $primaryCommon ?? $entity;
        $variants = [
            $this->joinTerms([$label, 'varieties', $location]),
            $this->joinTerms([$entity, 'cultivars', $location]),
            $this->joinTerms([$label, 'cultivars', $location]),
            $this->joinTerms([$entity, 'variety', 'classification', $location]),
            $this->joinTerms([$entity, 'cultivar classification', $location]),
        ];

        return array_values(array_filter($variants, static fn (string $v): bool => trim($v) !== ''));
    }

    /**
     * @return list<string>
     */
    private function buildPlantFamilyMemberVariants(string $family): array
    {
        return [
            $this->joinTerms([$family, 'plants']),
            $this->joinTerms([$family, 'species']),
            $this->joinTerms([$family, 'family members']),
            $this->joinTerms([$family, 'classification']),
            $this->joinTerms(['plants of', $family, 'family']),
        ];
    }

    private function isCropVarietyInventoryQuestion(KnowledgeQueryPlan $plan): bool
    {
        if ($plan->researchIntent === 'varieties') {
            return true;
        }

        $questionType = trim((string) ($plan->normalizedQuery->constraints['question_type'] ?? ''));
        $required = trim((string) ($plan->normalizedQuery->constraints['required_evidence_type'] ?? ''));
        if ($questionType === 'classification' && $required === 'classification_or_types_inventory') {
            $subjectType = is_array($plan->subjectEntity) ? (string) ($plan->subjectEntity['type'] ?? '') : '';
            if ($subjectType === 'crop' || $plan->normalizedQuery->cropId !== null) {
                return true;
            }
        }

        $haystack = mb_strtolower(trim(
            $plan->normalizedQuery->originalQuestion.' '.$plan->normalizedQuery->normalizedQuestion
        ));

        return preg_match('/\b(?:varieties|cultivars|cultivar)\b|اصناف|أصناف|صنف/u', $haystack) === 1
            && ($plan->normalizedQuery->cropId !== null || $plan->normalizedQuery->scientificName !== null);
    }

    private function resolveBotanicalFamily(KnowledgeQueryPlan $plan): ?string
    {
        $haystack = mb_strtolower(trim(
            $plan->normalizedQuery->originalQuestion.' '.$plan->normalizedQuery->normalizedQuestion
        ));
        $fromText = AgriculturalEntityCatalog::resolveBotanicalFamily($haystack);
        if ($fromText !== null) {
            return $fromText;
        }

        $subject = is_array($plan->subjectEntity) ? $plan->subjectEntity : null;
        if ($subject !== null && ($subject['type'] ?? null) === 'plant_family') {
            $value = trim((string) ($subject['value'] ?? $subject['label'] ?? ''));

            return $value !== '' ? $value : null;
        }

        return null;
    }

    private function countryGentilicAdjective(string $country): ?string
    {
        return match (mb_strtolower(trim($country))) {
            'egypt' => 'Egyptian',
            'saudi arabia' => 'Saudi',
            'turkey', 'türkiye', 'turkiye' => 'Turkish',
            'libya' => 'Libyan',
            'sudan' => 'Sudanese',
            'tunisia' => 'Tunisian',
            'algeria' => 'Algerian',
            'morocco' => 'Moroccan',
            'jordan' => 'Jordanian',
            'united arab emirates' => 'Emirati',
            'india' => 'Indian',
            default => null,
        };
    }

    /**
     * Land/soil classification terms from plan scientific topics (fallback when catalog has no sense list).
     *
     * @return list<string>
     */
    private function landClassificationTopicTerms(KnowledgeQueryPlan $plan): array
    {
        $preferred = [
            'land types',
            'soil classification',
            'land classification',
            'agricultural land types',
        ];
        $resolved = $this->resolveTopicTerms($plan);
        $ordered = [];

        foreach ($preferred as $term) {
            foreach ($resolved as $topic) {
                if (strcasecmp($topic, $term) === 0 && ! in_array($topic, $ordered, true)) {
                    $ordered[] = $topic;
                }
            }
        }

        foreach ($resolved as $topic) {
            $lower = mb_strtolower($topic);
            if ((str_contains($lower, 'land') || str_contains($lower, 'soil'))
                && ! in_array($topic, $ordered, true)) {
                $ordered[] = $topic;
            }
        }

        return $ordered !== []
            ? $ordered
            : ['land classification', 'soil classification', 'land types'];
    }

    /**
     * Land/soil suitability terms when the plan or question asks crop land suitability
     * (distinct from land_classification type inventories).
     *
     * @return list<string>
     */
    private function resolveLandSuitabilityTerms(KnowledgeQueryPlan $plan): array
    {
        $sense = trim((string) ($plan->normalizedQuery->constraints['scientific_sense'] ?? ''));
        if ($sense === 'land_classification') {
            return [];
        }

        $fromTopics = [];
        foreach ($this->resolveTopicTerms($plan) as $topic) {
            $lower = mb_strtolower($topic);
            if (str_contains($lower, 'land suitability')
                || str_contains($lower, 'soil suitability')
                || str_contains($lower, 'suitable land')
                || str_contains($lower, 'land capability')) {
                $fromTopics[] = $topic;
            }
        }
        if ($fromTopics !== []) {
            $merged = array_values(array_unique([...$fromTopics, 'land suitability', 'soil suitability']));

            return array_slice($merged, 0, 3);
        }

        $hay = mb_strtolower(trim(
            $plan->normalizedQuery->originalQuestion.' '.$plan->normalizedQuery->normalizedQuestion
        ));
        if ($hay === '') {
            return [];
        }

        if (preg_match(
            '/land\s*suitability|soil\s*suitability|suitable\s+lands?|land\s+capability|الأراضي\s*المناسبة|أرض\s*مناسبة|ملاءمة\s*(?:ال)?أراضي|صلاحية\s*(?:ال)?أراضي/u',
            $hay,
        ) === 1) {
            return ['land suitability', 'soil suitability'];
        }

        return [];
    }

    private function variantMentionsLocation(string $variant, string $location): bool
    {
        $location = trim($location);
        if ($location === '' || $variant === '') {
            return false;
        }

        if (mb_stripos($variant, $location) !== false) {
            return true;
        }

        // Gentilic forms (Egyptian → Egypt) count as preserving the country constraint.
        $gentilic = $this->countryGentilicAdjective($location);
        if ($gentilic !== null && mb_stripos($variant, $gentilic) !== false) {
            return true;
        }

        return false;
    }

    /**
     * English common / genus labels for the resolved crop (catalog-driven, not crop-hardcoded).
     *
     * @return list<string>
     */
    private function resolveCommonEntityLabels(KnowledgeQueryPlan $plan): array
    {
        $query = $plan->normalizedQuery;
        $cropId = trim((string) ($query->cropId ?? ''));
        if ($cropId === '') {
            return [];
        }

        $scientific = trim((string) ($query->scientificName ?? ''));
        $labels = [];
        $idLabel = str_replace('-', ' ', $cropId);
        if ($idLabel !== '') {
            $labels[] = $idLabel;
        }

        foreach (FieldCropTaxonomyCatalog::searchTermsFor($cropId) as $term) {
            $label = trim((string) $term);
            if ($label === '' || ($scientific !== '' && strcasecmp($label, $scientific) === 0)) {
                continue;
            }
            if (preg_match('/\p{Arabic}/u', $label) === 1) {
                continue;
            }
            if (! in_array($label, $labels, true)) {
                $labels[] = $label;
            }
        }

        // Prefer longer common names (e.g. "sweet potato" over short aliases like "batata").
        usort($labels, static fn (string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));

        return array_values($labels);
    }

    private function wantsCultivationProductionVariants(KnowledgeQueryPlan $plan): bool
    {
        $sense = trim((string) ($plan->normalizedQuery->constraints['scientific_sense'] ?? ''));
        // Entity-family / variety inventory: no cultivation-production drift.
        if (in_array($sense, ['varieties', 'plant_family_members'], true)) {
            return false;
        }
        if (in_array($sense, ['plant_growth'], true)) {
            return true;
        }

        return in_array($plan->researchIntent, [
            'cultivation',
            'environmental_requirements',
            'productivity',
        ], true);
    }

    private function mentionsRhizome(KnowledgeQueryPlan $plan): bool
    {
        $query = $plan->normalizedQuery;
        $haystack = mb_strtolower(trim(
            $query->originalQuestion.' '.$query->normalizedQuestion.' '.implode(' ', $plan->topics)
        ));

        foreach (['rhizome', 'rhizomes', 'ريزوم', 'الريزوم', 'جذمور', 'الجذمور'] as $marker) {
            if ($marker !== '' && mb_strpos($haystack, mb_strtolower($marker)) !== false) {
                return true;
            }
        }

        return false;
    }

    private function genusFromScientificName(string $scientificName): ?string
    {
        $parts = preg_split('/\s+/u', trim($scientificName)) ?: [];
        $genus = trim((string) ($parts[0] ?? ''));
        if ($genus === '' || strcasecmp($genus, $scientificName) === 0) {
            return null;
        }

        return $genus;
    }

    /**
     * @param  list<string>  $factors
     * @return list<array{0: string, 1?: string}>
     */
    private function synonymTopicPairs(array $factors, string $sense): array
    {
        $pairs = [];
        foreach (array_slice($factors, 0, 2) as $factor) {
            $synonyms = AgriculturalEntityCatalog::scientificSynonymsForFactor((string) $factor, $sense !== '' ? $sense : null);
            foreach (array_slice($synonyms, 0, 3) as $synonym) {
                $pairs[] = [$synonym];
            }
            if (isset($synonyms[1])) {
                $pairs[] = [$synonyms[0], $synonyms[1]];
            }
        }

        return array_slice($pairs, 0, 4);
    }

    private function resolveEntityTerm(KnowledgeQueryPlan $plan): ?string
    {
        $query = $plan->normalizedQuery;
        if ($query->scientificName !== null && trim($query->scientificName) !== '') {
            return trim($query->scientificName);
        }

        if ($query->cropId !== null && trim($query->cropId) !== '') {
            return str_replace('-', ' ', trim($query->cropId));
        }

        if (is_array($plan->subjectEntity) && ($plan->subjectEntity['type'] ?? '') === 'crop') {
            $value = trim((string) ($plan->subjectEntity['value'] ?? ''));
            if ($value !== '') {
                return str_replace('-', ' ', $value);
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function resolveTopicTerms(KnowledgeQueryPlan $plan): array
    {
        $terms = [];
        $factorTopics = $plan->normalizedQuery->constraints['scientific_topics'] ?? [];
        if (is_array($factorTopics)) {
            foreach ($factorTopics as $topic) {
                $label = trim((string) $topic);
                if ($label !== '' && ! in_array($label, $terms, true)) {
                    $terms[] = $label;
                }
            }
        }

        foreach ($plan->topics as $topic) {
            $label = trim((string) $topic);
            if ($label === '' || $label === $plan->researchIntent) {
                continue;
            }
            if (preg_match('/\p{Arabic}/u', $label) === 1) {
                continue;
            }
            if (! in_array($label, $terms, true)) {
                $terms[] = $label;
            }
        }

        return array_slice($terms, 0, 3);
    }

    private function latinScientificFragment(string $normalizedQuestion): ?string
    {
        $normalizedQuestion = trim($normalizedQuestion);
        if ($normalizedQuestion === '') {
            return null;
        }

        if (preg_match('/\p{Arabic}/u', $normalizedQuestion) === 1) {
            return null;
        }

        return $normalizedQuestion;
    }

    /**
     * @param  list<string|null>  $parts
     */
    private function joinTerms(array $parts): string
    {
        $filtered = [];
        foreach ($parts as $part) {
            if (! is_string($part)) {
                continue;
            }
            $term = trim($part);
            if ($term === '' || in_array($term, $filtered, true)) {
                continue;
            }
            $filtered[] = $term;
        }

        return trim(preg_replace('/\s+/u', ' ', implode(' ', $filtered)) ?? '');
    }
}
