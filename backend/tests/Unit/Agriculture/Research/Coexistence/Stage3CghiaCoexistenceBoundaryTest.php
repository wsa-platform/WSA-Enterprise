<?php

namespace Tests\Unit\Agriculture\Research\Coexistence;

use App\Services\Agriculture\Research\Capability\CapabilityAccessMethod;
use App\Services\Agriculture\Research\Capability\CapabilityAndResultClass;
use App\Services\Agriculture\Research\Capability\CapabilityDimension;
use App\Services\Agriculture\Research\Capability\CapabilityEvidenceFreshness;
use App\Services\Agriculture\Research\Capability\CapabilityRecord;
use App\Services\Agriculture\Research\Capability\CapabilityRecordId;
use App\Services\Agriculture\Research\Capability\CapabilityState;
use App\Services\Agriculture\Research\Capability\CapabilitySubject;
use App\Services\Agriculture\Research\Coexistence\CoexistenceBindingClass;
use App\Services\Agriculture\Research\Coexistence\CoexistenceDisclosureContext;
use App\Services\Agriculture\Research\Coexistence\CoexistenceEventCode;
use App\Services\Agriculture\Research\Coexistence\CoexistenceMode;
use App\Services\Agriculture\Research\Coexistence\Stage3CghiaCoexistenceBoundary;
use App\Services\Agriculture\Research\Coexistence\Stage3SourceKeyIdentityBridge;
use App\Services\Agriculture\Research\Correlation\Persistence\EloquentDurableCorrelationRepository;
use App\Services\Agriculture\Research\Disclosure\FidelityDisclosureCodes;
use App\Services\Agriculture\Research\Disclosure\FidelityDisclosureRecord;
use App\Services\Agriculture\Research\Identity\AdrMembershipId;
use App\Services\Agriculture\Research\Path\PathEligibilityState;
use App\Services\Agriculture\Research\Path\PathStatus;
use App\Services\Agriculture\Research\Projection\ProjectionFacetAccounting;
use App\Services\Agriculture\Research\Projection\ProjectionFacetDisposition;
use App\Services\Agriculture\Research\Projection\ProjectionFacetRecord;
use App\Services\Agriculture\Research\Projection\ProjectionFidelityClass;
use App\Services\Agriculture\Research\Synthesis\AnswerComposer;
use App\Services\Agriculture\Research\Synthesis\ScientificUserPresentation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionClass;
use Tests\TestCase;

/**
 * IU-09 — Stage-3 ↔ CGHIA Coexistence Boundary.
 */
final class Stage3CghiaCoexistenceBoundaryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        CoexistenceDisclosureContext::reset();
    }

    protected function tearDown(): void
    {
        CoexistenceDisclosureContext::reset();
        parent::tearDown();
    }

    public function test_legacy_only_preserves_stage3_behavior_without_cghia_artifacts(): void
    {
        $boundary = $this->boundary();
        $plan = $boundary->plan(
            CoexistenceMode::LEGACY_ONLY,
            ['openalex', 'crossref'],
            'wheat yield Egypt',
            'q:legacy',
            'csq:legacy',
        );

        $this->assertSame(CoexistenceMode::LEGACY_ONLY, $plan->mode);
        $this->assertSame(['openalex', 'crossref'], $plan->permittedSourceKeys());
        foreach ($plan->entries as $entry) {
            $this->assertTrue($entry->retrievalPermitted);
            $this->assertTrue($entry->legacyCompatible);
            $this->assertFalse($entry->cghiaArtifactsAttached);
            $this->assertFalse($entry->verifiedAutomationClaimed);
            $this->assertNull($entry->projection);
            $this->assertNull($entry->capabilityDecision);
            $this->assertNull($entry->pathDecision);
            $this->assertContains(CoexistenceEventCode::LEGACY_COMPATIBLE, $entry->events);
        }
        $this->assertSame([], CoexistenceDisclosureContext::instance()->all());
    }

    public function test_cghia_attached_consumes_selected_source_keys(): void
    {
        $boundary = $this->boundary();
        $plan = $boundary->plan(
            CoexistenceMode::CGHIA_ATTACHED,
            ['openalex', 'semantic_scholar'],
            'nitrogen wheat',
            'q:attach',
            'csq:attach',
        );

        $this->assertCount(2, $plan->entries);
        $this->assertSame(['openalex', 'semantic_scholar'], $plan->selectorSourceKeys);
        foreach ($plan->entries as $entry) {
            $this->assertTrue($entry->selectorSelected);
            $this->assertTrue($entry->retrievalPermitted);
            $this->assertSame(CoexistenceBindingClass::EXTERNAL_ONLY, $entry->identity->bindingClass);
        }
    }

    public function test_empty_capability_store_external_only_defers_path_legacy_compatible(): void
    {
        $boundary = $this->boundary();
        $plan = $boundary->plan(
            CoexistenceMode::CGHIA_ATTACHED,
            ['openalex'],
            'q',
            'q:empty-cap',
            'csq:empty-cap',
            capabilityRecords: [],
        );

        $entry = $plan->entries[0];
        $this->assertContains(CoexistenceEventCode::CAP_UNVERIFIED, $entry->events);
        $this->assertContains(CoexistenceEventCode::PATH_DEFERRED, $entry->events);
        $this->assertContains(CoexistenceEventCode::LEGACY_COMPATIBLE, $entry->events);
        $this->assertFalse($entry->verifiedAutomationClaimed);
        $this->assertFalse($entry->cghiaArtifactsAttached);
        $this->assertNull($entry->projection);
        $this->assertTrue($entry->retrievalPermitted);
    }

    public function test_selector_not_selected_does_not_invent_adapter(): void
    {
        $boundary = $this->boundary();
        $plan = $boundary->plan(
            CoexistenceMode::CGHIA_ATTACHED,
            ['openalex'],
            'q',
            'q:sel',
            'csq:sel',
            candidateSourceKeys: ['openalex', 'crossref'],
        );

        $byKey = [];
        foreach ($plan->entries as $entry) {
            $byKey[$entry->sourceKey] = $entry;
        }

        $this->assertTrue($byKey['openalex']->selectorSelected);
        $this->assertTrue($byKey['openalex']->retrievalPermitted);

        $this->assertFalse($byKey['crossref']->selectorSelected);
        $this->assertFalse($byKey['crossref']->retrievalPermitted);
        $this->assertContains(CoexistenceEventCode::SELECTOR_NOT_SELECTED, $byKey['crossref']->events);
        $this->assertFalse($byKey['crossref']->cghiaArtifactsAttached);
    }

    public function test_adr_bound_missing_cap_is_unverified_path_deferred(): void
    {
        $boundary = $this->adrBoundBoundary();
        $plan = $boundary->plan(
            CoexistenceMode::CGHIA_ATTACHED,
            ['openalex'],
            'builder query wheat',
            'q:adr-miss',
            'csq:adr-miss',
            capabilityRecords: [],
        );

        $entry = $plan->entries[0];
        $this->assertSame(CoexistenceBindingClass::ADR_BOUND, $entry->identity->bindingClass);
        $this->assertNotNull($entry->capabilityDecision);
        $this->assertSame(CapabilityAndResultClass::HAS_UNVERIFIED, $entry->capabilityDecision->andResultClass);
        $this->assertSame(PathEligibilityState::DEFERRED, $entry->eligibilityState);
        $this->assertContains(CoexistenceEventCode::CAP_UNVERIFIED, $entry->events);
        $this->assertContains(CoexistenceEventCode::PATH_DEFERRED, $entry->events);
        $this->assertTrue($entry->legacyCompatible);
        $this->assertFalse($entry->verifiedAutomationClaimed);
        $this->assertTrue($entry->cghiaArtifactsAttached);
        $this->assertNotNull($entry->projection);
        $this->assertNotSame(ProjectionFidelityClass::EXACT, $entry->projection->fidelityClass);
    }

    public function test_required_capability_and_semantics_and_partial_conditional(): void
    {
        $subject = CapabilitySubject::of(
            AdrMembershipId::fromString('G3-OA-01'),
            null,
        );
        $api = CapabilityDimension::accessMethod(CapabilityAccessMethod::API);

        $partial = CapabilityRecord::create(
            CapabilityRecordId::fromString('cap-partial-oa'),
            $subject,
            $api,
            CapabilityState::PARTIAL,
            CapabilityEvidenceFreshness::CURRENT,
            limitationText: 'partial API',
            snapshotVersion: 't1',
            updateMethod: 'manual',
        );

        $boundary = $this->adrBoundBoundary();
        $plan = $boundary->plan(
            CoexistenceMode::CGHIA_ATTACHED,
            ['openalex'],
            'q',
            'q:partial',
            'csq:partial',
            capabilityRecords: [$partial],
        );

        $entry = $plan->entries[0];
        $this->assertSame(CapabilityAndResultClass::HAS_PARTIAL, $entry->capabilityDecision?->andResultClass);
        $this->assertSame(PathEligibilityState::CONDITIONAL, $entry->eligibilityState);
        $this->assertFalse($entry->verifiedAutomationClaimed);
        $this->assertStringContainsString('PARTIAL', implode(' ', $entry->limitations));
    }

    public function test_unavailable_capability_path_ineligible_no_false_cghia_success(): void
    {
        $subject = CapabilitySubject::of(AdrMembershipId::fromString('G3-OA-01'), null);
        $api = CapabilityDimension::accessMethod(CapabilityAccessMethod::API);
        $unavail = CapabilityRecord::create(
            CapabilityRecordId::fromString('cap-unavail-oa'),
            $subject,
            $api,
            CapabilityState::UNAVAILABLE,
            CapabilityEvidenceFreshness::CURRENT,
            snapshotVersion: 't1',
            updateMethod: 'manual',
        );

        $boundary = $this->adrBoundBoundary();
        $plan = $boundary->plan(
            CoexistenceMode::CGHIA_ATTACHED,
            ['openalex'],
            'q',
            'q:unavail',
            'csq:unavail',
            capabilityRecords: [$unavail],
        );

        $entry = $plan->entries[0];
        $this->assertSame(CapabilityAndResultClass::HAS_UNAVAILABLE, $entry->capabilityDecision?->andResultClass);
        $this->assertSame(PathEligibilityState::INELIGIBLE, $entry->eligibilityState);
        $this->assertContains(CoexistenceEventCode::PATH_INELIGIBLE, $entry->events);
        $this->assertContains(CoexistenceEventCode::CAP_UNAVAILABLE, $entry->events);
        $this->assertFalse($entry->verifiedAutomationClaimed);
        $this->assertFalse($entry->cghiaArtifactsAttached);
        $this->assertNull($entry->projection);
        $this->assertTrue($entry->legacyCompatible);
        $this->assertTrue($entry->retrievalPermitted);
    }

    public function test_path_eligible_permits_retrieval_plan_with_exact_when_verified(): void
    {
        $subject = CapabilitySubject::of(AdrMembershipId::fromString('G3-OA-01'), null);
        $api = CapabilityDimension::accessMethod(CapabilityAccessMethod::API);
        $verified = CapabilityRecord::create(
            CapabilityRecordId::fromString('cap-ver-oa'),
            $subject,
            $api,
            CapabilityState::VERIFIED,
            CapabilityEvidenceFreshness::CURRENT,
            snapshotVersion: 't1',
            updateMethod: 'manual',
        );

        $boundary = $this->adrBoundBoundary();
        $plan = $boundary->plan(
            CoexistenceMode::CGHIA_ATTACHED,
            ['openalex'],
            'q=wheat',
            'q:elig',
            'csq:elig',
            capabilityRecords: [$verified],
        );

        $entry = $plan->entries[0];
        $this->assertSame(PathEligibilityState::ELIGIBLE, $entry->eligibilityState);
        $this->assertSame(PathStatus::SELECTED, $entry->pathStatus);
        $this->assertTrue($entry->retrievalPermitted);
        $this->assertTrue($entry->verifiedAutomationClaimed);
        $this->assertTrue($entry->cghiaArtifactsAttached);
        $this->assertNotNull($entry->projection);
        $this->assertSame(ProjectionFidelityClass::EXACT, $entry->projection->fidelityClass);
        $this->assertNotNull($entry->disclosureHandoff);
        $this->assertSame([], $entry->disclosureHandoff->disclosures);
        $this->assertFalse($entry->disclosureHandoff->qualificationRequired);
    }

    public function test_projection_created_only_after_identity_cap_path_and_preserves_facets(): void
    {
        $subject = CapabilitySubject::of(AdrMembershipId::fromString('G3-OA-01'), null);
        $api = CapabilityDimension::accessMethod(CapabilityAccessMethod::API);
        $verified = CapabilityRecord::create(
            CapabilityRecordId::fromString('cap-ver-facets'),
            $subject,
            $api,
            CapabilityState::VERIFIED,
            CapabilityEvidenceFreshness::CURRENT,
            snapshotVersion: 't1',
            updateMethod: 'manual',
        );

        $facets = ProjectionFacetAccounting::of([
            ProjectionFacetRecord::of('species', ProjectionFacetDisposition::UNSUPPORTED, reason: 'source cannot encode taxon'),
            ProjectionFacetRecord::of('dose', ProjectionFacetDisposition::UNRESOLVED, reason: 'dose unresolved'),
        ]);

        $boundary = $this->adrBoundBoundary();
        $plan = $boundary->plan(
            CoexistenceMode::CGHIA_ATTACHED,
            ['openalex'],
            'q=wheat dose',
            'q:facets',
            'csq:facets',
            capabilityRecords: [$verified],
            facetAccounting: $facets,
        );

        $entry = $plan->entries[0];
        $this->assertNotNull($entry->capabilityDecision);
        $this->assertNotNull($entry->pathDecision);
        $this->assertNotNull($entry->projection);

        $codes = array_map(
            static fn (ProjectionFacetRecord $r): string => $r->facetCode,
            $entry->projection->facetAccounting->records,
        );
        $this->assertContains('species', $codes);
        $this->assertContains('dose', $codes);

        $byCode = [];
        foreach ($entry->projection->facetAccounting->records as $record) {
            $byCode[$record->facetCode] = $record->disposition;
        }
        $this->assertSame(ProjectionFacetDisposition::UNSUPPORTED, $byCode['species']);
        $this->assertSame(ProjectionFacetDisposition::UNRESOLVED, $byCode['dose']);
        $this->assertNotSame(ProjectionFidelityClass::EXACT, $entry->projection->fidelityClass);
        $this->assertFalse($entry->verifiedAutomationClaimed);
    }

    public function test_identity_critical_non_exact_forbids_unqualified_exact_claim(): void
    {
        $subject = CapabilitySubject::of(AdrMembershipId::fromString('G3-OA-01'), null);
        $api = CapabilityDimension::accessMethod(CapabilityAccessMethod::API);
        $verified = CapabilityRecord::create(
            CapabilityRecordId::fromString('cap-ver-idcrit'),
            $subject,
            $api,
            CapabilityState::VERIFIED,
            CapabilityEvidenceFreshness::CURRENT,
            snapshotVersion: 't1',
            updateMethod: 'manual',
        );

        $facets = ProjectionFacetAccounting::of([
            ProjectionFacetRecord::of('species', ProjectionFacetDisposition::OMITTED, reason: 'omitted taxon'),
        ]);

        $boundary = $this->adrBoundBoundary();
        $plan = $boundary->plan(
            CoexistenceMode::CGHIA_ATTACHED,
            ['openalex'],
            'q',
            'q:idcrit',
            'csq:idcrit',
            capabilityRecords: [$verified],
            facetAccounting: $facets,
        );

        $handoff = $plan->entries[0]->disclosureHandoff;
        $this->assertNotNull($handoff);
        $this->assertTrue($handoff->qualificationRequired);
        $this->assertTrue($handoff->unqualifiedExactScientificClaimForbidden);

        $codes = array_map(
            static fn (FidelityDisclosureRecord $r): string => $r->code,
            $handoff->disclosures,
        );
        $this->assertContains(FidelityDisclosureCodes::IDENTITY_CRITICAL_LOSS, $codes);
        $this->assertNotEmpty(CoexistenceDisclosureContext::instance()->all());
    }

    public function test_b7_correlation_contains_required_keys_and_durable_idempotent(): void
    {
        $subject = CapabilitySubject::of(AdrMembershipId::fromString('G3-OA-01'), null);
        $api = CapabilityDimension::accessMethod(CapabilityAccessMethod::API);
        $verified = CapabilityRecord::create(
            CapabilityRecordId::fromString('cap-ver-b7'),
            $subject,
            $api,
            CapabilityState::VERIFIED,
            CapabilityEvidenceFreshness::CURRENT,
            snapshotVersion: 't1',
            updateMethod: 'manual',
        );

        $repo = new EloquentDurableCorrelationRepository();
        $boundary = new Stage3CghiaCoexistenceBoundary(
            identityBridge: new Stage3SourceKeyIdentityBridge([
                'openalex' => ['adr_id' => 'G3-OA-01'],
            ]),
            durableCorrelationRepository: $repo,
        );

        $plan1 = $boundary->plan(
            CoexistenceMode::CGHIA_ATTACHED,
            ['openalex'],
            'q',
            'q:b7',
            'csq:b7',
            capabilityRecords: [$verified],
        );
        $entry1 = $plan1->entries[0];
        $this->assertNotNull($entry1->correlation);
        $tokens = $entry1->correlation->toArray();
        $this->assertSame('q:b7', $tokens['question_identity']);
        $this->assertSame('csq:b7', $tokens['csq_identity']);
        $this->assertSame('G3-OA-01', $tokens['adr_id']);
        $this->assertNotEmpty($tokens['capability_decision_identity']);
        $this->assertNotEmpty($tokens['path_id']);
        $this->assertNotEmpty($tokens['projection_identity']);
        $this->assertArrayHasKey('canonical_identity_id', $tokens);
        $this->assertNotNull($entry1->durableCorrelation);

        $id1 = $entry1->durableCorrelation->persistenceRecordId->value;

        $plan2 = $boundary->plan(
            CoexistenceMode::CGHIA_ATTACHED,
            ['openalex'],
            'q',
            'q:b7',
            'csq:b7',
            capabilityRecords: [$verified],
        );
        $id2 = $plan2->entries[0]->durableCorrelation?->persistenceRecordId->value;
        $this->assertSame($id1, $id2);
    }

    public function test_fallback_cannot_increase_fidelity_and_external_remains_external(): void
    {
        $boundary = $this->boundary();
        $plan = $boundary->plan(
            CoexistenceMode::CGHIA_ATTACHED,
            ['consensus', 'fao_stat'],
            'q',
            'q:ext',
            'csq:ext',
        );

        foreach ($plan->entries as $entry) {
            $this->assertSame(CoexistenceBindingClass::EXTERNAL_ONLY, $entry->identity->bindingClass);
            $this->assertNull($entry->identity->adrId);
            $this->assertStringStartsWith('external:', (string) $entry->identity->externalRef?->identifier);
            $this->assertFalse($entry->verifiedAutomationClaimed);
            $this->assertNull($entry->projection);
        }
    }

    public function test_empty_result_and_runtime_failure_remain_distinct_event_codes(): void
    {
        $this->assertContains(CoexistenceEventCode::EMPTY_RESULT, CoexistenceEventCode::cases());
        $this->assertContains(CoexistenceEventCode::RUNTIME_FAILURE, CoexistenceEventCode::cases());
        $this->assertContains(CoexistenceEventCode::CAP_UNAVAILABLE, CoexistenceEventCode::cases());
        $this->assertNotSame(
            CoexistenceEventCode::EMPTY_RESULT,
            CoexistenceEventCode::CAP_UNAVAILABLE,
        );
        $this->assertNotSame(
            CoexistenceEventCode::RUNTIME_FAILURE,
            CoexistenceEventCode::CAP_UNVERIFIED,
        );
    }

    public function test_no_csq_mutation_surface_in_coexistence_package(): void
    {
        $dir = dirname(__DIR__, 5).'/app/Services/Agriculture/Research/Coexistence';
        $files = glob($dir.'/*.php') ?: [];
        $this->assertNotEmpty($files);
        foreach ($files as $file) {
            $src = (string) file_get_contents($file);
            foreach ([
                'QueryUnderstandingService',
                'CanonicalScientificQuestion',
                'ScientificSearchQueryBuilder',
                'AgriculturalEntityCatalog',
                'overallConfidence',
                'FINAL_ANSWER_CONFIDENCE_THRESHOLD',
            ] as $forbidden) {
                $this->assertStringNotContainsString($forbidden, $src, $file);
            }
        }
    }

    public function test_composer_units_confidence_r6_threshold_unchanged(): void
    {
        $this->assertSame(0.50, ScientificUserPresentation::FINAL_ANSWER_CONFIDENCE_THRESHOLD);

        $composer = new ReflectionClass(AnswerComposer::class);
        $this->assertTrue($composer->hasMethod('overallConfidence'));
        $this->assertTrue($composer->hasMethod('phase5QuestionClaimMatrix'));
        $this->assertTrue($composer->hasMethod('withCoexistenceDisclosure'));

        $helper = $composer->getMethod('withCoexistenceDisclosure');
        $this->assertTrue($helper->isPrivate());
        $src = (string) file_get_contents($composer->getFileName());
        $this->assertStringContainsString('Does not recalculate C9, confidence, R6, or Units A/B/C', $src);
        $this->assertStringNotContainsString('FINAL_ANSWER_CONFIDENCE_THRESHOLD =', $src);
    }

    public function test_mode_from_config_defaults_legacy_only(): void
    {
        config(['agricultural_intelligence.cghia_coexistence.mode' => 'legacy_only']);
        $this->assertSame(CoexistenceMode::LEGACY_ONLY, CoexistenceMode::fromConfig());

        config(['agricultural_intelligence.cghia_coexistence.mode' => 'cghia_attached']);
        $this->assertSame(CoexistenceMode::CGHIA_ATTACHED, CoexistenceMode::fromConfig());
    }

    public function test_and_semantics_missing_required_dimension_unverified(): void
    {
        $subject = CapabilitySubject::of(AdrMembershipId::fromString('G3-OA-01'), null);
        $api = CapabilityDimension::accessMethod(CapabilityAccessMethod::API);
        $oai = CapabilityDimension::accessMethod(CapabilityAccessMethod::OAI_PMH);

        // Boundary requires API only; exercise evaluator AND via two-record store with only one matching.
        $onlyOai = CapabilityRecord::create(
            CapabilityRecordId::fromString('cap-oai-only'),
            $subject,
            $oai,
            CapabilityState::VERIFIED,
            CapabilityEvidenceFreshness::CURRENT,
            snapshotVersion: 't1',
            updateMethod: 'manual',
        );

        $boundary = $this->adrBoundBoundary();
        $plan = $boundary->plan(
            CoexistenceMode::CGHIA_ATTACHED,
            ['openalex'],
            'q',
            'q:and',
            'csq:and',
            capabilityRecords: [$onlyOai],
        );

        // Required dim is API — missing → UNVERIFIED
        $this->assertSame(
            CapabilityAndResultClass::HAS_UNVERIFIED,
            $plan->entries[0]->capabilityDecision?->andResultClass,
        );
        unset($api);
    }

    public function test_no_duplicate_authority_classes_in_coexistence(): void
    {
        $dir = dirname(__DIR__, 5).'/app/Services/Agriculture/Research/Coexistence';
        foreach (glob($dir.'/*.php') ?: [] as $file) {
            $src = (string) file_get_contents($file);
            $this->assertStringNotContainsString('class CapabilityRequirementEvaluator', $src);
            $this->assertStringNotContainsString('class CapabilityToPathEligibilityMapper', $src);
            $this->assertStringNotContainsString('class ProjectionEnvelope', $src);
            $this->assertStringNotContainsString('class C9ComposerFidelityDisclosureMapper', $src);
            $this->assertStringNotContainsString('class CorrelationChain', $src);
        }
    }

    private function boundary(): Stage3CghiaCoexistenceBoundary
    {
        return new Stage3CghiaCoexistenceBoundary(new Stage3SourceKeyIdentityBridge());
    }

    private function adrBoundBoundary(): Stage3CghiaCoexistenceBoundary
    {
        return new Stage3CghiaCoexistenceBoundary(
            new Stage3SourceKeyIdentityBridge([
                'openalex' => ['adr_id' => 'G3-OA-01'],
            ]),
        );
    }
}
