<?php

declare(strict_types=1);

namespace Tests\Unit\Agriculture\Research\Persistence;

use App\Models\AuditLog;
use App\Models\LibraryItem;
use App\Models\Organization;
use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Persistence\ScientificKnowledgePersistenceService;
use App\Services\Agriculture\Research\Synthesis\AnswerSynthesisExecutionReport;
use App\Services\Agriculture\Research\Synthesis\ResearchAnswerCitation;
use App\Services\Agriculture\Research\Synthesis\ResearchAnswerClaim;
use App\Services\Agriculture\Research\Validation\EvidenceValidationExecutionReport;
use App\Services\Agriculture\Research\Validation\ScientificEvidenceItem;
use App\Services\Agriculture\ScientificSourceValidator;
use App\Services\Ai\Retrieval\KnowledgeSemanticIndexSync;
use App\Services\Audit\AuditService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\Support\DatabaseSafety;
use Tests\TestCase;

/**
 * Storage and download contract. The default connection is sqlite :memory:,
 * Storage::fake('local') receives the original bytes, and Http::fake serves them.
 * The semantic index is a mock, so this test does not open the operational stores.
 */
final class ScientificKnowledgePersistenceStorageTest extends TestCase
{
    use RefreshDatabase;

    private const SOURCE_URL = 'https://example.test/wheat.pdf';

    protected function setUp(): void
    {
        parent::setUp();

        $default = (string) config('database.default');
        $database = (string) config("database.connections.{$default}.database");
        $this->assertSame('sqlite', $default);
        $this->assertSame(':memory:', $database);
        $this->assertSame(':memory:', (string) config('database.connections.capver.database'));
        DatabaseSafety::assertScientificRecordStoreIsIsolated();

        Storage::fake('local');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_successful_persist_stores_original_bytes_and_library_row(): void
    {
        $binary = "binary\x00content\xFFtest";
        Http::fake([
            'example.test/*' => Http::response($binary, 200, ['Content-Type' => 'application/pdf']),
        ]);
        $organization = $this->organization('org-a');

        $report = $this->service()->persist(
            (int) $organization->id,
            $this->plan(),
            $this->synthesis(self::SOURCE_URL),
            $this->validation(),
        );

        $this->assertSame('persistence_completed', $report->status);
        $this->assertTrue($report->performed);
        $this->assertSame('created', $report->action);
        $item = LibraryItem::query()->findOrFail($report->libraryItemId);
        $this->assertSame((int) $organization->id, (int) $item->organization_id);
        $this->assertSame($report->slug, $item->slug);
        $this->assertSame('verified_research_knowledge', $item->item_type);
        $this->assertSame('What limits wheat yield?', $item->title);
        $this->assertNull($item->owner_user_id);
        $this->assertSame('local', $item->file_disk);
        $this->assertSame(sprintf('research-sources/%d/%s.pdf', $organization->id, $item->slug), $item->file_path);
        Storage::disk('local')->assertExists((string) $item->file_path);
        $this->assertSame($binary, Storage::disk('local')->get((string) $item->file_path));
        $this->assertSame(strlen($binary), $item->metadata['original_source_file']['byte_length']);
        $this->assertFalse($item->metadata['original_source_file']['transformed']);
        $this->assertFalse($item->metadata['original_source_file']['translated']);
        $this->assertSame(self::SOURCE_URL, $item->metadata['original_source_file']['source_url']);
    }

    public function test_non_http_url_does_not_store_a_file(): void
    {
        Http::fake();
        $organization = $this->organization('org-ftp');

        $report = $this->service()->persist(
            (int) $organization->id,
            $this->plan(),
            $this->synthesis('ftp://example.test/wheat.pdf'),
            $this->validation(),
        );

        $item = LibraryItem::query()->findOrFail($report->libraryItemId);
        $this->assertSame('persistence_completed', $report->status);
        $this->assertNull($item->file_path);
        $this->assertSame([], Storage::disk('local')->allFiles());
        Http::assertNothingSent();
    }

    public function test_http_failure_timeout_and_oversize_body_do_not_store_a_file(): void
    {
        $organization = $this->organization('org-http');
        $cases = [
            'status' => Http::response('nope', 503, ['Content-Type' => 'application/pdf']),
            'timeout' => function (mixed ...$arguments): never {
                throw new ConnectionException('Operation timed out after 20 seconds');
            },
            'oversize' => Http::response(str_repeat('A', 15_000_001), 200, ['Content-Type' => 'application/pdf']),
        ];

        foreach ($cases as $name => $fake) {
            Storage::fake('local');
            Http::fake(['example.test/*' => $fake]);
            $report = $this->service()->persist(
                (int) $organization->id,
                $this->plan($name),
                $this->synthesis(self::SOURCE_URL),
                $this->validation($name),
            );
            $item = LibraryItem::query()->where('slug', $report->slug)->firstOrFail();
            $this->assertSame('persistence_completed', $report->status, $name);
            $this->assertNull($item->file_path, $name);
            $this->assertArrayNotHasKey('original_source_file', $item->metadata ?? [], $name);
        }

        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_same_research_identity_does_not_overwrite_original_bytes(): void
    {
        $original = "binary\x00first";
        Http::fake([
            'example.test/*' => Http::sequence()
                ->push($original, 200, ['Content-Type' => 'application/pdf'])
                ->push('REPLACED', 200, ['Content-Type' => 'application/pdf']),
        ]);
        $organization = $this->organization('org-idem');
        $service = $this->service();
        $first = $service->persist((int) $organization->id, $this->plan(), $this->synthesis(self::SOURCE_URL), $this->validation('e1'));
        $second = $service->persist((int) $organization->id, $this->plan(), $this->synthesis(self::SOURCE_URL), $this->validation('e2'));

        $this->assertSame('persistence_unchanged', $second->status);
        $this->assertSame('same_research_identity', $second->observability['duplicate_protection']);
        $this->assertSame($first->libraryItemId, $second->libraryItemId);
        $this->assertSame(1, LibraryItem::query()->count());
        $item = LibraryItem::query()->findOrFail($first->libraryItemId);
        $this->assertSame($original, Storage::disk('local')->get((string) $item->file_path));
        Http::assertSentCount(1);
    }

    public function test_organizations_do_not_share_a_library_row(): void
    {
        $binary = "binary\x00shared-source";
        Http::fake([
            'example.test/*' => Http::response($binary, 200, ['Content-Type' => 'application/pdf']),
        ]);
        $first = $this->organization('org-1');
        $second = $this->organization('org-2');
        $service = $this->service();

        $left = $service->persist((int) $first->id, $this->plan(), $this->synthesis(self::SOURCE_URL), $this->validation());
        $right = $service->persist((int) $second->id, $this->plan(), $this->synthesis(self::SOURCE_URL), $this->validation());

        $this->assertNotSame($left->libraryItemId, $right->libraryItemId);
        $this->assertSame(2, LibraryItem::query()->count());
        $leftItem = LibraryItem::query()->findOrFail($left->libraryItemId);
        $rightItem = LibraryItem::query()->findOrFail($right->libraryItemId);
        $this->assertSame((int) $first->id, (int) $leftItem->organization_id);
        $this->assertSame((int) $second->id, (int) $rightItem->organization_id);
        $this->assertNotSame($leftItem->file_path, $rightItem->file_path);
        $this->assertSame($binary, Storage::disk('local')->get((string) $leftItem->file_path));
        $this->assertSame($binary, Storage::disk('local')->get((string) $rightItem->file_path));
    }

    public function test_public_tenant_mismatch_stores_nothing(): void
    {
        Http::fake();
        $organization = $this->organization('org-mismatch');
        $tenant = Mockery::mock(TenantContext::class);
        $tenant->shouldReceive('isPublicBound')->andReturn(true);
        $tenant->shouldReceive('organizationId')->andReturn((int) $organization->id + 1000);
        $audit = Mockery::mock(AuditService::class);
        $audit->shouldReceive('record')->once()->andReturn(new AuditLog());
        $index = Mockery::mock(KnowledgeSemanticIndexSync::class);
        $index->shouldReceive('syncLibraryItem')->never();

        $report = (new ScientificKnowledgePersistenceService(
            $index,
            new ScientificSourceValidator(),
            $tenant,
            $audit,
        ))->persist((int) $organization->id, $this->plan(), $this->synthesis(self::SOURCE_URL), $this->validation());

        $this->assertSame('public_tenant_mismatch', $report->status);
        $this->assertFalse($report->performed);
        $this->assertNull($report->libraryItemId);
        $this->assertSame(0, LibraryItem::query()->count());
        $this->assertSame([], Storage::disk('local')->allFiles());
        Http::assertNothingSent();
    }

    private function service(): ScientificKnowledgePersistenceService
    {
        $tenant = Mockery::mock(TenantContext::class);
        $tenant->shouldReceive('isPublicBound')->andReturn(false);
        $index = Mockery::mock(KnowledgeSemanticIndexSync::class);
        $index->shouldReceive('syncLibraryItem')->andReturnNull();
        $audit = Mockery::mock(AuditService::class);

        return new ScientificKnowledgePersistenceService(
            $index,
            new ScientificSourceValidator(),
            $tenant,
            $audit,
        );
    }

    private function organization(string $slug): Organization
    {
        return Organization::query()->create([
            'name' => $slug,
            'slug' => $slug,
        ]);
    }

    private function plan(string $suffix = 'base'): KnowledgeQueryPlan
    {
        return new KnowledgeQueryPlan(
            normalizedQuery: new AgriculturalKnowledgeQuery(
                originalQuestion: 'What limits wheat yield?',
                normalizedQuestion: 'wheat yield limits '.$suffix,
                language: 'en',
                agriculturalDomain: 'agronomy',
                subject: null,
                crop: 'wheat',
                cropId: null,
                scientificName: null,
                topic: 'yield',
                subtopic: null,
                requestedInformation: [],
                constraints: [],
                location: null,
                researchRequired: true,
                ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
                clarificationRequirements: [],
                researchIntent: 'explain',
            ),
            researchIntent: 'explain',
            agriculturalDomain: 'agronomy',
            subjectEntity: null,
            topics: ['yield'],
            subtopics: [],
            requestedInformation: [],
            evidenceRequirements: [],
            sourcePriorities: [],
            primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
        );
    }

    private function synthesis(string $url): AnswerSynthesisExecutionReport
    {
        return new AnswerSynthesisExecutionReport(
            status: 'ok',
            performed: true,
            answer: 'Nitrogen and water limit yield.',
            conciseSummary: 'Nitrogen and water limit yield.',
            detailedExplanation: null,
            keyFindings: [],
            claims: [
                new ResearchAnswerClaim(
                    claimId: 'c1',
                    claimText: 'Nitrogen and water limit yield.',
                    evidenceIds: ['e1'],
                    sourceIds: ['s1'],
                    validationStatus: 'usable',
                    claimRelationship: 'supports',
                    confidence: 0.8,
                ),
            ],
            citations: [
                new ResearchAnswerCitation(
                    citationId: 'cit-1',
                    sourceId: 's1',
                    evidenceId: 'e1',
                    title: 'Wheat yield limits',
                    authors: [],
                    organization: 'Example Institute',
                    journal: null,
                    doi: null,
                    url: $url,
                    publicationYear: 2020,
                    sourceType: 'peer_reviewed_journal',
                ),
            ],
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

    private function validation(string $evidenceId = 'e1'): EvidenceValidationExecutionReport
    {
        return new EvidenceValidationExecutionReport(
            status: 'ok',
            validatedEvidence: [
                new ScientificEvidenceItem(
                    evidenceId: $evidenceId,
                    sourceId: 's1',
                    sourceKey: 'example',
                    sourceType: 'peer_reviewed_journal',
                    publicationTitle: 'Wheat yield limits',
                    authors: [],
                    institution: 'Example Institute',
                    journal: null,
                    doi: null,
                    url: self::SOURCE_URL,
                    publicationYear: 2020,
                    retrievedAt: '2026-10-23T00:00:00Z',
                    agriculturalDomain: 'agronomy',
                    claimTopic: 'yield',
                    evidenceText: 'Nitrogen and water limit yield.',
                    validationStatus: 'usable',
                    validationFailures: [],
                    claimRelationship: 'supports',
                    confidence: 0.8,
                    qualityScore: 0.8,
                    qualityFactors: [],
                    sourceAttribution: [],
                ),
            ],
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
    }
}
