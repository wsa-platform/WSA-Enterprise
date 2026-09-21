<?php

namespace Tests\Feature;

use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatPipelineOutcome;
use App\Services\Agriculture\Research\ResearchPlanner;
use App\Services\Agriculture\Research\Search\ScientificSearchExecutionReport;
use App\Services\Agriculture\Research\Validation\AgriculturalScientificValidationService;
use Tests\TestCase;

/**
 * Phase 4 — FAOSTAT pipeline outcome must survive into validation searchSummary.
 */
class Phase4FaostatOutcomePropagationTest extends TestCase
{
    public function test_validation_search_summary_preserves_faostat_pipeline_outcome(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'What was wheat production in Egypt in 2020?',
        ]);

        $search = new ScientificSearchExecutionReport(
            status: 'search_completed',
            searchQuery: 'wheat production Egypt 2020',
            selectedSources: ['fao_stat', 'openalex'],
            attemptedSources: ['fao_stat', 'openalex'],
            successfulSources: ['openalex'],
            failedSources: [],
            emptySources: ['fao_stat'],
            sourceOutcomes: [],
            results: [],
            deduplicatedResults: [],
            planSummary: [
                'faostat_pipeline_outcome' => [
                    'stage' => FaoStatPipelineOutcome::REJECTED_ALIGNER,
                    'status' => FaoStatPipelineOutcome::REJECTED_ALIGNER,
                ],
                'result_pipeline' => [
                    'faostat_raw' => 3,
                    'aligner_rejected' => 3,
                ],
            ],
            internetFirst: true,
            searchQueries: ['wheat production Egypt 2020'],
        );

        $report = app(AgriculturalScientificValidationService::class)->validate($plan, $search);

        $this->assertSame(
            FaoStatPipelineOutcome::REJECTED_ALIGNER,
            $report->searchSummary['faostat_pipeline_outcome']['stage']
                ?? $report->searchSummary['faostat_pipeline_outcome']['status']
                ?? null,
        );
        $this->assertNotNull($report->searchSummary['result_pipeline'] ?? null);
        $this->assertSame(
            FaoStatPipelineOutcome::REJECTED_ALIGNER,
            $report->observability['faostat_pipeline_outcome']['stage']
                ?? $report->observability['faostat_pipeline_outcome']['status']
                ?? null,
        );
        $this->assertSame([], $report->searchSummary['failed_sources'] ?? null);
        $this->assertContains('openalex', $report->searchSummary['successful_sources'] ?? []);
    }

    public function test_empty_search_still_propagates_incomplete_filters_outcome(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'What was wheat production?',
        ]);

        $search = new ScientificSearchExecutionReport(
            status: 'no_results',
            searchQuery: 'wheat production',
            selectedSources: ['fao_stat'],
            attemptedSources: ['fao_stat'],
            successfulSources: [],
            failedSources: [],
            emptySources: ['fao_stat'],
            sourceOutcomes: [],
            results: [],
            deduplicatedResults: [],
            planSummary: [
                'faostat_pipeline_outcome' => [
                    'stage' => FaoStatPipelineOutcome::INCOMPLETE_FILTERS,
                    'status' => FaoStatPipelineOutcome::INCOMPLETE_FILTERS,
                ],
            ],
            internetFirst: true,
            searchQueries: ['wheat production'],
        );

        $report = app(AgriculturalScientificValidationService::class)->validate($plan, $search);

        $this->assertFalse($report->evidenceSufficient);
        $this->assertSame(
            FaoStatPipelineOutcome::INCOMPLETE_FILTERS,
            $report->searchSummary['faostat_pipeline_outcome']['stage']
                ?? $report->searchSummary['faostat_pipeline_outcome']['status']
                ?? null,
        );
    }
}
