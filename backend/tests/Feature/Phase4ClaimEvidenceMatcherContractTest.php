<?php

namespace Tests\Feature;

use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceMatcher;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use App\Services\Agriculture\Research\Validation\EvidenceValidationStatus;
use Tests\TestCase;

/**
 * Phase 4 Unit B — ClaimEvidenceMatcher claim_relation axis.
 */
class Phase4ClaimEvidenceMatcherContractTest extends TestCase
{
    public function test_matcher_relationship_is_canonical_claim_relation(): void
    {
        $plan = $this->plan('What irrigation methods improve wheat yield?');
        $result = new ScientificSearchResult(
            sourceKey: 'openalex',
            sourceIdentifier: 'W-claim',
            title: 'Wheat drip irrigation yield response',
            authors: ['Fixture'],
            publicationYear: 2021,
            doi: '10.9999/claim',
            canonicalUrl: 'https://example.test/claim',
            abstract: 'Wheat drip irrigation improved grain yield in multi-year field trials.',
            journal: 'Fixture',
            foundBySources: ['openalex'],
        );

        $match = app(ClaimEvidenceMatcher::class)->match(
            $plan,
            $result,
            $result->abstract,
            EvidenceValidationStatus::EVIDENCE_USABLE,
        );

        $this->assertArrayHasKey('relationship', $match);
        $this->assertContains($match['relationship'], ClaimEvidenceRelationship::all());
        $this->assertArrayHasKey('factors', $match);
        $factors = $match['factors'];
        $this->assertIsArray($factors);
        // Directness may appear as a factor annotation, but relationship remains claim_relation.
        if (isset($factors['evidence_directness'])) {
            $this->assertNotSame($factors['evidence_directness'], $match['relationship']);
        }
        $this->assertArrayNotHasKey('evidence_lifecycle_disposition', $match);
        $this->assertArrayNotHasKey('evidence_sufficient', $match);
    }

    public function test_offtopic_evidence_is_insufficient_not_supported(): void
    {
        $plan = $this->plan('What irrigation methods improve wheat yield?');
        $result = new ScientificSearchResult(
            sourceKey: 'openalex',
            sourceIdentifier: 'W-off',
            title: 'National banking reforms',
            authors: ['Fixture'],
            publicationYear: 2024,
            doi: '10.9999/off',
            canonicalUrl: 'https://example.test/off',
            abstract: 'Nationwide macroeconomic inventory of banking and tourism.',
            journal: 'Finance',
            foundBySources: ['openalex'],
        );

        $match = app(ClaimEvidenceMatcher::class)->match(
            $plan,
            $result,
            $result->abstract,
            EvidenceValidationStatus::EVIDENCE_USABLE,
        );

        $this->assertNotSame(ClaimEvidenceRelationship::SUPPORTED, $match['relationship']);
        $this->assertContains($match['relationship'], [
            ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE,
            ClaimEvidenceRelationship::NOT_VALIDATED,
            ClaimEvidenceRelationship::PARTIALLY_SUPPORTED,
            ClaimEvidenceRelationship::CONFLICTING,
        ]);
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
