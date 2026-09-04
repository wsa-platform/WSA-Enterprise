<?php

namespace App\Services\Agriculture\Research\Search;

use App\Services\Agriculture\Research\KnowledgeQueryPlan;

/**
 * Stage 3 multi-source scientific search orchestration layer.
 *
 * Runs all controlled query variants across selected Internet-First providers,
 * then normalize → deduplicate → query-aware rank → relevance filter.
 */
class MultiSourceScientificSearchOrchestrator
{
    public function __construct(
        private ScientificSourceAdapterRegistry $registry,
        private ScientificSourceSelector $sourceSelector,
        private ScientificSearchQueryBuilder $queryBuilder,
        private ScientificResultDeduplicator $deduplicator,
        private ScientificResultRanker $ranker,
    ) {}

    public function execute(KnowledgeQueryPlan $plan, int $limit = 10): ScientificSearchExecutionReport
    {
        $variants = $this->queryBuilder->buildVariantsFromPlan($plan);
        $searchQuery = $variants[0] ?? $this->queryBuilder->buildFromPlan($plan);

        $selectedSources = $this->sourceSelector->selectSources($plan);
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

        $adapters = $this->registry->resolveMany($selectedSources);
        if ($adapters === []) {
            return $this->emptyReport(
                plan: $plan,
                status: 'unsupported_sources',
                searchQuery: $searchQuery,
                selectedSources: $selectedSources,
                searchQueries: $variants,
            );
        }

        $outcomes = [];
        $allResults = [];
        $attempted = [];
        $successful = [];
        $failed = [];
        $empty = [];
        $adapterStatus = [];

        foreach ($adapters as $adapter) {
            $key = $adapter->sourceKey();
            $attempted[] = $key;
            $adapterStatus[$key] = 'empty';

            foreach ($variants as $variant) {
                $outcome = $adapter->search($variant, $limit);
                $outcomes[] = $outcome;

                if ($outcome->status === ScientificSourceSearchOutcome::STATUS_SUCCESS) {
                    $adapterStatus[$key] = 'success';
                    $allResults = array_merge($allResults, $outcome->results);

                    continue;
                }

                if ($outcome->status === ScientificSourceSearchOutcome::STATUS_EMPTY) {
                    if ($adapterStatus[$key] !== 'success' && $adapterStatus[$key] !== 'failed') {
                        $adapterStatus[$key] = 'empty';
                    }

                    continue;
                }

                // failed / unavailable / rate-limited / timeout
                if ($adapterStatus[$key] !== 'success') {
                    $adapterStatus[$key] = 'failed';
                }
            }
        }

        foreach ($adapterStatus as $key => $status) {
            if ($status === 'success') {
                $successful[] = $key;
            } elseif ($status === 'failed') {
                $failed[] = $key;
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
            ]),
            internetFirst: $plan->isInternetFirst(),
            searchQueries: $variants,
        );
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
