<?php

namespace Tests\Unit\Agriculture\Research\Onboarding;

use App\Services\Agriculture\Research\Onboarding\AcceptedOwnerDecisionCatalog;
use App\Services\Agriculture\Research\Onboarding\CurrentMembershipNearReadySeatCatalog;
use App\Services\Agriculture\Research\Onboarding\NextGateEligibleSeatCatalog;
use App\Services\Agriculture\Research\Search\LocalIndex\AgrisG101AcceptanceHarness;
use App\Services\Agriculture\Research\Search\LocalIndex\AgrisLocalIndexQuerySpecializer;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceCatalog;
use Illuminate\Support\Facades\Http;
use Tests\Support\ScientificEvidenceAnswerCitationBridge;
use Tests\TestCase;

/**
 * AGRIS G1-01 C12 — live application path against operational LocalIndex (READ-ONLY).
 *
 * Evidence class: OPERATIONAL_LOCALINDEX_READONLY / REAL_APPLICATION_PATH.
 * Does not claim Real Model Inference or SCIENTIFIC_INTEGRATION_VERIFIED.
 */
class AgrisG101C12LiveApplicationPathTest extends TestCase
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

    public function test_query_specializer_keeps_deficit_as_controlled_refinement_not_csq_mutation(): void
    {
        $specializer = new AgrisLocalIndexQuerySpecializer();
        $bundle = $specializer->specialize(
            (new AgrisG101AcceptanceHarnessTestPlanFactory())->irrigationPlan()
        );

        $this->assertSame('wheat irrigation yield', $bundle['primary_query']);
        $roles = array_column($bundle['versions'], 'role', 'query_version');
        $this->assertArrayHasKey('v1b_deficit_qualifier', $roles);
        foreach ($bundle['versions'] as $version) {
            $this->assertFalse($version['csq_mutated']);
        }
        $this->assertStringContainsString('deficit', mb_strtolower(
            collect($bundle['versions'])->firstWhere('query_version', 'v1b_deficit_qualifier')['query'] ?? ''
        ));
    }

    public function test_live_application_path_operational_readonly_to_composer(): void
    {
        $path = storage_path('app/scientific_record_store.sqlite');
        if (! is_file($path)) {
            $this->markTestSkipped('Operational LocalIndex sqlite not available');
        }

        $report = (new AgrisG101AcceptanceHarness())->runLiveApplicationPath([
            'composer' => ScientificEvidenceAnswerCitationBridge::makeComposer(),
            'limit' => 5,
        ]);

        if (($report['pass_gates']['readonly_bootstrap'] ?? false) !== true) {
            $this->markTestSkipped('RO bootstrap unavailable: '.($report['error'] ?? 'unknown'));
        }

        $this->assertSame('OPERATIONAL_LOCALINDEX_READONLY', $report['evidence_class']);
        $this->assertSame('REAL_APPLICATION_PATH', $report['path_level']);
        $this->assertTrue($report['pass_gates']['database_unchanged']);
        $this->assertTrue($report['pass_gates']['csq_preserved']);
        $this->assertTrue($report['pass_gates']['retrieval_non_empty']);
        $this->assertTrue($report['pass_gates']['identifiers_present']);
        $this->assertTrue($report['pass_gates']['source_key_agris']);
        $this->assertTrue($report['pass_gates']['question_aligned_retrieval']);
        $this->assertTrue($report['pass_gates']['validation_invoked']);
        $this->assertTrue($report['pass_gates']['composer_invoked']);
        $this->assertTrue($report['pass_gates']['answer_scope_preserved']);
        $this->assertTrue($report['not_fixture']);
        $this->assertTrue($report['not_isolated_sample']);
        $this->assertNotEmpty($report['retrieval']['original_identifiers']);
        $this->assertNotSame('', (string) ($report['trace_id'] ?? ''));
    }

    public function test_negative_identity_rejects_crop_substitution_in_specializer(): void
    {
        $bundle = (new AgrisLocalIndexQuerySpecializer())->specialize(
            (new AgrisG101AcceptanceHarnessTestPlanFactory())->irrigationPlan()
        );
        $this->assertStringNotContainsString('rice', mb_strtolower($bundle['primary_query']));
        $this->assertStringNotContainsString('oryza', mb_strtolower($bundle['primary_query']));
        $this->assertStringContainsString('wheat', mb_strtolower($bundle['primary_query']));
    }
}

/**
 * @internal
 */
final class AgrisG101AcceptanceHarnessTestPlanFactory
{
    public function irrigationPlan(): \App\Services\Agriculture\Research\KnowledgeQueryPlan
    {
        $original = 'What are deficit irrigation effects on wheat yield?';
        $csq = 'Deficit irrigation effects on wheat yield';
        $subject = ['type' => 'crop', 'value' => 'wheat', 'label' => 'wheat'];
        $query = new \App\Services\Agriculture\Research\AgriculturalKnowledgeQuery(
            originalQuestion: $original,
            normalizedQuestion: $csq,
            language: 'en',
            agriculturalDomain: 'agronomy',
            subject: $subject,
            crop: 'wheat',
            cropId: 'wheat',
            scientificName: null,
            topic: 'irrigation',
            subtopic: null,
            requestedInformation: ['overview'],
            constraints: [
                'scientific_sense' => 'irrigation',
                'scientific_factors' => ['deficit irrigation', 'irrigation'],
            ],
            location: null,
            researchRequired: true,
            ambiguityState: \App\Services\Agriculture\Research\AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            researchIntent: 'irrigation',
        );

        return new \App\Services\Agriculture\Research\KnowledgeQueryPlan(
            normalizedQuery: $query,
            researchIntent: 'irrigation',
            agriculturalDomain: 'agronomy',
            subjectEntity: $subject,
            topics: ['irrigation'],
            subtopics: [],
            requestedInformation: ['overview'],
            evidenceRequirements: ['peer_reviewed'],
            sourcePriorities: ['agris'],
            primaryResearchStrategy: \App\Services\Agriculture\Research\KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: \App\Services\Agriculture\Research\KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: \App\Services\Agriculture\Research\AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            contextInput: [],
            readyForStage3: true,
        );
    }
}
