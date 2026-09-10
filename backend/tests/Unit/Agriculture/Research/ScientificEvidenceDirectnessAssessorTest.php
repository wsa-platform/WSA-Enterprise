<?php

namespace Tests\Unit\Agriculture\Research;

use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;
use App\Services\Agriculture\Research\Validation\EvidenceVerificationLayer;
use Tests\TestCase;

class ScientificEvidenceDirectnessAssessorTest extends TestCase
{
    public function test_entity_less_topic_aligned_evidence_can_be_direct(): void
    {
        $plan = $this->entityLessPlan(
            question: 'How does soil organic matter mineralization affect nitrogen availability?',
            sense: 'plant_nutrition',
            factors: ['nitrogen'],
            questionType: 'causes',
        );

        $assessment = app(ScientificEvidenceDirectnessAssessor::class)->assess(
            $plan,
            'Soil organic matter mineralization and nitrogen availability in agricultural soils',
            'This study measures nitrogen mineralization rates and plant-available nitrogen after organic matter decomposition in agricultural soils.',
        );

        $this->assertSame(ScientificEvidenceDirectnessAssessor::DIRECT, $assessment['directness']);
        $this->assertContains('topic_sense_aligned_without_required_entity', $assessment['reasons']);
        $this->assertTrue($assessment['topic_matched']);
    }

    public function test_entity_less_off_topic_evidence_is_not_direct(): void
    {
        $plan = $this->entityLessPlan(
            question: 'How does soil organic matter mineralization affect nitrogen availability?',
            sense: 'plant_nutrition',
            factors: ['nitrogen'],
            questionType: 'causes',
        );

        $assessment = app(ScientificEvidenceDirectnessAssessor::class)->assess(
            $plan,
            'Cognitive behavioral therapy outcomes in urban clinics',
            'A randomized trial of psychotherapy protocols with no agricultural measurements.',
        );

        $this->assertNotSame(ScientificEvidenceDirectnessAssessor::DIRECT, $assessment['directness']);
    }

    public function test_crop_entity_questions_still_require_entity_alignment_for_direct(): void
    {
        $query = new AgriculturalKnowledgeQuery(
            originalQuestion: 'What nitrogen process is documented for the named crop entity?',
            normalizedQuestion: 'nitrogen process named crop entity',
            language: 'en',
            agriculturalDomain: 'agronomy',
            subject: ['type' => 'crop', 'value' => 'named_crop_entity'],
            crop: 'named_crop_entity',
            cropId: 'named_crop_entity',
            scientificName: null,
            topic: 'nitrogen',
            subtopic: null,
            requestedInformation: ['evidence'],
            constraints: [
                'question_type' => 'causes',
                'scientific_sense' => 'plant_nutrition',
                'scientific_factors' => ['nitrogen'],
            ],
            location: null,
            researchRequired: true,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            researchIntent: 'plant_nutrition',
        );

        $plan = new KnowledgeQueryPlan(
            normalizedQuery: $query,
            researchIntent: 'plant_nutrition',
            agriculturalDomain: 'agronomy',
            subjectEntity: ['type' => 'crop', 'value' => 'named_crop_entity'],
            topics: ['nitrogen'],
            subtopics: [],
            requestedInformation: ['evidence'],
            evidenceRequirements: ['peer_reviewed'],
            sourcePriorities: ['openalex', 'semantic_scholar'],
            primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            readyForStage3: true,
        );

        $assessment = app(ScientificEvidenceDirectnessAssessor::class)->assess(
            $plan,
            'Soil organic matter mineralization and nitrogen availability in agricultural soils',
            'This study measures nitrogen mineralization rates without naming the requested crop entity.',
        );

        $this->assertNotSame(ScientificEvidenceDirectnessAssessor::DIRECT, $assessment['directness']);
    }

    public function test_crossref_cannot_be_direct_scientific_proof(): void
    {
        $plan = $this->entityLessPlan(
            question: 'How does soil organic matter mineralization affect nitrogen availability?',
            sense: 'plant_nutrition',
            factors: ['nitrogen'],
            questionType: 'causes',
        );

        $result = new ScientificSearchResult(
            sourceKey: 'crossref',
            sourceIdentifier: '10.1000/example.nitrogen',
            title: 'Soil organic matter mineralization and nitrogen availability in agricultural soils',
            authors: ['Example Author'],
            publicationYear: 2024,
            doi: '10.1000/example.nitrogen',
            canonicalUrl: 'https://doi.org/10.1000/example.nitrogen',
            abstract: 'This study measures nitrogen mineralization rates and plant-available nitrogen after organic matter decomposition in agricultural soils.',
            journal: 'Example Journal',
            foundBySources: ['crossref'],
        );

        $assessment = app(EvidenceVerificationLayer::class)->assess($plan, $result);

        $this->assertNotSame(ScientificEvidenceDirectnessAssessor::DIRECT, $assessment['directness']);
        $this->assertContains('crossref_citation_metadata_not_scientific_proof', $assessment['reasons']);
    }

    /**
     * @param  list<string>  $factors
     */
    private function entityLessPlan(
        string $question,
        string $sense,
        array $factors,
        string $questionType,
    ): KnowledgeQueryPlan {
        $query = new AgriculturalKnowledgeQuery(
            originalQuestion: $question,
            normalizedQuestion: $question,
            language: 'en',
            agriculturalDomain: 'agronomy',
            subject: ['type' => 'soil', 'value' => 'soil'],
            crop: null,
            cropId: null,
            scientificName: null,
            topic: 'soil nitrogen',
            subtopic: null,
            requestedInformation: ['evidence'],
            constraints: [
                'question_type' => $questionType,
                'scientific_sense' => $sense,
                'scientific_factors' => $factors,
            ],
            location: null,
            researchRequired: true,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            researchIntent: $sense,
        );

        return new KnowledgeQueryPlan(
            normalizedQuery: $query,
            researchIntent: $sense,
            agriculturalDomain: 'agronomy',
            subjectEntity: ['type' => 'soil', 'value' => 'soil'],
            topics: ['soil nitrogen'],
            subtopics: [],
            requestedInformation: ['evidence'],
            evidenceRequirements: ['peer_reviewed'],
            sourcePriorities: ['openalex', 'semantic_scholar'],
            primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            readyForStage3: true,
        );
    }
}
