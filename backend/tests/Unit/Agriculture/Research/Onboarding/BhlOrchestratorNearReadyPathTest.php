<?php

namespace Tests\Unit\Agriculture\Research\Onboarding;

use App\Contracts\ScientificSourceAdapterV2Interface;
use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Onboarding\CurrentMembershipNearReadySeatCatalog;
use App\Services\Agriculture\Research\Path\PathFamily;
use App\Services\Agriculture\Research\Search\Family\Rest\RestJsonScientificSourceAdapter;
use App\Services\Agriculture\Research\Search\MultiSourceScientificSearchOrchestrator;
use App\Services\Agriculture\Research\Search\Registry\AlwaysEnabledSourceEnablement;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceAccessFamily;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceCapabilityDeclaration;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceCapabilityStatus;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceCatalog;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceExecutionGroup;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceLifecycle;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceOperation;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceRegistration;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceSelectionRole;
use App\Services\Agriculture\Research\Search\ScientificResultDeduplicator;
use App\Services\Agriculture\Research\Search\ScientificResultRanker;
use App\Services\Agriculture\Research\Search\ScientificSearchQueryBuilder;
use App\Services\Agriculture\Research\Search\ScientificSourceAdapterRegistry;
use App\Services\Agriculture\Research\Search\ScientificSourceSelector;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

/**
 * G6-20: CSQ-derived query through orchestrator.
 * Uses an ephemeral LEGACY registration to isolate adapter wiring; production
 * catalog may also list bhl after owner ACCEPT (asserted separately).
 */
class BhlOrchestratorNearReadyPathTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        CurrentMembershipNearReadySeatCatalog::swap(null);
        ScientificSourceCatalog::forgetDefault();
    }

    protected function tearDown(): void
    {
        CurrentMembershipNearReadySeatCatalog::swap(null);
        ScientificSourceCatalog::forgetDefault();
        parent::tearDown();
    }

    public function test_csq_query_reaches_bhl_adapter_with_fixture_results_without_activation(): void
    {
        config(['agricultural_intelligence.bhl.api_key' => 'test-fixture-key-not-a-secret']);
        Http::fake([
            'https://www.biodiversitylibrary.org/api3*' => Http::response(
                (string) file_get_contents(base_path('tests/Fixtures/ScientificSourceFamilies/bhl_publication_search_fixture_v1.json')),
                200,
            ),
        ]);

        $seat = CurrentMembershipNearReadySeatCatalog::get('G6-20');
        $this->assertNotNull($seat);
        $this->assertTrue($seat->isRuntimeActive());
        $this->assertTrue(ScientificSourceCatalog::default()->has('bhl'));

        $registration = new ScientificSourceRegistration(
            sourceKey: 'bhl',
            adapterClass: RestJsonScientificSourceAdapter::class,
            adapterContractVersion: ScientificSourceAdapterV2Interface::V2_CONTRACT_VERSION,
            lifecycle: ScientificSourceLifecycle::LEGACY_STAGE3,
            accessFamily: ScientificSourceAccessFamily::REST_API,
            selectionRole: ScientificSourceSelectionRole::INTERNET_FIRST_DEFAULT,
            executionGroup: ScientificSourceExecutionGroup::INDEPENDENT_SCHOLARLY,
            enablement: new AlwaysEnabledSourceEnablement(),
            pathFamily: PathFamily::P12,
            capabilities: [
                new ScientificSourceCapabilityDeclaration(
                    ScientificSourceOperation::SEARCH,
                    ScientificSourceCapabilityStatus::VERIFIED_SUPPORTED,
                    'test:fixture BHL PublicationSearch only',
                    verifiedAt: '2026-10-18T00:00:00+00:00',
                    expiresAt: '2027-10-18T00:00:00+00:00',
                ),
            ],
            familyProfile: $seat->familyProfile,
        );

        $catalog = new ScientificSourceCatalog([$registration]);
        $csqQuery = 'Triticum rust botanical literature';
        $builder = Mockery::mock(ScientificSearchQueryBuilder::class);
        $builder->shouldReceive('buildVariantsFromPlan')->andReturn([$csqQuery]);
        $builder->shouldReceive('buildFromPlan')->andReturn($csqQuery);
        $builder->shouldReceive('buildConsensusRequestOptions')->andReturn([]);

        $registry = new ScientificSourceAdapterRegistry(app(), $catalog);
        $this->assertInstanceOf(RestJsonScientificSourceAdapter::class, $registry->get('bhl'));

        $query = new AgriculturalKnowledgeQuery(
            originalQuestion: $csqQuery,
            normalizedQuestion: $csqQuery,
            language: 'en',
            agriculturalDomain: 'agronomy',
            subject: ['type' => 'topic', 'value' => 'triticum'],
            crop: null,
            cropId: null,
            scientificName: 'Triticum',
            topic: 'plant_pathology',
            subtopic: 'rust',
            requestedInformation: ['overview'],
            constraints: [],
            location: null,
            researchRequired: true,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            researchIntent: 'literature_review',
        );
        $plan = new KnowledgeQueryPlan(
            normalizedQuery: $query,
            researchIntent: 'literature_review',
            agriculturalDomain: 'agronomy',
            subjectEntity: ['type' => 'topic', 'value' => 'triticum'],
            topics: ['plant_pathology', 'rust'],
            subtopics: [],
            requestedInformation: ['overview'],
            evidenceRequirements: ['peer_reviewed'],
            sourcePriorities: ['bhl'],
            primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            contextInput: [],
            readyForStage3: true,
        );

        $orchestrator = new MultiSourceScientificSearchOrchestrator(
            $registry,
            new ScientificSourceSelector($catalog),
            $builder,
            app(ScientificResultDeduplicator::class),
            app(ScientificResultRanker::class),
            catalog: $catalog,
        );

        $report = $orchestrator->execute($plan, 5, ['bhl']);
        $this->assertSame(['bhl'], $report->attemptedSources);
        $this->assertContains('bhl', $report->successfulSources);
        $this->assertNotEmpty($report->results);
        $this->assertSame('bhl', $report->results[0]->sourceKey);
        $this->assertSame('bhl-fixture-part-1', (string) $report->results[0]->sourceIdentifier);
        $this->assertNotNull($report->results[0]->primaryProvenance());
        $this->assertTrue(CurrentMembershipNearReadySeatCatalog::get('G6-20')?->isRuntimeActive());
        $this->assertTrue(ScientificSourceCatalog::default()->has('bhl'));
    }
}
