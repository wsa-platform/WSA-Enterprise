<?php

namespace Tests\Feature;

use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use App\Services\Agriculture\Research\Validation\EvidenceValidationExecutionReport;
use App\Services\Agriculture\Research\Validation\EvidenceValidationStatus;
use App\Services\Agriculture\Research\Validation\ScientificEvidenceItem;

trait Phase5UnitATestFixtures
{
    /**
     * @param  list<string>  $requested
     * @param  array<string, mixed>  $constraints
     * @param  array<string, mixed>  $context
     */
    protected function phase5Plan(
        string $question = 'What is wheat production in Egypt?',
        array $requested = ['quantity'],
        array $constraints = ['answer_language' => 'en'],
        ?string $location = 'Egypt',
        string $language = 'en',
        string $intent = 'statistical_lookup',
        array $context = [],
    ): KnowledgeQueryPlan {
        $query = new AgriculturalKnowledgeQuery(
            originalQuestion: $question,
            normalizedQuestion: $question,
            language: $language,
            agriculturalDomain: 'agronomy',
            subject: ['type' => 'crop', 'value' => 'wheat', 'label' => 'wheat'],
            crop: 'wheat',
            cropId: 'wheat',
            scientificName: null,
            topic: 'production',
            subtopic: null,
            requestedInformation: $requested,
            constraints: $constraints,
            location: $location,
            researchRequired: true,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            researchIntent: $intent,
        );

        return new KnowledgeQueryPlan(
            normalizedQuery: $query,
            researchIntent: $intent,
            agriculturalDomain: 'agronomy',
            subjectEntity: $query->subject,
            topics: ['production'],
            subtopics: [],
            requestedInformation: $requested,
            evidenceRequirements: ['official_statistics'],
            sourcePriorities: ['openalex'],
            primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            contextInput: $context,
            readyForStage3: true,
        );
    }

    protected function phase5Evidence(
        string $id,
        string $relationship,
        string $directness = ScientificEvidenceDirectnessAssessor::DIRECT,
        bool $conflict = false,
    ): ScientificEvidenceItem {
        return new ScientificEvidenceItem(
            evidenceId: $id,
            sourceId: 'src-'.$id,
            sourceKey: 'openalex',
            sourceType: 'journal_article',
            publicationTitle: 'Title '.$id,
            authors: ['A'],
            institution: null,
            journal: 'J',
            doi: '10.1000/'.$id,
            url: 'https://example.test/'.$id,
            publicationYear: 2020,
            retrievedAt: now()->toIso8601String(),
            agriculturalDomain: 'agronomy',
            claimTopic: 'production',
            evidenceText: 'Wheat production evidence '.$id,
            validationStatus: EvidenceValidationStatus::EVIDENCE_USABLE,
            validationFailures: [],
            claimRelationship: $relationship,
            confidence: 0.8,
            qualityScore: 0.8,
            qualityFactors: ['evidence_directness' => $directness],
            sourceAttribution: ['evidence_directness' => $directness],
            hasConflict: $conflict,
        );
    }

    /**
     * @param  list<ScientificEvidenceItem>  $items
     */
    protected function phase5Validation(array $items, bool $sufficient): EvidenceValidationExecutionReport
    {
        return new EvidenceValidationExecutionReport(
            status: $sufficient ? 'validation_completed' : 'no_valid_evidence',
            validatedEvidence: $items,
            rejectedEvidence: [],
            sourcesReceived: count($items),
            validatedCount: count($items),
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
