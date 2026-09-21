<?php

namespace Tests\Unit\Agriculture\Research\Search;

use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatSearchOptionsResolver;
use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Search\AgriculturalScientificSearchService;
use App\Services\Agriculture\Research\Search\ScientificSearchQueryBuilder;
use App\Services\Agriculture\Research\Search\ScientificSearchTimeBudget;
use App\Services\Agriculture\Research\Search\ScientificSourceSelector;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ScientificProviderContractIsolationTest extends TestCase
{
    public function test_time_budget_reports_exhausted_when_deadline_has_passed(): void
    {
        $budget = new ScientificSearchTimeBudget(0, 1);
        $this->assertFalse($budget->remaining());
        $this->assertSame(0.0, $budget->remainingSeconds());
        $this->assertGreaterThanOrEqual(8, ScientificSearchTimeBudget::configuredSeconds());
        $this->assertLessThanOrEqual(90, ScientificSearchTimeBudget::configuredSeconds());
    }

    public function test_selector_places_faostat_before_scholarly_sources(): void
    {
        config(['agricultural_intelligence.faostat.enabled' => true]);
        $sources = app(ScientificSourceSelector::class)->selectSources($this->plan());
        $this->assertSame('fao_stat', $sources[0] ?? null);
        $this->assertContains('openalex', $sources);
        $this->assertContains('crossref', $sources);
        $this->assertContains('semantic_scholar', $sources);
    }

    public function test_faostat_options_do_not_leak_scholarly_parameters_and_vice_versa(): void
    {
        $plan = $this->plan([
            'item' => '56',
            'area' => '21',
            'element' => '2510',
            'year' => '2019',
            'domain' => 'QCL',
        ]);
        $faostat = FaoStatSearchOptionsResolver::fromPlan($plan);
        $scholarly = app(ScientificSearchQueryBuilder::class)->buildConsensusRequestOptions($plan);

        $this->assertSame('QCL', $faostat['domain']);
        $this->assertArrayNotHasKey('page_size', $faostat);
        $this->assertSame('56', $faostat['item']);
        $this->assertNotSame('agri', strtolower((string) ($faostat['domain'] ?? '')));

        $this->assertSame('agri', $scholarly['domain'] ?? null);
        $this->assertArrayNotHasKey('item', $scholarly);
        $this->assertArrayNotHasKey('area', $scholarly);
        $this->assertArrayNotHasKey('element', $scholarly);
        $this->assertArrayNotHasKey('year', $scholarly);
        $this->assertArrayNotHasKey('item_code', $scholarly);
    }

    public function test_one_provider_failure_does_not_drop_other_sources(): void
    {
        config([
            'agricultural_intelligence.faostat.enabled' => true,
            'agricultural_intelligence.faostat.username' => 'portal-user',
            'agricultural_intelligence.faostat.password' => 'portal-pass',
            'agricultural_intelligence.openalex.enabled' => true,
            'agricultural_intelligence.crossref.enabled' => false,
            'agricultural_intelligence.semantic_scholar.enabled' => false,
        ]);
        Http::fake([
            'faostatservices.fao.org/api/v1/auth/login' => Http::response(['message' => 'no'], 400),
            'api.openalex.org/*' => Http::response([
                'results' => [[
                    'id' => 'https://openalex.org/W1',
                    'display_name' => 'Barley irrigation water requirement',
                    'publication_year' => 2021,
                    'doi' => '10.1000/barley-water',
                    'authorships' => [['author' => ['display_name' => 'A Researcher']]],
                    'primary_location' => ['source' => ['display_name' => 'Ag Journal'], 'landing_page_url' => 'https://example.test/p'],
                    'abstract_inverted_index' => ['Irrigation' => [0], 'requirement' => [1]],
                ]],
            ], 200),
        ]);

        $report = app(AgriculturalScientificSearchService::class)->search($this->plan([
            'requested_property_key' => 'quantity',
            'scientific_sense' => 'crop_water_requirement',
            'requested_property_surface' => 'water requirement',
        ]), 5, ['fao_stat', 'openalex']);

        $this->assertContains('openalex', $report->successfulSources);
        $this->assertNotContains('openalex', $report->failedSources);
        // Phase 3-B: non-statistical plans execute scholarly before FAOSTAT consideration.
        $this->assertContains('fao_stat', array_merge(
            $report->attemptedSources,
            $report->failedSources,
            $report->emptySources,
            $report->selectedSources,
        ));
        $this->assertSame('openalex', $report->attemptedSources[0] ?? null);
        $this->assertArrayHasKey('search_time_budget_seconds', $report->planSummary);
        $this->assertArrayHasKey('search_observability', $report->planSummary);
    }

    /**
     * @param  array<string, mixed>  $constraints
     */
    private function plan(array $constraints = []): KnowledgeQueryPlan
    {
        $query = new AgriculturalKnowledgeQuery(
            originalQuestion: 'barley water requirement',
            normalizedQuestion: 'barley water requirement',
            language: 'en',
            agriculturalDomain: 'agronomy',
            subject: ['type' => 'crop', 'value' => 'barley'],
            crop: 'barley',
            cropId: 'barley',
            scientificName: null,
            topic: 'irrigation',
            subtopic: null,
            requestedInformation: ['evidence'],
            constraints: array_merge([
                'question_type' => 'quantity',
                'scientific_sense' => 'crop_water_requirement',
            ], $constraints),
            location: 'brazil',
            researchRequired: true,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            researchIntent: 'irrigation',
        );

        return new KnowledgeQueryPlan(
            normalizedQuery: $query,
            researchIntent: 'irrigation',
            agriculturalDomain: 'agronomy',
            subjectEntity: ['type' => 'crop', 'value' => 'barley'],
            topics: ['irrigation'],
            subtopics: [],
            requestedInformation: ['evidence'],
            evidenceRequirements: ['peer_reviewed'],
            sourcePriorities: ['fao_stat', 'openalex'],
            primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            readyForStage3: true,
        );
    }
}
