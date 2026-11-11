<?php

namespace Tests\Unit\Agriculture\Research\Onboarding;

use App\Contracts\ScientificSourceAdapterV2Interface;
use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Onboarding\CurrentMembershipNearReadySeatCatalog;
use App\Services\Agriculture\Research\Onboarding\SeatActivationState;
use App\Services\Agriculture\Research\Path\PathFamily;
use App\Services\Agriculture\Research\Search\Contract\ScientificSourceUsage;
use App\Services\Agriculture\Research\Search\Family\Bulk\BulkDatasetReader;
use App\Services\Agriculture\Research\Search\Family\OaiPmh\OaiPmhHarvester;
use App\Services\Agriculture\Research\Search\LocalIndex\InMemoryScientificRecordStore;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalIndexIngestionService;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalIndexScientificSourceAdapter;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalIngestionReport;
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
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

/**
 * G1-02 Organic Eprints: ListRecords harvest → LocalIndex → orchestrator search.
 * Does not activate: production index remains unpublished / empty outside fixtures.
 */
class OrganicEprintsOaiLocalIndexPathTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        config(['agricultural_intelligence.stage3_home_scholarly_concurrency' => false]);
        CurrentMembershipNearReadySeatCatalog::swap(null);
        ScientificSourceCatalog::forgetDefault();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        CurrentMembershipNearReadySeatCatalog::swap(null);
        ScientificSourceCatalog::forgetDefault();
        parent::tearDown();
    }

    public function test_seat_is_runtime_active_and_production_catalog_includes_governed_registration(): void
    {
        $seat = CurrentMembershipNearReadySeatCatalog::get('G1-02');
        $this->assertNotNull($seat);
        $this->assertSame(SeatActivationState::ACTIVE, $seat->intendedActivation);
        $this->assertTrue($seat->isRuntimeActive());
        $this->assertCount(2, CurrentMembershipNearReadySeatCatalog::activatedRegistrations());
        $this->assertTrue(ScientificSourceCatalog::default()->has('organic_eprints'));
        $this->assertTrue($seat->familyProfile->licensing()->permits(ScientificSourceUsage::RETRIEVAL));
        $this->assertTrue($seat->familyProfile->licensing()->permits(ScientificSourceUsage::METADATA_STORAGE));
        $this->assertFalse($seat->familyProfile->licensing()->permits(ScientificSourceUsage::REDISTRIBUTION));
        $this->assertSame('https://orgprints.org/{local_id}', $seat->familyProfile->toArray()['record_url_template']);
    }

    public function test_paginated_harvest_maps_stable_ids_provenance_and_publishes_local_index(): void
    {
        $seat = CurrentMembershipNearReadySeatCatalog::get('G1-02');
        $this->assertNotNull($seat?->familyProfile);

        Http::fake(function (Request $request) {
            $url = $request->url();
            parse_str((string) parse_url($url, PHP_URL_QUERY), $q);
            if (($q['resumptionToken'] ?? null) !== null) {
                return Http::response(self::fixture('organic_eprints_list_records_page2.xml'), 200, ['Content-Type' => 'text/xml']);
            }

            return Http::response(self::fixture('organic_eprints_list_records_page1.xml'), 200, ['Content-Type' => 'text/xml']);
        });

        $store = new InMemoryScientificRecordStore();
        $index = new LocalScientificIndex();
        $ingestion = new LocalIndexIngestionService(
            $store,
            $index,
            new LocalRecordRetentionPolicy(),
            new OaiPmhHarvester(),
            new BulkDatasetReader(),
        );

        $paused = $ingestion->harvestOaiPmh($seat->familyProfile, new DateTimeImmutable('2026-10-17T20:45:00+00:00'), maxPages: 1);
        $this->assertSame(LocalIngestionReport::PAUSED, $paused->status);
        $this->assertGreaterThan(0, $store->generation('organic_eprints'));

        $completed = $ingestion->harvestOaiPmh($seat->familyProfile, new DateTimeImmutable('2026-10-17T20:46:00+00:00'), maxPages: 5);
        $this->assertSame(LocalIngestionReport::COMPLETED, $completed->status);

        $adapter = new LocalIndexScientificSourceAdapter($seat->familyProfile, $index, $store);
        $hit = $adapter->search('nutritional value plant foods', 5);
        $this->assertSame(ScientificSourceSearchOutcome::STATUS_SUCCESS, $hit->status);
        $this->assertSame('organic_eprints', $hit->results[0]->sourceKey);
        $this->assertSame('oai:orgprints.org:116', (string) $hit->results[0]->sourceIdentifier);
        $this->assertSame('https://orgprints.org/116', $hit->results[0]->canonicalUrl);
        $provenance = $hit->results[0]->primaryProvenance();
        $this->assertNotNull($provenance);
        $this->assertSame('oai:orgprints.org:116', $provenance->originalIdentifier);
        $this->assertSame('header.identifier', $provenance->originalIdentifierField);
        $this->assertSame('https://orgprints.org/116', $provenance->recordUrl);
        $this->assertSame(OaiPmhHarvester::class, $provenance->adapterClass);
        $this->assertSame('oai_pmh', $provenance->accessFamily);

        $env = $adapter->search('Organic Agriculture and the Environment', 5);
        $this->assertSame(ScientificSourceSearchOutcome::STATUS_SUCCESS, $env->status);
        $this->assertSame('oai:orgprints.org:573', (string) $env->results[0]->sourceIdentifier);

        Http::assertSentCount(2);
        Http::assertSent(static function (Request $request): bool {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $q);

            return ($q['verb'] ?? null) === 'ListRecords'
                && ($q['metadataPrefix'] ?? null) === 'oai_dc'
                && ! isset($q['resumptionToken']);
        });
        Http::assertSent(static function (Request $request): bool {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $q);

            return ($q['verb'] ?? null) === 'ListRecords'
                && isset($q['resumptionToken'])
                && ! isset($q['metadataPrefix']);
        });
    }

    public function test_no_records_match_and_http_errors_fail_closed_without_activation(): void
    {
        $seat = CurrentMembershipNearReadySeatCatalog::get('G1-02');
        $this->assertNotNull($seat?->familyProfile);

        Http::fake([
            'https://orgprints.org/cgi/oai2*' => Http::sequence()
                ->push(self::fixture('organic_eprints_no_records.xml'), 200, ['Content-Type' => 'text/xml'])
                ->push('gateway timeout', 504),
        ]);

        $store = new InMemoryScientificRecordStore();
        $index = new LocalScientificIndex();
        $ingestion = new LocalIndexIngestionService(
            $store,
            $index,
            new LocalRecordRetentionPolicy(),
            new OaiPmhHarvester(),
            new BulkDatasetReader(),
        );

        $empty = $ingestion->harvestOaiPmh($seat->familyProfile, new DateTimeImmutable('2026-10-17T20:46:00+00:00'), maxPages: 1);
        $this->assertSame(LocalIngestionReport::COMPLETED, $empty->status);

        $failed = $ingestion->harvestOaiPmh($seat->familyProfile, new DateTimeImmutable('2026-10-17T20:47:00+00:00'), maxPages: 1);
        $this->assertSame(LocalIngestionReport::FAILED, $failed->status);

        $this->assertTrue($seat->isRuntimeActive());
        $unavailable = (new LocalIndexScientificSourceAdapter(
            $seat->familyProfile,
            new LocalScientificIndex(),
            new InMemoryScientificRecordStore(),
        ))->search('organic', 5);
        $this->assertSame(ScientificSourceSearchOutcome::STATUS_UNAVAILABLE, $unavailable->status);
        $this->assertSame('local_index_not_built', $unavailable->error);
    }

    public function test_seeded_local_index_is_reachable_through_orchestrator_with_csq_query_text(): void
    {
        $seat = CurrentMembershipNearReadySeatCatalog::get('G1-02');
        $this->assertNotNull($seat?->familyProfile);

        Http::fake([
            'https://orgprints.org/cgi/oai2*' => Http::response(self::fixture('organic_eprints_list_records_page2.xml'), 200, ['Content-Type' => 'text/xml']),
        ]);

        $store = new InMemoryScientificRecordStore();
        $index = new LocalScientificIndex();
        $ingestion = new LocalIndexIngestionService(
            $store,
            $index,
            new LocalRecordRetentionPolicy(),
            new OaiPmhHarvester(),
            new BulkDatasetReader(),
        );
        $ingestion->harvestOaiPmh($seat->familyProfile, new DateTimeImmutable('2026-10-17T20:45:00+00:00'), maxPages: 1);

        // Isolated LEGACY registration + seeded in-memory store for orchestrator path proof
        // (production GOVERNED catalog may also list organic_eprints after owner ACCEPT).
        $registration = new ScientificSourceRegistration(
            sourceKey: 'organic_eprints',
            adapterClass: LocalIndexScientificSourceAdapter::class,
            adapterContractVersion: ScientificSourceAdapterV2Interface::V2_CONTRACT_VERSION,
            lifecycle: ScientificSourceLifecycle::LEGACY_STAGE3,
            accessFamily: ScientificSourceAccessFamily::OAI_PMH,
            selectionRole: ScientificSourceSelectionRole::INTERNET_FIRST_DEFAULT,
            executionGroup: ScientificSourceExecutionGroup::INDEPENDENT_SCHOLARLY,
            enablement: new AlwaysEnabledSourceEnablement(),
            pathFamily: PathFamily::P12,
            capabilities: [
                new ScientificSourceCapabilityDeclaration(
                    ScientificSourceOperation::SEARCH,
                    ScientificSourceCapabilityStatus::VERIFIED_SUPPORTED,
                    'test:fixture local index only',
                    verifiedAt: '2026-10-17T20:00:00+00:00',
                    expiresAt: '2027-10-17T20:00:00+00:00',
                ),
            ],
            familyProfile: $seat->familyProfile,
        );

        app()->instance(LocalScientificIndex::class, $index);
        app()->instance(\App\Services\Agriculture\Research\Search\LocalIndex\ScientificRecordStore::class, $store);

        $catalog = new ScientificSourceCatalog([$registration]);
        // CSQ-derived query text (builder projection); must match indexed title tokens.
        $csqQuery = 'Organic Agriculture and the Environment';
        $builder = Mockery::mock(ScientificSearchQueryBuilder::class);
        $builder->shouldReceive('buildVariantsFromPlan')->andReturn([$csqQuery]);
        $builder->shouldReceive('buildFromPlan')->andReturn($csqQuery);
        $builder->shouldReceive('buildConsensusRequestOptions')->andReturn([]);

        $direct = (new LocalIndexScientificSourceAdapter($seat->familyProfile, $index, $store))->search($csqQuery, 5);
        $this->assertSame(ScientificSourceSearchOutcome::STATUS_SUCCESS, $direct->status, 'seeded index must hit before orchestrator');
        $this->assertGreaterThan(0, $store->generation('organic_eprints'));

        $registry = new ScientificSourceAdapterRegistry(app(), $catalog);
        $resolved = $registry->get('organic_eprints');
        $this->assertInstanceOf(LocalIndexScientificSourceAdapter::class, $resolved);
        $resolvedHit = $resolved->search($csqQuery, 5);
        $this->assertSame(
            ScientificSourceSearchOutcome::STATUS_SUCCESS,
            $resolvedHit->status,
            'registry-resolved adapter must share the seeded store/index; got '.$resolvedHit->status.' / '.($resolvedHit->error ?? ''),
        );

        $orchestrator = new MultiSourceScientificSearchOrchestrator(
            $registry,
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
            subject: ['type' => 'topic', 'value' => 'organic_agriculture'],
            crop: null,
            cropId: null,
            scientificName: null,
            topic: 'organic_agriculture',
            subtopic: 'environment',
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
            subjectEntity: ['type' => 'topic', 'value' => 'organic_agriculture'],
            topics: ['organic_agriculture', 'environment'],
            subtopics: [],
            requestedInformation: ['overview'],
            evidenceRequirements: ['peer_reviewed'],
            sourcePriorities: ['organic_eprints'],
            primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            contextInput: [],
            readyForStage3: true,
        );

        $report = $orchestrator->execute($plan, 5, ['organic_eprints']);
        $this->assertSame(['organic_eprints'], $report->attemptedSources);
        $this->assertContains('organic_eprints', $report->successfulSources);
        $this->assertNotEmpty($report->results);
        $this->assertSame('organic_eprints', $report->results[0]->sourceKey);
        $this->assertSame('oai:orgprints.org:573', (string) $report->results[0]->sourceIdentifier);
        // Owner ACCEPT + CapVer AUTHORIZE: governed production registration is active.
        $this->assertTrue(CurrentMembershipNearReadySeatCatalog::get('G1-02')?->isRuntimeActive());
        $this->assertTrue(ScientificSourceCatalog::default()->has('organic_eprints'));
    }

    private static function fixture(string $name): string
    {
        return (string) file_get_contents(base_path('tests/Fixtures/ScientificSourceFamilies/'.$name));
    }
}
