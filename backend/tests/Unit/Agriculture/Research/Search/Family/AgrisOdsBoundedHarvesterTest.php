<?php

namespace Tests\Unit\Agriculture\Research\Search\Family;

use App\Services\Agriculture\Research\Search\Family\Bulk\AgrisOdsBoundedHarvester;
use Tests\TestCase;

class AgrisOdsBoundedHarvesterTest extends TestCase
{
    public function test_harvester_reuses_local_bg3_and_writes_manifest_without_network_when_cached(): void
    {
        $root = storage_path('app/scientific_datasets/agris/AGRIS.ODS.xml');
        $bg3 = storage_path('app/scientific_datasets/agris/AGRIS.ODS.BG3.xml');
        if (! is_file($root) || ! is_file($bg3)) {
            $this->markTestSkipped('Local AGRIS ODS cache missing.');
        }

        $cache = storage_path('framework/testing/agris_ods_harvest_cache');
        $manifest = storage_path('framework/testing/agris_ods_harvest_manifest.json');
        @mkdir($cache, 0775, true);
        // Seed allowlisted basename so harvester can reuse without network for first matching URL ending in BG3.
        copy($bg3, $cache.DIRECTORY_SEPARATOR.'AGRIS.ODS.BG3.xml');
        @unlink($manifest);
        @unlink($cache.DIRECTORY_SEPARATOR.'harvest_checkpoint.v1.json');

        // Tiny synthetic root containing only the BG3 download URL already cached.
        $tinyRoot = $cache.DIRECTORY_SEPARATOR.'tiny_root.xml';
        file_put_contents($tinyRoot, <<<'XML'
<?xml version="1.0"?>
<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:dcat="http://www.w3.org/ns/dcat#">
  <dcat:downloadURL>https://agris.fao.org/ods/AGRIS.ODS.BG3.xml</dcat:downloadURL>
</rdf:RDF>
XML);

        $report = (new AgrisOdsBoundedHarvester)->harvest($tinyRoot, $cache, $manifest, 1, 50, 30);
        $this->assertSame('wsa.agris_ods_harvest_manifest.v1', $report['schema_id']);
        $this->assertGreaterThanOrEqual(1, (int) $report['totals']['projected']);
        $this->assertGreaterThan(0, (int) $report['totals']['records']);
        $this->assertFileExists($manifest);
        $decoded = json_decode((string) file_get_contents($manifest), true);
        $this->assertIsArray($decoded);
        $this->assertSame('projected', $decoded['providers'][0]['status'] ?? null);
        $this->assertTrue(($decoded['providers'][0]['reused_local'] ?? false) === true);
    }

    public function test_harvester_skips_non_allowlisted_hosts(): void
    {
        $cache = storage_path('framework/testing/agris_ods_harvest_skip');
        $manifest = storage_path('framework/testing/agris_ods_harvest_skip_manifest.json');
        @mkdir($cache, 0775, true);
        @unlink($manifest);
        @unlink($cache.DIRECTORY_SEPARATOR.'harvest_checkpoint.v1.json');
        $tinyRoot = $cache.DIRECTORY_SEPARATOR.'bad_root.xml';
        file_put_contents($tinyRoot, <<<'XML'
<?xml version="1.0"?>
<root xmlns:dcat="http://www.w3.org/ns/dcat#">
  <dcat:downloadURL>https://evil.example/ods/x.xml</dcat:downloadURL>
</root>
XML);
        $report = (new AgrisOdsBoundedHarvester)->harvest($tinyRoot, $cache, $manifest, 5, 10, 10);
        $this->assertSame(0, (int) $report['totals']['projected']);
        $this->assertSame('skipped_non_allowlisted_host', $report['providers'][0]['status'] ?? null);
    }

    public function test_no_download_mode_marks_missing_local_without_network(): void
    {
        $cache = storage_path('framework/testing/agris_ods_harvest_nodl');
        $manifest = storage_path('framework/testing/agris_ods_harvest_nodl_manifest.json');
        @mkdir($cache, 0775, true);
        @unlink($manifest);
        @unlink($cache.DIRECTORY_SEPARATOR.'harvest_checkpoint.v1.json');
        $tinyRoot = $cache.DIRECTORY_SEPARATOR.'missing_root.xml';
        file_put_contents($tinyRoot, <<<'XML'
<?xml version="1.0"?>
<root xmlns:dcat="http://www.w3.org/ns/dcat#">
  <dcat:downloadURL>https://agris.fao.org/ods/AGRIS.ODS.ZZ9.xml</dcat:downloadURL>
</root>
XML);

        $report = (new AgrisOdsBoundedHarvester)->harvest(
            $tinyRoot,
            $cache,
            $manifest,
            1,
            10,
            10,
            allowDownload: false,
        );

        $this->assertSame('existing_local_only_no_redownload', $report['scan_mode'] ?? null);
        $this->assertFalse(($report['allow_download'] ?? true));
        $this->assertSame(1, (int) ($report['totals']['missing_local_no_redownload'] ?? 0));
        $this->assertSame('missing_local_no_redownload', $report['providers'][0]['status'] ?? null);
        $this->assertSame('MISSING_AFTER_EXHAUSTIVE_SEARCH', $report['providers'][0]['failure_code'] ?? null);
        $this->assertFileDoesNotExist($cache.DIRECTORY_SEPARATOR.'AGRIS.ODS.ZZ9.xml');
    }
}
