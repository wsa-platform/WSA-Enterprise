<?php

namespace Tests\Unit\Agriculture\Research\Onboarding;

use App\Contracts\ScientificSourceAdapterV2Interface;
use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\Capability\CapVer\CapVerSeatVerificationService;
use App\Services\Agriculture\Research\Capability\CapabilityState;
use App\Services\Agriculture\Research\Capability\Persistence\CapabilityStoreRepository;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Onboarding\AcceptedOwnerDecisionCatalog;
use App\Services\Agriculture\Research\Onboarding\CurrentMembershipNearReadySeatCatalog;
use App\Services\Agriculture\Research\Onboarding\NextGateEligibleSeatCatalog;
use App\Services\Agriculture\Research\Path\PathFamily;
use App\Services\Agriculture\Research\Search\Family\Bulk\BulkDatasetReader;
use App\Services\Agriculture\Research\Search\Family\OaiPmh\OaiPmhHarvester;
use App\Services\Agriculture\Research\Search\LocalIndex\InMemoryScientificRecordStore;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalIndexIngestionService;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalIndexScientificSourceAdapter;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalRecordRetentionPolicy;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalScientificIndex;
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
use App\Services\Agriculture\Research\Search\ScientificSourceSearchOutcome;
use App\Services\Agriculture\Research\Search\ScientificSourceSelector;
use DateTimeImmutable;
use Mockery;
use RuntimeException;
use Tests\TestCase;

/**
 * Bounded AGRIS + limited Duke: owner scope ACCEPT → CapVer → governed catalog → orchestrator.
 */
class AgrisAndDrDukeGovernedActivationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        NextGateEligibleSeatCatalog::swap(null);
        CurrentMembershipNearReadySeatCatalog::swap(null);
        AcceptedOwnerDecisionCatalog::swap(null);
        ScientificSourceCatalog::forgetDefault();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        NextGateEligibleSeatCatalog::swap(null);
        CurrentMembershipNearReadySeatCatalog::swap(null);
        AcceptedOwnerDecisionCatalog::swap(null);
        ScientificSourceCatalog::forgetDefault();
        parent::tearDown();
    }

    public function test_owner_scope_and_capver_authorize_bounded_agris_and_limited_duke(): void
    {
        $this->assertTrue(AcceptedOwnerDecisionCatalog::onboardingAccepted('G1-01'));
        $this->assertTrue(AcceptedOwnerDecisionCatalog::onboardingAccepted('G1-17'));
        $this->assertTrue(AcceptedOwnerDecisionCatalog::capVerAuthorized('G1-01'));
        $this->assertTrue(AcceptedOwnerDecisionCatalog::capVerAuthorized('G1-17'));

        $store = Mockery::mock(CapabilityStoreRepository::class);
        $store->allows('findCurrentFact')->andReturn(null);
        $store->shouldReceive('persistCurrent')->twice()->andReturnUsing(function ($record, $refs, $assessment) {
            $this->assertSame(CapabilityState::VERIFIED, $record->state);
            $this->assertNotEmpty($refs);
            $this->assertNotNull($assessment);

            return \App\Services\Agriculture\Research\Capability\Persistence\CapabilityCellFact::forWrite($record, $refs);
        });

        $service = new CapVerSeatVerificationService($store);
        foreach (['G1-01', 'G1-17'] as $adrId) {
            $outcome = $service->execute($adrId);
            $this->assertTrue($outcome->prerequisitesSatisfied, $adrId);
            $this->assertTrue($outcome->contractVerifiedAuthored, $adrId);
            $this->assertSame('PERSISTED', $outcome->capabilityStoreStatus, $adrId.' '.$outcome->capabilityStoreDetail);
            $this->assertTrue($outcome->capVerPassedForActivation, $adrId);
        }
    }

    public function test_capver_reports_store_unavailable_without_fabricating_persistence(): void
    {
        $store = Mockery::mock(CapabilityStoreRepository::class);
        $store->shouldReceive('findCurrentFact')->once()->andThrow(new RuntimeException('could not find driver'));
        $outcome = (new CapVerSeatVerificationService($store))->execute('G1-01');
        $this->assertSame('STORE_UNAVAILABLE', $outcome->capabilityStoreStatus);
        $this->assertTrue($outcome->contractVerifiedAuthored);
        $this->assertFalse($outcome->capVerPassedForActivation, 'STORE_UNAVAILABLE must never count as CapVer activation-pass');
    }

    public function test_agris_fixture_reaches_orchestrator_on_governed_path(): void
    {
        $seat = NextGateEligibleSeatCatalog::get('G1-01');
        $this->assertTrue($seat?->isRuntimeActive());

        $store = new InMemoryScientificRecordStore();
        $index = new LocalScientificIndex();
        $ingestion = new LocalIndexIngestionService(
            $store,
            $index,
            new LocalRecordRetentionPolicy(),
            new OaiPmhHarvester(),
            new BulkDatasetReader(),
        );
        $ingestion->ingestSnapshot(
            $seat->familyProfile,
            base_path('tests/Fixtures/ScientificSourceFamilies/next_gate_agris_fixture_v1.jsonl'),
            new DateTimeImmutable('2026-10-18T17:00:00+00:00'),
        );

        $registration = new ScientificSourceRegistration(
            sourceKey: 'agris',
            adapterClass: LocalIndexScientificSourceAdapter::class,
            adapterContractVersion: ScientificSourceAdapterV2Interface::V2_CONTRACT_VERSION,
            lifecycle: ScientificSourceLifecycle::GOVERNED_ONBOARDED,
            accessFamily: ScientificSourceAccessFamily::BULK_DATASET,
            selectionRole: ScientificSourceSelectionRole::GOVERNED_EXPANSION,
            executionGroup: ScientificSourceExecutionGroup::INDEPENDENT_SCHOLARLY,
            enablement: new AlwaysEnabledSourceEnablement(),
            pathFamily: PathFamily::P12,
            adrId: \App\Services\Agriculture\Research\Identity\AdrMembershipId::fromString('G1-01'),
            capabilities: [
                new ScientificSourceCapabilityDeclaration(
                    ScientificSourceOperation::SEARCH,
                    ScientificSourceCapabilityStatus::VERIFIED_SUPPORTED,
                    'test:fixture agris bounded',
                    verifiedAt: '2026-10-18T17:00:00+00:00',
                    expiresAt: '2027-10-18T17:00:00+00:00',
                ),
            ],
            familyProfile: $seat->familyProfile,
        );

        app()->instance(LocalScientificIndex::class, $index);
        app()->instance(\App\Services\Agriculture\Research\Search\LocalIndex\ScientificRecordStore::class, $store);

        $catalog = new ScientificSourceCatalog([$registration]);
        $csqQuery = 'Deficit irrigation effects on wheat yield';
        $builder = Mockery::mock(ScientificSearchQueryBuilder::class);
        $builder->shouldReceive('buildVariantsFromPlan')->andReturn([$csqQuery]);
        $builder->shouldReceive('buildFromPlan')->andReturn($csqQuery);
        $builder->shouldReceive('buildConsensusRequestOptions')->andReturn([]);

        $orchestrator = new MultiSourceScientificSearchOrchestrator(
            new ScientificSourceAdapterRegistry(app(), $catalog),
            new ScientificSourceSelector($catalog),
            $builder,
            app(ScientificResultDeduplicator::class),
            app(ScientificResultRanker::class),
            catalog: $catalog,
        );

        $query = new AgriculturalKnowledgeQuery(
            originalQuestion: $csqQuery,
            normalizedQuestion: $csqQuery,
            language: 'en',
            agriculturalDomain: 'agronomy',
            subject: ['type' => 'topic', 'value' => 'irrigation'],
            crop: null,
            cropId: null,
            scientificName: null,
            topic: 'irrigation',
            subtopic: 'wheat',
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
            subjectEntity: ['type' => 'topic', 'value' => 'irrigation'],
            topics: ['irrigation', 'wheat'],
            subtopics: [],
            requestedInformation: ['overview'],
            evidenceRequirements: ['peer_reviewed'],
            sourcePriorities: ['agris'],
            primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            contextInput: [],
            readyForStage3: true,
        );

        $report = $orchestrator->execute($plan, 5, ['agris']);
        $this->assertContains('agris', $report->successfulSources);
        $this->assertSame('agris', $report->results[0]->sourceKey);
        $this->assertNotNull($report->results[0]->primaryProvenance());
        $this->assertSame(ScientificSourceSearchOutcome::STATUS_SUCCESS, (new LocalIndexScientificSourceAdapter(
            $seat->familyProfile,
            $index,
            $store,
        ))->search($csqQuery, 5)->status);
    }

    public function test_dr_duke_fixture_preserves_identity_on_governed_path(): void
    {
        $seat = NextGateEligibleSeatCatalog::get('G1-17');
        $this->assertTrue($seat?->isRuntimeActive());
        $store = new InMemoryScientificRecordStore();
        $index = new LocalScientificIndex();
        (new LocalIndexIngestionService(
            $store,
            $index,
            new LocalRecordRetentionPolicy(),
            new OaiPmhHarvester(),
            new BulkDatasetReader(),
        ))->ingestSnapshot(
            $seat->familyProfile,
            base_path('tests/Fixtures/ScientificSourceFamilies/next_gate_dr_duke_fixture_v1.jsonl'),
            new DateTimeImmutable('2026-10-18T17:00:00+00:00'),
        );

        $outcome = (new LocalIndexScientificSourceAdapter($seat->familyProfile, $index, $store))
            ->search('Mentha piperita', 5);
        $this->assertSame(ScientificSourceSearchOutcome::STATUS_SUCCESS, $outcome->status);
        $this->assertSame('dr_duke', $outcome->results[0]->sourceKey);
        $this->assertSame('drduke-fixture-1', (string) $outcome->results[0]->sourceIdentifier);
        $this->assertNotNull($outcome->results[0]->primaryProvenance());
        $this->assertTrue(ScientificSourceCatalog::default()->has('dr_duke'));
        $this->assertTrue(ScientificSourceCatalog::default()->has('organic_eprints'));
        $this->assertTrue(ScientificSourceCatalog::default()->has('bhl'));
        $this->assertTrue(ScientificSourceCatalog::default()->has('gbif'));
    }
}
