<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Services\Agriculture\Research\AgriculturalResearchAgent;
use App\Services\Agriculture\Research\Persistence\KnowledgePersistenceExecutionReport;
use App\Services\Agriculture\Research\Persistence\ScientificKnowledgePersistenceService;
use App\Services\Agriculture\Research\QueryUnderstandingService;
use App\Services\Agriculture\Research\Search\AgriculturalScientificSearchService;
use App\Services\Agriculture\Research\Search\ScientificSearchExecutionReport;
use App\Services\Agriculture\Research\Synthesis\AnswerComposer;
use App\Services\Agriculture\Research\Synthesis\AnswerSynthesisExecutionReport;
use App\Services\Agriculture\Research\Synthesis\ResearchAnswerCitation;
use App\Services\Agriculture\Research\Validation\AgriculturalScientificValidationService;
use App\Services\Agriculture\Research\Validation\EvidenceValidationExecutionReport;
use App\Services\Agriculture\ScientificSourceDiscoveryPipeline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class CropStructuredIntentAndSinglePipelineTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_knowledge_option_is_authoritative_across_crops_options_and_languages(): void
    {
        $qus = app(QueryUnderstandingService::class);
        $crops = [
            ['id' => 'wheat', 'name' => 'القمح'],
            ['id' => 'maize', 'name' => 'Maize'],
            ['id' => 'rice', 'name' => 'Riz'],
            ['id' => 'barley', 'name' => 'Arpa'],
            ['id' => 'tomato', 'name' => 'طماطم'],
            ['id' => 'potato', 'name' => 'Potato'],
            ['id' => 'strawberry', 'name' => 'Fraise'],
            ['id' => 'lettuce', 'name' => 'Lettuce'],
            ['id' => 'uncatalogued-x', 'name' => 'Named Future Crop'],
        ];
        $options = [
            'farming-needs' => ['intent' => 'cultivation', 'question_type' => 'requirements'],
            'scientific-research' => ['intent' => 'scientific_literature', 'question_type' => 'general'],
            'industries' => ['intent' => 'agricultural_industry', 'question_type' => 'general'],
        ];
        $queries = [
            'احتياجات زراعة المحصول',
            'wheat irrigation scheduling',
            'besoins de culture et irrigation',
            'sulama ve tarim ihtiyaclari',
        ];

        foreach ($crops as $crop) {
            foreach ($options as $option => $expected) {
                $baseline = $qus->understand([
                    'selected_crop_id' => $crop['id'],
                    'selected_crop_name' => $crop['name'],
                    'knowledge_option' => $option,
                ]);
                $this->assertSame($expected['intent'], $baseline->researchIntent, $crop['id'].' '.$option);
                $this->assertSame($expected['intent'], $baseline->topic);
                $this->assertSame($option, $baseline->subtopic);
                $this->assertSame($crop['id'], $baseline->cropId);
                $this->assertSame($expected['question_type'], $baseline->constraints['question_type'] ?? null);

                foreach ($queries as $query) {
                    $understood = $qus->understand([
                        'selected_crop_id' => $crop['id'],
                        'selected_crop_name' => $crop['name'],
                        'knowledge_option' => $option,
                        'query' => $query,
                    ]);
                    $this->assertSame(
                        $expected['intent'],
                        $understood->researchIntent,
                        $crop['id'].' '.$option.' '.$query,
                    );
                    $this->assertSame($expected['intent'], $understood->topic);
                    $this->assertSame($option, $understood->subtopic);
                    $this->assertSame($crop['id'], $understood->cropId);
                }
            }
        }
    }

    public function test_sufficient_crop_stage5_does_not_run_library_scientific_discovery(): void
    {
        Organization::create(['name' => 'WSA Demo', 'slug' => 'wsa-demo', 'is_active' => true]);

        $pipeline = Mockery::mock(ScientificSourceDiscoveryPipeline::class);
        $pipeline->shouldNotReceive('discoverMissingSections');
        $this->app->instance(ScientificSourceDiscoveryPipeline::class, $pipeline);

        $persist = Mockery::mock(ScientificKnowledgePersistenceService::class);
        $persist->shouldReceive('persist')->once()->andReturn(new KnowledgePersistenceExecutionReport(
            status: 'persisted',
            performed: true,
            libraryItemId: 11,
            slug: 'wheat-farming-needs',
            action: 'created',
            provenance: null,
            observability: [],
        ));
        $this->app->instance(ScientificKnowledgePersistenceService::class, $persist);

        $search = Mockery::mock(AgriculturalScientificSearchService::class);
        $search->shouldReceive('search')->once()->andReturn(new ScientificSearchExecutionReport(
            status: 'search_completed',
            searchQuery: 'wheat farming-needs',
            selectedSources: ['openalex'],
            attemptedSources: ['openalex'],
            successfulSources: ['openalex'],
            failedSources: [],
            emptySources: [],
            sourceOutcomes: [],
            results: [],
            deduplicatedResults: [],
            planSummary: [],
            internetFirst: true,
        ));
        $this->app->instance(AgriculturalScientificSearchService::class, $search);

        $validation = Mockery::mock(AgriculturalScientificValidationService::class);
        $validation->shouldReceive('validate')->once()->andReturn(new EvidenceValidationExecutionReport(
            status: 'validation_completed',
            validatedEvidence: [],
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
        ));
        $this->app->instance(AgriculturalScientificValidationService::class, $validation);

        $composer = Mockery::mock(AnswerComposer::class);
        $composer->shouldReceive('compose')->once()->andReturn(new AnswerSynthesisExecutionReport(
            status: 'scientific_generated',
            performed: true,
            answer: 'Canonical crop answer',
            conciseSummary: 'Summary',
            detailedExplanation: null,
            keyFindings: [],
            claims: [],
            citations: [
                new ResearchAnswerCitation(
                    citationId: 'c1',
                    sourceId: 's1',
                    evidenceId: 'e1',
                    title: 'Direct paper',
                    authors: ['A'],
                    organization: 'Org',
                    journal: null,
                    doi: '10.1/crop',
                    url: 'https://example.org/direct',
                    publicationYear: 2020,
                    sourceType: 'peer_reviewed_journal',
                ),
            ],
            evidenceReferences: [],
            confidence: 0.8,
            limitations: [],
            uncertainty: null,
            conflicts: [],
            language: 'ar',
            researchMetadata: ['direct_evidence_gate' => 'PASSED'],
            observability: [],
        ));
        $this->app->instance(AnswerComposer::class, $composer);

        $payload = app(AgriculturalResearchAgent::class)->conductResearch(1, [
            'selected_crop_id' => 'wheat',
            'selected_crop_name' => 'Wheat',
            'knowledge_option' => 'farming-needs',
        ]);

        $this->assertSame('Canonical crop answer', $payload['answer'] ?? null);
        $this->assertSame('farming-needs', $payload['knowledge_option'] ?? null);
        $this->assertTrue((bool) ($payload['library']['legacy_discovery_skipped'] ?? false));
        $this->assertSame([], $payload['library']['discoverers_used'] ?? null);
    }
}
