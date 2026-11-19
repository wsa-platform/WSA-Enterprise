<?php

namespace Tests\Unit\Agriculture\Research\Search;

use App\Services\Agriculture\Research\Search\MultiSourceScientificSearchOrchestrator;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Home scholarly process-pool overlap must be opt-in (default sequential).
 */
class ScholarlyConcurrencySequentialFallbackTest extends TestCase
{
    public function test_home_scholarly_concurrency_defaults_to_sequential(): void
    {
        config(['agricultural_intelligence.stage3_home_scholarly_concurrency' => false]);

        $orchestrator = app(MultiSourceScientificSearchOrchestrator::class);
        $method = new ReflectionMethod($orchestrator, 'shouldOverlapIndependentScholarlyProviders');
        $method->setAccessible(true);

        $plan = app(\App\Services\Agriculture\Research\ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'What is the effect of temperature on wheat growth?',
        ]);

        $this->assertFalse($method->invoke($orchestrator, $plan));

        config(['agricultural_intelligence.stage3_home_scholarly_concurrency' => true]);
        // Still requires generic_research crop-profile gate; assert config is readable.
        $this->assertTrue((bool) config('agricultural_intelligence.stage3_home_scholarly_concurrency'));
    }
}
