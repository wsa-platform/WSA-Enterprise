<?php

namespace App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat;

use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Search\ScientificSearchExecutionReport;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;
use App\Services\Agriculture\Research\Search\ScientificSourceSearchOutcome;

/**
 * Drops irrelevant FAOSTAT rows before ranking/synthesis. Other sources are unchanged.
 */
final class FaoStatSearchResultFilter
{
    public function __construct(
        private FaoStatObservationRelevanceGate $relevanceGate,
    ) {}

    public function apply(KnowledgeQueryPlan $plan, ScientificSearchExecutionReport $report): ScientificSearchExecutionReport
    {
        $consideration = $this->considerationFromReport($report);
        $filteredResults = $this->keepRelevant($plan, $report->results);
        $filteredRanked = $this->keepRelevant($plan, $report->deduplicatedResults);

        $summary = $report->planSummary;
        $summary['faostat_consideration'] = $consideration;

        return new ScientificSearchExecutionReport(
            status: $report->status,
            searchQuery: $report->searchQuery,
            selectedSources: $report->selectedSources,
            attemptedSources: $report->attemptedSources,
            successfulSources: $report->successfulSources,
            failedSources: $report->failedSources,
            emptySources: $report->emptySources,
            sourceOutcomes: $report->sourceOutcomes,
            results: $filteredResults,
            deduplicatedResults: $filteredRanked,
            planSummary: $summary,
            internetFirst: $report->internetFirst,
            searchQueries: $report->searchQueries,
        );
    }

    /**
     * @param  list<ScientificSearchResult>  $rows
     * @return list<ScientificSearchResult>
     */
    private function keepRelevant(KnowledgeQueryPlan $plan, array $rows): array
    {
        $kept = [];
        foreach ($rows as $row) {
            if (! $row instanceof ScientificSearchResult) {
                continue;
            }
            if ($row->sourceKey !== 'fao_stat') {
                $kept[] = $row;

                continue;
            }
            if ($this->relevanceGate->assess($plan, $row)['relevant'] === true) {
                $kept[] = $row;
            }
        }

        return $kept;
    }

    /**
     * @return array<string, mixed>
     */
    private function considerationFromReport(ScientificSearchExecutionReport $report): array
    {
        $selected = in_array('fao_stat', $report->selectedSources, true)
            || in_array('fao_stat', $report->attemptedSources, true);
        $decision = FaoStatConsiderationPolicy::decision();
        $status = $decision === FaoStatConsiderationPolicy::DISABLED
            ? FaoStatConsiderationPolicy::DISABLED
            : ($selected ? FaoStatConsiderationPolicy::CONSIDERED : FaoStatConsiderationPolicy::DISABLED);

        $outcomeStatus = null;
        $error = null;
        foreach ($report->sourceOutcomes as $outcome) {
            if (! $outcome instanceof ScientificSourceSearchOutcome || $outcome->sourceKey !== 'fao_stat') {
                continue;
            }
            $outcomeStatus = $outcome->status;
            $error = $outcome->error;
        }

        $evidenceDecision = match (true) {
            $status !== FaoStatConsiderationPolicy::CONSIDERED => $status,
            $error === FaoStatErrorCategory::INCOMPLETE_FILTERS => FaoStatErrorCategory::INCOMPLETE_FILTERS,
            $error === FaoStatErrorCategory::EMPTY_RESULT || $outcomeStatus === ScientificSourceSearchOutcome::STATUS_EMPTY => FaoStatErrorCategory::EMPTY_RESULT,
            $outcomeStatus === ScientificSourceSearchOutcome::STATUS_FAILED
                || $outcomeStatus === ScientificSourceSearchOutcome::STATUS_UNAVAILABLE => 'ERROR',
            $outcomeStatus === ScientificSourceSearchOutcome::STATUS_SUCCESS => FaoStatObservationRelevanceGate::RELEVANT,
            default => FaoStatConsiderationPolicy::CONSIDERED,
        };

        return [
            'decision' => $status,
            'evidence_decision' => $evidenceDecision,
            'selected' => $selected,
            'attempted' => in_array('fao_stat', $report->attemptedSources, true),
            'outcome_status' => $outcomeStatus,
            'error' => $error,
            'agricultural_detector' => false,
        ];
    }
}
