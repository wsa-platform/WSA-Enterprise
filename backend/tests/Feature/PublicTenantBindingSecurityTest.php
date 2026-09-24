<?php

namespace Tests\Feature;

use App\Models\LibraryItem;
use App\Models\Organization;
use App\Models\User;
use App\Services\Agriculture\Research\Persistence\ScientificKnowledgePersistenceService;
use App\Services\Agriculture\Research\ResearchPlanner;
use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use App\Services\Agriculture\Research\Synthesis\AnswerSynthesisExecutionReport;
use App\Services\Agriculture\Research\Synthesis\ResearchAnswerCitation;
use App\Services\Agriculture\Research\Synthesis\ResearchAnswerClaim;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use App\Services\Agriculture\Research\Validation\EvidenceValidationExecutionReport;
use App\Services\Agriculture\Research\Validation\EvidenceValidationStatus;
use App\Services\Agriculture\Research\Validation\ScientificEvidenceItem;
use App\Services\Tenancy\PublicTenantContext;
use App\Services\Tenancy\PublicTenantResolver;
use App\Services\Tenancy\TenantContext;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\FieldCropCultivationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * G1 / U8.1–U8.2 — MODEL B public tenant binding security matrix (TEST-01 … TEST-12).
 *
 * @group security
 */
class PublicTenantBindingSecurityTest extends TestCase
{
    use RefreshDatabase;

    private Organization $publicOrg;

    private Organization $victimOrg;

    protected function setUp(): void
    {
        parent::setUp();

        $this->publicOrg = Organization::create([
            'name' => 'WSA Demo',
            'slug' => 'wsa-demo',
            'is_active' => true,
        ]);
        $this->victimOrg = Organization::create([
            'name' => 'Victim Tenant',
            'slug' => 'victim-tenant',
            'is_active' => true,
        ]);

        Http::fake([
            'api.openalex.org/works*' => Http::response(['results' => []], 200),
            'api.crossref.org/works*' => Http::response(['message' => ['items' => []]], 200),
            'api.semanticscholar.org/*' => Http::response(['data' => []], 200),
            'faostatservices.fao.org/*' => Http::response(['message' => 'unavailable'], 503),
            'fenixservices.fao.org/*' => Http::response(['message' => 'unavailable'], 503),
        ]);
    }

    /** TEST-01 — Public request without organization uses configured public org. */
    public function test_01_omitted_organization_uses_configured_public_tenant(): void
    {
        $response = $this->postJson('/api/v1/public/research-agent/query', [
            'query' => 'wheat drip irrigation scheduling arid agriculture',
        ]);

        $response->assertOk();
        $this->assertSame($this->publicOrg->id, app(TenantContext::class)->organizationId());
        $this->assertTrue(app(TenantContext::class)->isPublicBound());
    }

    /** TEST-02 — Public request with valid public organization proceeds under that tenant. */
    public function test_02_valid_public_organization_slug_is_compatible_and_binds_public_tenant(): void
    {
        $response = $this->postJson('/api/v1/public/research-agent/query', [
            'organization' => 'wsa-demo',
            'query' => 'wheat drip irrigation scheduling arid agriculture',
        ]);

        $response->assertOk();
        $this->assertSame($this->publicOrg->id, app(TenantContext::class)->organizationId());
    }

    /** TEST-03 / TEST-04 — Client organization slug/id cannot select another tenant for writes. */
    public function test_03_04_client_organization_cannot_override_public_tenant_for_query(): void
    {
        $beforeVictim = LibraryItem::query()->where('organization_id', $this->victimOrg->id)->count();

        $bySlug = $this->postJson('/api/v1/public/research-agent/query', [
            'organization' => 'victim-tenant',
            'query' => 'wheat drip irrigation scheduling arid agriculture',
        ]);
        $bySlug->assertOk();
        $this->assertSame($this->publicOrg->id, app(TenantContext::class)->organizationId());

        $byId = $this->postJson('/api/v1/public/research-agent/query', [
            'organization_id' => $this->victimOrg->id,
            'query' => 'tomato greenhouse fertigation scheduling agriculture',
        ]);
        $byId->assertOk();
        $this->assertSame($this->publicOrg->id, app(TenantContext::class)->organizationId());

        $this->assertSame(
            $beforeVictim,
            LibraryItem::query()->where('organization_id', $this->victimOrg->id)->count(),
        );
    }

    /** TEST-05 — Nonexistent client organization does not 404 / does not fallback via client identity. */
    public function test_05_nonexistent_client_organization_is_ignored_not_oracle(): void
    {
        $response = $this->postJson('/api/v1/public/research-agent/query', [
            'organization' => 'does-not-exist-g1',
            'query' => 'wheat drip irrigation scheduling arid agriculture',
        ]);

        $response->assertOk();
        $this->assertNotSame('organization_not_found', $response->json('status'));
        $this->assertSame($this->publicOrg->id, app(TenantContext::class)->organizationId());
    }

    /** TEST-06 / TEST-07 — Authenticated membership isolation remains intact. */
    public function test_06_07_authenticated_cross_tenant_header_still_forbidden(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('email', 'admin@wsa.test')->firstOrFail();
        $token = $admin->createToken('g1-security')->plainTextToken;

        $memberOrgIds = $admin->organizations()->pluck('organizations.id')->all();
        $this->assertNotEmpty($memberOrgIds);
        $this->assertNotContains($this->victimOrg->id, $memberOrgIds);

        $this->getJson('/api/v1/audit-logs', [
            'Authorization' => 'Bearer '.$token,
            'X-Organization-Id' => (string) $this->victimOrg->id,
        ])->assertForbidden();
    }

    /** TEST-08 / TEST-09 — Persist defense-in-depth rejects mismatched public-bound org; allows matching. */
    public function test_08_09_persistence_rejects_mismatched_public_tenant_and_allows_bound(): void
    {
        app(TenantContext::class)->bindPublicTenant(new PublicTenantContext(
            organizationId: $this->publicOrg->id,
            slug: $this->publicOrg->slug,
        ));

        [$plan, $synthesis, $validation] = $this->persistableArtifacts();

        $rejected = app(ScientificKnowledgePersistenceService::class)->persist(
            $this->victimOrg->id,
            $plan,
            $synthesis,
            $validation,
        );
        $this->assertFalse($rejected->performed);
        $this->assertSame('public_tenant_mismatch', $rejected->status);
        $this->assertSame(0, LibraryItem::query()->where('organization_id', $this->victimOrg->id)->count());

        $allowed = app(ScientificKnowledgePersistenceService::class)->persist(
            $this->publicOrg->id,
            $plan,
            $synthesis,
            $validation,
        );
        $this->assertTrue($allowed->performed);
        $this->assertNotNull($allowed->libraryItemId);
        $this->assertDatabaseHas('library_items', [
            'id' => $allowed->libraryItemId,
            'organization_id' => $this->publicOrg->id,
        ]);
        $this->assertSame(0, LibraryItem::query()->where('organization_id', $this->victimOrg->id)->count());
    }

    /** TEST-10 — Fail closed when configured public organization is missing. */
    public function test_10_missing_public_organization_fails_closed(): void
    {
        $this->publicOrg->delete();
        config(['wsa.public_organization_slug' => 'wsa-demo']);

        $response = $this->postJson('/api/v1/public/research-agent/query', [
            'query' => 'wheat drip irrigation scheduling arid agriculture',
        ]);

        $response->assertStatus(503);
        $response->assertJsonPath('status', 'public_organization_unavailable');
        $this->assertStringNotContainsString('victim-tenant', (string) $response->getContent());
    }

    /** TEST-10b — Empty public slug config fails closed. */
    public function test_10b_empty_public_slug_config_fails_closed(): void
    {
        config(['wsa.public_organization_slug' => '']);

        $response = $this->postJson('/api/v1/public/research-agent/query', [
            'organization' => 'victim-tenant',
            'query' => 'wheat drip irrigation scheduling arid agriculture',
        ]);

        $response->assertStatus(503);
        $response->assertJsonPath('status', 'public_organization_unavailable');
        $this->assertSame(0, LibraryItem::query()->where('organization_id', $this->victimOrg->id)->count());
    }

    /** TEST-10c — Inactive public organization fails closed. */
    public function test_10c_inactive_public_organization_fails_closed(): void
    {
        $this->publicOrg->update(['is_active' => false]);

        $response = $this->postJson('/api/v1/public/research-agent/synthesize', [
            'organization_id' => $this->victimOrg->id,
            'query' => 'wheat drip irrigation scheduling arid agriculture',
        ]);

        $response->assertStatus(503);
        $response->assertJsonPath('status', 'public_organization_unavailable');
    }

    /** TEST-11 — Crop farming-needs-profile shares MODEL B binding. */
    public function test_11_crop_farming_needs_uses_public_tenant_not_client_org(): void
    {
        $this->seed(FieldCropCultivationSeeder::class);

        $beforeVictim = LibraryItem::query()->where('organization_id', $this->victimOrg->id)->count();

        $response = $this->getJson('/api/v1/public/field-crops/farming-needs-profile?'.http_build_query([
            'organization' => 'victim-tenant',
            'organization_id' => $this->victimOrg->id,
            'selected_crop_id' => 'wheat',
            'selected_crop_name' => 'القمح',
            'selected_category_id' => 'grains',
            'selected_category_name' => 'محاصيل الحبوب',
        ]));

        $response->assertOk();
        $this->assertSame($this->publicOrg->id, app(TenantContext::class)->organizationId());
        $this->assertTrue(app(TenantContext::class)->isPublicBound());
        $this->assertSame(
            $beforeVictim,
            LibraryItem::query()->where('organization_id', $this->victimOrg->id)->count(),
        );
        $this->assertGreaterThan(
            0,
            LibraryItem::query()->where('organization_id', $this->publicOrg->id)->count(),
        );
    }

    /** TEST-11b — Crop omit organization still binds public tenant. */
    public function test_11b_crop_omitted_organization_uses_public_tenant(): void
    {
        $this->seed(FieldCropCultivationSeeder::class);

        $response = $this->getJson('/api/v1/public/field-crops/farming-needs-profile?'.http_build_query([
            'selected_crop_id' => 'wheat',
            'selected_crop_name' => 'القمح',
        ]));

        $response->assertOk();
        $this->assertSame($this->publicOrg->id, app(TenantContext::class)->organizationId());
    }

    /** TEST-12 — Plant diagnosis unchanged; public browse uses server public tenant, not client org. */
    public function test_12_plant_diagnosis_and_browse_out_of_g1_write_scope(): void
    {
        $this->assertTrue(class_exists(\App\Http\Controllers\Api\PlantAiDiagnosisController::class));

        $browse = $this->getJson('/api/v1/public/library/items?organization=victim-tenant');
        $browse->assertOk();
        $browse->assertJsonPath('organization_slug', 'wsa-demo');
        $browse->assertJsonPath('organization_id', $this->publicOrg->id);
    }

    public function test_resolver_is_reusable_for_research_and_crop(): void
    {
        $a = app(PublicTenantResolver::class)->resolve();
        $b = app(PublicTenantResolver::class)->resolve();
        $this->assertSame($a->organizationId, $b->organizationId);
        $this->assertSame('wsa-demo', $a->slug);
    }

    public function test_authenticated_set_organization_clears_public_bound_flag(): void
    {
        $tenant = app(TenantContext::class);
        $tenant->bindPublicTenant(new PublicTenantContext(
            organizationId: $this->publicOrg->id,
            slug: 'wsa-demo',
        ));
        $this->assertTrue($tenant->isPublicBound());

        $tenant->setOrganizationId($this->victimOrg->id);
        $this->assertFalse($tenant->isPublicBound());
        $this->assertSame($this->victimOrg->id, $tenant->organizationId());
    }

    /**
     * @return array{0: \App\Services\Agriculture\Research\KnowledgeQueryPlan, 1: AnswerSynthesisExecutionReport, 2: EvidenceValidationExecutionReport}
     */
    private function persistableArtifacts(): array
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'vegetable production greenhouse systems',
        ]);
        $item = new ScientificEvidenceItem(
            evidenceId: 'ev-g1-persist',
            sourceId: 'source-g1',
            sourceKey: 'openalex',
            sourceType: 'university_research',
            publicationTitle: 'Vegetable production greenhouse systems',
            authors: ['Dr Researcher'],
            institution: 'University of Agriculture',
            journal: 'Journal of Agronomy',
            doi: '10.1000/g1-persist',
            url: 'https://doi.org/10.1000/g1-persist',
            publicationYear: 2023,
            retrievedAt: now()->toIso8601String(),
            agriculturalDomain: 'general',
            claimTopic: 'vegetable production',
            evidenceText: 'Vegetable production greenhouse systems for year round farming.',
            validationStatus: EvidenceValidationStatus::EVIDENCE_USABLE,
            validationFailures: [],
            claimRelationship: ClaimEvidenceRelationship::SUPPORTED,
            confidence: 0.8,
            qualityScore: 75.0,
            qualityFactors: [
                'not_scientific_certainty' => true,
                'evidence_directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
            ],
            sourceAttribution: [
                'organization' => 'University of Agriculture',
                'source_type' => 'university_research',
                'evidence_directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
            ],
        );
        $validation = new EvidenceValidationExecutionReport(
            status: 'validation_completed',
            validatedEvidence: [$item],
            rejectedEvidence: [],
            sourcesReceived: 1,
            validatedCount: 1,
            rejectedCount: 0,
            duplicateCount: 0,
            conflictingCount: 0,
            evidenceSufficient: true,
            validatorsUsed: [],
            qualityDistribution: [],
            searchSummary: [],
            observability: [],
        );

        // Construct synthesis DTOs directly so this security test does not depend on Composer WIP.
        $claim = new ResearchAnswerClaim(
            claimId: 'claim-g1',
            claimText: 'Greenhouse systems support year-round vegetable production.',
            evidenceIds: ['ev-g1-persist'],
            sourceIds: ['source-g1'],
            validationStatus: EvidenceValidationStatus::EVIDENCE_USABLE,
            claimRelationship: ClaimEvidenceRelationship::SUPPORTED,
            confidence: 0.8,
        );
        $citation = new ResearchAnswerCitation(
            citationId: 'cite-g1',
            sourceId: 'source-g1',
            evidenceId: 'ev-g1-persist',
            title: 'Vegetable production greenhouse systems',
            authors: ['Dr Researcher'],
            organization: 'University of Agriculture',
            journal: 'Journal of Agronomy',
            doi: '10.1000/g1-persist',
            url: 'https://doi.org/10.1000/g1-persist',
            publicationYear: 2023,
            sourceType: 'university_research',
        );
        $synthesis = new AnswerSynthesisExecutionReport(
            status: 'answered',
            performed: true,
            answer: 'Greenhouse systems support year-round vegetable production.',
            conciseSummary: 'Greenhouses support year-round vegetable production.',
            detailedExplanation: 'Validated evidence supports greenhouse vegetable production.',
            keyFindings: ['Greenhouse production enables year-round harvests.'],
            claims: [$claim],
            citations: [$citation],
            evidenceReferences: [],
            confidence: 0.8,
            limitations: [],
            uncertainty: null,
            conflicts: [],
            language: 'en',
            researchMetadata: [],
            observability: [],
        );

        return [$plan, $synthesis, $validation];
    }
}
