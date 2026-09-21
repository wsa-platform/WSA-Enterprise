<?php

namespace Tests\Feature;

use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceMatcher;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use App\Services\Agriculture\Research\Validation\EvidenceValidationStatus;
use Tests\TestCase;

/**
 * Phase 4 Unit C — ClaimEvidenceMatcher implementation.
 */
class Phase4ClaimEvidenceMatcherImplementationTest extends TestCase
{
    public function test_supported_relationship_for_aligned_wheat_irrigation(): void
    {
        $match = $this->match(
            'What irrigation methods improve wheat yield?',
            'Wheat drip irrigation yield response',
            'Wheat drip irrigation improved grain yield in multi-year field trials.',
        );

        $this->assertContains($match['relationship'], ClaimEvidenceRelationship::all());
        $this->assertContains($match['relationship'], [
            ClaimEvidenceRelationship::SUPPORTED,
            ClaimEvidenceRelationship::PARTIALLY_SUPPORTED,
        ]);
        $this->assertArrayHasKey('factors', $match);
        $this->assertArrayNotHasKey('evidence_lifecycle_disposition', $match);
        $this->assertArrayNotHasKey('evidence_sufficient', $match);
    }

    public function test_entity_mismatch_is_not_supported(): void
    {
        $match = $this->match(
            'What irrigation methods improve wheat yield?',
            'Maize nitrogen fertilizer response in Iowa',
            'Zea mays nitrogen fertilizer trials without wheat irrigation content.',
        );

        $this->assertNotSame(ClaimEvidenceRelationship::SUPPORTED, $match['relationship']);
    }

    public function test_conflicting_directness_factor_does_not_collapse_axes(): void
    {
        $match = $this->match(
            'What irrigation methods improve wheat yield?',
            'Wheat drip irrigation yield response',
            'Wheat drip irrigation improved grain yield in multi-year field trials.',
        );

        $directness = $match['factors']['evidence_directness'] ?? null;
        if (is_string($directness) && $directness !== '') {
            $this->assertNotSame($directness, $match['relationship']);
            $this->assertContains($directness, [
                ScientificEvidenceDirectnessAssessor::DIRECT,
                ScientificEvidenceDirectnessAssessor::SUPPORTING,
                ScientificEvidenceDirectnessAssessor::BACKGROUND,
                ScientificEvidenceDirectnessAssessor::RELATED,
                ScientificEvidenceDirectnessAssessor::IRRELEVANT,
                ScientificEvidenceDirectnessAssessor::GEOGRAPHIC_MISMATCH,
                ScientificEvidenceDirectnessAssessor::SUPPORTED,
            ]);
        }
    }

    private function match(string $question, string $title, string $abstract): array
    {
        $plan = $this->plan($question);
        $result = new ScientificSearchResult(
            sourceKey: 'openalex',
            sourceIdentifier: 'W-match',
            title: $title,
            authors: ['Fixture'],
            publicationYear: 2021,
            doi: '10.9999/match',
            canonicalUrl: 'https://example.test/match',
            abstract: $abstract,
            journal: 'Fixture',
            foundBySources: ['openalex'],
        );

        return app(ClaimEvidenceMatcher::class)->match(
            $plan,
            $result,
            $abstract,
            EvidenceValidationStatus::EVIDENCE_USABLE,
        );
    }

    private function plan(string $question): KnowledgeQueryPlan
    {
        $query = new AgriculturalKnowledgeQuery(
            originalQuestion: $question,
            normalizedQuestion: $question,
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
