<?php

namespace Tests\Unit\Architecture\MissionArtifactArchive;

use App\Services\Architecture\MissionArtifactArchive\ControlledMissionArtifactImporter;
use App\Services\Architecture\MissionArtifactArchive\FilesystemMissionArtifactArchive;
use App\Services\Architecture\MissionArtifactArchive\MissionArtifactArchiveFactory;
use App\Services\Architecture\MissionArtifactArchive\MissionArtifactSecretFilter;
use Tests\TestCase;

class MissionArtifactArchiveTest extends TestCase
{
    private string $base;

    protected function setUp(): void
    {
        parent::setUp();
        $this->base = storage_path('framework/testing/mission_artifact_archive_'.uniqid('', true));
        @mkdir($this->base, 0775, true);
    }

    protected function tearDown(): void
    {
        $this->rrmdir($this->base);
        parent::tearDown();
    }

    public function test_stable_id_exact_bytes_sha256_and_idempotent_ingest(): void
    {
        $archive = MissionArtifactArchiveFactory::forTesting($this->base);
        $src = $this->base.DIRECTORY_SEPARATOR.'prompt.txt';
        $body = "MASTER PROMPT\nexact bytes — لا تُعدّل\n";
        file_put_contents($src, $body);
        $r1 = $archive->ingestFile($src, 'test:prompt:v1', 'unit', 'mission-test');
        $this->assertSame('archived', $r1['status']);
        $id = $r1['archive_id'];
        $this->assertNotNull($id);
        $r2 = $archive->ingestFile($src, 'test:prompt:v1', 'unit', 'mission-test');
        $this->assertSame('unchanged', $r2['status']);
        $this->assertSame($id, $r2['archive_id']);
        $bytes = $archive->readOriginalBytes((string) $id);
        $this->assertSame($body, $bytes);
        $v = $archive->verifyIntegrity((string) $id);
        $this->assertTrue($v['ok']);
        $this->assertSame(hash('sha256', $body), $v['expected']);
    }

    public function test_tampering_detected_and_version_supersession_without_delete(): void
    {
        $archive = MissionArtifactArchiveFactory::forTesting($this->base);
        $a = $archive->ingestBytes("v1\n", 'doc:v1', 'report.md', 'engineering_report', 'markdown', 'unit', null, 'mission-test');
        $b = $archive->ingestBytes("v2\n", 'doc:v2', 'report.md', 'engineering_report', 'markdown', 'unit', null, 'mission-test', $a['archive_id']);
        $this->assertSame('archived', $b['status']);
        $old = $archive->get((string) $a['archive_id']);
        $this->assertTrue($old->isSuperseded);
        $this->assertNotNull($archive->readOriginalBytes((string) $a['archive_id']));
        $latest = $archive->latestByType('mission-test', 'engineering_report');
        $this->assertSame($b['archive_id'], $latest?->archiveId);

        // Tamper blob
        $blob = $archive->blobRoot().DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $old->blobRelativePath);
        file_put_contents($blob, "tampered\n");
        $v = $archive->verifyIntegrity((string) $a['archive_id']);
        $this->assertTrue($v['tampered']);
        $this->assertFalse($v['ok']);
    }

    public function test_identical_content_distinct_provenance_and_secret_exclusion(): void
    {
        $archive = MissionArtifactArchiveFactory::forTesting($this->base);
        $body = "{\"schema_id\":\"x\"}";
        $a = $archive->ingestBytes($body, 'prov:a', 'a.json', 'json_artifact', 'json', 'unit');
        $b = $archive->ingestBytes($body, 'prov:b', 'b.json', 'json_artifact', 'json', 'unit');
        $this->assertNotSame($a['archive_id'], $b['archive_id']);
        $this->assertSame($a['record']->contentSha256, $b['record']->contentSha256);

        $env = $this->base.DIRECTORY_SEPARATOR.'.env';
        file_put_contents($env, "BHL_API_KEY=should-never-archive\n");
        $ex = $archive->ingestFile($env, 'secret:env', 'unit');
        $this->assertSame('excluded_secret', $ex['status']);
        $this->assertNull($ex['archive_id']);
        $filter = new MissionArtifactSecretFilter();
        $this->assertTrue($filter->isExcluded($env));
    }

    public function test_json_malformed_retained_and_export_blocks_traversal(): void
    {
        $archive = MissionArtifactArchiveFactory::forTesting($this->base);
        $bad = '{not-json';
        $r = $archive->ingestBytes($bad, 'json:bad', 'bad.json', 'json_artifact', 'json', 'unit');
        $rec = $archive->get((string) $r['archive_id']);
        $this->assertFalse($rec->extractedMetadata['json_valid']);
        $this->assertSame($bad, $archive->readOriginalBytes((string) $r['archive_id']));

        $dest = $this->base.DIRECTORY_SEPARATOR.'export';
        mkdir($dest);
        $ok = $archive->exportOriginal((string) $r['archive_id'], $dest, 'out.json');
        $this->assertSame('exported', $ok['status']);
        $badName = $archive->exportOriginal((string) $r['archive_id'], $dest, '..\\evil.json');
        // basename strips traversal → still safe under dest
        $this->assertSame('exported', $badName['status']);
        $this->assertStringContainsString(realpath($dest), (string) realpath($badName['path']));
    }

    public function test_durability_across_new_archive_instance_and_search(): void
    {
        $archive = MissionArtifactArchiveFactory::forTesting($this->base);
        $archive->ingestBytes("stop point Q1\n", 'stop:1', 'stop.md', 'checkpoint_or_manifest', 'markdown', 'unit', null, 'mission-test');
        $reopened = new FilesystemMissionArtifactArchive(
            $this->base.DIRECTORY_SEPARATOR.'archive.sqlite',
            $this->base.DIRECTORY_SEPARATOR.'blobs',
        );
        $hits = $reopened->search('stop point', 5);
        $this->assertNotEmpty($hits);
        $this->assertSame('mission-test', $hits[0]->missionId);
    }

    public function test_controlled_importer_dry_run_and_aho_unsupported_note(): void
    {
        $archive = MissionArtifactArchiveFactory::forTesting($this->base);
        $root = $this->base.DIRECTORY_SEPARATOR.'docs';
        mkdir($root);
        file_put_contents($root.DIRECTORY_SEPARATOR.'2026-10-18-sample-report.md', "# report\n");
        file_put_contents($root.DIRECTORY_SEPARATOR.'sample.json', "{\"schema_id\":\"wsa.test\"}");
        $manifest = $this->base.DIRECTORY_SEPARATOR.'manifest.json';
        $checkpoint = $this->base.DIRECTORY_SEPARATOR.'checkpoint.json';
        $report = (new ControlledMissionArtifactImporter($archive))->inventoryAndImport(
            [$root],
            $manifest,
            $checkpoint,
            10,
            true,
        );
        $this->assertSame(2, $report['discovered']);
        $this->assertSame(0, $report['aho_discovered']);
        $this->assertFalse($report['aho_schema_supported']);
        $live = (new ControlledMissionArtifactImporter($archive))->inventoryAndImport(
            [$root],
            $manifest,
            $checkpoint,
            10,
            false,
        );
        $this->assertGreaterThanOrEqual(2, $live['totals']['archived'] + $live['totals']['unchanged']);
    }

    public function test_aho_format_retained_without_invented_schema(): void
    {
        $archive = MissionArtifactArchiveFactory::forTesting($this->base);
        $bytes = "AHO-UNKNOWN-FORMAT\n";
        $r = $archive->ingestBytes($bytes, 'aho:1', 'sample.aho', 'aho_artifact', 'aho', 'unit');
        $rec = $archive->get((string) $r['archive_id']);
        $this->assertSame('aho', $rec->format);
        $this->assertSame('retained_original_only_schema_unknown', $rec->extractedMetadata['aho_extraction']);
        $this->assertSame($bytes, $archive->readOriginalBytes((string) $r['archive_id']));
    }

    public function test_find_by_original_path_prevents_duplicate_on_identity_scheme_change(): void
    {
        $archive = MissionArtifactArchiveFactory::forTesting($this->base);
        $src = $this->base.DIRECTORY_SEPARATOR.'hist.md';
        file_put_contents($src, "historical prompt body\n");
        $first = $archive->ingestFile($src, 'old-scheme:hist.md|sha256:'.hash_file('sha256', $src), 'unit', 'mission-test');
        $this->assertSame('archived', $first['status']);
        $found = $archive->findByOriginalPath($src);
        $this->assertNotNull($found);
        $this->assertSame($first['archive_id'], $found->archiveId);
    }

    public function test_importer_rescans_from_zero_so_mid_list_new_files_are_not_skipped(): void
    {
        $archive = MissionArtifactArchiveFactory::forTesting($this->base);
        $root = $this->base.DIRECTORY_SEPARATOR.'mission_docs';
        mkdir($root);
        file_put_contents($root.DIRECTORY_SEPARATOR.'z-late.md', "late\n");
        $manifest = $this->base.DIRECTORY_SEPARATOR.'manifest2.json';
        $checkpoint = $this->base.DIRECTORY_SEPARATOR.'checkpoint2.json';
        $first = (new ControlledMissionArtifactImporter($archive))->inventoryAndImport(
            [$root],
            $manifest,
            $checkpoint,
            10,
            false,
        );
        $this->assertSame(1, $first['totals']['archived']);
        // Simulate a prior tip: cursor past the only file, then add a file that sorts earlier.
        file_put_contents($checkpoint, json_encode([
            'completed_paths' => [$root.DIRECTORY_SEPARATOR.'z-late.md'],
            'cursor' => 99,
        ], JSON_THROW_ON_ERROR));
        file_put_contents($root.DIRECTORY_SEPARATOR.'a-early-new.md', "new early decision\n");
        $second = (new ControlledMissionArtifactImporter($archive))->inventoryAndImport(
            [$root],
            $manifest,
            $checkpoint,
            10,
            false,
        );
        $this->assertSame(1, $second['totals']['archived'], json_encode($second));
        $this->assertGreaterThanOrEqual(2, $archive->countAll());
    }

    private function rrmdir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir.DIRECTORY_SEPARATOR.$item;
            if (is_dir($path)) {
                $this->rrmdir($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}
