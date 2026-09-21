<?php

namespace Tests\Feature;

use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use App\Services\Agriculture\Research\Synthesis\QuestionClaimEvidenceMapper;
use App\Services\Agriculture\Research\Synthesis\QuestionClaimExtractor;
use App\Services\Agriculture\Research\Synthesis\QuestionClaimSynthesisContract;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use App\Services\Agriculture\Research\Validation\EvidenceValidationExecutionReport;
use App\Services\Agriculture\Research\Validation\EvidenceValidationStatus;
use App\Services\Agriculture\Research\Validation\ScientificEvidenceItem;
use Tests\TestCase;

class Phase5ClaimEvidenceMappingTest extends TestCase
{
    public function test_multiple_evidence_items_bind_to_one_question_claim(): void
    {
        $plan = $this->plan();
        $claims = (new QuestionClaimExtractor)->extract($plan);
        $evidence = [
            $this->evidence('e-1', ClaimEvidenceRelationship::SUPPORTED),
            $this->evidence('e-2', ClaimEvidenceRelationship::PARTIALLY_SUPPORTED, ScientificEvidenceDirectnessAssessor::SUPPORTING),
        ];

        $bindings = (new QuestionClaimEvidenceMapper)->bind($claims, $evidence);

        $this->assertCount(2, $bindings);
        $this->assertSame(['qc-1', 'qc-1'], array_map(static fn ($b) => $b->questionClaimId, $bindings));
        $this->assertSame(['e-1', 'e-2'], array_map(static fn ($b) => $b->evidenceId, $bindings));
        $this->assertSame(['src-e-1', 'src-e-2'], array_map(static fn ($b) => $b->sourceId, $bindings));
        $this->assertSame('https://example.test/e-1', $bindings[0]->sourceUrl);
    }

    public function test_identities_remain_stable_and_distinct(): void
    {
        $plan = $this->plan();
        $claims = (new QuestionClaimExtractor)->extract($plan);
        $evidence = [$this->evidence('evidence-stable-9', ClaimEvidenceRelationship::SUPPORTED)];
        $bindings = (new QuestionClaimEvidenceMapper)->bind($claims, $evidence);

        $this->assertSame('qc-1', $claims[0]->claimId);
        $this->assertSame('evidence-stable-9', $bindings[0]->evidenceId);
        $this->assertSame('src-evidence-stable-9', $bindings[0]->sourceId);
        $this->assertNotSame($bindings[0]->evidenceId, $bindings[0]->sourceId);
        $this->assertNotSame($claims[0]->claimId, $bindings[0]->evidenceId);
    }

    public function test_phase4_relationship_vocabulary_preserved(): void
    {
        $plan = $this->plan();
        $claims = (new QuestionClaimExtractor)->extract($plan);
        $mapper = new QuestionClaimEvidenceMapper;
        $supported = $mapper->bind($claims, [$this->evidence('e-s', ClaimEvidenceRelationship::SUPPORTED)]);
        $partial = $mapper->bind($claims, [$this->evidence('e-p', ClaimEvidenceRelationship::PARTIALLY_SUPPORTED)]);
        $conflict = $mapper->bind($claims, [$this->evidence('e-c', ClaimEvidenceRelationship::CONFLICTING, conflict: true)]);

        $this->assertSame(ClaimEvidenceRelationship::SUPPORTED, $mapper->aggregateRelationshipForClaim('qc-1', $supported));
        $this->assertSame(ClaimEvidenceRelationship::PARTIALLY_SUPPORTED, $mapper->aggregateRelationshipForClaim('qc-1', $partial));
        $this->assertSame(ClaimEvidenceRelationship::CONFLICTING, $mapper->aggregateRelationshipForClaim('qc-1', $conflict));
    }

    public function test_conflict_is_not_collapsed_by_supporting_sibling(): void
    {
        $plan = $this->plan();
        $claims = (new QuestionClaimExtractor)->extract($plan);
        $bindings = (new QuestionClaimEvidenceMapper)->bind($claims, [
            $this->evidence('e-s', ClaimEvidenceRelationship::SUPPORTED),
            $this->evidence('e-c', ClaimEvidenceRelationship::CONFLICTING, conflict: true),
        ]);

        $aggregate = (new QuestionClaimEvidenceMapper)->aggregateRelationshipForClaim('qc-1', $bindings);

        $this->assertSame(ClaimEvidenceRelationship::CONFLICTING, $aggregate);
        $this->assertTrue($bindings[1]->hasConflict);
    }

    private function plan(): KnowledgeQueryPlan
    {
        $query = new AgriculturalKnowledgeQuery(
            originalQuestion: 'Wheat production in Egypt 2020',
            normalizedQuestion: 'Wheat production in Egypt 2020',
            language: 'en',
            agriculturalDomain: 'agronomy',
            subject: ['type' => 'crop', 'value' => 'wheat'],
            crop: 'wheat',
            cropId: 'wheat',
            scientificName: null,
            topic: 'production',
            subtopic: null,
            requestedInformation: ['quantity'],
            constraints: ['answer_language' => 'en', 'year' => '2020'],
            location: 'Egypt',
            researchRequired: true,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            researchIntent: 'statistical_lookup',
        );

        return new KnowledgeQueryPlan(
            normalizedQuery: $query,
            researchIntent: 'statistical_lookup',
            agriculturalDomain: 'agronomy',
            subjectEntity: $query->subject,
            topics: ['production'],
            subtopics: [],
            requestedInformation: ['quantity'],
            evidenceRequirements: ['official_statistics'],
            sourcePriorities: ['openalex'],
            primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            readyForStage3: true,
        );
    }

    private function evidence(
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
            doi: null,
            url: 'https://example.test/'.$id,
            publicationYear: 2020,
            retrievedAt: now()->toIso8601String(),
            agriculturalDomain: 'agronomy',
            claimTopic: 'production',
            evidenceText: 'Evidence '.$id,
            validationStatus: EvidenceValidationStatus::EVIDENCE_USABLE,
            validationFailures: [],
            claimRelationship: $relationship,
            confidence: 0.7,
            qualityScore: 0.7,
            qualityFactors: ['evidence_directness' => $directness],
            sourceAttribution: ['evidence_directness' => $directness],
            hasConflict: $conflict,
        );
    }
}
