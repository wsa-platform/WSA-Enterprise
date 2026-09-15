<?php

namespace App\Services\Agriculture\Research\Search;

use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatConsiderationPolicy;
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
        'semantic_scholar',
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

        $sources = self::DEFAULT_INTERNET_FIRST_SOURCES;

        // Portal flag: consider FAOSTAT for every Internet-First question (no agricultural detector).
        // FENIX flag: preserve existing append behavior when the portal is off.
        if (FaoStatConsiderationPolicy::isEnabled()
            || filter_var(config('agricultural_intelligence.fao.enabled', false), FILTER_VALIDATE_BOOL)) {
            $sources[] = 'fao_stat';
        }

        if (! filter_var(config('agricultural_intelligence.openalex.enabled', true), FILTER_VALIDATE_BOOL)) {
            $sources = array_values(array_filter($sources, static fn (string $s): bool => $s !== 'openalex'));
        }
        if (! filter_var(config('agricultural_intelligence.crossref.enabled', true), FILTER_VALIDATE_BOOL)) {
            $sources = array_values(array_filter($sources, static fn (string $s): bool => $s !== 'crossref'));
        }
        if (! filter_var(config('agricultural_intelligence.semantic_scholar.enabled', true), FILTER_VALIDATE_BOOL)) {
            $sources = array_values(array_filter($sources, static fn (string $s): bool => $s !== 'semantic_scholar'));
        }

        return $sources;
    }
}
