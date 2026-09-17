<?php

namespace App\Services\Agriculture\Research\Search;

use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatRuntimePolicy;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatSearchOptionsResolver;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;

/**
 * Stage 3 multi-source scientific search orchestration layer.
 *
 * Runs all controlled query variants across selected Internet-First providers,
 * then normalize → deduplicate → query-aware rank → relevance filter.
 */
class MultiSourceScientificSearchOrchestrator
{
    private const ADEQUATE_RESULT_COUNT = 8;

    private const MAX_VARIANTS_PER_PROVIDER = 2;

    public function __construct(
        private ScientificSourceAdapterRegistry $registry,
        private ScientificSourceSelector $sourceSelector,
        private ScientificSearchQueryBuilder $queryBuilder,
        private ScientificResultDeduplicator $deduplicator,
        private ScientificResultRanker $ranker,
    ) {}

    public function execute(KnowledgeQueryPlan $plan, int $limit = 10, ?array $sourceKeys = null): ScientificSearchExecutionReport
    {
        $variants = $this->queryBuilder->buildVariantsFromPlan($plan);
        $searchQuery = $variants[0] ?? $this->queryBuilder->buildFromPlan($plan);

        $selectedSources = $sourceKeys !== null
            ? $this->filterEnabledSources($sourceKeys)
            : $this->sourceSelector->selectSources($plan);
        if ($selectedSources === []) {
            return $this->emptyReport(
                plan: $plan,
                status: $plan->needsClarification() ? 'needs_clarification' : 'no_sources_selected',
                searchQuery: $searchQuery,
                searchQueries: $variants,
            );
        }

        if (trim($searchQuery) === '') {
            return $this->emptyReport(
                plan: $plan,
                status: 'empty_query',
                searchQuery: '',
                selectedSources: $selectedSources,
                searchQueries: $variants,
            );
        }

        $adapters = $this->registry->resolveMany($this->prioritizeSources($selectedSources));
        if ($adapters === []) {
            return $this->emptyReport(
                plan: $plan,
                status: 'unsupported_sources',
                searchQuery: $searchQuery,
                selectedSources: $selectedSources,
                searchQueries: $variants,
            );
        }

        $budget = ScientificSearchTimeBudget::start();
        $outcomes = [];
        $allResults = [];
        $attempted = [];
        $successful = [];
        $failed = [];
        $empty = [];
        $adapterStatus = [];
        $skippedBudget = [];

        foreach ($adapters as $adapter) {
            $key = $adapter->sourceKey();
            if ($budget->remainingSeconds() < 1.0) {
                $skippedBudget[] = $key;
                $adapterStatus[$key] = 'skipped';
                $outcomes[] = new ScientificSourceSearchOutcome(
                    sourceKey: $key,
                    status: ScientificSourceSearchOutcome::STATUS_UNAVAILABLE,
                    error: 'time_budget_exhausted',
                    observability: [
                        'reason' => 'search_time_budget',
                        'budget_seconds' => $budget->seconds,
                        'remaining_seconds' => $budget->remainingSeconds(),
                    ],
                );

                continue;
            }

            $attempted[] = $key;
            $adapterStatus[$key] = 'empty';
            $skipRemainingVariants = false;
            $variantsAttempted = 0;

            foreach ($variants as $variant) {
                if ($skipRemainingVariants || $variantsAttempted >= self::MAX_VARIANTS_PER_PROVIDER) {
                    continue;
                }
                if ($budget->remainingSeconds() < 1.0) {
                    $skipRemainingVariants = true;
                    $skippedBudget[] = $key;

                    continue;
                }

                $outcome = $adapter->search(
                    $variant,
                    $limit,
                    array_merge($this->optionsForSource($key, $plan), [
                        'search_budget_remaining_seconds' => $budget->remainingSeconds(),
                    ]),
                );
                $variantsAttempted++;
                $outcomes[] = $outcome;

                if ($outcome->status === ScientificSourceSearchOutcome::STATUS_SUCCESS) {
                    $adapterStatus[$key] = 'success';
                    $allResults = array_merge($allResults, $outcome->results);
                    if ($outcome->results !== [] && count($allResults) >= self::ADEQUATE_RESULT_COUNT) {
                        $skipRemainingVariants = true;
                    }
                    if ($key === FaoStatRuntimePolicy::canonicalSourceKey()
                        && $this->hasCompleteStructuredObservation($outcome)) {
                        $skipRemainingVariants = true;
                    }

                    continue;
                }

                // Missing Consensus key: unavailable but not a provider failure — OA+CR continue.
                if ($outcome->status === ScientificSourceSearchOutcome::STATUS_UNAVAILABLE
                    && $outcome->error === 'missing_api_key') {
                    if ($adapterStatus[$key] !== 'success' && $adapterStatus[$key] !== 'failed') {
                        $adapterStatus[$key] = 'skipped';
                    }
                    $skipRemainingVariants = true;

                    continue;
                }

                if ($outcome->status === ScientificSourceSearchOutcome::STATUS_EMPTY) {
                    if ($adapterStatus[$key] !== 'success' && $adapterStatus[$key] !== 'failed') {
                        $adapterStatus[$key] = 'empty';
                    }

                    continue;
                }

                // failed / unavailable / rate-limited / timeout — provider-partial only
                if ($adapterStatus[$key] !== 'success') {
                    $adapterStatus[$key] = 'failed';
                }

                // After first OpenAlex (or any provider) 429, skip remaining variants for
                // that provider in this request; other providers still run all variants.
                if ($this->isRateLimitedOutcome($outcome)) {
                    $skipRemainingVariants = true;
                }
            }
        }

        foreach ($adapterStatus as $key => $status) {
            if ($status === 'success') {
                $successful[] = $key;
            } elseif ($status === 'failed') {
                $failed[] = $key;
            } elseif ($status === 'skipped') {
                // Intentionally omitted from empty/failed aggregates.
                continue;
            } else {
                $empty[] = $key;
            }
        }

        $deduplicated = $this->deduplicator->deduplicate($allResults);
        $ranked = $this->ranker->rank($searchQuery, $deduplicated, $plan);
        $ranked = $this->ranker->filterRelevant($ranked);
        $ranked = $this->ranker->diversifyByProviderJournalInstitution($ranked);

        $status = match (true) {
            $successful !== [] && $ranked !== [] => 'search_completed',
            $successful !== [] && $ranked === [] => 'no_results',
            $failed !== [] && $empty !== [] => 'partial_source_failure',
            $failed !== [] => 'all_sources_failed',
            default => 'no_results',
        };

        return new ScientificSearchExecutionReport(
            status: $status,
            searchQuery: $searchQuery,
            selectedSources: $selectedSources,
            attemptedSources: $attempted,
            successfulSources: $successful,
            failedSources: $failed,
            emptySources: $empty,
            sourceOutcomes: $outcomes,
            results: $allResults,
            deduplicatedResults: $ranked,
            planSummary: array_merge($plan->toArray(), [
                'search_queries' => $variants,
                'search_time_budget_seconds' => $budget->seconds,
                'adapters_skipped_time_budget' => array_values(array_unique($skippedBudget)),
            ]),
            internetFirst: $plan->isInternetFirst(),
            searchQueries: $variants,
        );
    }

    /**
     * OpenAlex Consensus options (domain=agri, country=ISO) must not reach FAOSTAT.
     * FAOSTAT interprets domain as a dataset code (QCL) and must receive only FAOSTAT options.
     *
     * @return array<string, mixed>
     */
    private function optionsForSource(string $sourceKey, KnowledgeQueryPlan $plan): array
    {
        if ($sourceKey === 'fao_stat') {
            return FaoStatSearchOptionsResolver::fromPlan($plan);
        }

        return $this->queryBuilder->buildConsensusRequestOptions($plan);
    }

    private function isRateLimitedOutcome(ScientificSourceSearchOutcome $outcome): bool
    {
        return $outcome->status === ScientificSourceSearchOutcome::STATUS_UNAVAILABLE
            && ($outcome->httpStatus === 429 || $outcome->error === 'rate_limited');
    }

    private function hasCompleteStructuredObservation(ScientificSourceSearchOutcome $outcome): bool
    {
        if ($outcome->status !== ScientificSourceSearchOutcome::STATUS_SUCCESS) {
            return false;
        }

        foreach ($outcome->results as $result) {
            $observation = ScientificStructuredObservation::fromResult($result);
            if ($observation !== null && $observation->isComplete()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $sourceKeys
     * @return list<string>
     */
    private function filterEnabledSources(array $sourceKeys): array
    {
        $enabled = [];
        foreach ($sourceKeys as $key) {
            $key = strtolower(trim((string) $key));
            if ($key === '') {
                continue;
            }
            $flag = match ($key) {
                'openalex' => (bool) config('agricultural_intelligence.openalex.enabled', true),
                'crossref' => (bool) config('agricultural_intelligence.crossref.enabled', true),
                'semantic_scholar' => (bool) config('agricultural_intelligence.semantic_scholar.enabled', true),
                'fao_stat' => FaoStatRuntimePolicy::isEnabled(),
                default => true,
            };
            if ($flag) {
                $enabled[] = $key;
            }
        }

        return array_values(array_unique($this->prioritizeSources($enabled)));
    }

    /**
     * Statistical FAOSTAT runs first so a slow scholarly provider cannot consume the whole budget.
     *
     * @param  list<string>  $sourceKeys
     * @return list<string>
     */
    private function prioritizeSources(array $sourceKeys): array
    {
        $preferred = [];
        $rest = [];
        foreach ($sourceKeys as $key) {
            if ($key === FaoStatRuntimePolicy::canonicalSourceKey()) {
                $preferred[] = $key;
            } else {
                $rest[] = $key;
            }
        }

        return [...$preferred, ...$rest];
    }

    /**
     * @param  list<string>  $selectedSources
     * @param  list<string>  $searchQueries
     */
    private function emptyReport(
        KnowledgeQueryPlan $plan,
        string $status,
        string $searchQuery,
        array $selectedSources = [],
        array $searchQueries = [],
    ): ScientificSearchExecutionReport {
        return new ScientificSearchExecutionReport(
            status: $status,
            searchQuery: $searchQuery,
            selectedSources: $selectedSources,
            attemptedSources: [],
            successfulSources: [],
            failedSources: [],
            emptySources: [],
            sourceOutcomes: [],
            results: [],
            deduplicatedResults: [],
            planSummary: $plan->toArray(),
            internetFirst: $plan->isInternetFirst(),
            searchQueries: $searchQueries,
        );
    }
}
