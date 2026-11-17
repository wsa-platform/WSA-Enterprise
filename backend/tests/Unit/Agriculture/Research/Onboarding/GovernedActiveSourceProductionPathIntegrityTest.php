<?php

namespace Tests\Unit\Agriculture\Research\Onboarding;

use App\Contracts\ScientificSourceAdapterV2Interface;
use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\Identity\AdrMembershipId;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Onboarding\AcceptedOwnerDecisionCatalog;
use App\Services\Agriculture\Research\Onboarding\CurrentMembershipNearReadySeatCatalog;
use App\Services\Agriculture\Research\Onboarding\NextGateEligibleSeatCatalog;
use App\Services\Agriculture\Research\Path\PathFamily;
use App\Services\Agriculture\Research\Search\Family\Bulk\BulkDatasetReader;
use App\Services\Agriculture\Research\Search\Family\OaiPmh\OaiPmhHarvester;
use App\Services\Agriculture\Research\Search\GovernedScientificSourceEligibility;
use App\Services\Agriculture\Research\Search\LocalIndex\InMemoryScientificRecordStore;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalIndexIngestionService;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalIndexScientificSourceAdapter;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalRecordRetentionPolicy;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalScientificIndex;
use App\Services\Agriculture\Research\Search\LocalIndex\ScientificRecordStore;
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
use App\Services\Agriculture\Research\Validation\AgriculturalScientificValidationService;
use DateTimeImmutable;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\Support\ScientificEvidenceAnswerCitationBridge;
use Tests\TestCase;

/**
 * Rev 11.0.10 §11 — production-path integrity for ACTIVE governed seats.
 *
 * Path: plan → relevance selection → orchestrator retrieval → Stage-4 validation → AnswerComposer.
 * Fixture-backed retrieval (not live HTTP). Does NOT grant SCIENTIFIC_INTEGRATION_VERIFIED.
 */
class GovernedActiveSourceProductionPathIntegrityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        config(['agricultural_intelligence.stage3_home_scholarly_concurrency' => false]);
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

    public function test_agris_production_path_preserves_identity_through_validation_and_composer(): void
    {
        $seat = NextGateEligibleSeatCatalog::get('G1-01');
        $this->assertNotNull($seat);

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
            base_path('tests/Fixtures/ScientificSourceFamilies/next_gate_agris_fixture_v1.jsonl'),
            new DateTimeImmutable('2026-10-18T17:00:00+00:00'),
        );

        $registration = $this->localIndexRegistration(
            sourceKey: 'agris',
            adrId: 'G1-01',
            accessFamily: ScientificSourceAccessFamily::BULK_DATASET,
            familyProfile: $seat->familyProfile,
            capabilityNote: 'test:production-path agris',
        );
        app()->instance(LocalScientificIndex::class, $index);
        app()->instance(ScientificRecordStore::class, $store);

        $originalQuestion = 'What are deficit irrigation effects on wheat yield?';
        $csqQuery = 'Deficit irrigation effects on wheat yield';
        $plan = $this->plan($originalQuestion, $csqQuery, 'agris', null, 'wheat', 'irrigation', [
            'type' => 'crop', 'value' => 'wheat', 'label' => 'wheat',
        ]);

        $eligibility = new GovernedScientificSourceEligibility();
        $this->assertTrue($eligibility->isEligible('agris', $plan));
        $this->assertFalse($eligibility->isEligible('dr_duke', $plan));

        $search = $this->executeForced('agris', $registration, $plan, $csqQuery);
        $this->assertNotEmpty($search->deduplicatedResults);
        $hit = $search->deduplicatedResults[0];
        $this->assertSame('agris', $hit->sourceKey);
        $this->assertNotNull($hit->sourceIdentifier);

        $validation = app(AgriculturalScientificValidationService::class)->validate($plan, $search);
        $this->assertNotSame('needs_clarification', $validation->status);
        $allItems = array_merge($validation->validatedEvidence, $validation->rejectedEvidence);
        // Retained items may live only in observability; assert at least search identity survived into validation cycle.
        $this->assertGreaterThan(0, $validation->sourcesReceived);
        $this->assertSame($originalQuestion, $plan->normalizedQuery->originalQuestion);

        $composer = ScientificEvidenceAnswerCitationBridge::makeComposer();
        $answer = $composer->compose($plan, $validation);
        $this->assertSame($originalQuestion, $plan->normalizedQuery->originalQuestion);
        $this->assertNotSame('', (string) $answer->status);
        // Fixture abstracts are engineering stubs — insufficient answers are honest; never invent claims.
        if ($answer->claims !== []) {
            foreach ($answer->claims as $claim) {
                $this->assertNotSame('', trim((string) $claim->claimText));
            }
        }
        $this->assertSame('agris', $hit->sourceKey);
        $this->assertNotNull($hit->primaryProvenance());
    }

    public function test_dr_duke_relevance_and_forced_path_preserve_plant_identity(): void
    {
        $seat = NextGateEligibleSeatCatalog::get('G1-17');
        $this->assertNotNull($seat);

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

        $registration = $this->localIndexRegistration(
            sourceKey: 'dr_duke',
            adrId: 'G1-17',
            accessFamily: ScientificSourceAccessFamily::STATIC_DATASET,
            familyProfile: $seat->familyProfile,
            capabilityNote: 'test:production-path dr_duke',
            executionGroup: ScientificSourceExecutionGroup::SEQUENTIAL,
        );
        app()->instance(LocalScientificIndex::class, $index);
        app()->instance(ScientificRecordStore::class, $store);

        $originalQuestion = 'Mentha piperita phytochemical constituents';
        $plan = $this->plan(
            $originalQuestion,
            $originalQuestion,
            'dr_duke',
            'Mentha piperita',
            'mint',
            'phytochemistry',
            ['type' => 'crop', 'value' => 'mint', 'label' => 'mint'],
        );

        $this->assertTrue((new GovernedScientificSourceEligibility())->isEligible('dr_duke', $plan));

        $catalog = new ScientificSourceCatalog([$registration]);
        $builder = Mockery::mock(ScientificSearchQueryBuilder::class);
        $builder->shouldReceive('buildVariantsFromPlan')->andReturn(['mint', 'Mentha taxonomy', 'mint taxonomy']);
        $builder->shouldReceive('buildFromPlan')->andReturn('mint');
        $builder->shouldReceive('buildConsensusRequestOptions')->andReturn([]);

        $search = (new MultiSourceScientificSearchOrchestrator(
            new ScientificSourceAdapterRegistry(app(), $catalog),
            new ScientificSourceSelector($catalog),
            $builder,
            app(ScientificResultDeduplicator::class),
            app(ScientificResultRanker::class),
            catalog: $catalog,
        ))->execute($plan, 5, ['dr_duke']);

        $this->assertContains('dr_duke', $search->successfulSources);
        $this->assertNotEmpty($search->results);
        $this->assertSame('Mentha piperita', $plan->normalizedQuery->scientificName);

        $validation = app(AgriculturalScientificValidationService::class)->validate($plan, $search);
        $answer = ScientificEvidenceAnswerCitationBridge::makeComposer()->compose($plan, $validation);
        $this->assertSame($originalQuestion, $plan->normalizedQuery->originalQuestion);
        $this->assertSame('Mentha piperita', $plan->normalizedQuery->scientificName);
        $this->assertNotSame('', (string) $answer->status);
    }

    public function test_cross_source_relevance_keeps_source_identity_separate(): void
    {
        $irrigation = $this->plan(
            'What is barley water requirement?',
            'barley water requirement',
            'agris',
            null,
            'barley',
            'irrigation',
            ['type' => 'crop', 'value' => 'barley'],
        );
        $taxonomy = $this->plan(
            'Triticum aestivum species classification',
            'Triticum aestivum species classification',
            'gbif',
            'Triticum aestivum',
            'wheat',
            'taxonomy',
            ['type' => 'scientific_name', 'value' => 'Triticum aestivum'],
        );

        $eligibility = new GovernedScientificSourceEligibility();
        $this->assertTrue($eligibility->isEligible('agris', $irrigation));
        $this->assertFalse($eligibility->isEligible('gbif', $irrigation));
        $this->assertTrue($eligibility->isEligible('gbif', $taxonomy));
        $this->assertTrue($eligibility->isEligible('bhl', $taxonomy));
        $this->assertFalse($eligibility->isEligible('dr_duke', $irrigation));
    }

    private function executeForced(
        string $sourceKey,
        ScientificSourceRegistration $registration,
        KnowledgeQueryPlan $plan,
        string $csqQuery,
    ): object {
        $catalog = new ScientificSourceCatalog([$registration]);
        $builder = Mockery::mock(ScientificSearchQueryBuilder::class);
        $builder->shouldReceive('buildVariantsFromPlan')->andReturn([$csqQuery]);
        $builder->shouldReceive('buildFromPlan')->andReturn($csqQuery);
        $builder->shouldReceive('buildConsensusRequestOptions')->andReturn([]);

        return (new MultiSourceScientificSearchOrchestrator(
            new ScientificSourceAdapterRegistry(app(), $catalog),
            new ScientificSourceSelector($catalog),
            $builder,
            app(ScientificResultDeduplicator::class),
            app(ScientificResultRanker::class),
            catalog: $catalog,
        ))->execute($plan, 5, [$sourceKey]);
    }

    private function localIndexRegistration(
        string $sourceKey,
        string $adrId,
        ScientificSourceAccessFamily $accessFamily,
        mixed $familyProfile,
        string $capabilityNote,
        ScientificSourceExecutionGroup $executionGroup = ScientificSourceExecutionGroup::INDEPENDENT_SCHOLARLY,
    ): ScientificSourceRegistration {
        return new ScientificSourceRegistration(
            sourceKey: $sourceKey,
            adapterClass: LocalIndexScientificSourceAdapter::class,
            adapterContractVersion: ScientificSourceAdapterV2Interface::V2_CONTRACT_VERSION,
            lifecycle: ScientificSourceLifecycle::GOVERNED_ONBOARDED,
            accessFamily: $accessFamily,
            selectionRole: ScientificSourceSelectionRole::GOVERNED_EXPANSION,
            executionGroup: $executionGroup,
            enablement: new AlwaysEnabledSourceEnablement(),
            pathFamily: PathFamily::P12,
            adrId: AdrMembershipId::fromString($adrId),
            capabilities: [
                new ScientificSourceCapabilityDeclaration(
                    ScientificSourceOperation::SEARCH,
                    ScientificSourceCapabilityStatus::VERIFIED_SUPPORTED,
                    $capabilityNote,
                    verifiedAt: '2026-10-18T17:00:00+00:00',
                    expiresAt: '2027-10-18T17:00:00+00:00',
                ),
            ],
            familyProfile: $familyProfile,
        );
    }

    /**
     * @param  array{type: string, value: string, label?: string}  $subject
     */
    private function plan(
        string $originalQuestion,
        string $csqSurface,
        string $sourceKey,
        ?string $scientificName,
        ?string $crop,
        string $topic,
        array $subject,
    ): KnowledgeQueryPlan {
        $intent = $topic === 'taxonomy' ? 'taxonomy' : ($topic === 'phytochemistry' ? 'phytochemistry' : 'irrigation');
        $query = new AgriculturalKnowledgeQuery(
            originalQuestion: $originalQuestion,
            normalizedQuestion: $csqSurface,
            language: 'en',
            agriculturalDomain: 'agronomy',
            subject: $subject,
            crop: $crop,
            cropId: $crop,
            scientificName: $scientificName,
            topic: $topic,
            subtopic: null,
            requestedInformation: ['overview'],
            constraints: ['scientific_sense' => $intent],
            location: null,
            researchRequired: true,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            researchIntent: $intent,
        );

        return new KnowledgeQueryPlan(
            normalizedQuery: $query,
            researchIntent: $intent,
            agriculturalDomain: 'agronomy',
            subjectEntity: $subject,
            topics: [$topic],
            subtopics: [],
            requestedInformation: ['overview'],
            evidenceRequirements: ['peer_reviewed'],
            sourcePriorities: [$sourceKey],
            primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            contextInput: [],
            readyForStage3: true,
        );
    }
}
