<?php

namespace Tests\Feature;

use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\ResearchPlanner;
use App\Services\Agriculture\Research\Search\ScientificSearchExecutionReport;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;
use App\Services\Agriculture\Research\Validation\AgriculturalScientificValidationService;
use Tests\TestCase;

/**
 * Phase 4 — evidence sufficiency contract (validation-owned, rule-based).
 */
class Phase4SufficiencyContractTest extends TestCase
{
    public function test_empty_retrieval_is_insufficient(): void
    {
        $plan = $this->homePlan('What is the optimal temperature for wheat seed germination?');
        $report = app(AgriculturalScientificValidationService::class)->validate(
            $plan,
            $this->searchReport([]),
        );

        $this->assertFalse($report->evidenceSufficient);
        $this->assertSame(0, (int) ($report->searchSummary['direct_evidence_count'] ?? -1));
        $this->assertSame(0, (int) ($report->searchSummary['supporting_evidence_count'] ?? -1));
    }

    public function test_sufficiency_does_not_treat_ranker_score_as_scientific_truth(): void
    {
        $plan = $this->homePlan('What irrigation methods improve wheat yield?');
        $result = new ScientificSearchResult(
            sourceKey: 'openalex',
            sourceIdentifier: 'W-rank',
            title: 'Unrelated banking reforms',
            authors: ['Fixture'],
            publicationYear: 2024,
            doi: '10.9999/w-rank',
            canonicalUrl: 'https://example.test/w-rank',
            abstract: 'National banking GDP inventory across the country.',
            journal: 'Finance Journal',
            foundBySources: ['openalex'],
            relevanceMetadata: ['ranking_only' => true],
            rawMetadata: null,
            relevanceScore: 99.0,
        );

        $report = app(AgriculturalScientificValidationService::class)->validate(
            $plan,
            $this->searchReport([$result]),
        );

        $this->assertFalse($report->evidenceSufficient);
    }

    public function test_sufficiency_counts_are_emitted_for_validated_path(): void
    {
        $plan = $this->homePlan('What irrigation methods improve wheat yield?');
        $result = new ScientificSearchResult(
            sourceKey: 'openalex',
            sourceIdentifier: 'W-wheat',
            title: 'Wheat drip irrigation yield response',
            authors: ['Fixture'],
            publicationYear: 2021,
            doi: '10.9999/w-wheat',
            canonicalUrl: 'https://example.test/w-wheat',
            abstract: 'Wheat drip irrigation improved grain yield in multi-year field trials.',
            journal: 'Agronomy Journal',
            foundBySources: ['openalex'],
        );

        $report = app(AgriculturalScientificValidationService::class)->validate(
            $plan,
            $this->searchReport([$result]),
        );

        $this->assertArrayHasKey('direct_evidence_count', $report->searchSummary);
        $this->assertArrayHasKey('supporting_evidence_count', $report->searchSummary);
        $this->assertArrayHasKey('answer_eligible_supporting_count', $report->searchSummary);
        $this->assertIsBool($report->evidenceSufficient);
        // Ranker score must not appear as a sufficiency input field.
        $this->assertArrayNotHasKey('relevance_score', $report->observability);
    }

    private function homePlan(string $query): KnowledgeQueryPlan
    {
        return app(ResearchPlanner::class)->planKnowledgeQuery(['query' => $query]);
    }

    /**
     * @param  list<ScientificSearchResult>  $results
     */
    private function searchReport(array $results): ScientificSearchExecutionReport
    {
        return new ScientificSearchExecutionReport(
            status: $results === [] ? 'no_results' : 'search_completed',
            searchQuery: 'phase4 sufficiency',
            selectedSources: ['openalex'],
            attemptedSources: ['openalex'],
            successfulSources: $results === [] ? [] : ['openalex'],
            failedSources: [],
            emptySources: $results === [] ? ['openalex'] : [],
            sourceOutcomes: [],
            results: $results,
            deduplicatedResults: $results,
            planSummary: [],
            internetFirst: true,
            searchQueries: ['phase4 sufficiency'],
        );
    }
}
