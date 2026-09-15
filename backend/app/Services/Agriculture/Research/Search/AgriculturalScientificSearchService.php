<?php

namespace App\Services\Agriculture\Research\Search;

use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatSearchResultFilter;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;

/**
 * Stage 3 agricultural scientific search service.
 */
class AgriculturalScientificSearchService
{
    public function __construct(
        private MultiSourceScientificSearchOrchestrator $orchestrator,
        private FaoStatSearchResultFilter $faostatResultFilter,
    ) {}

    public function search(KnowledgeQueryPlan $plan, int $limit = 10, ?array $sourceKeys = null): ScientificSearchExecutionReport
    {
        if ($plan->needsClarification() || ! $plan->readyForStage3) {
            return new ScientificSearchExecutionReport(
                status: 'needs_clarification',
                searchQuery: '',
                selectedSources: [],
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

        return $this->faostatResultFilter->apply($plan, $this->orchestrator->execute($plan, $limit, $sourceKeys));
    }
}
