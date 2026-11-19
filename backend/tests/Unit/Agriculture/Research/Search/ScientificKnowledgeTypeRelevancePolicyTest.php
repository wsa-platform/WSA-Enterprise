<?php

namespace Tests\Unit\Agriculture\Research\Search;

use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Search\ScientificEvidenceRelevanceGate;
use App\Services\Agriculture\Research\Search\ScientificKnowledgeType;
use App\Services\Agriculture\Research\Search\ScientificKnowledgeTypeRelevancePolicy;
use App\Services\Agriculture\Research\Synthesis\AnswerExpressionAccuracyGate;
use PHPUnit\Framework\TestCase;

final class ScientificKnowledgeTypeRelevancePolicyTest extends TestCase
{
    public function test_gbif_entity_match_passes_for_taxonomy_question(): void
    {
        $plan = $this->plan(
            question: 'What is the scientific name and taxonomy of Triticum aestivum?',
            scientificName: 'Triticum aestivum',
            cropId: 'wheat',
            intent: 'species_list_or_taxonomy',
            topics: ['taxonomy'],
        );
        $gate = new ScientificEvidenceRelevanceGate(new AnswerExpressionAccuracyGate);
        $assessment = $gate->assess(
            $plan,
            'Triticum aestivum L.',
            'Accepted scientific name for bread wheat.',
            null,
            null,
            'gbif',
            ['knowledge_type' => ScientificKnowledgeType::TAXONOMIC_IDENTITY],
        );

        $this->assertTrue($assessment['relevant']);
        $this->assertTrue($assessment['factors']['identity_oriented_relevance']);
        $this->assertSame(ScientificKnowledgeType::TAXONOMIC_IDENTITY, $assessment['factors']['record_knowledge_type']);
    }

    public function test_traditional_use_not_compatible_with_scholarly_efficacy_only(): void
    {
        $policy = new ScientificKnowledgeTypeRelevancePolicy;
        $this->assertFalse(
            $policy->isCompatible(
                [ScientificKnowledgeType::SCHOLARLY_EVIDENCE, ScientificKnowledgeType::EXPERIMENTAL_EVIDENCE],
                ScientificKnowledgeType::TRADITIONAL_USE,
            ),
        );
    }

    public function test_protected_sources_include_gbif_for_taxonomy(): void
    {
        $policy = new ScientificKnowledgeTypeRelevancePolicy;
        $plan = $this->plan(
            question: 'taxonomy of wheat',
            scientificName: 'Triticum aestivum',
            cropId: 'wheat',
            intent: 'species_list_or_taxonomy',
            topics: ['taxonomy'],
        );
        $this->assertContains('gbif', $policy->protectedSourceKeys($plan));
    }

    private function plan(
        string $question,
        string $scientificName,
        string $cropId,
        string $intent,
        array $topics,
    ): KnowledgeQueryPlan {
        $query = new AgriculturalKnowledgeQuery(
            originalQuestion: $question,
            normalizedQuestion: $question,
            language: 'en',
            agriculturalDomain: 'crop_production',
            subject: ['type' => 'crop', 'value' => $cropId, 'label' => $cropId],
            crop: $cropId,
            cropId: $cropId,
            scientificName: $scientificName,
            topic: $topics[0] ?? 'general',
            subtopic: null,
            requestedInformation: $topics,
            constraints: [
                'scientific_topics' => $topics,
                'requested_property' => $topics[0] ?? 'general',
                'scientific_sense' => $intent === 'species_list_or_taxonomy' ? 'plant_family_members' : '',
            ],
            location: null,
            researchRequired: true,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            researchIntent: $intent,
        );

        return new KnowledgeQueryPlan(
            normalizedQuery: $query,
            researchIntent: $intent,
            agriculturalDomain: 'crop_production',
            subjectEntity: $query->subject,
            topics: $topics,
            subtopics: [],
            requestedInformation: $topics,
            evidenceRequirements: ['peer_reviewed'],
            sourcePriorities: ['openalex', 'gbif'],
            primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
        );
    }
}
