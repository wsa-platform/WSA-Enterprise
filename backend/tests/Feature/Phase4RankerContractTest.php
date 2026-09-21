<?php

namespace Tests\Feature;

use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use App\Services\Agriculture\Research\Search\ScientificResultRanker;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;
use Tests\TestCase;

/**
 * Phase 4 Unit B — Ranker score semantics (ordering ≠ scientific truth).
 */
class Phase4RankerContractTest extends TestCase
{
    public function test_relevance_score_is_ordering_only_not_validation(): void
    {
        $plan = $this->plan(
            question: 'What irrigation methods improve wheat yield?',
            location: null,
            constraints: ['question_type' => 'mechanism'],
        );

        $relevant = $this->makeResult(
            'W-rel',
            'Wheat drip irrigation yield response',
            'Wheat drip irrigation improved grain yield in multi-year field trials.',
        );
        $irrelevant = $this->makeResult(
            'W-irr',
            'National banking reforms and tourism GDP',
            'Nationwide macroeconomic inventory of banking indicators.',
        );

        $ranked = app(ScientificResultRanker::class)->rank(
            'wheat irrigation yield',
            [$irrelevant, $relevant],
            $plan,
        );

        $this->assertSame('W-rel', $ranked[0]->sourceIdentifier);
        $this->assertSame('ranking_order', $ranked[0]->relevanceMetadata['score_role'] ?? null);
        $this->assertTrue((bool) ($ranked[0]->relevanceMetadata['not_scientific_validation'] ?? false));
        $this->assertTrue((bool) ($this->byId($ranked, 'W-irr')->relevanceMetadata['rejected_by_relevance_gate'] ?? false)
            || (($this->byId($ranked, 'W-irr')->relevanceScore ?? 0.0) < ($ranked[0]->relevanceScore ?? 0.0)));
    }

    public function test_geo_scope_is_soft_preference_after_topical_gate(): void
    {
        $plan = $this->plan(
            question: 'What are the types of agricultural land in Egypt?',
            location: 'Egypt',
            constraints: [
                'required_evidence_type' => 'classification_or_types_inventory',
                'question_type' => 'classification',
                'scientific_sense' => 'land_classification',
            ],
        );

        $national = $this->makeResult(
            'W-nat',
            'Soil Map of Egypt: national soil types and land classification',
            'Nationwide inventory of soil types and agricultural land classes across the country.',
        );
        $regional = $this->makeResult(
            'W-reg',
            'Soil Classification and Land Capability Evaluation in South Sinai, Egypt',
            'Lists soil types and land capability classes for the South Sinai governorate.',
        );

        $ranked = app(ScientificResultRanker::class)->rank(
            'agricultural land types Egypt',
            [$regional, $national],
            $plan,
        );

        $this->assertSame('W-nat', $ranked[0]->sourceIdentifier);
        $natMeta = $ranked[0]->relevanceMetadata ?? [];
        // Soft preference: geo metadata may be national when topical gate passes; never hard-deletes regional.
        if (($natMeta['document_geo_scope'] ?? null) === ScientificResultRanker::DOCUMENT_GEO_SCOPE_NATIONAL) {
            $this->assertSame('country', $natMeta['query_geo_level'] ?? null);
        }
        $this->assertTrue((bool) ($natMeta['not_scientific_validation'] ?? false));
        $this->assertSame('ranking_order', $natMeta['score_role'] ?? null);
        $this->assertNotNull($this->byId($ranked, 'W-reg'));
        $this->assertGreaterThan(
            (float) ($this->byId($ranked, 'W-reg')->relevanceScore ?? 0.0),
            (float) ($ranked[0]->relevanceScore ?? 0.0),
        );
    }

    public function test_required_evidence_adjust_is_preference_not_sufficiency(): void
    {
        $plan = $this->plan(
            question: 'What are the soil types in Egypt?',
            location: 'Egypt',
            constraints: [
                'required_evidence_type' => 'classification_or_types_inventory',
                'question_type' => 'classification',
            ],
        );

        $inventory = $this->makeResult(
            'W-inv',
            'Soil types and land classes inventory of Egypt',
            'Nationwide inventory of soil types and agricultural land classes.',
        );
        $methodOnly = $this->makeResult(
            'W-ml',
            'Random forest land classification model',
            'Machine learning neural network CNN for remote sensing classification algorithm.',
        );

        $ranked = app(ScientificResultRanker::class)->rank(
            'soil types Egypt',
            [$methodOnly, $inventory],
            $plan,
        );

        $this->assertSame('W-inv', $ranked[0]->sourceIdentifier);
        // Ranker must not emit sufficiency / disposition / verification fields.
        $this->assertArrayNotHasKey('evidence_sufficient', $ranked[0]->relevanceMetadata ?? []);
        $this->assertArrayNotHasKey('evidence_lifecycle_disposition', $ranked[0]->relevanceMetadata ?? []);
        $this->assertArrayNotHasKey('claim_relationship', $ranked[0]->relevanceMetadata ?? []);
    }

    public function test_filter_relevant_is_ranking_exclusion_not_verification(): void
    {
        $plan = $this->plan(
            question: 'What irrigation methods improve wheat yield?',
            location: null,
            constraints: [],
        );
        $relevant = $this->makeResult(
            'W-ok',
            'Wheat drip irrigation yield',
            'Wheat drip irrigation improved yield in field trials.',
        );
        $noise = $this->makeResult(
            'W-noise',
            'Unrelated quantum computing survey',
            'Quantum algorithms for cryptography without agriculture.',
        );

        $ranker = app(ScientificResultRanker::class);
        $ranked = $ranker->rank('wheat irrigation', [$noise, $relevant], $plan);
        $filtered = $ranker->filterRelevant($ranked);

        $ids = array_map(static fn (ScientificSearchResult $r): ?string => $r->sourceIdentifier, $filtered);
        $this->assertContains('W-ok', $ids);
        // Exclusion is ranking-pipeline only; metadata still carries score_role on survivors.
        foreach ($filtered as $row) {
            $this->assertSame('ranking_order', $row->relevanceMetadata['score_role'] ?? null);
        }
    }

    public function test_directness_tie_break_does_not_claim_truth(): void
    {
        $plan = $this->plan(
            question: 'What irrigation methods improve wheat yield?',
            location: null,
            constraints: ['question_type' => 'mechanism'],
        );
        $a = $this->makeResult('W-a', 'Wheat irrigation review', 'Wheat irrigation methods in arid regions.');
        $b = $this->makeResult('W-b', 'Wheat irrigation field trial', 'Wheat drip irrigation improved yield in field trials.');

        $ranked = app(ScientificResultRanker::class)->rank('wheat irrigation', [$a, $b], $plan);
        foreach ($ranked as $row) {
            $directness = $row->relevanceMetadata['evidence_directness'] ?? null;
            if ($directness !== null) {
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
            $this->assertTrue((bool) ($row->relevanceMetadata['not_scientific_validation'] ?? false));
        }
    }

    /**
     * @param  array<string, mixed>  $constraints
     */
    private function plan(string $question, ?string $location, array $constraints): KnowledgeQueryPlan
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
            constraints: array_merge([
                'answer_language' => 'en',
                'question_language' => 'en',
            ], $constraints),
            location: $location,
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

    private function makeResult(string $id, string $title, string $abstract): ScientificSearchResult
    {
        return new ScientificSearchResult(
            sourceKey: 'openalex',
            sourceIdentifier: $id,
            title: $title,
            authors: ['Fixture'],
            publicationYear: 2021,
            doi: '10.9999/'.$id,
            canonicalUrl: 'https://example.test/'.$id,
            abstract: $abstract,
            journal: 'Fixture Journal',
            foundBySources: ['openalex'],
        );
    }

    /**
     * @param  list<ScientificSearchResult>  $ranked
     */
    private function byId(array $ranked, string $id): ScientificSearchResult
    {
        foreach ($ranked as $row) {
            if ($row->sourceIdentifier === $id) {
                return $row;
            }
        }

        $this->fail('missing ranked id '.$id);
    }
}
