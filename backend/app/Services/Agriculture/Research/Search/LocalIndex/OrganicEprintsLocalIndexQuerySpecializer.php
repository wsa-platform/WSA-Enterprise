<?php

namespace App\Services\Agriculture\Research\Search\LocalIndex;

use App\Services\Agriculture\Research\CanonicalScientificQuestion;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;

/**
 * Organic Eprints (G1-02) LocalIndex retrieval-query specializer.
 *
 * Derives FTS-friendly query versions from the plan/CSQ without mutating CSQ identity.
 * LocalIndex FTS ANDs tokenized terms; keep versions short and organic-scope preserving.
 * Does not execute HTTP. Does not write to the store or index.
 */
final class OrganicEprintsLocalIndexQuerySpecializer
{
    public const SOURCE_KEY = 'organic_eprints';

    public const ADR_ID = 'G1-02';

    /**
     * @return array{
     *   source_key: string,
     *   adr_id: string,
     *   csq_fingerprint: string,
     *   original_question: string,
     *   csq_surface: string,
     *   search_fields: list<string>,
     *   fts_capability: array<string, mixed>,
     *   versions: list<array<string, mixed>>,
     *   primary_query: string,
     *   expansions: list<array<string, mixed>>
     * }
     */
    public function specialize(KnowledgeQueryPlan $plan): array
    {
        $query = $plan->normalizedQuery;
        $original = (string) $query->originalQuestion;
        $csq = $query->canonicalQuestion;
        $csqSurface = $csq instanceof CanonicalScientificQuestion
            ? $this->csqSurface($csq, $query->normalizedQuestion)
            : (string) $query->normalizedQuestion;

        $entity = $this->resolveEntity($plan);
        $organic = $this->organicScopeTerm($original, $csqSurface, $plan);
        $outcome = $this->outcomeTerm($original, $csqSurface, (string) ($query->topic ?? ''));

        $coreTerms = $this->uniqueNonEmpty([$organic, $entity, $outcome]);
        $expansions = [];

        $versions = [];
        $versions[] = $this->version(
            'v1_core_organic_entity_outcome',
            implode(' ', $coreTerms),
            $coreTerms,
            'Primary LocalIndex AND query preserving organic scope, subject, and outcome.',
            'primary',
        );

        if ($entity !== null && $organic !== null) {
            $versions[] = $this->version(
                'v2_organic_entity',
                implode(' ', [$organic, $entity]),
                [$organic, $entity],
                'Controlled refinement if outcome token over-constrains FTS AND. CSQ unchanged.',
                'refinement',
                mayChangeRecallOnly: true,
            );
        }

        $versions[] = $this->version(
            'v3_organic_agriculture',
            'organic agriculture',
            ['organic', 'agriculture'],
            'Broader organic literature recall variant; does not replace CSQ subject.',
            'refinement',
            mayChangeRecallOnly: true,
        );

        $versions[] = $this->version(
            'v_neg_unrelated_token',
            'zzzxoenonmatchtokenqqq999',
            ['zzzxoenonmatchtokenqqq999'],
            'Negative empty-result probe; not a scientific reformulation.',
            'negative_probe',
        );

        $primary = (string) ($versions[0]['query'] ?? 'organic agriculture');

        return [
            'source_key' => self::SOURCE_KEY,
            'adr_id' => self::ADR_ID,
            'csq_fingerprint' => 'csq:'.hash('sha256', $csqSurface !== '' ? $csqSurface : $original),
            'original_question' => $original,
            'csq_surface' => $csqSurface,
            'search_fields' => ['title', 'abstract', 'original_identifier'],
            'fts_capability' => [
                'engine' => 'sqlite_fts5_or_in_memory_postings',
                'boolean_operators_claimed' => false,
                'phrase_search_claimed' => false,
                'wildcard_claimed' => false,
                'fuzzy_claimed' => false,
                'actual_matching' => 'token_and_intersection_after_stopword_filter',
            ],
            'versions' => $versions,
            'primary_query' => $primary,
            'expansions' => $expansions,
        ];
    }

    /** @return list<string> */
    public function retrievalQueries(KnowledgeQueryPlan $plan): array
    {
        $bundle = $this->specialize($plan);
        $out = [];
        foreach ($bundle['versions'] as $version) {
            if (($version['role'] ?? '') === 'negative_probe') {
                continue;
            }
            $q = trim((string) ($version['query'] ?? ''));
            if ($q !== '') {
                $out[] = $q;
            }
        }

        return array_values(array_unique($out));
    }

    private function csqSurface(CanonicalScientificQuestion $csq, string $fallback): string
    {
        foreach (['surfaceForm', 'surface', 'normalizedSurface', 'text'] as $prop) {
            if (isset($csq->{$prop}) && is_string($csq->{$prop}) && trim($csq->{$prop}) !== '') {
                return trim($csq->{$prop});
            }
        }

        return $fallback;
    }

    private function resolveEntity(KnowledgeQueryPlan $plan): ?string
    {
        $query = $plan->normalizedQuery;
        $scientific = trim((string) ($query->scientificName ?? ''));
        if ($scientific !== '' && preg_match('/\p{Arabic}/u', $scientific) !== 1) {
            return $scientific;
        }
        $cropId = trim((string) ($query->cropId ?? ''));
        if ($cropId !== '' && preg_match('/\p{Arabic}/u', $cropId) !== 1) {
            return str_replace('-', ' ', $cropId);
        }
        foreach ([
            $query->crop,
            is_array($query->subject) ? ($query->subject['label'] ?? $query->subject['value'] ?? null) : null,
            is_array($plan->subjectEntity) ? ($plan->subjectEntity['label'] ?? $plan->subjectEntity['value'] ?? null) : null,
        ] as $candidate) {
            $term = trim((string) $candidate);
            if ($term === '' || preg_match('/\p{Arabic}/u', $term) === 1) {
                continue;
            }
            $resolved = \App\Services\Agriculture\Research\AgriculturalEntityCatalog::resolveExactCanonicalCropIdFromLabel($term)
                ?? \App\Services\Agriculture\Research\AgriculturalEntityCatalog::resolveCanonicalCropIdFromLabel($term);
            if (is_string($resolved) && $resolved !== '') {
                return str_replace('-', ' ', $resolved);
            }

            return $term;
        }

        return $scientific !== '' ? $scientific : null;
    }

    private function organicScopeTerm(string $original, string $csqSurface, KnowledgeQueryPlan $plan): string
    {
        $hay = mb_strtolower($original.' '.$csqSurface.' '.(string) ($plan->researchIntent ?? ''));
        foreach ([
            'agroecology' => 'agroecology',
            'biodynamic' => 'biodynamic',
            'regenerative' => 'regenerative',
            'organic farming' => 'organic farming',
            'organic agriculture' => 'organic agriculture',
            'organic production' => 'organic production',
            'organic' => 'organic',
            'عضوي' => 'organic',
            'عضوية' => 'organic',
            'الزراعة العضوية' => 'organic farming',
            'organik' => 'organic',
            'biologique' => 'organic',
        ] as $needle => $term) {
            if (str_contains($hay, $needle)) {
                return $term;
            }
        }

        return 'organic';
    }

    private function outcomeTerm(string $original, string $csqSurface, string $topic): ?string
    {
        $hay = mb_strtolower($original.' '.$csqSurface.' '.$topic);
        foreach (['yield', 'productivity', 'production', 'soil', 'nutrient', 'quality'] as $token) {
            if (str_contains($hay, $token)) {
                return $token;
            }
        }

        return null;
    }

    /**
     * @param  list<?string>  $terms
     * @return list<string>
     */
    private function uniqueNonEmpty(array $terms): array
    {
        $out = [];
        $seen = [];
        foreach ($terms as $term) {
            $t = trim((string) $term);
            if ($t === '') {
                continue;
            }
            $key = mb_strtolower($t);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = $t;
        }

        return $out;
    }

    /**
     * @param  list<string>  $terms
     * @return array<string, mixed>
     */
    private function version(
        string $id,
        string $query,
        array $terms,
        string $reason,
        string $role,
        bool $mayChangeRecallOnly = false,
    ): array {
        return [
            'query_version' => $id,
            'query' => $query,
            'terms' => $terms,
            'reason' => $reason,
            'role' => $role,
            'csq_mutated' => false,
            'may_change_recall_only' => $mayChangeRecallOnly,
        ];
    }
}
