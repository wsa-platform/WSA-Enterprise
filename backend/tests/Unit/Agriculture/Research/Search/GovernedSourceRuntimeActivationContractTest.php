<?php

namespace Tests\Unit\Agriculture\Research\Search;

use App\Services\Agriculture\Research\Onboarding\AcceptedOnboardingDecisionCatalog;
use App\Services\Agriculture\Research\ResearchPlanner;
use App\Services\Agriculture\Research\Search\ScientificSearchQueryBuilder;
use App\Services\Agriculture\Research\Search\ScientificSourceAdapterRegistry;
use App\Services\Agriculture\Research\Search\ScientificSourceSelector;
use Tests\TestCase;

/**
 * EXIT-08 / EXIT-09: authorized ACTIVE governed seats must be selected and resolvable;
 * CSQ geography must survive into query variants used by orchestration.
 *
 * ACTIVE ≠ SCIENTIFIC_INTEGRATION_VERIFIED (§8.1).
 */
class GovernedSourceRuntimeActivationContractTest extends TestCase
{
    public function test_authorized_active_governed_keys_are_registered_selected_and_resolvable(): void
    {
        $expected = AcceptedOnboardingDecisionCatalog::activeSourceKeys();
        sort($expected);
        $this->assertSame(['agris', 'bhl', 'dr_duke', 'gbif', 'organic_eprints'], $expected);

        $registry = app(ScientificSourceAdapterRegistry::class);
        $selector = app(ScientificSourceSelector::class);
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'What are the types of agricultural land in Egypt?',
        ]);

        $selected = $selector->selectSources($plan);
        foreach ($expected as $key) {
            $this->assertContains($key, $registry->registeredSourceKeys(), $key.' must be registered');
            $adapter = $registry->get($key);
            $this->assertNotNull($adapter, $key.' adapter must resolve');
            $this->assertSame($key, $adapter->sourceKey());
        }
        // Rev 11.0.10 §16: land-classification selects agricultural literature (AGRIS),
        // not phytochemistry / taxonomy / organic-cued seats by default.
        $this->assertContains('agris', $selected, 'AGRIS must be selected for agricultural land queries');
        $this->assertNotContains('dr_duke', $selected);
        $this->assertNotContains('gbif', $selected);
        $this->assertNotContains('bhl', $selected);
        $this->assertNotContains('consensus', $selected);
    }

    public function test_egypt_land_csq_variants_preserve_national_geography_for_orchestrator_queries(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما هي انواع الاراضي الزراعيه فى مصر',
        ]);
        $this->assertNotNull($plan->normalizedQuery->canonicalQuestion);
        $variants = app(ScientificSearchQueryBuilder::class)->buildVariantsFromPlan($plan);
        $joined = mb_strtolower(implode(' | ', $variants));
        $this->assertStringContainsString('egypt', $joined);
        $this->assertTrue(
            str_contains($joined, 'land') || str_contains($joined, 'soil'),
            $joined,
        );
    }
}
