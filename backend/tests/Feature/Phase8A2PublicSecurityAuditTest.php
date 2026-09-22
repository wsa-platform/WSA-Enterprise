<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\LibraryItem;
use App\Models\Organization;
use App\Models\ResearchFeedbackRecord;
use App\Models\User;
use App\Services\Agriculture\Research\Feedback\PositiveResearchFeedbackService;
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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Phase 8A-2 — Structured security-audit event scope for public MODEL B writes.
 *
 * @group security
 */
class Phase8A2PublicSecurityAuditTest extends TestCase
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

    public function test_01_client_organization_override_is_durably_audited_without_raw_client_values(): void
    {
        $response = $this->postJson('/api/v1/public/research-agent/query', [
            'organization' => 'victim-tenant',
            'organization_id' => $this->victimOrg->id,
            'query' => 'wheat drip irrigation scheduling arid agriculture',
            'password' => 'should-never-audit',
            'token' => 'tok-should-never-audit',
        ], [
            'X-Request-Id' => 'p8a2-override-req',
        ]);

        $response->assertOk();
        $this->assertSame($this->publicOrg->id, app(TenantContext::class)->organizationId());

        $log = AuditLog::query()
            ->where('action', PublicTenantResolver::ACTION_CLIENT_OVERRIDE_IGNORED)
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('p8a2-override-req', $log->request_id);
        $this->assertSame($this->publicOrg->id, $log->organization_id);
        $this->assertNull($log->user_id);
        $this->assertTrue((bool) ($log->new_values['client_organization_id_present'] ?? false));
        $this->assertTrue((bool) ($log->new_values['client_organization_slug_present'] ?? false));
        $this->assertPrivacySafeMetadata($log);
        $this->assertArrayNotHasKey('client_organization_id', $log->new_values ?? []);
        $this->assertArrayNotHasKey('client_organization_slug', $log->new_values ?? []);
        $this->assertArrayNotHasKey('organization', $log->new_values ?? []);
        $this->assertArrayNotHasKey('organization_id', $log->new_values ?? []);
        $this->assertArrayNotHasKey('attempted_organization_id', $log->new_values ?? []);
        $this->assertStringNotContainsString('victim-tenant', json_encode($log->new_values) ?: '');
    }

    public function test_02_public_organization_unavailable_is_audited_once_with_request_id(): void
    {
        Log::spy();
        config(['wsa.public_organization_slug' => 'missing-public-org-p8a2']);

        $response = $this->postJson('/api/v1/public/research-agent/query', [
            'query' => 'wheat drip irrigation scheduling arid agriculture',
        ], [
            'X-Request-Id' => 'p8a2-unavailable-req',
        ]);

        $response->assertStatus(503);
        $response->assertJsonPath('status', 'public_organization_unavailable');

        Log::shouldHaveReceived('warning')->withArgs(function (string $message): bool {
            return $message === 'security.public_tenant_not_found';
        })->once();

        $logs = AuditLog::query()
            ->where('action', PublicTenantResolver::ACTION_PUBLIC_TENANT_UNAVAILABLE)
            ->get();

        $this->assertCount(1, $logs);
        $this->assertSame('p8a2-unavailable-req', $logs->first()->request_id);
        $this->assertNull($logs->first()->user_id);
        $this->assertSame('not_found', $logs->first()->new_values['reason'] ?? null);
        $this->assertPrivacySafeMetadata($logs->first());
        $this->assertArrayNotHasKey('configured_slug', $logs->first()->new_values ?? []);
        $this->assertStringNotContainsString('APP_KEY', json_encode($logs->first()->new_values) ?: '');
    }

    public function test_02b_empty_and_inactive_public_organization_each_emit_one_unavailable_audit(): void
    {
        config(['wsa.public_organization_slug' => '']);
        $this->bindRequestId('p8a2-empty-slug');
        try {
            app(PublicTenantResolver::class)->resolve();
            $this->fail('Expected PublicTenantResolutionException');
        } catch (\App\Services\Tenancy\PublicTenantResolutionException) {
            // expected
        }
        $this->assertSame(
            1,
            AuditLog::query()->where('action', PublicTenantResolver::ACTION_PUBLIC_TENANT_UNAVAILABLE)->count(),
        );
        $empty = AuditLog::query()->where('action', PublicTenantResolver::ACTION_PUBLIC_TENANT_UNAVAILABLE)->first();
        $this->assertSame('empty_public_organization_slug', $empty?->new_values['reason'] ?? null);
        $this->assertSame('p8a2-empty-slug', $empty?->request_id);

        AuditLog::query()->delete();

        $this->publicOrg->update(['is_active' => false]);
        config(['wsa.public_organization_slug' => 'wsa-demo']);
        $this->bindRequestId('p8a2-inactive-org');
        try {
            app(PublicTenantResolver::class)->resolve();
            $this->fail('Expected PublicTenantResolutionException');
        } catch (\App\Services\Tenancy\PublicTenantResolutionException) {
            // expected
        }
        $inactive = AuditLog::query()->where('action', PublicTenantResolver::ACTION_PUBLIC_TENANT_UNAVAILABLE)->get();
        $this->assertCount(1, $inactive);
        $this->assertSame('inactive', $inactive->first()->new_values['reason'] ?? null);
        $this->assertSame($this->publicOrg->id, $inactive->first()->organization_id);
        $this->assertSame('p8a2-inactive-org', $inactive->first()->request_id);
    }

    public function test_03_public_persistence_tenant_mismatch_is_rejected_and_audited(): void
    {
        Log::spy();
        $this->bindRequestId('p8a2-mismatch-req');
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

        Log::shouldHaveReceived('warning')->withArgs(function (string $message): bool {
            return $message === 'security.public_tenant_persistence_rejected';
        })->once();

        $log = AuditLog::query()
            ->where('action', ScientificKnowledgePersistenceService::ACTION_PERSISTENCE_REJECTED)
            ->latest('id')
            ->first();
        $this->assertNotNull($log);
        $this->assertSame('p8a2-mismatch-req', $log->request_id);
        $this->assertSame($this->publicOrg->id, $log->organization_id);
        $this->assertNull($log->user_id);
        $this->assertTrue((bool) ($log->new_values['mismatch'] ?? false));
        $this->assertSame(
            0,
            AuditLog::query()->where('action', ScientificKnowledgePersistenceService::ACTION_PERSISTENCE_ACCEPTED)->count(),
        );
        $this->assertPrivacySafeMetadata($log);
        $this->assertArrayNotHasKey('attempted_organization_id', $log->new_values ?? []);
        $this->assertArrayNotHasKey('client_organization_id', $log->new_values ?? []);
        $this->assertArrayNotHasKey('client_organization_slug', $log->new_values ?? []);
        $this->assertStringNotContainsString('victim-tenant', json_encode($log->new_values) ?: '');
    }

    public function test_04_successful_public_library_persistence_is_audited_without_content(): void
    {
        $this->bindRequestId('p8a2-persist-ok');
        app(TenantContext::class)->bindPublicTenant(new PublicTenantContext(
            organizationId: $this->publicOrg->id,
            slug: $this->publicOrg->slug,
        ));

        [$plan, $synthesis, $validation] = $this->persistableArtifacts();
        $allowed = app(ScientificKnowledgePersistenceService::class)->persist(
            $this->publicOrg->id,
            $plan,
            $synthesis,
            $validation,
        );

        $this->assertTrue($allowed->performed);
        $this->assertNotNull($allowed->libraryItemId);

        $log = AuditLog::query()
            ->where('action', ScientificKnowledgePersistenceService::ACTION_PERSISTENCE_ACCEPTED)
            ->where('new_values->persistence_surface', 'library')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('p8a2-persist-ok', $log->request_id);
        $this->assertSame($this->publicOrg->id, $log->organization_id);
        $this->assertNull($log->user_id);
        $this->assertPrivacySafeMetadata($log);
        $encoded = json_encode($log->new_values) ?: '';
        $this->assertStringNotContainsString('Greenhouse systems support', $encoded);
        $this->assertStringNotContainsString('Vegetable production greenhouse', $encoded);
        $this->assertStringNotContainsString('evidenceText', $encoded);
    }

    public function test_05_successful_feedback_persistence_is_audited_once_without_notes(): void
    {
        $response = $this->postJson('/api/v1/public/research-agent/feedback', [
            'polarity' => 'positive',
            'organization' => 'victim-tenant',
            'question' => 'SECRET_QUESTION_TEXT_SHOULD_NOT_AUDIT',
            'what_worked' => 'SECRET_FEEDBACK_NOTE_SHOULD_NOT_AUDIT',
            'search_notes' => 'SECRET_SEARCH_NOTES',
        ], [
            'X-Request-Id' => 'p8a2-feedback-ok',
        ]);

        $response->assertCreated();
        $this->assertSame(1, ResearchFeedbackRecord::query()->count());

        $accepted = AuditLog::query()
            ->where('action', PositiveResearchFeedbackService::ACTION_PERSISTENCE_ACCEPTED)
            ->where('new_values->persistence_surface', 'feedback')
            ->get();
        $this->assertCount(1, $accepted);
        $this->assertSame('p8a2-feedback-ok', $accepted->first()->request_id);
        $this->assertSame($this->publicOrg->id, $accepted->first()->organization_id);
        $this->assertNull($accepted->first()->user_id);
        $this->assertPrivacySafeMetadata($accepted->first());
        $encoded = json_encode($accepted->first()->new_values) ?: '';
        $this->assertStringNotContainsString('SECRET_QUESTION_TEXT_SHOULD_NOT_AUDIT', $encoded);
        $this->assertStringNotContainsString('SECRET_FEEDBACK_NOTE_SHOULD_NOT_AUDIT', $encoded);
        $this->assertStringNotContainsString('SECRET_SEARCH_NOTES', $encoded);

        $override = AuditLog::query()
            ->where('action', PublicTenantResolver::ACTION_CLIENT_OVERRIDE_IGNORED)
            ->get();
        $this->assertCount(1, $override);
        $this->assertStringNotContainsString('victim-tenant', json_encode($override->first()->new_values) ?: '');
    }

    public function test_06_public_audit_events_use_null_user_id(): void
    {
        $this->postJson('/api/v1/public/research-agent/feedback', [
            'polarity' => 'positive',
            'organization_id' => $this->victimOrg->id,
            'question' => 'anonymous actor check',
        ], [
            'X-Request-Id' => 'p8a2-anonymous',
        ])->assertCreated();

        $actions = [
            PublicTenantResolver::ACTION_CLIENT_OVERRIDE_IGNORED,
            PositiveResearchFeedbackService::ACTION_PERSISTENCE_ACCEPTED,
        ];

        foreach ($actions as $action) {
            $log = AuditLog::query()->where('action', $action)->latest('id')->first();
            $this->assertNotNull($log, $action);
            $this->assertNull($log->user_id, $action);
        }
    }

    public function test_07_phase8a2_action_names_fit_audit_logs_action_column(): void
    {
        $actions = [
            PublicTenantResolver::ACTION_CLIENT_OVERRIDE_IGNORED,
            PublicTenantResolver::ACTION_PUBLIC_TENANT_UNAVAILABLE,
            ScientificKnowledgePersistenceService::ACTION_PERSISTENCE_REJECTED,
            ScientificKnowledgePersistenceService::ACTION_PERSISTENCE_ACCEPTED,
            PositiveResearchFeedbackService::ACTION_PERSISTENCE_ACCEPTED,
        ];

        foreach ($actions as $action) {
            $this->assertLessThanOrEqual(50, strlen($action), $action);
            $this->assertStringStartsWith('security.public_tenant_', $action);
        }

        $this->assertSame(
            ScientificKnowledgePersistenceService::ACTION_PERSISTENCE_ACCEPTED,
            PositiveResearchFeedbackService::ACTION_PERSISTENCE_ACCEPTED,
        );
    }

    public function test_08_privacy_negative_fields_are_absent_from_security_audit_metadata(): void
    {
        $this->postJson('/api/v1/public/research-agent/query', [
            'organization' => 'victim-tenant',
            'query' => 'wheat drip irrigation scheduling arid agriculture',
            'password' => 'p@ss',
            'token' => 'abc',
            'api_key' => 'key',
            'authorization' => 'Bearer xyz',
            'question' => 'should-not-appear-in-override-audit',
            'answer' => 'should-not-appear',
            'evidence' => 'should-not-appear',
        ], [
            'X-Request-Id' => 'p8a2-privacy',
            'Authorization' => 'Bearer should-not-land-in-audit',
        ])->assertOk();

        $log = AuditLog::query()
            ->where('action', PublicTenantResolver::ACTION_CLIENT_OVERRIDE_IGNORED)
            ->latest('id')
            ->first();
        $this->assertNotNull($log);
        $this->assertPrivacySafeMetadata($log);
    }

    public function test_09_request_id_correlation_matches_response_header(): void
    {
        $response = $this->postJson('/api/v1/public/research-agent/feedback', [
            'polarity' => 'positive',
            'organization' => 'victim-tenant',
        ], [
            'X-Request-Id' => 'p8a2-correlation-42',
        ]);

        $response->assertCreated();
        $this->assertSame('p8a2-correlation-42', $response->headers->get('X-Request-Id'));

        foreach ([
            PublicTenantResolver::ACTION_CLIENT_OVERRIDE_IGNORED,
            PositiveResearchFeedbackService::ACTION_PERSISTENCE_ACCEPTED,
        ] as $action) {
            $log = AuditLog::query()->where('action', $action)->latest('id')->first();
            $this->assertSame('p8a2-correlation-42', $log?->request_id, $action);
        }
    }

    public function test_10_authenticated_cross_tenant_denial_audit_still_works(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('email', 'admin@wsa.test')->firstOrFail();
        $token = $admin->createToken('p8a2-auth-regression')->plainTextToken;

        $this->getJson('/api/v1/dashboard', [
            'Authorization' => 'Bearer '.$token,
            'X-Organization-Id' => '999999',
            'X-Request-Id' => 'p8a2-cross-tenant',
        ])->assertForbidden();

        $log = AuditLog::query()->where('action', 'security.cross_tenant_denied')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame('p8a2-cross-tenant', $log->request_id);
    }

    public function test_11_successful_public_bind_does_not_emit_bind_success_audit(): void
    {
        $before = AuditLog::query()->count();

        $response = $this->postJson('/api/v1/public/research-agent/query', [
            'query' => 'wheat drip irrigation scheduling arid agriculture',
        ], [
            'X-Request-Id' => 'p8a2-silent-bind',
        ]);

        $response->assertOk();
        $this->assertTrue(app(TenantContext::class)->isPublicBound());

        $this->assertSame(0, AuditLog::query()->where('action', 'like', '%bound%')->count());
        $this->assertSame(0, AuditLog::query()->where('action', 'like', '%bind%')->count());
        $this->assertSame(
            0,
            AuditLog::query()->where('action', PublicTenantResolver::ACTION_CLIENT_OVERRIDE_IGNORED)->count(),
        );
        $this->assertSame(
            0,
            AuditLog::query()->where('action', PublicTenantResolver::ACTION_PUBLIC_TENANT_UNAVAILABLE)->count(),
        );
        // H2: successful bind itself must not create a dedicated success audit.
        $this->assertSame($before, AuditLog::query()->count());
    }

    public function test_12_ordinary_nothing_to_persist_skip_is_not_a_security_audit(): void
    {
        $this->bindRequestId('p8a2-nonssecurity-skip');
        app(TenantContext::class)->bindPublicTenant(new PublicTenantContext(
            organizationId: $this->publicOrg->id,
            slug: $this->publicOrg->slug,
        ));

        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'vegetable production greenhouse systems',
        ]);
        $emptySynthesis = new AnswerSynthesisExecutionReport(
            status: 'insufficient_evidence',
            performed: false,
            answer: null,
            conciseSummary: null,
            detailedExplanation: null,
            keyFindings: [],
            claims: [],
            citations: [],
            evidenceReferences: [],
            confidence: 0.0,
            limitations: [],
            uncertainty: null,
            conflicts: [],
            language: 'en',
            researchMetadata: [],
            observability: [],
        );
        $emptyValidation = new EvidenceValidationExecutionReport(
            status: 'validation_completed',
            validatedEvidence: [],
            rejectedEvidence: [],
            sourcesReceived: 0,
            validatedCount: 0,
            rejectedCount: 0,
            duplicateCount: 0,
            conflictingCount: 0,
            evidenceSufficient: false,
            validatorsUsed: [],
            qualityDistribution: [],
            searchSummary: [],
            observability: [],
        );

        $skipped = app(ScientificKnowledgePersistenceService::class)->persist(
            $this->publicOrg->id,
            $plan,
            $emptySynthesis,
            $emptyValidation,
        );

        $this->assertFalse($skipped->performed);
        $this->assertSame(
            0,
            AuditLog::query()->where('action', ScientificKnowledgePersistenceService::ACTION_PERSISTENCE_REJECTED)->count(),
        );
        $this->assertSame(
            0,
            AuditLog::query()->where('action', ScientificKnowledgePersistenceService::ACTION_PERSISTENCE_ACCEPTED)->count(),
        );
    }

    private function bindRequestId(string $requestId): void
    {
        $request = Request::create('/api/v1/public/research-agent/query', 'POST');
        $request->attributes->set('request_id', $requestId);
        $this->app->instance('request', $request);
    }

    private function assertPrivacySafeMetadata(AuditLog $log): void
    {
        $payload = strtolower(json_encode([
            'old' => $log->old_values,
            'new' => $log->new_values,
        ]) ?: '');

        foreach ([
            'password',
            'token',
            'api_key',
            'api-key',
            'authorization',
            'bearer ',
            'secret_question',
            'secret_feedback',
            'secret_search',
        ] as $needle) {
            $this->assertStringNotContainsString($needle, $payload);
        }
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
            evidenceId: 'ev-p8a2-persist',
            sourceId: 'source-p8a2',
            sourceKey: 'openalex',
            sourceType: 'university_research',
            publicationTitle: 'Vegetable production greenhouse systems',
            authors: ['Dr Researcher'],
            institution: 'University of Agriculture',
            journal: 'Journal of Agronomy',
            doi: '10.1000/p8a2-persist',
            url: 'https://doi.org/10.1000/p8a2-persist',
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

        $claim = new ResearchAnswerClaim(
            claimId: 'claim-p8a2',
            claimText: 'Greenhouse systems support year-round vegetable production.',
            evidenceIds: ['ev-p8a2-persist'],
            sourceIds: ['source-p8a2'],
            validationStatus: EvidenceValidationStatus::EVIDENCE_USABLE,
            claimRelationship: ClaimEvidenceRelationship::SUPPORTED,
            confidence: 0.8,
        );
        $citation = new ResearchAnswerCitation(
            citationId: 'cite-p8a2',
            sourceId: 'source-p8a2',
            evidenceId: 'ev-p8a2-persist',
            title: 'Vegetable production greenhouse systems',
            authors: ['Dr Researcher'],
            organization: 'University of Agriculture',
            journal: 'Journal of Agronomy',
            doi: '10.1000/p8a2-persist',
            url: 'https://doi.org/10.1000/p8a2-persist',
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
