<?php

namespace App\Services\Agriculture\Research\Search;

/**
 * Structured Stage 3 multi-source search execution report.
 */
final class ScientificSearchExecutionReport
{
    /**
     * @param  list<string>  $selectedSources
     * @param  list<string>  $attemptedSources
     * @param  list<string>  $successfulSources
     * @param  list<string>  $failedSources
     * @param  list<string>  $emptySources
     * @param  list<ScientificSourceSearchOutcome>  $sourceOutcomes
     * @param  list<ScientificSearchResult>  $results
     * @param  list<ScientificSearchResult>  $deduplicatedResults
     * @param  array<string, mixed>  $planSummary
     */
    public function __construct(
        public readonly string $status,
        public readonly string $searchQuery,
        public readonly array $selectedSources,
        public readonly array $attemptedSources,
        public readonly array $successfulSources,
        public readonly array $failedSources,
        public readonly array $emptySources,
        public readonly array $sourceOutcomes,
        public readonly array $results,
        public readonly array $deduplicatedResults,
        public readonly array $planSummary,
        public readonly bool $internetFirst = true,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'stage' => 3,
            'internet_first' => $this->internetFirst,
            'search_query' => $this->searchQuery,
            'selected_sources' => $this->selectedSources,
            'attempted_sources' => $this->attemptedSources,
            'successful_sources' => $this->successfulSources,
            'failed_sources' => $this->failedSources,
            'empty_sources' => $this->emptySources,
            'result_count' => count($this->results),
            'deduplicated_result_count' => count($this->deduplicatedResults),
            'source_outcomes' => array_map(
                static fn (ScientificSourceSearchOutcome $o): array => $o->toArray(),
                $this->sourceOutcomes,
            ),
            'results' => array_map(
                static fn (ScientificSearchResult $r): array => $r->toArray(),
                $this->deduplicatedResults,
            ),
            'plan' => $this->planSummary,
            'validation' => [
                'performed' => false,
                'stage' => 4,
                'reason' => 'stage_3_search_only',
            ],
        ];
    }
}
