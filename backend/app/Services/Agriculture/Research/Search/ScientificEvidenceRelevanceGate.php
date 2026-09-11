<?php

namespace App\Services\Agriculture\Research\Search;

use App\Services\Agriculture\FieldCropTaxonomyCatalog;
use App\Services\Agriculture\Research\AgriculturalEntityCatalog;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;

/**
 * Relevance gate: crop/entity + topic/sense + agricultural context before synthesis.
 *
 * DOI alone is never sufficient for crop- or topic-constrained questions.
 * Bare entity + weak topic keyword is insufficient when crop+topic are required.
 */
class ScientificEvidenceRelevanceGate
{
    private const MIN_SCORE_CROP_TOPIC = 70.0;

    private const SPECIES_EXACT = 'exact_species';

    private const SPECIES_GENUS_ONLY = 'genus_only';

    private const SPECIES_DIFFERENT = 'different_species';

    private const SPECIES_ENTITY_LESS = 'entity_less';

    /**
     * @return array{
     *     relevant: bool,
     *     score: float,
     *     entity_matched: bool,
     *     topic_matched: bool,
     *     sense_matched: bool,
     *     context_matched: bool,
     *     context_adequate: bool,
     *     requires_entity: bool,
     *     requires_topic: bool,
     *     species_relation: string,
     *     exact_species_matched: bool,
     *     rejection_reasons: list<string>,
     *     factors: array<string, mixed>
     * }
     */
    public function assess(
        KnowledgeQueryPlan $plan,
        string $title,
        ?string $abstract = null,
        ?string $doi = null,
        ?string $extraText = null,
    ): array {
        $primaryHaystack = $this->buildHaystack([$title, $abstract]);
        $fullHaystack = $this->buildHaystack([$title, $abstract, $extraText]);
        // Prefer title+abstract for sense/context; fall back to keywords/concepts only as supplement.
        $senseHaystack = $primaryHaystack !== '' ? $primaryHaystack : $fullHaystack;

        $requiresEntity = $this->requiresEntity($plan);
        $requiresTopic = $this->requiresTopic($plan);
        $entityMatched = $this->matchesEntity($plan, $fullHaystack !== '' ? $fullHaystack : $senseHaystack);
        $topicStrength = $this->topicMatchStrength($plan, $senseHaystack);
        if ($topicStrength === 'none' && $extraText !== null && trim($extraText) !== '') {
            $topicStrength = $this->topicMatchStrength($plan, $fullHaystack);
        }
        $topicMatched = $topicStrength !== 'none';
        $senseMatched = $topicStrength === 'strong';
        $contextMatched = $this->matchesAgriculturalContext($senseHaystack)
            || ($extraText !== null && $this->matchesAgriculturalContext(mb_strtolower(trim($extraText))));
        $domainHaystack = $senseHaystack !== '' ? $senseHaystack : $fullHaystack;
        $domainAssessment = $this->assessDomainRelevance(
            $plan,
            $domainHaystack,
            $entityMatched,
            $topicMatched,
            $contextMatched,
            $senseMatched,
        );
        $offDomain = $domainAssessment['hard_reject'];
        $blockedSenses = $this->blockedNegativeSenses($plan, $domainHaystack);
        $crossDomainBlocked = $this->blockedCrossDomainProductionSenses($plan, $domainHaystack);

        $contextAdequate = ! $requiresEntity || ! $requiresTopic
            || $contextMatched
            || $senseMatched
            || $this->queryRequestsPostHarvestSense($plan);

        $rejectionReasons = [];
        $score = 0.0;

        if ($entityMatched) {
            $score += 50.0;
        }
        if ($topicMatched) {
            $score += $senseMatched ? 30.0 : 20.0;
        }
        if ($contextMatched) {
            $score += 15.0;
        }
        if ($this->matchesIntentSense($plan, $senseHaystack)) {
            $score += 10.0;
        }
        if ($doi !== null && trim($doi) !== '') {
            $score += 1.0;
        }

        if ($blockedSenses !== []) {
            $rejectionReasons[] = 'negative_context_sense';
            $score -= 45.0 * count($blockedSenses);
        }

        if ($crossDomainBlocked !== []) {
            $rejectionReasons[] = 'cross_domain_production_sense';
            $score -= 50.0;
        }

        if ($domainAssessment['penalty'] > 0.0) {
            $score -= $domainAssessment['penalty'];
        }

        if ($offDomain) {
            $rejectionReasons[] = 'irrelevant_domain';
            $score = min($score, 1.0);
        }

        if ($requiresEntity && ! $entityMatched) {
            $rejectionReasons[] = 'missing_crop_or_entity';
        }

        if ($requiresTopic && ! $topicMatched) {
            $rejectionReasons[] = 'missing_topic_or_factor';
        }

        if ($requiresEntity && $requiresTopic && $entityMatched && $topicMatched && ! $contextAdequate) {
            $rejectionReasons[] = 'insufficient_topic_sense';
        }

        if ($requiresEntity && $requiresTopic && $score < self::MIN_SCORE_CROP_TOPIC) {
            $rejectionReasons[] = 'below_minimum_relevance_score';
        }

        $hardReject = ($requiresEntity && ! $entityMatched)
            || ($requiresTopic && ! $topicMatched)
            || $offDomain
            || $blockedSenses !== []
            || $crossDomainBlocked !== []
            || ($requiresEntity && $requiresTopic && ! $contextAdequate)
            || ($requiresEntity && $requiresTopic && $score < self::MIN_SCORE_CROP_TOPIC);

        $factors = [
            'doi_alone_insufficient' => true,
            'crop_id' => $plan->normalizedQuery->cropId,
            'scientific_name' => $plan->normalizedQuery->scientificName,
            'scientific_topics' => $plan->normalizedQuery->constraints['scientific_topics'] ?? [],
            'topic_strength' => $topicStrength,
            'sense_matched' => $senseMatched,
            'context_matched' => $contextMatched,
            'context_adequate' => $contextAdequate,
            'blocked_senses' => $blockedSenses,
            'cross_domain_blocked' => $crossDomainBlocked,
            'research_intent' => $plan->researchIntent,
            'domain_class' => $domainAssessment['domain_class'],
            'domain_penalty' => $domainAssessment['penalty'],
            'domain_hard_reject' => $offDomain,
        ];

        $species = $this->classifySpeciesRelation(
            $plan,
            $fullHaystack !== '' ? $fullHaystack : $senseHaystack,
            trim(implode(' ', array_filter(
                [$title, $abstract, $extraText],
                static fn ($part): bool => is_string($part) && trim($part) !== '',
            ))),
            $entityMatched,
        );
        $factors['species_relation'] = $species['relation'];
        $factors['exact_species_matched'] = $species['exact'];

        if ($hardReject) {
            return [
                'relevant' => false,
                'score' => max(0.0, $score),
                'entity_matched' => $entityMatched,
                'topic_matched' => $topicMatched,
                'sense_matched' => $senseMatched,
                'context_matched' => $contextMatched,
                'context_adequate' => $contextAdequate,
                'requires_entity' => $requiresEntity,
                'requires_topic' => $requiresTopic,
                'species_relation' => $species['relation'],
                'exact_species_matched' => $species['exact'],
                'rejection_reasons' => array_values(array_unique($rejectionReasons)),
                'factors' => $factors,
            ];
        }

        return [
            'relevant' => true,
            'score' => $score,
            'entity_matched' => $entityMatched,
            'topic_matched' => $topicMatched,
            'sense_matched' => $senseMatched,
            'context_matched' => $contextMatched,
            'context_adequate' => $contextAdequate,
            'requires_entity' => $requiresEntity,
            'requires_topic' => $requiresTopic,
            'species_relation' => $species['relation'],
            'exact_species_matched' => $species['exact'],
            'rejection_reasons' => [],
            'factors' => $factors,
        ];
    }

    public function isRelevant(
        KnowledgeQueryPlan $plan,
        string $title,
        ?string $abstract = null,
        ?string $doi = null,
        ?string $extraText = null,
    ): bool {
        return $this->assess($plan, $title, $abstract, $doi, $extraText)['relevant'];
    }

    /**
     * @param  list<string|null>  $parts
     */
    private function buildHaystack(array $parts): string
    {
        return mb_strtolower(trim(implode(' ', array_filter($parts, static fn ($part): bool => is_string($part) && trim($part) !== ''))));
    }

    private function requiresEntity(KnowledgeQueryPlan $plan): bool
    {
        $subjectType = is_array($plan->subjectEntity) ? ($plan->subjectEntity['type'] ?? null) : null;

        return $plan->normalizedQuery->cropId !== null
            || $plan->normalizedQuery->scientificName !== null
            || $subjectType === 'crop'
            || $subjectType === 'plant_family';
    }

    private function requiresTopic(KnowledgeQueryPlan $plan): bool
    {
        $factors = $plan->normalizedQuery->constraints['scientific_factors'] ?? [];
        if (is_array($factors) && $factors !== []) {
            return true;
        }

        $sense = trim((string) ($plan->normalizedQuery->constraints['scientific_sense'] ?? ''));
        if ($sense === 'plant_family_members') {
            return true;
        }

        $topics = $plan->normalizedQuery->constraints['scientific_topics'] ?? [];

        return is_array($topics) && $topics !== []
            && ((is_array($plan->subjectEntity) ? ($plan->subjectEntity['type'] ?? null) : null) === 'plant_family');
    }

    private function matchesEntity(KnowledgeQueryPlan $plan, string $haystack): bool
    {
        if ($haystack === '') {
            return false;
        }

        $needles = [];
        $query = $plan->normalizedQuery;

        if ($query->scientificName !== null && $query->scientificName !== '') {
            $needles[] = $query->scientificName;
            $genus = trim(explode(' ', $query->scientificName)[0] ?? '');
            if ($genus !== '') {
                $needles[] = $genus;
            }
        }

        if ($query->cropId !== null) {
            $needles = array_merge($needles, AgriculturalEntityCatalog::recognitionLabelsForCrop($query->cropId));
            $needles = array_merge($needles, FieldCropTaxonomyCatalog::searchTermsFor($query->cropId));
        }

        if (is_array($plan->subjectEntity) && ($plan->subjectEntity['type'] ?? '') === 'crop') {
            $needles[] = (string) ($plan->subjectEntity['value'] ?? '');
            $needles[] = (string) ($plan->subjectEntity['label'] ?? '');
        }

        if (is_array($plan->subjectEntity) && ($plan->subjectEntity['type'] ?? '') === 'plant_family') {
            $family = trim((string) ($plan->subjectEntity['value'] ?? $plan->subjectEntity['label'] ?? ''));
            if ($family !== '') {
                $needles = array_merge(
                    $needles,
                    AgriculturalEntityCatalog::recognitionLabelsForBotanicalFamily($family),
                );
            }
        }

        foreach (array_unique(array_filter($needles)) as $needle) {
            if (AgriculturalEntityCatalog::containsTerm($haystack, mb_strtolower(trim((string) $needle)))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Broad entity_matched may include genus relevance. Species identity is separate.
     *
     * @return array{relation: string, exact: bool}
     */
    private function classifySpeciesRelation(
        KnowledgeQueryPlan $plan,
        string $haystack,
        string $originalHaystack,
        bool $entityMatched,
    ): array {
        if (! $this->requiresEntity($plan)) {
            return ['relation' => self::SPECIES_ENTITY_LESS, 'exact' => false];
        }

        $scientific = mb_strtolower(trim((string) ($plan->normalizedQuery->scientificName ?? '')));
        if (preg_match('/^([a-z]{3,})\s+([a-z]{2,})$/u', $scientific, $target) !== 1) {
            return [
                'relation' => $entityMatched ? self::SPECIES_EXACT : self::SPECIES_ENTITY_LESS,
                'exact' => $entityMatched,
            ];
        }

        $genus = $target[1];
        $epithet = $target[2];
        $source = $originalHaystack !== '' ? $originalHaystack : $haystack;

        if ($this->mentionsHybridOfTarget($source, $genus, $epithet)) {
            return ['relation' => self::SPECIES_GENUS_ONLY, 'exact' => false];
        }

        $blob = mb_strtolower($source);
        if ($this->mentionsExactScientificName($blob, $genus, $epithet)) {
            return ['relation' => self::SPECIES_EXACT, 'exact' => true];
        }

        foreach ($this->sameGenusEpithets($source, $genus) as $foundEpithet) {
            if (! in_array($foundEpithet, [$epithet, 'spp', 'sp', 'ssp', 'var'], true)) {
                return ['relation' => self::SPECIES_GENUS_ONLY, 'exact' => false];
            }
            if (in_array($foundEpithet, ['spp', 'sp'], true)) {
                return ['relation' => self::SPECIES_GENUS_ONLY, 'exact' => false];
            }
        }

        if ($this->mentionsForeignBinomial($source, $genus, $plan)) {
            return ['relation' => self::SPECIES_DIFFERENT, 'exact' => false];
        }

        if ($this->mentionsExactCommonName($blob, $plan, $genus, $epithet)) {
            return ['relation' => self::SPECIES_EXACT, 'exact' => true];
        }

        if (AgriculturalEntityCatalog::containsTerm($haystack, $genus)) {
            return ['relation' => self::SPECIES_GENUS_ONLY, 'exact' => false];
        }

        return [
            'relation' => $entityMatched ? self::SPECIES_GENUS_ONLY : self::SPECIES_ENTITY_LESS,
            'exact' => false,
        ];
    }

    private function mentionsExactScientificName(string $blob, string $genus, string $epithet): bool
    {
        return AgriculturalEntityCatalog::containsTerm($blob, $genus.' '.$epithet)
            || preg_match('/\b'.preg_quote($genus[0], '/').'\.\s*'.preg_quote($epithet, '/').'\b/u', $blob) === 1;
    }

    private function mentionsExactCommonName(
        string $blob,
        KnowledgeQueryPlan $plan,
        string $genus,
        string $epithet,
    ): bool {
        $cropId = $plan->normalizedQuery->cropId;
        if ($cropId === null || $cropId === '') {
            return false;
        }

        $labels = array_merge(
            AgriculturalEntityCatalog::recognitionLabelsForCrop($cropId),
            FieldCropTaxonomyCatalog::searchTermsFor($cropId),
        );
        foreach ($labels as $label) {
            $normalized = mb_strtolower(trim((string) $label));
            if ($normalized === '' || $normalized === $genus || $normalized === $genus.' '.$epithet) {
                continue;
            }
            if (AgriculturalEntityCatalog::containsTerm($blob, $normalized)) {
                return true;
            }
        }

        return false;
    }

    private function mentionsHybridOfTarget(string $text, string $genus, string $epithet): bool
    {
        $core = preg_quote($genus, '/').'\s+'.preg_quote($epithet, '/');
        $blob = mb_strtolower($text);

        return preg_match('/\b'.$core.'\s*[×x]\s+/u', $blob) === 1
            || preg_match('/\b[×x]\s+'.$core.'\b/u', $blob) === 1;
    }

    /**
     * @return list<string>
     */
    private function sameGenusEpithets(string $text, string $genus): array
    {
        $blob = mb_strtolower($text);
        $epithets = [];
        $patterns = [
            '/\b'.preg_quote($genus, '/').'\s+([a-z]+|spp\.?|sp\.?)\b/u',
            '/\b'.preg_quote($genus[0], '/').'\.\s*([a-z]+)\b/u',
        ];
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $blob, $matches) === false) {
                continue;
            }
            foreach ($matches[1] as $found) {
                $epithets[] = rtrim(mb_strtolower((string) $found), '.');
            }
        }

        return array_values(array_unique(array_filter($epithets)));
    }

    private function mentionsForeignBinomial(string $original, string $targetGenus, KnowledgeQueryPlan $plan): bool
    {
        if (preg_match_all('/\b([A-Z][a-z]{3,})\s+([a-z]{4,})\b/u', $original, $matches, PREG_SET_ORDER) === false) {
            return false;
        }

        $targetLabels = [];
        $cropId = $plan->normalizedQuery->cropId;
        if ($cropId !== null && $cropId !== '') {
            foreach (array_merge(
                AgriculturalEntityCatalog::recognitionLabelsForCrop($cropId),
                FieldCropTaxonomyCatalog::searchTermsFor($cropId),
            ) as $label) {
                $targetLabels[mb_strtolower(trim((string) $label))] = true;
            }
        }

        $skip = [
            'temperature' => true, 'germination' => true, 'characteristics' => true,
            'requirements' => true, 'conditions' => true, 'evidence' => true,
            'optimal' => true, 'effect' => true, 'effects' => true, 'study' => true,
            'growth' => true, 'light' => true, 'seed' => true, 'seeds' => true,
            'plant' => true, 'plants' => true, 'range' => true, 'under' => true,
            'controlled' => true, 'measured' => true, 'availability' => true,
            'determination' => true, 'cardinal' => true, 'japanese' => true,
            'sweet' => true, 'white' => true, 'common' => true, 'wild' => true,
        ];
        foreach ($matches as $match) {
            $genus = mb_strtolower($match[1]);
            $epithet = mb_strtolower($match[2]);
            if ($genus === $targetGenus || isset($skip[$genus]) || isset($skip[$epithet])) {
                continue;
            }
            if (isset($targetLabels[$genus.' '.$epithet])) {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * @return 'none'|'weak'|'strong'
     */
    private function topicMatchStrength(KnowledgeQueryPlan $plan, string $haystack): string
    {
        if ($haystack === '') {
            return 'none';
        }

        $factors = $plan->normalizedQuery->constraints['scientific_factors'] ?? [];
        if (! is_array($factors) || $factors === []) {
            $best = 'none';
            $sense = trim((string) ($plan->normalizedQuery->constraints['scientific_sense'] ?? ''));
            if ($sense !== '') {
                foreach (AgriculturalEntityCatalog::senseQueryTerms($sense) as $term) {
                    if (in_array(mb_strtolower(trim($term)), ['agriculture', 'farming'], true)) {
                        continue;
                    }
                    if (AgriculturalEntityCatalog::containsTerm($haystack, mb_strtolower(trim($term)))) {
                        return 'strong';
                    }
                }
            }
            $topics = $plan->normalizedQuery->constraints['scientific_topics'] ?? [];
            if (is_array($topics)) {
                foreach ($topics as $topic) {
                    $normalized = mb_strtolower(trim((string) $topic));
                    if ($normalized === '' || in_array($normalized, ['agriculture', 'farming'], true)) {
                        continue;
                    }
                    // Skip Latin family names here — those are entity needles, not topics.
                    if ((is_array($plan->subjectEntity) ? ($plan->subjectEntity['type'] ?? '') : '') === 'plant_family'
                        && strcasecmp($normalized, (string) ($plan->subjectEntity['value'] ?? '')) === 0) {
                        continue;
                    }
                    if (AgriculturalEntityCatalog::containsTerm($haystack, $normalized)) {
                        $best = 'strong';
                        break;
                    }
                }
            }
            if ($best !== 'none') {
                return $best;
            }
            foreach (AgriculturalEntityCatalog::englishTermsForIntent($plan->researchIntent) as $term) {
                if (in_array($term, ['agriculture', 'farming'], true)) {
                    continue;
                }
                if (AgriculturalEntityCatalog::containsTerm($haystack, mb_strtolower(trim($term)))) {
                    return 'strong';
                }
            }

            return 'none';
        }

        $best = 'none';
        foreach ($factors as $factor) {
            $strongSignals = AgriculturalEntityCatalog::strongTopicFactorSignals()[$factor] ?? [];
            foreach ($strongSignals as $signal) {
                if (AgriculturalEntityCatalog::containsTerm($haystack, mb_strtolower(trim($signal)))) {
                    return 'strong';
                }
            }

            $signals = AgriculturalEntityCatalog::topicFactorSignals()[$factor] ?? [];
            foreach ($signals as $signal) {
                if (AgriculturalEntityCatalog::containsTerm($haystack, mb_strtolower(trim((string) $signal)))) {
                    $best = 'weak';
                    break;
                }
            }
            $english = AgriculturalEntityCatalog::topicFactorEnglishLabels()[$factor] ?? null;
            if ($english !== null && AgriculturalEntityCatalog::containsTerm($haystack, mb_strtolower($english))) {
                $best = 'weak';
            }
        }

        return $best;
    }

    private function matchesAgriculturalContext(string $haystack): bool
    {
        if ($haystack === '') {
            return false;
        }

        foreach (AgriculturalEntityCatalog::agriculturalContextSignals() as $signal) {
            if (AgriculturalEntityCatalog::containsTerm($haystack, mb_strtolower(trim($signal)))) {
                return true;
            }
        }

        return false;
    }

    private function matchesIntentSense(KnowledgeQueryPlan $plan, string $haystack): bool
    {
        if ($haystack === '') {
            return false;
        }

        foreach (AgriculturalEntityCatalog::englishTermsForIntent($plan->researchIntent) as $term) {
            if (in_array($term, ['agriculture', 'farming'], true)) {
                continue;
            }
            if (AgriculturalEntityCatalog::containsTerm($haystack, mb_strtolower(trim($term)))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function blockedNegativeSenses(KnowledgeQueryPlan $plan, string $haystack): array
    {
        if ($haystack === '') {
            return [];
        }

        $blocked = [];
        foreach (AgriculturalEntityCatalog::negativeSenseMarkers() as $sense => $markers) {
            if ($this->queryAllowsSense($plan, $sense)) {
                continue;
            }

            foreach ($markers as $marker) {
                if (AgriculturalEntityCatalog::containsTerm($haystack, mb_strtolower(trim($marker)))) {
                    $blocked[] = $sense;
                    break;
                }
            }
        }

        return array_values(array_unique($blocked));
    }

    private function queryAllowsSense(KnowledgeQueryPlan $plan, string $sense): bool
    {
        $explicit = $this->explicitPostHarvestSensesFromPlan($plan);
        if ($explicit !== []) {
            return in_array($sense, $explicit, true);
        }

        if (in_array($plan->researchIntent, AgriculturalEntityCatalog::intentsAllowingNegativeSense($sense), true)) {
            return true;
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function explicitPostHarvestSensesFromPlan(KnowledgeQueryPlan $plan): array
    {
        $found = [];
        $factors = $plan->normalizedQuery->constraints['scientific_factors'] ?? [];
        foreach (['drying', 'storage', 'extraction', 'processing'] as $sense) {
            if (is_array($factors) && in_array($sense, $factors, true)) {
                $found[] = $sense;
            }
        }

        $question = mb_strtolower(trim(implode(' ', array_filter([
            $plan->normalizedQuery->normalizedQuestion,
            $plan->normalizedQuery->originalQuestion,
        ]))));

        foreach (AgriculturalEntityCatalog::negativeSenseMarkers() as $sense => $markers) {
            foreach ($markers as $marker) {
                if (AgriculturalEntityCatalog::containsTerm($question, mb_strtolower(trim($marker)))) {
                    $found[] = $sense;
                    break;
                }
            }
        }

        return array_values(array_unique($found));
    }

    private function queryRequestsPostHarvestSense(KnowledgeQueryPlan $plan): bool
    {
        foreach ($this->explicitPostHarvestSensesFromPlan($plan) as $sense) {
            if (in_array($sense, ['drying', 'storage'], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Plant-growth / germination / irrigation questions must not accept livestock/poultry
     * feed trials that merely mention the crop name + temperature.
     *
     * @return list<string>
     */
    private function blockedCrossDomainProductionSenses(KnowledgeQueryPlan $plan, string $haystack): array
    {
        if ($haystack === '') {
            return [];
        }

        if (in_array($plan->researchIntent, [
            'animal_production',
            'poultry_production',
            'feed',
            'aquaculture',
            'beekeeping',
        ], true)) {
            return [];
        }

        $sense = trim((string) ($plan->normalizedQuery->constraints['scientific_sense'] ?? ''));
        $plantSenses = [
            'plant_growth',
            'seed_germination',
            'salinity_physiology',
            'crop_water_requirement',
            'plant_nutrition',
        ];
        if ($sense !== '' && ! in_array($sense, $plantSenses, true)) {
            return [];
        }
        if ($sense === '' && ! $this->requiresEntity($plan)) {
            return [];
        }

        $markers = [
            'broiler' => ['broiler', 'broilers'],
            'poultry' => ['poultry', 'chicken', 'chickens', 'hen ', ' hens'],
            'livestock_feed' => ['livestock feed', 'animal feed', 'cattle diet', 'dairy cow', 'pig diet', 'swine diet'],
        ];
        $blocked = [];
        foreach ($markers as $label => $needles) {
            foreach ($needles as $needle) {
                if (AgriculturalEntityCatalog::containsTerm($haystack, mb_strtolower(trim($needle)))) {
                    $blocked[] = $label;
                    break;
                }
            }
        }

        return array_values(array_unique($blocked));
    }

    /**
     * Context-aware domain classification.
     * Ambiguous words (economics, psychology, …) alone never hard-reject;
     * strong unrelated markers still do unless an agricultural rescue applies.
     * Secondary domain mentions with ag+entity+topic get a score penalty only.
     *
     * @return array{
     *     hard_reject: bool,
     *     penalty: float,
     *     domain_class: string|null
     * }
     */
    private function assessDomainRelevance(
        KnowledgeQueryPlan $plan,
        string $haystack,
        bool $entityMatched,
        bool $topicMatched,
        bool $contextMatched,
        bool $senseMatched,
    ): array {
        if ($haystack === '') {
            return ['hard_reject' => false, 'penalty' => 0.0, 'domain_class' => null];
        }

        if ($plan->researchIntent === 'agricultural_economics') {
            return ['hard_reject' => false, 'penalty' => 0.0, 'domain_class' => 'agricultural_economics'];
        }

        $agAnchored = $this->hasAgriculturalDomainAnchor($haystack, $entityMatched, $topicMatched, $contextMatched, $senseMatched);
        $penalty = 0.0;
        $domainClass = null;
        $hardReject = false;

        foreach (AgriculturalEntityCatalog::strongIrrelevantDomainMarkers() as $family => $markers) {
            if (! $this->haystackContainsAny($haystack, $markers)) {
                continue;
            }

            // Clinical/finance markers stay hard-reject unless explicitly agricultural.
            if ($this->hasAgriculturalDomainRescue($haystack, $family)) {
                $domainClass = 'agricultural_'.$family.'_secondary';
                $penalty = max($penalty, 12.0);

                continue;
            }

            $hardReject = true;
            $domainClass = 'unrelated_'.$family;
        }

        if ($hardReject) {
            return ['hard_reject' => true, 'penalty' => 0.0, 'domain_class' => $domainClass];
        }

        foreach (AgriculturalEntityCatalog::ambiguousDomainMarkers() as $family => $markers) {
            if (! $this->haystackContainsAny($haystack, $markers)) {
                continue;
            }

            if ($this->hasAgriculturalDomainRescue($haystack, $family)) {
                $domainClass = 'agricultural_'.$family;
                // Explicit ag economics/psych is on-domain for sciences; light penalty only
                // when the question is a crop-growth/topic question (secondary branch).
                if ($this->requiresEntity($plan) && $this->requiresTopic($plan) && $family === 'economics') {
                    $penalty = max($penalty, 8.0);
                }

                continue;
            }

            if ($agAnchored) {
                // Incidental domain word inside an otherwise on-topic agricultural paper.
                $domainClass = $family.'_incidental';
                $penalty = max($penalty, 12.0);

                continue;
            }

            $hardReject = true;
            $domainClass = 'unrelated_'.$family;
            break;
        }

        if (! $hardReject && $domainClass === null && $this->matchesAgriculturalScienceBranch($haystack)) {
            $domainClass = 'agricultural_sciences';
        }

        return [
            'hard_reject' => $hardReject,
            'penalty' => $hardReject ? 0.0 : $penalty,
            'domain_class' => $domainClass,
        ];
    }

    private function hasAgriculturalDomainAnchor(
        string $haystack,
        bool $entityMatched,
        bool $topicMatched,
        bool $contextMatched,
        bool $senseMatched,
    ): bool {
        if ($this->matchesAgriculturalScienceBranch($haystack)) {
            return true;
        }

        // Crop/topic papers with clear agricultural growth context: domain words are secondary.
        return $entityMatched && $topicMatched && ($contextMatched || $senseMatched);
    }

    private function hasAgriculturalDomainRescue(string $haystack, string $family): bool
    {
        $phrases = AgriculturalEntityCatalog::agriculturalDomainRescuePhrases()[$family] ?? [];

        return $this->haystackContainsAny($haystack, $phrases);
    }

    private function matchesAgriculturalScienceBranch(string $haystack): bool
    {
        foreach (AgriculturalEntityCatalog::agriculturalScienceBranchSignals() as $signal) {
            if (AgriculturalEntityCatalog::containsTerm($haystack, mb_strtolower(trim($signal)))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $needles
     */
    private function haystackContainsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (AgriculturalEntityCatalog::containsTerm($haystack, mb_strtolower(trim($needle)))) {
                return true;
            }
        }

        return false;
    }
}
