<?php

namespace App\Services\Agriculture\Research\Search;

use App\Services\Agriculture\Research\KnowledgeQueryPlan;

/**
 * GBIF species-search query specialization (G5-04 projection).
 *
 * Prefers scientific-name / taxonomy queries. Does not claim occurrence filters
 * or unsupported facets.
 */
final class GbifSpeciesQuerySpecializer
{
    public const SOURCE_KEY = 'gbif';

    /**
     * @return list<string>
     */
    public function retrievalQueries(KnowledgeQueryPlan $plan): array
    {
        $scientific = trim((string) ($plan->normalizedQuery->scientificName ?? ''));
        $crop = trim((string) ($plan->normalizedQuery->cropId ?? $plan->normalizedQuery->crop ?? ''));
        $sense = trim((string) ($plan->normalizedQuery->constraints['scientific_sense'] ?? ''));
        $topics = $plan->normalizedQuery->constraints['scientific_topics'] ?? [];
        $topicBag = mb_strtolower(implode(' ', is_array($topics) ? $topics : []));

        $wantsTaxonomy = $sense === 'taxonomy'
            || str_contains($topicBag, 'taxonom')
            || str_contains($topicBag, 'species')
            || $plan->researchIntent === 'taxonomy'
            || $plan->researchIntent === 'species_lookup';

        // GBIF species/search matches binomial identity; appending "species classification"
        // often yields an empty result set on the live API (verified 2026-11-19).
        $queries = [];
        if ($scientific !== '') {
            $queries[] = $scientific;
            $parts = preg_split('/\s+/u', $scientific) ?: [];
            $genus = trim((string) ($parts[0] ?? ''));
            if ($genus !== '' && strcasecmp($genus, $scientific) !== 0) {
                $queries[] = $genus;
            }
        } elseif ($crop !== '' && $wantsTaxonomy) {
            $queries[] = $crop;
        }

        return array_values(array_unique(array_filter(
            $queries,
            static fn (string $q): bool => trim($q) !== '',
        )));
    }
}
