<?php

namespace Tests\Unit\Agriculture\Research\Onboarding;

use App\Services\Agriculture\Research\Onboarding\AcceptedOwnerDecisionCatalog;
use App\Services\Agriculture\Research\Onboarding\CurrentMembershipNearReadySeatCatalog;
use App\Services\Agriculture\Research\Onboarding\NextGateEligibleSeatCatalog;
use App\Services\Agriculture\Research\Search\LocalIndex\OrganicEprintsG102AcceptanceHarness;
use App\Services\Agriculture\Research\Search\LocalIndex\OrganicEprintsLocalIndexQuerySpecializer;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceCatalog;
use Illuminate\Support\Facades\Http;
use Tests\Support\ScientificEvidenceAnswerCitationBridge;
use Tests\TestCase;

/**
 * Organic Eprints G1-02 acceptance — specializer + fixture regression + live RO path.
 */
class OrganicEprintsG102AcceptanceHarnessTest extends TestCase
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

    public function test_specializer_preserves_organic_scope_and_does_not_mutate_csq(): void
    {
        $harness = new OrganicEprintsG102AcceptanceHarness();
        $ref = new \ReflectionClass($harness);
        $method = $ref->getMethod('organicPlan');
        $method->setAccessible(true);
        $plan = $method->invoke($harness);

        $bundle = (new OrganicEprintsLocalIndexQuerySpecializer())->specialize($plan);
        $this->assertSame('organic_eprints', $bundle['source_key']);
        $this->assertStringContainsString('organic', mb_strtolower($bundle['primary_query']));
        $this->assertStringContainsString('wheat', mb_strtolower($bundle['primary_query']));
        foreach ($bundle['versions'] as $version) {
            $this->assertFalse($version['csq_mutated']);
        }
        $this->assertStringNotContainsString('rice', mb_strtolower($bundle['primary_query']));
    }

    public function test_isolated_fixture_path_retrieves_and_invokes_validation_composer(): void
    {
        $report = (new OrganicEprintsG102AcceptanceHarness())->runIsolatedFixtureAcceptance([
            'composer' => ScientificEvidenceAnswerCitationBridge::makeComposer(),
            'limit' => 5,
        ]);

        $this->assertTrue($report['ok'] ?? false, json_encode($report));
        $this->assertSame('FIXTURE_OAI_INMEMORY_LOCALINDEX', $report['evidence_class']);
        $this->assertGreaterThan(0, (int) $report['retrieval_count']);
        $this->assertNotEmpty($report['original_identifiers']);
        $this->assertNotSame('', (string) ($report['validation']['status'] ?? ''));
        $this->assertNotSame('', (string) ($report['composer']['status'] ?? ''));
    }

    public function test_live_application_path_oai_ephemeral_localindex_to_composer(): void
    {
        // Live HTTP required — clear stray-request prevention from setUp.
        Http::allowStrayRequests();

        $report = (new OrganicEprintsG102AcceptanceHarness())->runLiveApplicationPath([
            'composer' => ScientificEvidenceAnswerCitationBridge::makeComposer(),
            'limit' => 5,
            'oai_max_pages' => 1,
        ]);

        if (($report['pass_gates']['live_oai'] ?? false) !== true) {
            $this->markTestSkipped('Live OAI unavailable: '.($report['error'] ?? json_encode($report['live_oai'] ?? [])));
        }

        $this->assertSame('LIVE_OAI_EPHEMERAL_LOCALINDEX', $report['evidence_class']);
        $this->assertSame('REAL_APPLICATION_PATH', $report['path_level']);
        $this->assertTrue($report['pass_gates']['database_unchanged']);
        $this->assertTrue($report['pass_gates']['csq_preserved']);
        $this->assertTrue($report['pass_gates']['retrieval_non_empty'], json_encode($report['retrieval'] ?? []));
        $this->assertTrue($report['pass_gates']['identifiers_present']);
        $this->assertTrue($report['pass_gates']['source_key_organic_eprints']);
        $this->assertTrue($report['pass_gates']['validation_invoked']);
        $this->assertTrue($report['pass_gates']['composer_invoked']);
        $this->assertTrue($report['pass_gates']['answer_scope_preserved']);
        $this->assertNotEmpty($report['retrieval']['original_identifiers']);
        $this->assertStringStartsWith('oai:orgprints.org:', (string) $report['retrieval']['original_identifiers'][0]);
        $this->assertNotSame('', (string) ($report['trace_id'] ?? ''));
    }
}
