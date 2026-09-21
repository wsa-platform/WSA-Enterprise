<?php

namespace App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat;

use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Search\ScientificSearchExecutionReport;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;
use App\Services\Agriculture\Research\Search\ScientificSourceSearchOutcome;

/**
 * Drops irrelevant FAOSTAT rows before ranking/synthesis. Other sources are unchanged.
 * Annotates internal pipeline outcomes without changing the public result envelope.
 */
final class FaoStatSearchResultFilter
{
    public function __construct(
        private FaoStatObservationRelevanceGate $relevanceGate,
    ) {}

    public function apply(KnowledgeQueryPlan $plan, ScientificSearchExecutionReport $report): ScientificSearchExecutionReport
    {
        $consideration = $this->considerationFromReport($report);
        $pipeline = $this->pipelineDiagnostics($plan, $report);
        $filteredResults = $this->keepRelevant($plan, $report->results);
        $filteredRanked = $this->keepRelevant($plan, $report->deduplicatedResults);

        $summary = $report->planSummary;
        $summary['faostat_consideration'] = $consideration;
        $summary['faostat_pipeline_outcome'] = $pipeline;

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

    /**
     * @return array<string, mixed>
     */
    private function pipelineDiagnostics(KnowledgeQueryPlan $plan, ScientificSearchExecutionReport $report): array
    {
        $options = FaoStatSearchOptionsResolver::fromPlan($plan);
        $faostatRaw = array_values(array_filter(
            $report->results,
            static fn (ScientificSearchResult $r): bool => $r->sourceKey === 'fao_stat',
        ));
        $passedGate = 0;
        $rejectedGate = 0;
        foreach ($faostatRaw as $row) {
            if ($this->relevanceGate->assess($plan, $row)['relevant'] === true) {
                $passedGate++;
            } else {
                $rejectedGate++;
            }
        }

        $passedAligner = 0;
        $rejectedAligner = 0;
        foreach ($report->results as $row) {
            if ($row->sourceKey !== 'fao_stat') {
                continue;
            }
            if (($row->relevanceMetadata['rejected_by_relevance_gate'] ?? false) === true
                && is_array($row->relevanceMetadata['rejection_reasons'] ?? null)) {
                $rejectedAligner++;
            } elseif (($row->relevanceMetadata['statistical_claim_aligned'] ?? null) === true) {
                $passedAligner++;
            } elseif (($row->relevanceMetadata['statistical_claim_aligned'] ?? null) === false) {
                $rejectedAligner++;
            }
        }

        $finalFaostat = array_values(array_filter(
            $report->deduplicatedResults,
            static fn (ScientificSearchResult $r): bool => $r->sourceKey === 'fao_stat',
        ));
        // Survivors after FAO gate (matches post-filter semantics of this class).
        $gateSurvivors = [];
        foreach ($finalFaostat as $row) {
            if ($this->relevanceGate->assess($plan, $row)['relevant'] === true) {
                $gateSurvivors[] = $row;
            }
        }

        $error = null;
        $outcomeStatus = null;
        $queryIdentity = null;
        foreach ($report->sourceOutcomes as $outcome) {
            if (! $outcome instanceof ScientificSourceSearchOutcome || $outcome->sourceKey !== 'fao_stat') {
                continue;
            }
            $outcomeStatus = $outcome->status;
            $error = $outcome->error;
            if (is_array($outcome->observability) && isset($outcome->observability['query_identity'])) {
                $queryIdentity = $outcome->observability['query_identity'];
            }
        }

        $stage = match (true) {
            ! in_array('fao_stat', $report->selectedSources, true)
                && ! in_array('fao_stat', $report->attemptedSources, true) => FaoStatPipelineOutcome::NOT_SELECTED,
            ($options['element_resolution_status'] ?? '') === FaoStatPipelineOutcome::MEASURE_CONFLICT => FaoStatPipelineOutcome::MEASURE_CONFLICT,
            ($options['element_resolution_status'] ?? '') === FaoStatPipelineOutcome::AMBIGUOUS_MEASURES
                || $error === FaoStatErrorCategory::INCOMPLETE_FILTERS => (
                    ($options['element_resolution_status'] ?? '') === FaoStatPipelineOutcome::AMBIGUOUS_MEASURES
                        ? FaoStatPipelineOutcome::AMBIGUOUS_MEASURES
                        : FaoStatPipelineOutcome::INCOMPLETE_FILTERS
                ),
            $error === FaoStatErrorCategory::EMPTY_RESULT => FaoStatPipelineOutcome::EMPTY_RESULT,
            $outcomeStatus === ScientificSourceSearchOutcome::STATUS_FAILED
                || $outcomeStatus === ScientificSourceSearchOutcome::STATUS_UNAVAILABLE => FaoStatPipelineOutcome::PROVIDER_ERROR,
            $gateSurvivors !== [] => FaoStatPipelineOutcome::SUCCESS,
            $faostatRaw !== [] && $passedGate === 0 => FaoStatPipelineOutcome::REJECTED_FAO_GATE,
            $faostatRaw !== [] && $rejectedAligner > 0 && $gateSurvivors === [] => FaoStatPipelineOutcome::REJECTED_ALIGNER,
            $faostatRaw !== [] && $gateSurvivors === [] => FaoStatPipelineOutcome::REJECTED_DOWNSTREAM,
            default => FaoStatPipelineOutcome::EMPTY_RESULT,
        };

        return [
            'stage' => $stage,
            'dimensions' => [
                'area' => $options['area'] ?? null,
                'item' => $options['item'] ?? null,
                'element' => $options['element'] ?? null,
                'year' => $options['year'] ?? null,
                'domain' => $options['domain'] ?? null,
                'element_resolution_status' => $options['element_resolution_status'] ?? null,
                'requested_measures' => $options['requested_measures'] ?? null,
            ],
            'query_identity' => $queryIdentity ?? FaoStatProviderQueryIdentity::fromOptions($options),
            'retrieved_count' => count($faostatRaw),
            'passed_fao_gate' => $passedGate,
            'rejected_fao_gate' => $rejectedGate,
            'passed_aligner' => $passedAligner,
            'rejected_aligner' => $rejectedAligner,
            'survived_final' => count($gateSurvivors),
            'provider_error' => $error,
            'provider_status' => $outcomeStatus,
        ];
    }
}
