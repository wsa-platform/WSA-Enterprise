<?php

namespace Tests\Unit\Agriculture\Research\Search\Family;

use App\Services\Agriculture\Research\Search\Family\Bulk\AgrisOdsProviderXmlProjector;
use App\Services\Agriculture\Research\Search\Family\Bulk\DrDukeCsvRelationshipProjector;
use Tests\TestCase;

class AgrisOdsAndDrDukeProjectorTest extends TestCase
{
    public function test_agris_ods_provider_xml_projects_bibliographic_resources(): void
    {
        $xml = storage_path('app/scientific_datasets/agris/AGRIS.ODS.BG3.xml');
        if (! is_file($xml)) {
            $this->markTestSkipped('AGRIS.ODS.BG3.xml not present in local scientific_datasets cache.');
        }
        $out = storage_path('framework/testing/agris_bg3_projection.jsonl');
        @unlink($out);
        $report = (new AgrisOdsProviderXmlProjector)->projectToJsonl($xml, $out, 50);
        $this->assertSame('completed', $report['status']);
        $this->assertGreaterThan(0, $report['records']);
        $this->assertSame('CC-BY-4.0', $report['license']);
        $first = json_decode((string) fgets(fopen($out, 'rb')), true);
        $this->assertIsArray($first);
        $this->assertNotSame('', $first['id'] ?? '');
        $this->assertNotSame('', $first['title'] ?? '');
        @unlink($out);
    }

    public function test_dr_duke_relationship_projector_maps_real_keys_without_inventing_efficacy(): void
    {
        $dir = storage_path('app/scientific_datasets/dr_duke/extracted');
        if (! is_file($dir.'/FNFTAX.csv')) {
            $this->markTestSkipped('Dr. Duke extracted CSVs not present.');
        }
        $out = storage_path('framework/testing/dr_duke_projection.jsonl');
        @unlink($out);
        $paths = [
            'FNFTAX' => $dir.'/FNFTAX.csv',
            'CHEMICALS' => $dir.'/CHEMICALS.csv',
            'ETHNOBOT' => $dir.'/ETHNOBOT.csv',
            'FARMACY' => $dir.'/FARMACY.csv',
        ];
        if (is_file($dir.'/FARMACY_NEW.csv')) {
            $paths['FARMACY_NEW'] = $dir.'/FARMACY_NEW.csv';
        }
        $report = (new DrDukeCsvRelationshipProjector)->projectSearchableJsonl($paths, $out, 500);
        $this->assertSame('completed', $report['status']);
        $this->assertGreaterThan(0, $report['records']);
        $this->assertGreaterThan(0, $report['relationships']['farmacy_fnfnum_matches']);
        $this->assertGreaterThan(0, $report['relationships']['farmacy_chem_matches']);
        $this->assertGreaterThan(0, $report['relationships']['ethnobot_taxon_name_matches']);
        $this->assertSame(0, $report['relationships']['farmacy_farmacy_new_merged']);
        $blob = (string) file_get_contents($out);
        $this->assertStringContainsString('not a clinical efficacy claim', $blob);
        $this->assertStringContainsString('"source_table":"FARMACY"', $blob);
        if (isset($paths['FARMACY_NEW'])) {
            $this->assertStringContainsString('"source_table":"FARMACY_NEW"', $blob);
            $this->assertGreaterThan(0, $report['table_counts']['FARMACY_NEW']);
        }
        @unlink($out);
    }
}
