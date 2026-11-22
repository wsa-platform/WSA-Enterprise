<?php

namespace Tests\Unit\Agriculture\Research\Search;

use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatRuntimePolicy;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceCatalog;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceSelectionRole;
use Tests\TestCase;

/**
 * Workstream B: preserve FAOSTAT default-disabled and Consensus OPTIONAL_EXPLICIT_ONLY.
 */
class FaoStatAndConsensusPolicyContractTest extends TestCase
{
    public function test_consensus_is_optional_explicit_only_in_catalog(): void
    {
        $catalog = ScientificSourceCatalog::default();
        $registration = $catalog->get('consensus');

        $this->assertNotNull($registration);
        $this->assertSame(
            ScientificSourceSelectionRole::OPTIONAL_EXPLICIT_ONLY,
            $registration->selectionRole,
        );
        $this->assertContains(
            'consensus',
            $catalog->keysWithRole(ScientificSourceSelectionRole::OPTIONAL_EXPLICIT_ONLY),
        );
    }

    public function test_faostat_is_statistical_consideration_and_default_disabled(): void
    {
        config(['agricultural_intelligence.faostat.enabled' => false]);

        $catalog = ScientificSourceCatalog::default();
        $registration = $catalog->get('fao_stat');

        $this->assertNotNull($registration);
        $this->assertSame(
            ScientificSourceSelectionRole::STATISTICAL_CONSIDERATION,
            $registration->selectionRole,
        );
        $this->assertFalse(FaoStatRuntimePolicy::isEnabled());
    }

    public function test_faostat_enabled_flag_can_be_toggled_in_isolated_config(): void
    {
        config(['agricultural_intelligence.faostat.enabled' => true]);
        $this->assertTrue(FaoStatRuntimePolicy::isEnabled());

        config(['agricultural_intelligence.faostat.enabled' => false]);
        $this->assertFalse(FaoStatRuntimePolicy::isEnabled());
    }
}
