<?php

namespace Tests\Feature;

use App\Services\Agriculture\OpenAlexScientificClient;
use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Search\Adapters\OpenAlexScientificSourceAdapter;
use App\Services\Agriculture\Research\Search\AgriculturalScientificSearchService;
use App\Services\Agriculture\Research\Search\ScientificSourceSearchOutcome;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenAlexRateLimitBudgetTest extends TestCase
{
    public function test_openalex_fail_fasts_when_retry_after_exceeds_remaining_budget(): void
    {
        $calls = 0;
        Http::fake([
            'api.openalex.org/works*' => function () use (&$calls) {
                $calls++;

                return Http::response(['results' => []], 429, ['Retry-After' => '120']);
            },
        ]);

        $outcome = app(OpenAlexScientificSourceAdapter::class)->search(
            'cattle meat breeds',
            5,
            ['search_budget_remaining_seconds' => 5.0],
        );

        $this->assertSame(1, $calls);
        $this->assertSame(ScientificSourceSearchOutcome::STATUS_UNAVAILABLE, $outcome->status);
        $this->assertSame('rate_limited', $outcome->error);
        $this->assertSame(0, $outcome->observability['retry_count'] ?? null);
    }

    public function test_openalex_still_retries_three_times_when_retry_after_is_zero(): void
    {
        $calls = 0;
        Http::fake([
            'api.openalex.org/works*' => function () use (&$calls) {
                $calls++;

                return Http::response(['results' => []], 429, ['Retry-After' => '0']);
            },
        ]);

        $outcome = app(OpenAlexScientificSourceAdapter::class)->search('wheat irrigation', 5);

        $this->assertSame(3, $calls);
        $this->assertSame('rate_limited', $outcome->error);
        $this->assertSame(2, $outcome->observability['retry_count'] ?? null);
    }

    public function test_orchestrator_continues_to_crossref_after_openalex_huge_retry_after(): void
    {
        $openAlexCalls = 0;
        Http::fake([
            'api.openalex.org/works*' => function () use (&$openAlexCalls) {
                $openAlexCalls++;

                return Http::response(['results' => []], 429, ['Retry-After' => '8944']);
            },
            'api.semanticscholar.org/graph/v1/paper/search*' => Http::response(['data' => []], 200),
            'api.crossref.org/works*' => Http::response(['message' => ['items' => [[
                'DOI' => '10.1000/cr-oa429-budget',
                'title' => ['Beef cattle meat production breeds'],
                'abstract' => 'Documented meat cattle breeds and production systems in agriculture.',
                'publisher' => 'University of Agriculture',
                'container-title' => ['Journal of Agricultural Science'],
                'issued' => ['date-parts' => [[2022]]],
                'author' => [['given' => 'A', 'family' => 'Researcher']],
            ]]]], 200),
        ]);

        $report = app(AgriculturalScientificSearchService::class)->search($this->plan(), 5, [
            'openalex',
            'crossref',
            'semantic_scholar',
        ]);

        $this->assertSame(1, $openAlexCalls, 'Huge Retry-After must fail-fast instead of burning three attempts');
        $this->assertContains('openalex', $report->failedSources);
        $this->assertContains('crossref', $report->successfulSources);
        $this->assertNotSame('all_sources_failed', $report->status);
        $this->assertGreaterThan(0, count($report->results));
    }

    public function test_openalex_client_skips_later_calls_after_in_request_429(): void
    {
        $calls = 0;
        Http::fake([
            'api.openalex.org/works*' => function () use (&$calls) {
                $calls++;

                return Http::response(['results' => []], 429, ['Retry-After' => '8944']);
            },
        ]);

        $client = app(OpenAlexScientificClient::class);
        $this->assertSame([], $client->searchWorks('first'));
        $this->assertSame([], $client->searchWorks('second'));
        $this->assertSame(1, $calls);
        $this->assertTrue(OpenAlexScientificClient::isRequestRateLimited());
    }

    private function plan(): KnowledgeQueryPlan
    {
        $query = new AgriculturalKnowledgeQuery(
            originalQuestion: 'beef cattle meat breeds',
            normalizedQuestion: 'beef cattle meat breeds',
            language: 'en',
            agriculturalDomain: 'livestock',
            subject: ['type' => 'topic', 'value' => 'cattle'],
            crop: null,
            cropId: null,
            scientificName: null,
            topic: 'livestock',
            subtopic: null,
            requestedInformation: ['evidence'],
            constraints: [],
            location: null,
            researchRequired: true,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            researchIntent: 'livestock',
        );

        return new KnowledgeQueryPlan(
            normalizedQuery: $query,
            researchIntent: 'livestock',
            agriculturalDomain: 'livestock',
            subjectEntity: ['type' => 'topic', 'value' => 'cattle'],
            topics: ['livestock'],
            subtopics: [],
            requestedInformation: ['evidence'],
            evidenceRequirements: ['peer_reviewed'],
            sourcePriorities: ['openalex', 'crossref', 'semantic_scholar'],
            primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            readyForStage3: true,
        );
    }
}
