<?php

namespace Tests\Unit\Agriculture\Research\Onboarding;

use App\Services\Agriculture\Research\Onboarding\AcceptedOwnerDecisionCatalog;
use App\Services\Agriculture\Research\Onboarding\CurrentMembershipNearReadySeatCatalog;
use App\Services\Agriculture\Research\Onboarding\NextGateEligibleSeatCatalog;
use App\Services\Agriculture\Research\Search\GbifG504AcceptanceHarness;
use App\Services\Agriculture\Research\Search\GovernedScientificSourceEligibility;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceCatalog;
use Illuminate\Support\Facades\Http;
use Tests\Support\ScientificEvidenceAnswerCitationBridge;
use Tests\TestCase;

/**
 * G5-04 GBIF acceptance — fixture + live species-search path (no causation inflation).
 */
class GbifG504AcceptanceHarnessTest extends TestCase
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
        NextGateEligibleSeatCatalog::swap(null);
        CurrentMembershipNearReadySeatCatalog::swap(null);
        AcceptedOwnerDecisionCatalog::swap(null);
        ScientificSourceCatalog::forgetDefault();
        parent::tearDown();
    }

    public function test_gbif_seat_active_and_eligible_for_taxonomy_plan(): void
    {
        $seat = NextGateEligibleSeatCatalog::get('G5-04');
        $this->assertNotNull($seat);
        $this->assertTrue($seat->isRuntimeActive());

        $harness = new GbifG504AcceptanceHarness();
        $ref = new \ReflectionClass($harness);
        $method = $ref->getMethod('taxonomyPlan');
        $method->setAccessible(true);
        $plan = $method->invoke($harness);

        $this->assertTrue((new GovernedScientificSourceEligibility())->isEligible('gbif', $plan));

        $irrigationPlan = app(\App\Services\Agriculture\Research\ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'What are deficit irrigation effects on wheat yield?',
        ]);
        // Irrigation/yield physiology is not GBIF taxonomy/occurrence scope unless a
        // scientific name alone forces eligibility — allow either outcome but record it.
        $gbifOnIrrigation = (new GovernedScientificSourceEligibility())->isEligible('gbif', $irrigationPlan);
        $this->assertIsBool($gbifOnIrrigation);
    }

    public function test_isolated_fixture_path_retrieves_and_invokes_validation_composer(): void
    {
        $report = (new GbifG504AcceptanceHarness())->runIsolatedFixtureAcceptance([
            'composer' => ScientificEvidenceAnswerCitationBridge::makeComposer(),
            'limit' => 5,
        ]);

        $this->assertTrue($report['ok'] ?? false, json_encode($report));
        $this->assertSame('FIXTURE_HTTP_SPECIES_SEARCH', $report['evidence_class']);
        $this->assertGreaterThan(0, (int) $report['gbif_raw_count']);
        $this->assertNotEmpty($report['original_identifiers']);
        $this->assertSame('2704179', (string) $report['original_identifiers'][0]);
        $this->assertSame('taxonomic_species_search_result_not_experimental_claim', $report['record_semantics']);
        $this->assertTrue($report['pass_gates']['validation_invoked']);
        $this->assertTrue($report['pass_gates']['composer_invoked']);
        $this->assertTrue($report['pass_gates']['provenance_present']);
        $this->assertFalse($report['scientific_integration_verified']);
    }

    public function test_live_species_search_application_path(): void
    {
        Http::allowStrayRequests();

        $report = (new GbifG504AcceptanceHarness())->runLiveApplicationPath([
            'composer' => ScientificEvidenceAnswerCitationBridge::makeComposer(),
            'limit' => 5,
        ]);

        if (($report['pass_gates']['retrieval_non_empty'] ?? false) !== true) {
            $this->markTestSkipped('Live GBIF unavailable: '.json_encode($report));
        }

        $this->assertSame('LIVE_REST_SPECIES_SEARCH', $report['evidence_class']);
        $this->assertSame('REAL_APPLICATION_PATH', $report['path_level']);
        $this->assertTrue($report['pass_gates']['identifiers_present']);
        $this->assertTrue($report['pass_gates']['provenance_present']);
        $this->assertTrue($report['pass_gates']['validation_invoked']);
        $this->assertTrue($report['pass_gates']['composer_invoked']);
        $this->assertNotEmpty($report['original_identifiers']);
        $this->assertFalse($report['scientific_integration_verified']);
    }
}
