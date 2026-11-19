<?php

namespace App\Services\Agriculture\Research\Search\LocalIndex;

use App\Services\Agriculture\Research\CanonicalScientificQuestion;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;

/**
 * AGRIS (G1-01) LocalIndex retrieval-query specializer.
 *
 * Derives FTS-friendly query versions from the plan/CSQ without replacing CSQ identity.
 * LocalIndex FTS (SqliteFtsScientificIndex / LocalScientificIndex) ANDs tokenized terms;
 * over-long scholarly phrases therefore reduce recall. Refinement is recorded, never silent.
 *
 * Does not execute HTTP. Does not write to the store or index.
 */
final class AgrisLocalIndexQuerySpecializer
{
    public const SOURCE_KEY = 'agris';

    public const ADR_ID = 'G1-01';

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
        $topic = trim((string) ($query->topic ?? ''));
        $sense = trim((string) ($query->constraints['scientific_sense'] ?? $plan->researchIntent ?? ''));
        $factors = is_array($query->constraints['scientific_factors'] ?? null)
            ? array_values(array_filter(array_map('strval', $query->constraints['scientific_factors'])))
            : [];

        $coreTerms = $this->uniqueNonEmpty([
            $entity,
            $this->factorTerm($sense, $factors),
            $this->outcomeTerm($topic, $sense, $original, $csqSurface),
        ]);

        $expansions = [];
        $synonym = $this->equivalentSynonym($entity);
        if ($synonym !== null) {
            $expansions[] = [
                'original_term' => $entity,
                'added_term' => $synonym,
                'relation' => 'scientific_or_common_name_equivalent',
                'reason' => 'AGRIS literature often indexes either form',
                'may_widen_scope' => false,
            ];
        }

        $versions = [];
        $versions[] = $this->version(
            'v1_core_entity_factor_outcome',
            implode(' ', $coreTerms),
            $coreTerms,
            'Primary LocalIndex AND query preserving subject, intervention, and outcome.',
            'primary',
        );

        $hay = mb_strtolower($original.' '.$csqSurface);
        if (str_contains($hay, 'deficit') && $entity !== null) {
            $deficitTerms = $this->uniqueNonEmpty([
                $entity,
                'deficit',
                $this->factorTerm($sense, $factors) ?? 'irrigation',
            ]);
            $versions[] = $this->version(
                'v1b_deficit_qualifier',
                implode(' ', $deficitTerms),
                $deficitTerms,
                'Treatment qualifier "deficit" as a separate retrieval version; not merged into CSQ.',
                'refinement',
                mayChangeRecallOnly: true,
            );
        }

        if ($synonym !== null && $entity !== null) {
            $synTerms = $this->uniqueNonEmpty([
                $synonym,
                $this->factorTerm($sense, $factors),
                $this->outcomeTerm($topic, $sense, $original, $csqSurface),
            ]);
            $versions[] = $this->version(
                'v2_synonym_entity_factor_outcome',
                implode(' ', $synTerms),
                $synTerms,
                'Equivalent name form only; does not broaden crop scope.',
                'refinement',
            );
        }

        // Controlled relaxation: drop outcome token if FTS AND is too strict — keep entity+factor.
        $relaxed = $this->uniqueNonEmpty([
            $entity ?? $synonym,
            $this->factorTerm($sense, $factors),
        ]);
        if (count($relaxed) >= 2 && implode(' ', $relaxed) !== ($versions[0]['query'] ?? '')) {
            $versions[] = $this->version(
                'v3_entity_factor_only',
                implode(' ', $relaxed),
                $relaxed,
                'Controlled refinement if outcome token over-constrains FTS AND matching. CSQ unchanged.',
                'refinement',
                mayChangeRecallOnly: true,
            );
        }

        // Negative control query (must not be used as CSQ): documents insufficiency path.
        $versions[] = $this->version(
            'v_neg_unrelated_token',
            'zzzxagrisnonmatchtokenqqq999',
            ['zzzxagrisnonmatchtokenqqq999'],
            'Negative empty-result probe; not a scientific reformulation of the question.',
            'negative_probe',
        );

        $primary = (string) ($versions[0]['query'] ?? 'agriculture');

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
                'term_tokenizer' => LocalScientificIndex::class.'::terms',
            ],
            'versions' => $versions,
            'primary_query' => $primary,
            'expansions' => $expansions,
        ];
    }

    /**
     * Retrieval query strings only (excludes negative probes).
     *
     * @return list<string>
     */
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
        foreach (['canonicalText', 'surfaceForm', 'normalizedText'] as $method) {
            if (method_exists($csq, $method)) {
                $value = trim((string) $csq->{$method}());
                if ($value !== '') {
                    return $value;
                }
            }
        }
        if (method_exists($csq, 'toArray')) {
            $arr = $csq->toArray();
            foreach (['canonical_text', 'surface', 'normalized_question'] as $key) {
                if (! empty($arr[$key]) && is_string($arr[$key])) {
                    return trim($arr[$key]);
                }
            }
        }

        return trim($fallback);
    }

    private function resolveEntity(KnowledgeQueryPlan $plan): ?string
    {
        $query = $plan->normalizedQuery;
        foreach ([
            $query->scientificName,
            $query->crop,
            is_array($query->subject) ? ($query->subject['label'] ?? $query->subject['value'] ?? null) : null,
            is_array($plan->subjectEntity) ? ($plan->subjectEntity['label'] ?? $plan->subjectEntity['value'] ?? null) : null,
        ] as $candidate) {
            $term = trim((string) $candidate);
            if ($term !== '') {
                return $term;
            }
        }

        return null;
    }

    /**
     * Prefer a single FTS-safe factor token. Multi-word factors are tokenized into AND terms
     * by LocalIndex; packing "deficit irrigation" into the primary query over-constrains recall.
     *
     * @param  list<string>  $factors
     */
    private function factorTerm(string $sense, array $factors): ?string
    {
        $sense = mb_strtolower($sense);
        if (str_contains($sense, 'irrig')) {
            return 'irrigation';
        }
        if (str_contains($sense, 'phytochem')) {
            return 'phytochemistry';
        }
        foreach ($factors as $factor) {
            $t = trim($factor);
            if ($t === '') {
                continue;
            }
            // Prefer the last lexical token of a multi-word factor for FTS AND safety.
            $parts = preg_split('/\s+/u', mb_strtolower($t)) ?: [];
            $parts = array_values(array_filter($parts, static fn (string $p): bool => mb_strlen($p) > 2));
            if ($parts !== []) {
                if (in_array('irrigation', $parts, true)) {
                    return 'irrigation';
                }

                return $parts[count($parts) - 1];
            }
        }

        return $sense !== '' ? $sense : null;
    }

    private function outcomeTerm(string $topic, string $sense, string $original, string $csqSurface): ?string
    {
        $hay = mb_strtolower($original.' '.$csqSurface.' '.$topic.' '.$sense);
        // Prefer outcome tokens that commonly appear in AGRIS agricultural literature titles.
        foreach (['yield', 'productivity', 'evapotranspiration'] as $token) {
            if (str_contains($hay, $token)) {
                return $token;
            }
        }
        // "deficit" is a treatment qualifier — keep it for a dedicated refinement version, not primary AND.
        $topic = trim($topic);

        return $topic !== '' && ! in_array(mb_strtolower($topic), ['irrigation', 'taxonomy'], true)
            ? $topic
            : null;
    }

    private function equivalentSynonym(?string $entity): ?string
    {
        if ($entity === null) {
            return null;
        }
        $map = [
            'wheat' => 'Triticum aestivum',
            'triticum aestivum' => 'wheat',
            'barley' => 'Hordeum vulgare',
            'hordeum vulgare' => 'barley',
            'maize' => 'Zea mays',
            'zea mays' => 'maize',
            'rice' => 'Oryza sativa',
            'oryza sativa' => 'rice',
        ];
        $key = mb_strtolower(trim($entity));

        return $map[$key] ?? null;
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
            $k = mb_strtolower($t);
            if (isset($seen[$k])) {
                continue;
            }
            $seen[$k] = true;
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
