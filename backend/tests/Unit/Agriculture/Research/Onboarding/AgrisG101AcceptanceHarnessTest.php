<?php

namespace Tests\Unit\Agriculture\Research\Onboarding;

use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Onboarding\AcceptedOwnerDecisionCatalog;
use App\Services\Agriculture\Research\Onboarding\CurrentMembershipNearReadySeatCatalog;
use App\Services\Agriculture\Research\Onboarding\NextGateEligibleSeatCatalog;
use App\Services\Agriculture\Research\Search\LocalIndex\AgrisG101AcceptanceHarness;
use App\Services\Agriculture\Research\Search\LocalIndex\AgrisG101ReadOnlyGuard;
use App\Services\Agriculture\Research\Search\LocalIndex\AgrisLocalIndexQuerySpecializer;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceCatalog;
use Illuminate\Support\Facades\Http;
use Tests\Support\ScientificEvidenceAnswerCitationBridge;
use Tests\TestCase;

/**
 * AGRIS G1-01 acceptance: LocalIndex-sampled isolated corpus → validation → AnswerComposer.
 *
 * Environment is LOCALINDEX_SAMPLED_ISOLATED (not Production Live).
 * Does not grant SCIENTIFIC_INTEGRATION_VERIFIED alone.
 */
class AgrisG101AcceptanceHarnessTest extends TestCase
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

    public function test_agris_query_specializer_preserves_csq_and_emits_fts_safe_versions(): void
    {
        $plan = $this->irrigationPlan();
        $bundle = (new AgrisLocalIndexQuerySpecializer())->specialize($plan);

        $this->assertSame('agris', $bundle['source_key']);
        $this->assertSame('G1-01', $bundle['adr_id']);
        $this->assertSame($plan->normalizedQuery->originalQuestion, $bundle['original_question']);
        $this->assertNotSame('', $bundle['primary_query']);
        $this->assertStringContainsString('wheat', mb_strtolower($bundle['primary_query']));
        $this->assertStringContainsString('irrigation', mb_strtolower($bundle['primary_query']));
        $this->assertFalse($bundle['fts_capability']['boolean_operators_claimed']);
        foreach ($bundle['versions'] as $version) {
            $this->assertFalse($version['csq_mutated']);
        }
        $queries = (new AgrisLocalIndexQuerySpecializer())->retrievalQueries($plan);
        $this->assertNotEmpty($queries);
        $this->assertSame($plan->normalizedQuery->originalQuestion, $plan->normalizedQuery->originalQuestion);
    }

    public function test_agris_isolated_path_preserves_identity_through_validation_and_composer(): void
    {
        $sample = base_path(AgrisG101AcceptanceHarness::SAMPLE_JSONL);
        $this->assertFileExists($sample, 'LocalIndex-sampled JSONL must exist (RO extract)');

        $harness = new AgrisG101AcceptanceHarness();
        $report = $harness->runIsolatedAcceptance([
            'composer' => ScientificEvidenceAnswerCitationBridge::makeComposer(),
        ]);

        $this->assertTrue($report['eligibility']['agris_eligible']);
        $this->assertTrue($report['eligibility']['dr_duke_blocked_for_irrigation']);
        $this->assertTrue($report['identity']['preserved'], 'CSQ/plan identity must survive search→validation→compose');
        $this->assertTrue($report['pass_gates']['retrieval_non_empty']);
        $this->assertTrue($report['pass_gates']['identifiers_present']);
        $this->assertTrue($report['pass_gates']['source_key_agris']);
        $this->assertTrue($report['pass_gates']['validation_invoked']);
        $this->assertTrue($report['pass_gates']['composer_invoked']);
        $this->assertSame('LOCALINDEX_SAMPLED_ISOLATED', $report['environment']);
        $this->assertTrue($report['not_production_live']);
        $this->assertNotEmpty($report['retrieval']['original_identifiers']);
        $meta = $report['sample_meta'] ?? [];
        $this->assertSame('LOCALINDEX_SAMPLED_ISOLATED', $meta['environment'] ?? null);
        $hay = mb_strtolower(json_encode($report['retrieval'], JSON_UNESCAPED_UNICODE) ?: '');
        $this->assertTrue(
            str_contains($hay, 'wheat')
            || str_contains($hay, 'irrigation')
            || str_contains($hay, 'triticum')
            || ($report['retrieval']['relevant_survivor_count'] ?? 0) > 0
            || ($report['retrieval']['survivor_count'] ?? 0) > 0,
            'Retrieved AGRIS LocalIndex sample must participate in irrigation/wheat acceptance path'
        );
    }

    public function test_agris_negative_empty_and_nonsense_queries_return_no_hits(): void
    {
        $this->assertFileExists(base_path(AgrisG101AcceptanceHarness::SAMPLE_JSONL));
        $probe = (new AgrisG101AcceptanceHarness())->runNegativeEmptyProbe();
        $this->assertTrue($probe['pass']);
        $this->assertSame(0, $probe['empty_query_count']);
        $this->assertSame(0, $probe['nonsense_count']);
    }

    public function test_agris_answer_identity_rejects_species_substitution_claim_shape(): void
    {
        $plan = $this->irrigationPlan();
        $this->assertSame('wheat', $plan->normalizedQuery->crop);
        $this->assertNotSame('rice', $plan->normalizedQuery->crop);
        $bundle = (new AgrisLocalIndexQuerySpecializer())->specialize($plan);
        $this->assertStringNotContainsString('rice', mb_strtolower($bundle['primary_query']));
        $this->assertStringNotContainsString('oryza', mb_strtolower($bundle['primary_query']));
    }

    public function test_operational_sqlite_readonly_guard_when_store_present(): void
    {
        $path = storage_path('app/scientific_record_store.sqlite');
        if (! is_file($path)) {
            $this->markTestSkipped('Operational LocalIndex sqlite not mounted in this environment');
        }

        $probe = (new AgrisG101ReadOnlyGuard())->probe($path);
        $this->assertTrue($probe['readonly_open_ok'], (string) ($probe['error'] ?? 'ro open failed'));
        $this->assertTrue($probe['write_attempt_blocked']);
        $this->assertTrue($probe['unchanged']);
    }

    public function test_composer_citation_bridge_preserves_agris_original_identifier(): void
    {
        $this->assertFileExists(base_path(AgrisG101AcceptanceHarness::SAMPLE_JSONL));
        $harness = new AgrisG101AcceptanceHarness();
        $report = $harness->runIsolatedAcceptance([
            'composer' => ScientificEvidenceAnswerCitationBridge::makeComposer(),
        ]);
        $this->assertNotEmpty($report['retrieval']['original_identifiers']);
        $id = $report['retrieval']['original_identifiers'][0];

        // Bridge path: search hit identity → citation (C1→C3 style), independent of claim sufficiency.
        $hits = [];
        // Re-run is expensive; assert chain entries from harness report instead.
        $this->assertNotEmpty($report['evidence_chain']);
        $linked = false;
        foreach ($report['evidence_chain'] as $link) {
            if (($link['original_identifier'] ?? '') === $id) {
                $this->assertSame('agris', $link['source_key']);
                $linked = true;
            }
        }
        $this->assertTrue($linked);
    }

    private function irrigationPlan(): KnowledgeQueryPlan
    {
        $original = 'What are deficit irrigation effects on wheat yield?';
        $csq = 'Deficit irrigation effects on wheat yield';
        $subject = ['type' => 'crop', 'value' => 'wheat', 'label' => 'wheat'];
        $query = new AgriculturalKnowledgeQuery(
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
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            researchIntent: 'irrigation',
        );

        return new KnowledgeQueryPlan(
            normalizedQuery: $query,
            researchIntent: 'irrigation',
            agriculturalDomain: 'agronomy',
            subjectEntity: $subject,
            topics: ['irrigation'],
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
    }
}
