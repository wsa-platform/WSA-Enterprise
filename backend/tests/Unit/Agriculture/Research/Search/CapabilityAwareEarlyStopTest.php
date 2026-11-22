<?php

namespace Tests\Unit\Agriculture\Research\Search;

use App\Services\Agriculture\Research\ResearchPlanner;
use App\Services\Agriculture\Research\Search\ScientificKnowledgeType;
use App\Services\Agriculture\Research\Search\ScientificKnowledgeTypeRelevancePolicy;
use Tests\TestCase;

/**
 * Workstream B: early-stop must be capability-aware — raw hit count is not enough.
 */
class CapabilityAwareEarlyStopTest extends TestCase
{
    public function test_organic_pest_control_requires_practice_and_blocks_agris_early_stop(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'What are organic pest control practices for tomato according to agricultural research?',
        ]);
        $policy = new ScientificKnowledgeTypeRelevancePolicy();

        $this->assertContains(ScientificKnowledgeType::AGRICULTURAL_PRACTICE, $policy->requiredTypes($plan));
        $this->assertFalse($policy->mayEarlyStopSource('agris', $plan));
        $this->assertFalse($policy->mayEarlyStopSource('organic_eprints', $plan));
        $this->assertTrue($policy->mayEarlyStopSource('dr_duke', $plan));
    }

    public function test_taxonomy_question_blocks_gbif_and_bhl_early_stop(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'What is the taxonomic classification of Mentha piperita?',
        ]);
        $policy = new ScientificKnowledgeTypeRelevancePolicy();

        $this->assertContains(ScientificKnowledgeType::TAXONOMIC_IDENTITY, $policy->requiredTypes($plan));
        $this->assertFalse($policy->mayEarlyStopSource('gbif', $plan));
        $this->assertFalse($policy->mayEarlyStopSource('bhl', $plan));
        $this->assertTrue($policy->mayEarlyStopSource('dr_duke', $plan));
    }

    public function test_physiology_scholarly_question_may_early_stop_agris(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'What is the effect of temperature on wheat growth?',
        ]);
        $policy = new ScientificKnowledgeTypeRelevancePolicy();

        $this->assertTrue($policy->mayEarlyStopSource('agris', $plan));
        $this->assertTrue($policy->mayEarlyStopSource('gbif', $plan));
        $this->assertSame([], $policy->distinctiveRequiredTypes($plan));
    }

    public function test_phytochemistry_blocks_dr_duke_early_stop(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'What chemical constituents are reported for Mentha piperita phytochemistry?',
        ]);
        $policy = new ScientificKnowledgeTypeRelevancePolicy();

        $this->assertContains(ScientificKnowledgeType::CHEMICAL_ASSOCIATION, $policy->requiredTypes($plan));
        $this->assertFalse($policy->mayEarlyStopSource('dr_duke', $plan));
        $this->assertTrue($policy->mayEarlyStopSource('gbif', $plan));
    }

    public function test_agris_default_knowledge_type_is_agricultural_practice(): void
    {
        $this->assertSame(
            ScientificKnowledgeType::AGRICULTURAL_PRACTICE,
            ScientificKnowledgeType::forSourceKey('agris'),
        );
        $this->assertSame(
            ScientificKnowledgeType::AGRICULTURAL_PRACTICE,
            ScientificKnowledgeType::forSourceKey('organic_eprints'),
        );
    }
}
