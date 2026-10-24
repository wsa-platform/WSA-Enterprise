<?php

declare(strict_types=1);

namespace Tests\Unit\Agriculture\Research\Persistence;

use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Persistence\ScientificKnowledgePersistenceService;
use App\Services\Agriculture\Research\Synthesis\AnswerSynthesisExecutionReport;
use App\Services\Agriculture\Research\Validation\EvidenceValidationExecutionReport;
use App\Services\Agriculture\ScientificSourceValidator;
use App\Services\Ai\Retrieval\KnowledgeSemanticIndexSync;
use App\Services\Audit\AuditService;
use App\Services\Tenancy\TenantContext;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Early-reject contract of knowledge persistence.
 * Does not boot Laravel and does not open a database: these branches return
 * before LibraryItem queries and before any source download.
 */
final class ScientificKnowledgePersistenceServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_rejects_organization_id_below_one(): void
    {
        $report = $this->service()->persist(0, $this->plan(), $this->synthesis(true, ['claim'], ['citation']), $this->validation(1, true));

        $this->assertSame('invalid_organization', $report->status);
        $this->assertFalse($report->performed);
        $this->assertNull($report->libraryItemId);
    }

    public function test_rejects_empty_claims(): void
    {
        $report = $this->service(false)->persist(1, $this->plan(), $this->synthesis(true, [], ['citation']), $this->validation(1, true));

        $this->assertSame('nothing_to_persist', $report->status);
        $this->assertSame('no_verified_claims', $report->observability['failure_reason'] ?? null);
    }

    public function test_rejects_empty_citations(): void
    {
        $report = $this->service(false)->persist(1, $this->plan(), $this->synthesis(true, ['claim'], []), $this->validation(1, true));

        $this->assertSame('nothing_to_persist', $report->status);
        $this->assertSame('no_verified_citations', $report->observability['failure_reason'] ?? null);
    }

    public function test_rejects_when_no_validated_evidence_was_counted(): void
    {
        $report = $this->service(false)->persist(1, $this->plan(), $this->synthesis(true, ['claim'], ['citation']), $this->validation(0, true));

        $this->assertSame('nothing_to_persist', $report->status);
        $this->assertSame('no_validated_evidence', $report->observability['failure_reason'] ?? null);
    }

    public function test_rejects_when_evidence_is_not_sufficient(): void
    {
        $report = $this->service(false)->persist(1, $this->plan(), $this->synthesis(true, ['claim'], ['citation']), $this->validation(1, false));

        $this->assertSame('insufficiently_verified', $report->status);
        $this->assertSame('evidence_not_sufficient_for_library', $report->observability['failure_reason'] ?? null);
        $this->assertFalse($report->performed);
    }

    private function service(bool $publicBound = false): ScientificKnowledgePersistenceService
    {
        $tenant = Mockery::mock(TenantContext::class);
        $tenant->shouldReceive('isPublicBound')->andReturn($publicBound)->byDefault();

        return new ScientificKnowledgePersistenceService(
            Mockery::mock(KnowledgeSemanticIndexSync::class),
            Mockery::mock(ScientificSourceValidator::class),
            $tenant,
            Mockery::mock(AuditService::class),
        );
    }

    private function plan(): KnowledgeQueryPlan
    {
        return new KnowledgeQueryPlan(
            normalizedQuery: new AgriculturalKnowledgeQuery(
                originalQuestion: 'What limits wheat yield?',
                normalizedQuestion: 'wheat yield limits',
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

    /**
     * @param  list<mixed>  $claims
     * @param  list<mixed>  $citations
     */
    private function synthesis(bool $performed, array $claims, array $citations): AnswerSynthesisExecutionReport
    {
        return new AnswerSynthesisExecutionReport(
            status: 'ok',
            performed: $performed,
            answer: null,
            conciseSummary: null,
            detailedExplanation: null,
            keyFindings: [],
            claims: $claims,
            citations: $citations,
            evidenceReferences: [],
            confidence: 0.0,
            limitations: [],
            uncertainty: null,
            conflicts: [],
            language: 'en',
            researchMetadata: [],
            observability: [],
        );
    }

    private function validation(int $validatedCount, bool $sufficient): EvidenceValidationExecutionReport
    {
        return new EvidenceValidationExecutionReport(
            status: 'ok',
            validatedEvidence: [],
            rejectedEvidence: [],
            sourcesReceived: $validatedCount,
            validatedCount: $validatedCount,
            rejectedCount: 0,
            duplicateCount: 0,
            conflictingCount: 0,
            evidenceSufficient: $sufficient,
            validatorsUsed: [],
            qualityDistribution: [],
            searchSummary: [],
            observability: [],
        );
    }
}
