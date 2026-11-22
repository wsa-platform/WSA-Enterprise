<?php

namespace Tests\Unit\Agriculture\Research\Search;

use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatSearchResultFilter;
use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\RetrievalSemanticContract;
use App\Services\Agriculture\Research\Search\AgriculturalScientificSearchService;
use App\Services\Agriculture\Research\Search\MultiSourceScientificSearchOrchestrator;
use App\Services\Agriculture\Research\Search\ScientificSearchExecutionReport;
use Mockery;
use Tests\TestCase;

/**
 * Mission 75193: force_execute must not be swallowed by Stage-3 clarification short-circuit.
 */
final class ForceExecuteHonorsStage3GateTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_without_force_execute_clarification_short_circuits(): void
    {
        $plan = $this->clarificationPlan(forceExecute: false);
        $orchestrator = Mockery::mock(MultiSourceScientificSearchOrchestrator::class);
        $orchestrator->shouldReceive('execute')->never();

        $service = new AgriculturalScientificSearchService(
            $orchestrator,
            app(FaoStatSearchResultFilter::class),
            app(RetrievalSemanticContract::class),
        );

        $report = $service->search($plan, 5, ['dr_duke']);
        $this->assertSame('needs_clarification', $report->status);
    }

    public function test_force_execute_invokes_orchestrator_when_plan_needs_clarification(): void
    {
        $plan = $this->clarificationPlan(forceExecute: true);
        $expected = new ScientificSearchExecutionReport(
            status: 'search_completed',
            searchQuery: 'Mentha',
            selectedSources: ['dr_duke'],
            attemptedSources: ['dr_duke'],
            successfulSources: ['dr_duke'],
            failedSources: [],
            emptySources: [],
            sourceOutcomes: [],
            results: [],
            deduplicatedResults: [],
            planSummary: [],
            internetFirst: true,
        );

        $orchestrator = Mockery::mock(MultiSourceScientificSearchOrchestrator::class);
        $orchestrator->shouldReceive('execute')->once()->andReturn($expected);

        $service = new AgriculturalScientificSearchService(
            $orchestrator,
            app(FaoStatSearchResultFilter::class),
            app(RetrievalSemanticContract::class),
        );

        $report = $service->search($plan, 5, ['dr_duke']);
        $this->assertSame('search_completed', $report->status);
        $this->assertSame(['dr_duke'], $report->successfulSources);
    }

    private function clarificationPlan(bool $forceExecute): KnowledgeQueryPlan
    {
        $query = new AgriculturalKnowledgeQuery(
            originalQuestion: 'Mentha',
            normalizedQuestion: 'mentha',
            language: 'en',
            agriculturalDomain: 'crop',
            subject: null,
            crop: null,
            cropId: null,
            scientificName: null,
            topic: 'Mentha',
            subtopic: null,
            requestedInformation: [],
            constraints: [],
            location: null,
            researchRequired: false,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_NEEDS_CLARIFICATION,
            clarificationRequirements: ['entity'],
            researchIntent: 'scientific_research',
        );

        return new KnowledgeQueryPlan(
            normalizedQuery: $query,
            researchIntent: 'scientific_research',
            agriculturalDomain: 'crop',
            subjectEntity: null,
            topics: ['Mentha'],
            subtopics: [],
            requestedInformation: [],
            evidenceRequirements: [],
            sourcePriorities: [],
            primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_NEEDS_CLARIFICATION,
            clarificationRequirements: ['entity'],
            contextInput: $forceExecute ? ['force_execute' => true] : [],
            readyForStage3: false,
        );
    }
}
