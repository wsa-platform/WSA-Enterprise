<?php

namespace Tests\Unit\Agriculture\Research\Search;

use App\Services\Agriculture\Research\ResearchPlanner;
use App\Services\Agriculture\Research\Search\ScientificKnowledgeType;
use App\Services\Agriculture\Research\Search\ScientificKnowledgeTypeRelevancePolicy;
use Tests\TestCase;

/**
 * R3.1: AGRIS / Dr. Duke / Semantic Scholar effectiveness depends on knowledge-type
 * early-stop protection being contract-driven — not question-name special cases.
 */
class PrioritySourceEarlyStopProtectionTest extends TestCase
{
    public function test_cultivation_irrigation_requires_agricultural_practice_and_protects_agris(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'What are recommended irrigation practices for maize cultivation according to agricultural research?',
        ]);
        $policy = new ScientificKnowledgeTypeRelevancePolicy();
        $required = $policy->requiredTypes($plan);
        $protected = $policy->protectedSourceKeys($plan);

        $this->assertContains(ScientificKnowledgeType::AGRICULTURAL_PRACTICE, $required);
        $this->assertContains('agris', $protected);
    }

    public function test_wheat_physiology_does_not_unconditionally_protect_agris(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'What is the effect of temperature on wheat growth?',
        ]);
        $policy = new ScientificKnowledgeTypeRelevancePolicy();
        $protected = $policy->protectedSourceKeys($plan);

        $this->assertNotContains('agris', $protected);
    }

    public function test_phytochemical_question_protects_dr_duke(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'What chemical constituents are reported in Mentha piperita according to ethnobotanical phytochemical data?',
        ]);
        $policy = new ScientificKnowledgeTypeRelevancePolicy();
        $required = $policy->requiredTypes($plan);
        $protected = $policy->protectedSourceKeys($plan);

        $this->assertContains(ScientificKnowledgeType::CHEMICAL_ASSOCIATION, $required);
        $this->assertContains('dr_duke', $protected);
    }
}
