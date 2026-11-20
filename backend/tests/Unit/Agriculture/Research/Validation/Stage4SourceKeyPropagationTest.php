<?php

namespace Tests\Unit\Agriculture\Research\Validation;

use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\QueryUnderstandingService;
use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use App\Services\Agriculture\Research\Search\ScientificEvidenceRelevanceGate;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;
use App\Services\Agriculture\Research\Synthesis\AnswerExpressionAccuracyGate;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceMatcher;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use App\Services\Agriculture\Research\Validation\EvidenceValidationStatus;
use App\Services\Agriculture\Research\Validation\EvidenceVerificationLayer;
use PHPUnit\Framework\TestCase;

/**
 * P0 regression: Stage-4 claim matching must pass source_key into the relevance gate
 * so identity-oriented taxonomic evaluation matches Stage-3.
 */
class Stage4SourceKeyPropagationTest extends TestCase
{
    public function test_claim_matcher_with_source_key_accepts_taxonomy_paper(): void
    {
        $plan = $this->menthaPlan();
        $gate = new ScientificEvidenceRelevanceGate();
        $directness = new ScientificEvidenceDirectnessAssessor($gate);
        $matcher = new ClaimEvidenceMatcher(
            $gate,
            $directness,
            new EvidenceVerificationLayer($directness),
            new AnswerExpressionAccuracyGate(),
        );

        $result = new ScientificSearchResult(
            sourceKey: 'openalex',
            sourceIdentifier: '10.1000/example.taxonomy',
            title: 'Taxonomic classification and phylogeny of Mentha piperita L.',
            authors: ['Example'],
            publicationYear: 2020,
            doi: '10.1000/example.taxonomy',
            canonicalUrl: 'https://doi.org/10.1000/example.taxonomy',
            abstract: 'Mentha piperita belongs to Lamiaceae. Genus Mentha taxonomy and species delimitation are reviewed.',
            journal: 'Example Journal',
            foundBySources: ['openalex'],
        );

        $match = $matcher->match(
            $plan,
            $result,
            $result->abstract,
            EvidenceValidationStatus::METADATA_VALID,
        );

        $this->assertContains(
            $match['relationship'],
            [
                ClaimEvidenceRelationship::SUPPORTED,
                ClaimEvidenceRelationship::PARTIALLY_SUPPORTED,
            ],
            json_encode($match, JSON_UNESCAPED_UNICODE),
        );
        $this->assertNotSame(
            'relevance_gate_rejected',
            $match['factors']['reason'] ?? null,
            json_encode($match, JSON_UNESCAPED_UNICODE),
        );
    }

    public function test_identity_oriented_relevance_requires_source_key_for_scholarly(): void
    {
        $plan = $this->menthaPlan();
        $gate = new ScientificEvidenceRelevanceGate();
        $with = $gate->assess(
            $plan,
            'Taxonomic classification of Mentha piperita',
            'Mentha piperita taxonomy and family Lamiaceae classification.',
            null,
            null,
            'openalex',
            null,
        );
        $without = $gate->assess(
            $plan,
            'Taxonomic classification of Mentha piperita',
            'Mentha piperita taxonomy and family Lamiaceae classification.',
            null,
            null,
            null,
            null,
        );

        $this->assertTrue($with['relevant'], json_encode($with, JSON_UNESCAPED_UNICODE));
        $this->assertTrue((bool) ($with['factors']['identity_oriented_relevance'] ?? false));
        $this->assertFalse((bool) ($without['factors']['identity_oriented_relevance'] ?? false));
    }

    private function menthaPlan(): KnowledgeQueryPlan
    {
        $understood = (new QueryUnderstandingService())->understand([
            'query' => 'What is the taxonomic classification of Mentha piperita?',
        ]);

        return new KnowledgeQueryPlan(
            normalizedQuery: $understood,
            researchIntent: 'species_list_or_taxonomy',
            agriculturalDomain: (string) $understood->agriculturalDomain,
            subjectEntity: is_array($understood->subject) ? $understood->subject : null,
            topics: array_values(array_filter([
                $understood->topic,
                ...((array) ($understood->constraints['scientific_topics'] ?? [])),
            ], static fn ($v): bool => is_string($v) && trim($v) !== '')),
            subtopics: [],
            requestedInformation: $understood->requestedInformation,
            evidenceRequirements: ['peer_reviewed'],
            sourcePriorities: ['openalex', 'gbif'],
            primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            readyForStage3: true,
        );
    }
}
