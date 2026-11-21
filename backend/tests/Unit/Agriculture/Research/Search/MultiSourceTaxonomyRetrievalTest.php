<?php

namespace Tests\Unit\Agriculture\Research\Search;

use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\QueryUnderstandingService;
use App\Services\Agriculture\Research\ResearchPlanner;
use App\Services\Agriculture\Research\Search\ScientificKnowledgeTypeRelevancePolicy;
use App\Services\Agriculture\Research\Search\ScientificSearchQueryBuilder;
use App\Services\Agriculture\Research\Search\ScientificSourceSelector;
use Tests\TestCase;

/**
 * Multi-source restoration: taxonomy questions must not treat GBIF as exclusive —
 * BHL stays early-stop-protected and scholarly variants target taxonomy/phylogeny.
 */
class MultiSourceTaxonomyRetrievalTest extends TestCase
{
    public function test_taxonomy_plan_protects_gbif_and_bhl_from_early_stop(): void
    {
        $plan = $this->menthaPlan();
        $protected = (new ScientificKnowledgeTypeRelevancePolicy())->protectedSourceKeys($plan);

        $this->assertContains('gbif', $protected);
        $this->assertContains('bhl', $protected);
        $this->assertNotContains('openalex', $protected);
    }

    public function test_taxonomy_query_variants_prefer_taxonomy_not_cultivation(): void
    {
        $plan = $this->menthaPlan();
        $variants = (new ScientificSearchQueryBuilder())->buildVariantsFromPlan($plan);
        $joined = mb_strtolower(implode(' | ', $variants));

        $this->assertNotSame([], $variants);
        $this->assertTrue(
            str_contains($joined, 'taxonom') || str_contains($joined, 'phylogen'),
            'expected taxonomy/phylogeny variants, got: '.$joined,
        );
        $this->assertFalse(
            str_contains($joined, 'cultivation') && ! str_contains($joined, 'taxonom'),
            'cultivation-only variants must not dominate taxonomy questions: '.$joined,
        );
    }

    public function test_selector_includes_gbif_and_bhl_for_mentha_taxonomy(): void
    {
        $plan = $this->menthaPlan();
        $selected = (new ScientificSourceSelector())->selectSources($plan);

        $this->assertContains('gbif', $selected);
        $this->assertContains('bhl', $selected);
        $this->assertContains('openalex', $selected);
        $this->assertContains('crossref', $selected);
    }

    public function test_capability_only_subset_is_not_sufficient_without_selector_merge(): void
    {
        $plan = $this->menthaPlan();
        $capabilityOnly = ['openalex', 'crossref', 'semantic_scholar', 'fao_stat'];
        $selectorKeys = (new ScientificSourceSelector())->selectSources($plan);
        $merged = array_values(array_unique([...$capabilityOnly, ...$selectorKeys]));

        $this->assertNotContains('gbif', $capabilityOnly);
        $this->assertContains('gbif', $merged);
        $this->assertContains('bhl', $merged);
    }

    private function menthaPlan(): KnowledgeQueryPlan
    {
        $understood = (new QueryUnderstandingService())->understand([
            'query' => 'What is the taxonomic classification of Mentha piperita?',
        ]);

        return (new ResearchPlanner(new QueryUnderstandingService()))->planKnowledgeQuery([
            'query' => 'What is the taxonomic classification of Mentha piperita?',
        ]);
    }
}
