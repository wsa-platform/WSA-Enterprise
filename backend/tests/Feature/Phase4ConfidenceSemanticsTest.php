<?php

namespace Tests\Feature;

use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use App\Services\Agriculture\Research\Search\ScientificResultRanker;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;
use App\Services\Agriculture\Research\Synthesis\AnswerComposer;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use App\Services\Agriculture\Research\Validation\EvidenceValidationExecutionReport;
use App\Services\Agriculture\Research\Validation\EvidenceValidationStatus;
use App\Services\Agriculture\Research\Validation\ScientificEvidenceItem;
use Tests\TestCase;

/**
 * Phase 4 Unit B — confidence is not Ranker score; limitations survive composition.
 */
class Phase4ConfidenceSemanticsTest extends TestCase
{
    public function test_ranker_score_is_not_copied_as_answer_confidence(): void
    {
        $plan = $this->plan();
        $searchResult = new ScientificSearchResult(
            sourceKey: 'openalex',
            sourceIdentifier: 'W-conf',
            title: 'Wheat drip irrigation yield response',
            authors: ['Fixture'],
            publicationYear: 2021,
            doi: '10.9999/conf',
            canonicalUrl: 'https://example.test/conf',
            abstract: 'Wheat drip irrigation improved grain yield in multi-year field trials.',
            journal: 'Fixture',
            foundBySources: ['openalex'],
        );

        $ranked = app(ScientificResultRanker::class)->rank('wheat irrigation', [$searchResult], $plan);
        $rankerScore = (float) ($ranked[0]->relevanceScore ?? 0.0);
        $this->assertGreaterThan(0.0, $rankerScore);

        $item = new ScientificEvidenceItem(
            evidenceId: 'conf-1',
            sourceId: 'src-conf-1',
            sourceKey: 'openalex',
            sourceType: 'peer_reviewed_journal',
            publicationTitle: $searchResult->title,
            authors: ['Fixture'],
            institution: 'Fixture',
            journal: 'Fixture',
            doi: $searchResult->doi,
            url: $searchResult->canonicalUrl,
            publicationYear: 2021,
            retrievedAt: '2026-09-21T00:00:00+00:00',
            agriculturalDomain: 'field_crops',
            claimTopic: 'irrigation',
            evidenceText: $searchResult->abstract,
            validationStatus: EvidenceValidationStatus::EVIDENCE_USABLE,
            validationFailures: [],
            claimRelationship: ClaimEvidenceRelationship::SUPPORTED,
            confidence: 0.82,
            qualityScore: 82.0,
            qualityFactors: [
                'evidence_directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
                'answer_eligible' => true,
            ],
            sourceAttribution: [
                'evidence_directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
            ],
            cropOrEntity: 'wheat',
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
            searchSummary: ['search_status' => 'search_completed'],
            observability: [],
        );

        $synthesis = app(AnswerComposer::class)->compose($plan, $validation);
        $this->assertNotEqualsWithDelta(
            $rankerScore,
            $synthesis->confidence,
            0.0001,
            'Answer confidence must not equal Ranker relevanceScore',
        );
        $this->assertGreaterThanOrEqual(0.0, $synthesis->confidence);
        $this->assertLessThanOrEqual(1.0, $synthesis->confidence);
    }

    public function test_limitations_array_is_present_on_synthesis_report(): void
    {
        $plan = $this->plan();
        $item = new ScientificEvidenceItem(
            evidenceId: 'lim-1',
            sourceId: 'src-lim-1',
            sourceKey: 'openalex',
            sourceType: 'peer_reviewed_journal',
            publicationTitle: 'Wheat irrigation partial support',
            authors: ['Fixture'],
            institution: 'Fixture',
            journal: 'Fixture',
            doi: '10.9999/lim',
            url: 'https://example.test/lim',
            publicationYear: 2020,
            retrievedAt: '2026-09-21T00:00:00+00:00',
            agriculturalDomain: 'field_crops',
            claimTopic: 'irrigation',
            evidenceText: 'Wheat irrigation was only partially documented for one cultivar group.',
            validationStatus: EvidenceValidationStatus::EVIDENCE_USABLE,
            validationFailures: [],
            claimRelationship: ClaimEvidenceRelationship::PARTIALLY_SUPPORTED,
            confidence: 0.55,
            qualityScore: 55.0,
            qualityFactors: [
                'evidence_directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
                'answer_eligible' => true,
            ],
            sourceAttribution: [
                'evidence_directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
            ],
            cropOrEntity: 'wheat',
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
            searchSummary: ['search_status' => 'search_completed'],
            observability: [],
        );

        $synthesis = app(AnswerComposer::class)->compose($plan, $validation);
        $this->assertIsArray($synthesis->limitations);
        $payload = $synthesis->toArray();
        $this->assertArrayHasKey('limitations', $payload);
        $this->assertIsArray($payload['limitations']);
    }

    private function plan(): KnowledgeQueryPlan
    {
        $query = new AgriculturalKnowledgeQuery(
            originalQuestion: 'What irrigation methods improve wheat yield?',
            normalizedQuestion: 'What irrigation methods improve wheat yield?',
            language: 'en',
            agriculturalDomain: 'agronomy',
            subject: ['type' => 'crop', 'value' => 'wheat', 'label' => 'wheat'],
            crop: 'wheat',
            cropId: 'wheat',
            scientificName: null,
            topic: 'irrigation',
            subtopic: null,
            requestedInformation: ['mechanism'],
            constraints: ['answer_language' => 'en', 'question_type' => 'mechanism'],
            location: null,
            researchRequired: true,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            researchIntent: 'scientific_explanation',
        );

        return new KnowledgeQueryPlan(
            normalizedQuery: $query,
            researchIntent: $query->researchIntent,
            agriculturalDomain: 'agronomy',
            subjectEntity: $query->subject,
            topics: ['irrigation'],
            subtopics: [],
            requestedInformation: $query->requestedInformation,
            evidenceRequirements: ['peer_reviewed'],
            sourcePriorities: ['openalex'],
            primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            readyForStage3: true,
        );
    }
}
