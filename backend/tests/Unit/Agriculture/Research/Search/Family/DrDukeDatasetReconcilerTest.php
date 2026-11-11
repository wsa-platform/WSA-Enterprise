<?php

namespace Tests\Unit\Agriculture\Research\Search\Family;

use App\Services\Agriculture\Research\Search\Family\Bulk\DrDukeDatasetReconciler;
use Tests\TestCase;

class DrDukeDatasetReconcilerTest extends TestCase
{
    public function test_reconciler_measures_farmacy_keys_and_does_not_blind_merge(): void
    {
        $dir = storage_path('app/scientific_datasets/dr_duke/extracted');
        if (! is_file($dir.'/FNFTAX.csv')) {
            $this->markTestSkipped('Dr. Duke extracted CSVs not present.');
        }
        $paths = [];
        foreach (['FNFTAX', 'CHEMICALS', 'ETHNOBOT', 'FARMACY', 'FARMACY_NEW', 'COMMON_NAMES'] as $t) {
            $p = $dir.'/'.$t.'.csv';
            if (is_file($p)) {
                $paths[$t] = $p;
            }
        }
        $report = (new DrDukeDatasetReconciler)->reconcile($paths);
        $this->assertSame('wsa.dr_duke_reconciliation.v1', $report['schema_id']);
        $this->assertGreaterThan(1000, (int) $report['fnftax_keys']);
        $this->assertGreaterThan(0, $report['relationships']['FARMACY.FNFNUM->FNFTAX']['matched']);
        $this->assertGreaterThan(0, $report['relationships']['FARMACY.CHEM->CHEMICALS.CHEM']['matched']);
        $this->assertStringContainsString('Do not blind-merge', $report['farmacy_vs_farmacy_new']['interpretation']);
        $this->assertContains('FARMACY', $report['projection_policy']['relationship_validated_not_row_projected']);
        $this->assertContains('ACTIVITIES', $report['projection_policy']['inspected_not_projected']);
    }
}
