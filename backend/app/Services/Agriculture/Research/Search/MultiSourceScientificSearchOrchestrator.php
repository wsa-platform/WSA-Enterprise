<?php

namespace App\Services\Agriculture\Research\Search;

use App\Services\Agriculture\Research\KnowledgeQueryPlan;

/**
 * Stage 3 multi-source scientific search orchestration layer.
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
        $selectedSources = $this->sourceSelector->selectSources($plan);
        if ($selectedSources === []) {
            return $this->emptyReport(
                plan: $plan,
                status: $plan->needsClarification() ? 'needs_clarification' : 'no_sources_selected',
                searchQuery: $this->queryBuilder->buildFromPlan($plan),
            );
        }

        $searchQuery = $this->queryBuilder->buildFromPlan($plan);
        if (trim($searchQuery) === '') {
            return $this->emptyReport(
                plan: $plan,
                status: 'empty_query',
                searchQuery: '',
                selectedSources: $selectedSources,
            );
        }

        $adapters = $this->registry->resolveMany($selectedSources);
        if ($adapters === []) {
            return $this->emptyReport(
                plan: $plan,
                status: 'unsupported_sources',
                searchQuery: $searchQuery,
                selectedSources: $selectedSources,
            );
        }

        $outcomes = [];
        $allResults = [];
        $attempted = [];
        $successful = [];
        $failed = [];
        $empty = [];

        foreach ($adapters as $adapter) {
            $attempted[] = $adapter->sourceKey();
            $outcome = $adapter->search($searchQuery, $limit);
            $outcomes[] = $outcome;

            if ($outcome->status === ScientificSourceSearchOutcome::STATUS_SUCCESS) {
                $successful[] = $adapter->sourceKey();
                $allResults = array_merge($allResults, $outcome->results);

                continue;
            }

            if ($outcome->status === ScientificSourceSearchOutcome::STATUS_EMPTY) {
                $empty[] = $adapter->sourceKey();

                continue;
            }

            $failed[] = $adapter->sourceKey();
        }

        $deduplicated = $this->deduplicator->deduplicate($allResults);
        $ranked = $this->ranker->rank($searchQuery, $deduplicated);

        $status = match (true) {
            $successful !== [] => 'search_completed',
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
            planSummary: $plan->toArray(),
            internetFirst: $plan->isInternetFirst(),
        );
    }

    /**
     * @param  list<string>  $selectedSources
     */
    private function emptyReport(
        KnowledgeQueryPlan $plan,
        string $status,
        string $searchQuery,
        array $selectedSources = [],
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
        );
    }
}
