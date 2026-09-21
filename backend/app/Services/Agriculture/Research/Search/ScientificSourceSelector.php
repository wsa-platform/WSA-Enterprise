<?php

namespace App\Services\Agriculture\Research\Search;

use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatConsiderationPolicy;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatRuntimePolicy;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;

/**
 * Deterministic source selection from KnowledgeQueryPlan.
 *
 * Policy (Phase 3-B / ADR-021 §3):
 * - Internet-First scholarly defaults: OpenAlex, Crossref, Semantic Scholar.
 * - Consensus remains optional/legacy and is never auto-selected.
 * - FAOSTAT Developer Portal is universally considered when enabled (Phase 3-A);
 *   consideration ≠ evidence acceptance (gate/pipeline outcomes still apply).
 * - Inactive providers are excluded; unknown keys are never invented here.
 */
class ScientificSourceSelector
{
    /** @var list<string> */
    public const DEFAULT_INTERNET_FIRST_SOURCES = [
        'openalex',
        'crossref',
        'semantic_scholar',
    ];

    /** Registered but never auto-selected. */
    /** @var list<string> */
    public const OPTIONAL_SOURCES = [
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

        $sources = self::DEFAULT_INTERNET_FIRST_SOURCES;

        // Canonical FAOSTAT flag only. Retired Fenix FAO_ENABLED must not select fao_stat.
        if (FaoStatConsiderationPolicy::isEnabled()) {
            array_unshift($sources, FaoStatRuntimePolicy::canonicalSourceKey());
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

    /**
     * Internal observability for Stage 3 forensics (not a public API envelope change).
     *
     * @return array{
     *     selected: list<string>,
     *     skipped_inactive: list<string>,
     *     optional_not_selected: list<string>,
     *     faostat_consideration: string,
     *     policy: string
     * }
     */
    public function selectionTrace(KnowledgeQueryPlan $plan): array
    {
        $selected = $this->selectSources($plan);
        $skippedInactive = [];

        foreach (self::DEFAULT_INTERNET_FIRST_SOURCES as $key) {
            if (in_array($key, $selected, true)) {
                continue;
            }
            $enabled = match ($key) {
                'openalex' => filter_var(config('agricultural_intelligence.openalex.enabled', true), FILTER_VALIDATE_BOOL),
                'crossref' => filter_var(config('agricultural_intelligence.crossref.enabled', true), FILTER_VALIDATE_BOOL),
                'semantic_scholar' => filter_var(config('agricultural_intelligence.semantic_scholar.enabled', true), FILTER_VALIDATE_BOOL),
                default => true,
            };
            if (! $enabled) {
                $skippedInactive[] = $key;
            }
        }

        if (FaoStatConsiderationPolicy::decision() === FaoStatConsiderationPolicy::DISABLED
            || ! FaoStatRuntimePolicy::isEnabled()) {
            if (! in_array(FaoStatRuntimePolicy::canonicalSourceKey(), $selected, true)) {
                $skippedInactive[] = FaoStatRuntimePolicy::canonicalSourceKey();
            }
        }

        return [
            'selected' => $selected,
            'skipped_inactive' => array_values(array_unique($skippedInactive)),
            'optional_not_selected' => self::OPTIONAL_SOURCES,
            'faostat_consideration' => FaoStatConsiderationPolicy::decision(),
            'policy' => 'internet_first_defaults_plus_universal_faostat_when_enabled',
        ];
    }
}
