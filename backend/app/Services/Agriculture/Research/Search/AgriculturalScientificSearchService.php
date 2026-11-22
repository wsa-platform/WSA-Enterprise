<?php

namespace App\Services\Agriculture\Research\Search;

use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatSearchResultFilter;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\RetrievalSemanticContract;

/**
 * Stage 3 agricultural scientific search service.
 */
class AgriculturalScientificSearchService
{
    public function __construct(
        private MultiSourceScientificSearchOrchestrator $orchestrator,
        private FaoStatSearchResultFilter $faostatResultFilter,
        private RetrievalSemanticContract $retrievalSemantics,
    ) {}

    public function search(KnowledgeQueryPlan $plan, int $limit = 10, ?array $sourceKeys = null): ScientificSearchExecutionReport
    {
        // Align with AgriculturalResearchAgent: force_execute continues Stage-3 despite QUS clarification.
        $forceExecute = filter_var($plan->contextInput['force_execute'] ?? false, FILTER_VALIDATE_BOOL);
        if (($plan->needsClarification() || ! $plan->readyForStage3) && ! $forceExecute) {
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

        $plan = $this->retrievalSemantics->apply($plan);

        return $this->faostatResultFilter->apply($plan, $this->orchestrator->execute($plan, $limit, $sourceKeys));
    }
}
