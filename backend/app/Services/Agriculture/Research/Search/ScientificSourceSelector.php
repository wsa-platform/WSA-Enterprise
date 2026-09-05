<?php

namespace App\Services\Agriculture\Research\Search;

use App\Services\Agriculture\Research\KnowledgeQueryPlan;

/**
 * Deterministic source selection from KnowledgeQueryPlan.
 */
class ScientificSourceSelector
{
    /** @var list<string> */
    public const DEFAULT_INTERNET_FIRST_SOURCES = [
        'openalex',
        'crossref',
        'consensus',
    ];

    /** @return list<string> */
    public function selectSources(KnowledgeQueryPlan $plan): array
    {
        if ($plan->needsClarification() || ! $plan->readyForStage3) {
            return [];
        }

        if (! $plan->isInternetFirst()) {
            return [];
        }

        return self::DEFAULT_INTERNET_FIRST_SOURCES;
    }
}
