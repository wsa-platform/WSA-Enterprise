<?php

namespace Tests\Feature;

use App\Models\LibraryCategory;
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
use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Phase 8B-2 — Library scientific research product contract.
 *
 * @group security
 */
class Phase8B2LibraryScientificResearchContractTest extends TestCase
{
    use RefreshDatabase;

    private Organization $publicOrg;

    private Organization $otherOrg;

    protected function setUp(): void
    {
        parent::setUp();

        $this->publicOrg = Organization::create([
            'name' => 'WSA Demo',
            'slug' => 'wsa-demo',
            'is_active' => true,
        ]);
        $this->otherOrg = Organization::create([
            'name' => 'Other Farm Co',
            'slug' => 'other-farm',
            'is_active' => true,
        ]);

        Http::fake([
            'example.test/*' => Http::response('%PDF-1.4 research-source', 200, [
                'Content-Type' => 'application/pdf',
            ]),
            'api.openalex.org/works*' => Http::response(['results' => []], 200),
            'api.crossref.org/works*' => Http::response(['message' => ['items' => []]], 200),
            'api.semanticscholar.org/*' => Http::response(['data' => []], 200),
            'faostatservices.fao.org/*' => Http::response(['message' => 'unavailable'], 503),
            'fenixservices.fao.org/*' => Http::response(['message' => 'unavailable'], 503),
        ]);
    }

    public function test_01_r5_eligibility_still_gates_library_save(): void
    {
        $plan = $this->plan('wheat drip irrigation arid regions', cropId: 'wheat', cropName: 'Wheat');
        $item = $this->evidence('r5-gate');
        $validation = $this->validation([$item], evidenceSufficient: false);
        $synthesis = $this->synthesis([$this->claim('c1', $item)], [$this->citation($item)]);

        $result = app(ScientificKnowledgePersistenceService::class)->persist(
            $this->publicOrg->id,
            $plan,
            $synthesis,
            $validation,
        );

        $this->assertFalse($result->performed);
        $this->assertSame('insufficiently_verified', $result->status);
        $this->assertSame(0, LibraryItem::query()->count());
    }

    public function test_02_03_04_scientific_research_folder_create_and_reuse(): void
    {
        $plan = $this->plan('wheat drip irrigation arid regions', cropId: 'wheat', cropName: 'Wheat');
        [$synthesis, $validation] = $this->eligibleArtifacts($plan, 'folder-1');

        $first = app(ScientificKnowledgePersistenceService::class)->persist(
            $this->publicOrg->id,
            $plan,
            $synthesis,
            $validation,
        );
        $this->assertTrue($first->performed);
        $this->assertSame('created', $first->action);

        $row = LibraryItem::query()->findOrFail($first->libraryItemId);
        $this->assertSame(ScientificKnowledgePersistenceService::ITEM_TYPE_VERIFIED_RESEARCH, $row->item_type);
        $this->assertNull($row->owner_user_id);
        $this->assertNotNull($row->category_id);

        $scientific = LibraryCategory::query()->findOrFail($row->category_id);
        $this->assertSame('Scientific Research', $scientific->name);
        $topic = LibraryCategory::query()->findOrFail($scientific->parent_id);
        $this->assertSame('Wheat', $topic->name);

        $categoryCountBefore = LibraryCategory::query()->where('organization_id', $this->publicOrg->id)->count();

        // Different research identity under the same crop topic should reuse folders.
        $planB = $this->plan('wheat fertilizer nitrogen timing', cropId: 'wheat', cropName: 'Wheat');
        [$synthesisB, $validationB] = $this->eligibleArtifacts($planB, 'folder-2');
        $second = app(ScientificKnowledgePersistenceService::class)->persist(
            $this->publicOrg->id,
            $planB,
            $synthesisB,
            $validationB,
        );
        $this->assertTrue($second->performed);
        $this->assertSame('created', $second->action);

        $this->assertSame(
            $categoryCountBefore,
            LibraryCategory::query()->where('organization_id', $this->publicOrg->id)->count(),
        );
        $rowB = LibraryItem::query()->findOrFail($second->libraryItemId);
        $this->assertSame((int) $scientific->id, (int) $rowB->category_id);
    }

    public function test_05_06_duplicate_research_does_not_create_another_item_or_file(): void
    {
        Storage::fake('local');
        $plan = $this->plan('tomato greenhouse fertigation', cropId: 'tomato', cropName: 'Tomato');
        [$synthesis, $validation] = $this->eligibleArtifacts($plan, 'dup-1', withUrl: true);

        $service = app(ScientificKnowledgePersistenceService::class);
        $first = $service->persist($this->publicOrg->id, $plan, $synthesis, $validation);
        $this->assertSame('created', $first->action);

        $item = LibraryItem::query()->findOrFail($first->libraryItemId);
        $path = (string) $item->file_path;
        $this->assertNotSame('', $path);
        $this->assertTrue(Storage::disk('local')->exists($path));
        $filesBefore = count(Storage::disk('local')->allFiles());

        $second = $service->persist($this->publicOrg->id, $plan, $synthesis, $validation);
        $this->assertSame('unchanged', $second->action);
        $this->assertSame($first->libraryItemId, $second->libraryItemId);
        $this->assertSame(1, LibraryItem::query()->where('item_type', ScientificKnowledgePersistenceService::ITEM_TYPE_VERIFIED_RESEARCH)->count());
        $this->assertSame($filesBefore, count(Storage::disk('local')->allFiles()));

        // Same identity, different evidence fingerprint: still no second item / overwrite.
        $altEvidence = $this->evidence('dup-alt', url: 'https://example.test/r5/dup-alt');
        $altValidation = $this->validation([$altEvidence], evidenceSufficient: true);
        $altSynthesis = $this->synthesis([$this->claim('c-alt', $altEvidence)], [$this->citation($altEvidence)]);
        $third = $service->persist($this->publicOrg->id, $plan, $altSynthesis, $altValidation);
        $this->assertSame('unchanged', $third->action);
        $this->assertSame($first->libraryItemId, $third->libraryItemId);
        $this->assertSame(1, LibraryItem::query()->count());
        $this->assertSame(
            'same_research_identity',
            $third->observability['duplicate_protection'] ?? null,
        );
    }

    public function test_07_08_ordinary_authenticated_member_can_view_scientific_research_without_supervisor(): void
    {
        $plan = $this->plan('wheat protein quality irrigation', cropId: 'wheat', cropName: 'Wheat');
        [$synthesis, $validation] = $this->eligibleArtifacts($plan, 'auth-view');
        $persisted = app(ScientificKnowledgePersistenceService::class)->persist(
            $this->publicOrg->id,
            $plan,
            $synthesis,
            $validation,
        );
        $this->assertTrue($persisted->performed);

        $member = $this->ordinaryMember($this->otherOrg, 'library-member@wsa.test');
        $token = $member->createToken('p8b2')->plainTextToken;

        $response = $this->getJson('/api/v1/library/items', [
            'Authorization' => 'Bearer '.$token,
            'X-Organization-Id' => (string) $this->otherOrg->id,
        ]);

        $response->assertOk();
        $payload = $response->json();
        $items = is_array($payload) && array_is_list($payload)
            ? $payload
            : (is_array($payload['data'] ?? null) ? $payload['data'] : []);
        $ids = collect($items)->pluck('id')->all();
        $this->assertContains($persisted->libraryItemId, $ids);

        $row = collect($items)->firstWhere('id', $persisted->libraryItemId);
        $this->assertSame(ScientificKnowledgePersistenceService::ITEM_TYPE_VERIFIED_RESEARCH, $row['item_type'] ?? null);
        $this->assertTrue(array_key_exists('owner_user_id', $row));
        $this->assertNull($row['owner_user_id']);
        // Explicitly prove supervisor permission is not required.
        $this->assertFalse(
            app(\App\Services\Ownership\ServiceOwnershipAuthorizer::class)
                ->canSupervise($member, $this->otherOrg->id),
        );
    }

    public function test_09_organization_is_storage_tenant_not_publisher_identity(): void
    {
        $plan = $this->plan('olive irrigation scheduling', cropId: 'olive', cropName: 'Olive');
        [$synthesis, $validation] = $this->eligibleArtifacts($plan, 'org-role');
        $persisted = app(ScientificKnowledgePersistenceService::class)->persist(
            $this->publicOrg->id,
            $plan,
            $synthesis,
            $validation,
        );

        $item = LibraryItem::query()->findOrFail($persisted->libraryItemId);
        $this->assertSame($this->publicOrg->id, $item->organization_id);
        $this->assertNull($item->owner_user_id);
        $this->assertNull($item->author);
        $meta = is_array($item->metadata) ? $item->metadata : [];
        $this->assertArrayNotHasKey('published_by_organization_name', $meta);
        $this->assertSame('scientific-research', $meta['library_file_section'] ?? null);
    }

    public function test_10_anonymous_public_library_excludes_verified_research(): void
    {
        $plan = $this->plan('barley drought tolerance', cropId: 'barley', cropName: 'Barley');
        [$synthesis, $validation] = $this->eligibleArtifacts($plan, 'public-hide');
        $persisted = app(ScientificKnowledgePersistenceService::class)->persist(
            $this->publicOrg->id,
            $plan,
            $synthesis,
            $validation,
        );

        LibraryItem::create([
            'organization_id' => $this->publicOrg->id,
            'slug' => 'manual-published-guide',
            'title' => 'Manual Guide',
            'item_type' => 'guide',
            'publication_status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/public/library/items?organization=wsa-demo');
        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertNotContains($persisted->libraryItemId, $ids);
        $this->assertContains(
            LibraryItem::query()->where('slug', 'manual-published-guide')->value('id'),
            $ids,
        );
    }

    public function test_11_anonymous_crop_files_exclude_scientific_research_originals(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('library/crop-files/ok.pdf', '%PDF-1.4 ok');
        Storage::disk('local')->put('research-sources/1/secret.pdf', '%PDF-1.4 research');

        $plan = $this->plan('maize nitrogen uptake', cropId: 'maize', cropName: 'Maize');
        [$synthesis, $validation] = $this->eligibleArtifacts($plan, 'crop-file-hide', withUrl: true);
        $persisted = app(ScientificKnowledgePersistenceService::class)->persist(
            $this->publicOrg->id,
            $plan,
            $synthesis,
            $validation,
        );
        $research = LibraryItem::query()->findOrFail($persisted->libraryItemId);
        $this->assertNotSame('', (string) $research->file_path);

        $allowed = LibraryItem::create([
            'organization_id' => $this->publicOrg->id,
            'slug' => 'maize-farming-needs-file',
            'title' => 'Maize Farming Needs',
            'item_type' => 'crop_library_file',
            'publication_status' => 'published',
            'published_at' => now(),
            'file_disk' => 'local',
            'file_path' => 'library/crop-files/ok.pdf',
            'metadata' => [
                'plant_production_category_id' => 'grains',
                'field_crop_id' => 'maize',
                'library_file_section' => 'farming-needs',
            ],
        ]);

        $index = $this->getJson('/api/v1/public/library/crop-files?'.http_build_query([
            'organization' => 'wsa-demo',
            'plant_production_category_id' => 'grains',
            'field_crop_id' => 'maize',
            'library_file_section' => 'farming-needs',
        ]));
        $index->assertOk();
        $ids = collect($index->json('data'))->pluck('id')->all();
        $this->assertContains($allowed->id, $ids);
        $this->assertNotContains($research->id, $ids);

        $this->getJson('/api/v1/public/library/crop-files/'.$research->id.'/content?organization=wsa-demo')
            ->assertNotFound();
    }

    public function test_12_r1_public_write_still_binds_server_public_tenant(): void
    {
        app(TenantContext::class)->bindPublicTenant(new PublicTenantContext(
            organizationId: $this->publicOrg->id,
            slug: $this->publicOrg->slug,
        ));

        $plan = $this->plan('sorghum water use efficiency', cropId: 'sorghum', cropName: 'Sorghum');
        [$synthesis, $validation] = $this->eligibleArtifacts($plan, 'r1-bind');

        $rejected = app(ScientificKnowledgePersistenceService::class)->persist(
            $this->otherOrg->id,
            $plan,
            $synthesis,
            $validation,
        );
        $this->assertFalse($rejected->performed);
        $this->assertSame('public_tenant_mismatch', $rejected->status);

        $allowed = app(ScientificKnowledgePersistenceService::class)->persist(
            $this->publicOrg->id,
            $plan,
            $synthesis,
            $validation,
        );
        $this->assertTrue($allowed->performed);
        $this->assertDatabaseHas('library_items', [
            'id' => $allowed->libraryItemId,
            'organization_id' => $this->publicOrg->id,
            'item_type' => ScientificKnowledgePersistenceService::ITEM_TYPE_VERIFIED_RESEARCH,
        ]);
    }

    private function ordinaryMember(Organization $organization, string $email): User
    {
        $user = User::create([
            'name' => 'Ordinary Member',
            'email' => $email,
            'password' => Hash::make('password'),
        ]);
        $organization->members()->syncWithoutDetaching([
            $user->id => ['role' => 'member', 'is_active' => true],
        ]);

        return $user;
    }

    private function plan(
        string $query,
        ?string $cropId = null,
        ?string $cropName = null,
        ?string $entityValue = null,
        ?string $entityLabel = null,
        ?string $entityType = null,
    ): \App\Services\Agriculture\Research\KnowledgeQueryPlan {
        $input = ['query' => $query];
        if ($cropId !== null) {
            $input['selected_crop_id'] = $cropId;
            $input['selected_crop_name'] = $cropName ?? $cropId;
        }
        if ($entityValue !== null) {
            $input['subject_entity'] = [
                'type' => $entityType ?? 'entity',
                'value' => $entityValue,
                'label' => $entityLabel ?? $entityValue,
            ];
        }

        return app(ResearchPlanner::class)->planKnowledgeQuery($input);
    }

    /**
     * @return array{0: AnswerSynthesisExecutionReport, 1: EvidenceValidationExecutionReport}
     */
    private function eligibleArtifacts(
        \App\Services\Agriculture\Research\KnowledgeQueryPlan $plan,
        string $evidenceId,
        bool $withUrl = false,
    ): array {
        $item = $this->evidence($evidenceId, url: $withUrl ? 'https://example.test/r5/'.$evidenceId : null);
        $validation = $this->validation([$item], evidenceSufficient: true);
        $synthesis = $this->synthesis([$this->claim('c-'.$evidenceId, $item)], [$this->citation($item)]);

        return [$synthesis, $validation];
    }

    private function evidence(string $id, ?string $url = null): ScientificEvidenceItem
    {
        return new ScientificEvidenceItem(
            evidenceId: $id,
            sourceId: 'src-'.$id,
            sourceKey: 'openalex',
            sourceType: 'peer_reviewed_journal',
            publicationTitle: 'Fixture research '.$id,
            authors: ['Fixture'],
            institution: 'Fixture Lab',
            journal: 'Fixture Journal',
            doi: '10.9999/p8b2-'.$id,
            url: $url ?? 'https://example.test/r5/'.$id,
            publicationYear: 2022,
            retrievedAt: now()->toIso8601String(),
            agriculturalDomain: 'field_crops',
            claimTopic: 'fixture',
            evidenceText: 'Fixture verified agricultural evidence '.$id,
            validationStatus: EvidenceValidationStatus::EVIDENCE_USABLE,
            validationFailures: [],
            claimRelationship: ClaimEvidenceRelationship::SUPPORTED,
            confidence: 0.85,
            qualityScore: 85.0,
            qualityFactors: [
                'evidence_directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
                'answer_eligible' => true,
            ],
            sourceAttribution: [
                'evidence_directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
            ],
            cropOrEntity: 'fixture',
        );
    }

    /** @param  list<ScientificEvidenceItem>  $items */
    private function validation(array $items, bool $evidenceSufficient): EvidenceValidationExecutionReport
    {
        return new EvidenceValidationExecutionReport(
            status: 'validation_completed',
            validatedEvidence: $items,
            rejectedEvidence: [],
            sourcesReceived: count($items),
            validatedCount: count($items),
            rejectedCount: 0,
            duplicateCount: 0,
            conflictingCount: 0,
            evidenceSufficient: $evidenceSufficient,
            validatorsUsed: [],
            qualityDistribution: [],
            searchSummary: [],
            observability: [],
        );
    }

    /**
     * @param  list<ResearchAnswerClaim>  $claims
     * @param  list<ResearchAnswerCitation>  $citations
     */
    private function synthesis(array $claims, array $citations): AnswerSynthesisExecutionReport
    {
        return new AnswerSynthesisExecutionReport(
            status: 'answered',
            performed: true,
            answer: 'Verified agricultural answer.',
            conciseSummary: 'Verified summary.',
            detailedExplanation: 'Verified detailed explanation.',
            keyFindings: ['Finding'],
            claims: $claims,
            citations: $citations,
            evidenceReferences: [],
            confidence: 0.8,
            limitations: [],
            uncertainty: null,
            conflicts: [],
            language: 'en',
            researchMetadata: [],
            observability: [],
        );
    }

    private function claim(string $id, ScientificEvidenceItem $item): ResearchAnswerClaim
    {
        return new ResearchAnswerClaim(
            claimId: $id,
            claimText: $item->evidenceText ?? 'claim',
            evidenceIds: [$item->evidenceId],
            sourceIds: [$item->sourceId],
            validationStatus: EvidenceValidationStatus::EVIDENCE_USABLE,
            claimRelationship: ClaimEvidenceRelationship::SUPPORTED,
            confidence: 0.8,
        );
    }

    private function citation(ScientificEvidenceItem $item): ResearchAnswerCitation
    {
        return new ResearchAnswerCitation(
            citationId: 'cite-'.$item->evidenceId,
            sourceId: $item->sourceId,
            evidenceId: $item->evidenceId,
            title: $item->publicationTitle,
            authors: $item->authors,
            organization: $item->institution,
            journal: $item->journal,
            doi: $item->doi,
            url: $item->url,
            publicationYear: $item->publicationYear,
            sourceType: $item->sourceType,
        );
    }
}
