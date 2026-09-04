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

        $variants = [];

        if ($entity !== null) {
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
            }

            $primaryTopic = $topics[0] ?? ($senseTerms[0] ?? null);
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
        } else {
            $latinQuestion = $this->latinScientificFragment($query->normalizedQuestion);
            $variants[] = $this->joinTerms([...$topics, ...array_slice($senseTerms, 0, 2), ...array_slice($intentTerms, 0, 2)]);
            if ($latinQuestion !== null) {
                $variants[] = $this->joinTerms([$latinQuestion, 'agriculture']);
            }
            $variants[] = $this->joinTerms([$plan->researchIntent, $plan->agriculturalDomain, 'agriculture']);
        }

        $unique = [];
        foreach ($variants as $variant) {
            $trimmed = trim($variant);
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

        usort($labels, static fn (string $a, string $b): int => mb_strlen($a) <=> mb_strlen($b));

        return array_values($labels);
    }

    private function wantsCultivationProductionVariants(KnowledgeQueryPlan $plan): bool
    {
        $sense = trim((string) ($plan->normalizedQuery->constraints['scientific_sense'] ?? ''));
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
