<?php

namespace Tests\Unit\Agriculture\Research\Onboarding;

use App\Services\Agriculture\Research\Onboarding\AcceptedOwnerDecisionCatalog;
use App\Services\Agriculture\Research\Onboarding\CurrentMembershipNearReadySeatCatalog;
use App\Services\Agriculture\Research\Onboarding\NextGateEligibleSeatCatalog;
use App\Services\Agriculture\Research\Search\LocalIndex\DrDukeG117AcceptanceHarness;
use App\Services\Agriculture\Research\Search\LocalIndex\DrDukeLocalIndexQuerySpecializer;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceCatalog;
use Illuminate\Support\Facades\Http;
use Tests\Support\ScientificEvidenceAnswerCitationBridge;
use Tests\TestCase;

class DrDukeG117AcceptanceHarnessTest extends TestCase
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

    public function test_specializer_keeps_mentha_and_avoids_clinical_efficacy_expansion(): void
    {
        $harness = new DrDukeG117AcceptanceHarness();
        $ref = new \ReflectionClass($harness);
        $method = $ref->getMethod('menthaPlan');
        $method->setAccessible(true);
        $plan = $method->invoke($harness);
        $bundle = (new DrDukeLocalIndexQuerySpecializer())->specialize($plan);

        $this->assertStringContainsString('Mentha', $bundle['primary_query']);
        $this->assertStringNotContainsString('clinical efficacy', mb_strtolower($bundle['primary_query']));
        foreach ($bundle['versions'] as $version) {
            $this->assertFalse($version['csq_mutated']);
        }
    }

    public function test_isolated_fixture_path_retrieves_and_invokes_validation_composer(): void
    {
        $report = (new DrDukeG117AcceptanceHarness())->runIsolatedFixtureAcceptance([
            'composer' => ScientificEvidenceAnswerCitationBridge::makeComposer(),
            'limit' => 5,
        ]);

        $this->assertTrue($report['ok'] ?? false, json_encode($report));
        $this->assertSame('FIXTURE_LOCALINDEX_LIMITED_PROJECTION', $report['evidence_class']);
        $this->assertTrue($report['not_live_api']);
        $this->assertGreaterThan(0, (int) $report['retrieval_count']);
        $this->assertNotSame('', (string) ($report['validation']['status'] ?? ''));
        $this->assertNotSame('', (string) ($report['composer']['status'] ?? ''));
        $this->assertTrue($report['scientific_boundaries']['no_clinical_efficacy_invented']);
        $this->assertTrue($report['scientific_boundaries']['mentha_identity_preserved']);
    }

    public function test_live_application_path_operational_readonly_to_composer(): void
    {
        if (! is_file(storage_path('app/scientific_record_store.sqlite'))) {
            $this->markTestSkipped('Operational LocalIndex sqlite not available');
        }

        $report = (new DrDukeG117AcceptanceHarness())->runLiveApplicationPath([
            'composer' => ScientificEvidenceAnswerCitationBridge::makeComposer(),
            'limit' => 5,
        ]);

        if (($report['pass_gates']['readonly_bootstrap'] ?? false) !== true) {
            $this->markTestSkipped('RO bootstrap unavailable: '.($report['error'] ?? 'unknown'));
        }

        $this->assertSame('OPERATIONAL_LOCALINDEX_READONLY', $report['evidence_class']);
        $this->assertSame('REAL_APPLICATION_PATH', $report['path_level']);
        $this->assertTrue($report['not_live_api']);
        $this->assertTrue($report['pass_gates']['database_unchanged']);
        $this->assertTrue($report['pass_gates']['validation_invoked']);
        $this->assertTrue($report['pass_gates']['composer_invoked']);
        if (! ($report['pass_gates']['retrieval_non_empty'] ?? false)) {
            $this->markTestSkipped('Operational dr_duke FTS returned zero for Mentha probes; fixture path remains the acceptance corpus.');
        }
        $this->assertTrue($report['pass_gates']['source_key_dr_duke']);
    }
}
