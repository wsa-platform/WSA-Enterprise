<?php

namespace Tests\Unit\Agriculture\Research\Onboarding;

use App\Services\Agriculture\Research\Onboarding\AcceptedOwnerDecisionCatalog;
use App\Services\Agriculture\Research\Onboarding\CurrentMembershipNearReadySeatCatalog;
use App\Services\Agriculture\Research\Search\BhlG620AcceptanceHarness;
use App\Services\Agriculture\Research\Search\GovernedScientificSourceEligibility;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceCatalog;
use Illuminate\Support\Facades\Http;
use Tests\Support\ScientificEvidenceAnswerCitationBridge;
use Tests\TestCase;

/**
 * G6-20 BHL acceptance — fixture + live PublicationSearch (credential from env; never assert value).
 * Seat lives in CurrentMembershipNearReadySeatCatalog (not the R7 NextGate-17 list).
 */
class BhlG620AcceptanceHarnessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        config(['agricultural_intelligence.stage3_home_scholarly_concurrency' => false]);
        CurrentMembershipNearReadySeatCatalog::swap(null);
        AcceptedOwnerDecisionCatalog::swap(null);
        ScientificSourceCatalog::forgetDefault();
    }

    protected function tearDown(): void
    {
        CurrentMembershipNearReadySeatCatalog::swap(null);
        AcceptedOwnerDecisionCatalog::swap(null);
        ScientificSourceCatalog::forgetDefault();
        parent::tearDown();
    }

    public function test_bhl_seat_active_and_eligible_for_heritage_plan(): void
    {
        $seat = CurrentMembershipNearReadySeatCatalog::get('G6-20');
        $this->assertNotNull($seat);
        $this->assertTrue($seat->isRuntimeActive());

        $harness = new BhlG620AcceptanceHarness();
        $ref = new \ReflectionClass($harness);
        $method = $ref->getMethod('heritageLiteraturePlan');
        $method->setAccessible(true);
        $plan = $method->invoke($harness);

        $this->assertTrue((new GovernedScientificSourceEligibility())->isEligible('bhl', $plan));

        $irrigationPlan = app(\App\Services\Agriculture\Research\ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'What are deficit irrigation effects on wheat yield?',
        ]);
        $this->assertFalse((new GovernedScientificSourceEligibility())->isEligible('bhl', $irrigationPlan));
    }

    public function test_isolated_fixture_path_retrieves_and_invokes_validation_composer(): void
    {
        $report = (new BhlG620AcceptanceHarness())->runIsolatedFixtureAcceptance([
            'composer' => ScientificEvidenceAnswerCitationBridge::makeComposer(),
            'limit' => 5,
        ]);

        $this->assertTrue($report['ok'] ?? false, json_encode($report));
        $this->assertSame('FIXTURE_HTTP_PUBLICATION_SEARCH', $report['evidence_class']);
        $this->assertGreaterThan(0, (int) $report['bhl_raw_count']);
        $this->assertNotEmpty($report['original_identifiers']);
        $this->assertSame('bhl-fixture-part-1', (string) $report['original_identifiers'][0]);
        $this->assertSame(
            'historical_publication_search_result_not_modern_experimental_claim',
            $report['record_semantics'],
        );
        $this->assertTrue($report['pass_gates']['validation_invoked']);
        $this->assertTrue($report['pass_gates']['composer_invoked']);
        $this->assertTrue($report['pass_gates']['provenance_present']);
        $this->assertTrue($report['pass_gates']['secret_not_in_report']);
        $this->assertFalse($report['scientific_integration_verified']);
        $encoded = json_encode($report);
        $this->assertIsString($encoded);
        $this->assertStringNotContainsString('test-fixture-key-not-a-secret', $encoded);
    }

    public function test_live_publication_search_application_path(): void
    {
        Http::allowStrayRequests();

        $key = config('agricultural_intelligence.bhl.api_key');
        if (! is_string($key) || trim($key) === '') {
            $this->markTestSkipped('BHL_API_KEY not configured in runtime env');
        }

        $report = (new BhlG620AcceptanceHarness())->runLiveApplicationPath([
            'composer' => ScientificEvidenceAnswerCitationBridge::makeComposer(),
            'limit' => 5,
        ]);

        if (($report['error'] ?? null) === 'missing_api_key') {
            $this->markTestSkipped('BHL credential unavailable at runtime');
        }

        if (($report['pass_gates']['retrieval_non_empty'] ?? false) !== true) {
            $this->markTestSkipped('Live BHL unavailable: '.json_encode($report));
        }

        $this->assertSame('LIVE_REST_PUBLICATION_SEARCH', $report['evidence_class']);
        $this->assertSame('REAL_APPLICATION_PATH', $report['path_level']);
        $this->assertTrue($report['pass_gates']['identifiers_present']);
        $this->assertTrue($report['pass_gates']['provenance_present']);
        $this->assertTrue($report['pass_gates']['validation_invoked']);
        $this->assertTrue($report['pass_gates']['composer_invoked']);
        $this->assertTrue($report['pass_gates']['credential_configured']);
        $this->assertFalse($report['scientific_integration_verified']);
        $encoded = json_encode($report);
        $this->assertIsString($encoded);
        $this->assertStringNotContainsString($key, $encoded);
    }
}
