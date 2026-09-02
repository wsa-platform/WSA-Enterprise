<?php

namespace App\Services\Agriculture\Research\Search;

use App\Services\Agriculture\Research\KnowledgeQueryPlan;

/**
 * Builds deterministic scholarly search queries from Stage 2 planning output.
 */
class ScientificSearchQueryBuilder
{
    public function buildFromPlan(KnowledgeQueryPlan $plan): string
    {
        $query = $plan->normalizedQuery;
        $parts = array_values(array_filter([
            $query->normalizedQuestion !== '' ? $query->normalizedQuestion : $query->originalQuestion,
            $query->scientificName,
            $query->cropId,
            is_array($query->subject) ? ($query->subject['value'] ?? null) : null,
            $plan->researchIntent,
            $plan->agriculturalDomain,
            implode(' ', $plan->topics),
        ]));

        $built = trim(preg_replace('/\s+/u', ' ', implode(' ', $parts)) ?? '');

        return $built !== '' ? $built : 'agriculture';
    }
}
