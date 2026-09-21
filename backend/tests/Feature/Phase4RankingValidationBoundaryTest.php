<?php

namespace Tests\Feature;

use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use App\Services\Agriculture\Research\Search\ScientificEvidenceRelevanceGate;
use App\Services\Agriculture\Research\Search\ScientificResultRanker;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceMatcher;
use App\Services\Agriculture\Research\Validation\EvidenceValidationStatus;
use App\Services\Agriculture\Research\Validation\EvidenceVerificationLayer;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;
use ReflectionClass;
use Tests\TestCase;

/**
 * Phase 4 Unit B — relevance / matching / directness / EVL / ranking ownership boundaries.
 */
class Phase4RankingValidationBoundaryTest extends TestCase
{
    public function test_relevance_gate_answers_candidate_relevance_not_truth(): void
    {
        $plan = $this->plan('What irrigation methods improve wheat yield?');
        $gate = app(ScientificEvidenceRelevanceGate::class);

        $ok = $gate->assess(
            $plan,
            'Wheat drip irrigation yield response',
            'Wheat drip irrigation improved grain yield in multi-year field trials.',
            '10.9999/ok',
            null,
        );
        $noise = $gate->assess(
            $plan,
            'National banking reforms',
            'Nationwide macroeconomic inventory of banking indicators.',
            '10.9999/noise',
            null,
        );

        $this->assertTrue($ok['relevant']);
        $this->assertFalse($noise['relevant']);
        $this->assertArrayHasKey('rejection_reasons', $noise);
        $this->assertArrayNotHasKey('claim_relationship', $ok);
        $this->assertArrayNotHasKey('evidence_lifecycle_disposition', $ok);
        $this->assertArrayNotHasKey('evidence_sufficient', $ok);
    }

    public function test_directness_independent_from_claim_relation_and_disposition(): void
    {
        $plan = $this->plan('What irrigation methods improve wheat yield?');
        $directness = app(ScientificEvidenceDirectnessAssessor::class)->assess(
            $plan,
            'Wheat drip irrigation yield response',
            'Wheat drip irrigation improved grain yield in multi-year field trials.',
            '10.9999/d',
            null,
        );

        $this->assertArrayHasKey('directness', $directness);
        $this->assertContains($directness['directness'], [
            ScientificEvidenceDirectnessAssessor::DIRECT,
            ScientificEvidenceDirectnessAssessor::SUPPORTING,
            ScientificEvidenceDirectnessAssessor::BACKGROUND,
            ScientificEvidenceDirectnessAssessor::RELATED,
            ScientificEvidenceDirectnessAssessor::IRRELEVANT,
            ScientificEvidenceDirectnessAssessor::GEOGRAPHIC_MISMATCH,
            ScientificEvidenceDirectnessAssessor::SUPPORTED,
        ]);
        $this->assertArrayNotHasKey('claim_relationship', $directness);
        $this->assertArrayNotHasKey('evidence_lifecycle_disposition', $directness);
        $this->assertNotContains($directness['directness'], ClaimEvidenceRelationship::all());
    }

    public function test_matcher_emits_claim_relationship_not_disposition(): void
    {
        $plan = $this->plan('What irrigation methods improve wheat yield?');
        $result = new ScientificSearchResult(
            sourceKey: 'openalex',
            sourceIdentifier: 'W-m',
            title: 'Wheat drip irrigation yield response',
            authors: ['Fixture'],
            publicationYear: 2021,
            doi: '10.9999/wm',
            canonicalUrl: 'https://example.test/wm',
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
        $this->assertArrayNotHasKey('evidence_lifecycle_disposition', $match);
        $this->assertArrayNotHasKey('evidence_sufficient', $match);
    }

    public function test_evl_refines_directness_without_owning_disposition_or_ranking(): void
    {
        $plan = $this->plan('What irrigation methods improve wheat yield?');
        $evl = app(EvidenceVerificationLayer::class);
        $refined = $evl->assess(
            $plan,
            'Wheat drip irrigation yield response',
            'Wheat drip irrigation improved grain yield in multi-year field trials.',
            '10.9999/evl',
            null,
        );

        $this->assertArrayHasKey('directness', $refined);
        $this->assertArrayHasKey('verification_label', $refined);
        $this->assertArrayNotHasKey('evidence_lifecycle_disposition', $refined);
        $this->assertArrayNotHasKey('relevanceScore', $refined);
        $this->assertArrayNotHasKey('score_role', $refined);

        $constants = (new ReflectionClass(EvidenceVerificationLayer::class))->getConstants();
        foreach ($constants as $value) {
            if (! is_string($value)) {
                continue;
            }
            $this->assertStringNotContainsString('composer_used', $value);
            $this->assertStringNotContainsString('no_results_retrieved', $value);
        }
    }

    public function test_ranker_does_not_set_claim_relationship_or_sufficiency(): void
    {
        $plan = $this->plan('What irrigation methods improve wheat yield?');
        $result = new ScientificSearchResult(
            sourceKey: 'openalex',
            sourceIdentifier: 'W-r',
            title: 'Wheat drip irrigation yield response',
            authors: ['Fixture'],
            publicationYear: 2021,
            doi: '10.9999/wr',
            canonicalUrl: 'https://example.test/wr',
            abstract: 'Wheat drip irrigation improved grain yield in multi-year field trials.',
            journal: 'Fixture',
            foundBySources: ['openalex'],
        );

        $ranked = app(ScientificResultRanker::class)->rank('wheat irrigation', [$result], $plan);
        $meta = $ranked[0]->relevanceMetadata ?? [];
        $this->assertSame('ranking_order', $meta['score_role'] ?? null);
        $this->assertArrayNotHasKey('claim_relationship', $meta);
        $this->assertArrayNotHasKey('evidence_sufficient', $meta);
        $this->assertArrayNotHasKey('evidence_lifecycle_disposition', $meta);
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
            constraints: [
                'answer_language' => 'en',
                'question_language' => 'en',
                'question_type' => 'mechanism',
            ],
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
