<?php

namespace Tests\Unit\Agriculture\Research\Validation;

use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\Home\HomeEvidenceLifecycleDisposition;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Search\ScientificSearchExecutionReport;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;
use App\Services\Agriculture\Research\Synthesis\AnswerComposer;
use App\Services\Agriculture\Research\Validation\AgriculturalScientificValidationService;
use Tests\TestCase;

/**
 * Workstream B: raw hits wiped by relevance must not be labeled no_search_results.
 */
class NoSearchResultsRelevanceRejectionClassificationTest extends TestCase
{
    public function test_raw_hits_with_zero_survivors_classify_as_all_results_rejected_by_relevance(): void
    {
        $plan = $this->plan('What is the effect of temperature on ginger growth?');
        $raw = [
            new ScientificSearchResult(
                sourceKey: 'openalex',
                sourceIdentifier: 'W1',
                title: 'Ginger essential oil chemistry profile',
                authors: ['A'],
                publicationYear: 2020,
                doi: null,
                canonicalUrl: null,
                abstract: 'Chemical composition of ginger oil.',
                journal: null,
                foundBySources: ['openalex'],
            ),
        ];
        $search = new ScientificSearchExecutionReport(
            status: 'no_results',
            searchQuery: 'ginger temperature',
            selectedSources: ['openalex'],
            attemptedSources: ['openalex'],
            successfulSources: ['openalex'],
            failedSources: [],
            emptySources: [],
            sourceOutcomes: [],
            results: $raw,
            deduplicatedResults: [],
            planSummary: [
                'result_pipeline' => [
                    'raw_count' => 1,
                    'survivor_count' => 0,
                ],
            ],
            internetFirst: true,
            searchQueries: ['ginger temperature'],
        );

        $validation = app(AgriculturalScientificValidationService::class)->validate($plan, $search);
        $this->assertSame('all_results_rejected_by_relevance', $validation->status);
        $this->assertSame(1, $validation->sourcesReceived);
        $this->assertSame('ALL_RESULTS_REJECTED', $validation->observability['failure_class'] ?? null);

        $answer = app(AnswerComposer::class)->compose($plan, $validation);
        $this->assertSame('all_results_rejected_by_relevance', $answer->status);
        $this->assertSame(
            HomeEvidenceLifecycleDisposition::RETRIEVED_BUT_REJECTED,
            $answer->researchMetadata['evidence_lifecycle_disposition']
                ?? $answer->observability['evidence_lifecycle_disposition']
                ?? null
        );
        $this->assertSame([], $answer->claims);
    }

    public function test_truly_empty_retrieval_remains_no_search_results(): void
    {
        $plan = $this->plan('wheat irrigation');
        $search = new ScientificSearchExecutionReport(
            status: 'search_empty',
            searchQuery: 'wheat irrigation',
            selectedSources: ['openalex'],
            attemptedSources: ['openalex'],
            successfulSources: [],
            failedSources: [],
            emptySources: ['openalex'],
            sourceOutcomes: [],
            results: [],
            deduplicatedResults: [],
            planSummary: [],
            internetFirst: true,
            searchQueries: ['wheat irrigation'],
        );

        $validation = app(AgriculturalScientificValidationService::class)->validate($plan, $search);
        $this->assertSame('no_search_results', $validation->status);
        $this->assertSame(0, $validation->sourcesReceived);
    }

    private function plan(string $question): KnowledgeQueryPlan
    {
        $query = new AgriculturalKnowledgeQuery(
            originalQuestion: $question,
            normalizedQuestion: $question,
            language: 'en',
            agriculturalDomain: 'agronomy',
            subject: ['type' => 'crop', 'value' => 'ginger', 'label' => 'ginger'],
            crop: 'ginger',
            cropId: 'ginger',
            scientificName: 'Zingiber officinale',
            topic: 'temperature',
            subtopic: null,
            requestedInformation: ['overview'],
            constraints: [],
            location: null,
            researchRequired: true,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            researchIntent: 'temperature',
        );

        return new KnowledgeQueryPlan(
            normalizedQuery: $query,
            researchIntent: 'temperature',
            agriculturalDomain: 'agronomy',
            subjectEntity: ['type' => 'crop', 'value' => 'ginger'],
            topics: ['temperature'],
            subtopics: [],
            requestedInformation: ['overview'],
            evidenceRequirements: ['peer_reviewed'],
            sourcePriorities: ['openalex'],
            primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            contextInput: [],
            readyForStage3: true,
        );
    }
}
