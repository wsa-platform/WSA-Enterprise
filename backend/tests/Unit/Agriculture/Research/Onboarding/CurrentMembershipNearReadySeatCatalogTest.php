<?php

namespace Tests\Unit\Agriculture\Research\Onboarding;

use App\Services\Agriculture\Research\Onboarding\AcceptedOnboardingDecisionCatalog;
use App\Services\Agriculture\Research\Onboarding\AcceptedOwnerDecisionCatalog;
use App\Services\Agriculture\Research\Onboarding\CurrentMembershipNearReadySeatCatalog;
use App\Services\Agriculture\Research\Onboarding\SeatActivationState;
use App\Services\Agriculture\Research\Search\Contract\ScientificSourceUsage;
use App\Services\Agriculture\Research\Search\Family\OaiPmh\OaiPmhHarvester;
use App\Services\Agriculture\Research\Search\Family\Rest\RestJsonScientificSourceAdapter;
use App\Services\Agriculture\Research\Search\LocalIndex\InMemoryScientificRecordStore;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalIndexIngestionService;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalIndexScientificSourceAdapter;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalRecordRetentionPolicy;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalScientificIndex;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceAccessFamily;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceCatalog;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceLifecycle;
use App\Services\Agriculture\Research\Search\ScientificSourceSearchOutcome;
use DateTimeImmutable;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * G1-02 / G6-20: owner ACCEPT onboarding ACTIVE (2026-10-18) + CapVer AUTHORIZE.
 */
class CurrentMembershipNearReadySeatCatalogTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        CurrentMembershipNearReadySeatCatalog::swap(null);
        AcceptedOnboardingDecisionCatalog::swap(null);
        ScientificSourceCatalog::forgetDefault();
    }

    protected function tearDown(): void
    {
        CurrentMembershipNearReadySeatCatalog::swap(null);
        AcceptedOnboardingDecisionCatalog::swap(null);
        ScientificSourceCatalog::forgetDefault();
        parent::tearDown();
    }

    public function test_owner_accepted_seats_are_runtime_active_and_enter_governed_catalog(): void
    {
        $this->assertTrue(AcceptedOwnerDecisionCatalog::onboardingAccepted('G1-02'));
        $this->assertTrue(AcceptedOwnerDecisionCatalog::onboardingAccepted('G6-20'));
        $this->assertTrue(AcceptedOwnerDecisionCatalog::capVerAuthorized('G1-02'));
        $this->assertTrue(AcceptedOwnerDecisionCatalog::capVerAuthorized('G6-20'));

        $this->assertSame(['G1-02', 'G6-20'], CurrentMembershipNearReadySeatCatalog::adrIds());
        $this->assertCount(2, CurrentMembershipNearReadySeatCatalog::activatedRegistrations());
        $this->assertTrue(ScientificSourceCatalog::default()->has('organic_eprints'));
        $this->assertTrue(ScientificSourceCatalog::default()->has('bhl'));

        $oe = CurrentMembershipNearReadySeatCatalog::get('G1-02');
        $bhl = CurrentMembershipNearReadySeatCatalog::get('G6-20');
        $this->assertNotNull($oe);
        $this->assertNotNull($bhl);
        $this->assertSame(SeatActivationState::ACTIVE, $oe->intendedActivation);
        $this->assertSame(SeatActivationState::ACTIVE, $bhl->intendedActivation);
        $this->assertTrue($oe->isRuntimeActive());
        $this->assertTrue($bhl->isRuntimeActive());
        $this->assertSame(ScientificSourceAccessFamily::OAI_PMH, $oe->primaryFamily);
        $this->assertSame(ScientificSourceAccessFamily::REST_API, $bhl->primaryFamily);
        $this->assertSame(ScientificSourceLifecycle::GOVERNED_ONBOARDED, ScientificSourceCatalog::default()->get('organic_eprints')?->lifecycle);
        $this->assertSame(ScientificSourceLifecycle::GOVERNED_ONBOARDED, ScientificSourceCatalog::default()->get('bhl')?->lifecycle);
        $this->assertContains('organic_eprints', AcceptedOnboardingDecisionCatalog::activeSourceKeys());
        $this->assertContains('bhl', AcceptedOnboardingDecisionCatalog::activeSourceKeys());
        $this->assertContains('gbif', AcceptedOnboardingDecisionCatalog::activeSourceKeys());
    }

    public function test_organic_eprints_licence_and_fixture_harvest_search_path(): void
    {
        $seat = CurrentMembershipNearReadySeatCatalog::get('G1-02');
        $this->assertNotNull($seat?->familyProfile);
        $this->assertTrue($seat->familyProfile->licensing()->permits(ScientificSourceUsage::RETRIEVAL));
        $this->assertTrue($seat->familyProfile->licensing()->permits(ScientificSourceUsage::METADATA_STORAGE));
        $this->assertFalse($seat->familyProfile->licensing()->permits(ScientificSourceUsage::REDISTRIBUTION));

        Http::fake([
            'https://orgprints.org/cgi/oai2*' => Http::response(
                (string) file_get_contents(base_path('tests/Fixtures/ScientificSourceFamilies/oai_pmh_last_page.xml')),
                200,
                ['Content-Type' => 'text/xml'],
            ),
        ]);

        $store = new InMemoryScientificRecordStore();
        $index = new LocalScientificIndex();
        $ingestion = new LocalIndexIngestionService(
            $store,
            $index,
            new LocalRecordRetentionPolicy(),
            new OaiPmhHarvester(),
            new \App\Services\Agriculture\Research\Search\Family\Bulk\BulkDatasetReader(),
        );
        $report = $ingestion->harvestOaiPmh($seat->familyProfile, new DateTimeImmutable('2026-10-17T20:00:00+00:00'), maxPages: 1);
        $this->assertSame('completed', $report->status);
        $this->assertGreaterThan(0, $store->generation('organic_eprints'));

        $adapter = new LocalIndexScientificSourceAdapter($seat->familyProfile, $index, $store);
        $hit = $adapter->search('wheat', 5);
        $this->assertSame(ScientificSourceSearchOutcome::STATUS_SUCCESS, $hit->status);
        $this->assertSame('organic_eprints', $hit->results[0]->sourceKey);
        $this->assertSame('Wheat rust surveillance', $hit->results[0]->title);

        $unseeded = new LocalIndexScientificSourceAdapter(
            $seat->familyProfile,
            new LocalScientificIndex(),
            new InMemoryScientificRecordStore(),
        );
        $unavailable = $unseeded->search('wheat', 5);
        $this->assertSame(ScientificSourceSearchOutcome::STATUS_UNAVAILABLE, $unavailable->status);
        $this->assertSame('local_index_not_built', $unavailable->error);
    }

    public function test_bhl_missing_key_is_unavailable_and_fixture_maps_with_key(): void
    {
        $seat = CurrentMembershipNearReadySeatCatalog::get('G6-20');
        $this->assertNotNull($seat?->familyProfile);
        $this->assertTrue($seat->familyProfile->licensing()->permits(ScientificSourceUsage::RETRIEVAL));
        $this->assertTrue($seat->isRuntimeActive());

        config(['agricultural_intelligence.bhl.api_key' => null]);
        $missing = (new RestJsonScientificSourceAdapter($seat->familyProfile))->search('wheat', 5);
        $this->assertSame(ScientificSourceSearchOutcome::STATUS_UNAVAILABLE, $missing->status);
        $this->assertSame('missing_api_key', $missing->error);

        config(['agricultural_intelligence.bhl.api_key' => 'test-fixture-key-not-live']);
        Http::fake([
            'https://www.biodiversitylibrary.org/api3*' => Http::response(
                (string) file_get_contents(base_path('tests/Fixtures/ScientificSourceFamilies/bhl_publication_search_fixture_v1.json')),
                200,
            ),
        ]);
        $ok = (new RestJsonScientificSourceAdapter($seat->familyProfile))->search('wheat rust', 5);
        $this->assertSame(ScientificSourceSearchOutcome::STATUS_SUCCESS, $ok->status);
        $this->assertSame('bhl', $ok->results[0]->sourceKey);
        $this->assertSame('Wheat rust observations in temperate climates', $ok->results[0]->title);
        $this->assertSame('bhl-fixture-part-1', (string) $ok->results[0]->sourceIdentifier);
        $this->assertNotNull($ok->results[0]->primaryProvenance());
    }
}
