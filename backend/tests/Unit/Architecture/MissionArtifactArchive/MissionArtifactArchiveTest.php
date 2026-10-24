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
        $this->assertSame($bad, file_get_contents($dest.DIRECTORY_SEPARATOR.'out.json'));
        $badName = $archive->exportOriginal((string) $r['archive_id'], $dest, '..\\evil.json');
        $this->assertSame('failed', $badName['status']);
        $this->assertSame('unsafe_name', $badName['reason']);
        $this->assertFileDoesNotExist($dest.DIRECTORY_SEPARATOR.'evil.json');
        $this->assertFileDoesNotExist(dirname($dest).DIRECTORY_SEPARATOR.'evil.json');
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

    public function test_empty_and_missing_paths_do_not_ingest(): void
    {
        $archive = MissionArtifactArchiveFactory::forTesting($this->base);
        $empty = $archive->ingestFile('', 'test:empty', 'unit', 'mission-test');
        $this->assertSame('failed', $empty['status']);
        $this->assertNull($empty['archive_id']);
        $this->assertSame('unreadable', $empty['reason']);

        $missing = $archive->ingestFile($this->base.DIRECTORY_SEPARATOR.'no-such-file.txt', 'test:missing', 'unit', 'mission-test');
        $this->assertSame('failed', $missing['status']);
        $this->assertSame('unreadable', $missing['reason']);
        $this->assertSame(0, $archive->countAll());

        $destMissing = $archive->exportOriginal('missing-id', $this->base.DIRECTORY_SEPARATOR.'absent-dest', 'ok.txt');
        $this->assertSame('not_found', $destMissing['status']);
    }

    public function test_export_accepts_plain_names_and_rejects_path_segments(): void
    {
        $archive = MissionArtifactArchiveFactory::forTesting($this->base);
        $bytes = "export-name-contract\n";
        $stored = $archive->ingestBytes($bytes, 'export:names', 'note.txt', 'text_artifact', 'text', 'unit');
        $this->assertSame('archived', $stored['status']);
        $id = (string) $stored['archive_id'];
        $dest = $this->base.DIRECTORY_SEPARATOR.'export-names';
        mkdir($dest);

        foreach (['file.txt', 'valid-name.txt'] as $name) {
            $exported = $archive->exportOriginal($id, $dest, $name);
            $this->assertSame('exported', $exported['status'], $name);
            $this->assertSame($bytes, file_get_contents($dest.DIRECTORY_SEPARATOR.$name));
            $this->assertSame(realpath($dest), realpath(dirname((string) $exported['path'])));
        }

        foreach (['', '.', '..', 'sub/file.txt', 'sub\\file.txt', '../etc/passwd', '..\\..\\outside.txt', 'nul', 'aux.txt', 'file.txt:', "space.txt "] as $name) {
            $rejected = $archive->exportOriginal($id, $dest, $name);
            $this->assertSame('failed', $rejected['status'], $name);
            $this->assertSame('unsafe_name', $rejected['reason'], $name);
        }

        $this->assertFileDoesNotExist($this->base.DIRECTORY_SEPARATOR.'outside.txt');
        $this->assertFileDoesNotExist($this->base.DIRECTORY_SEPARATOR.'passwd');
    }

    public function test_blob_directory_failure_leaves_no_archive_row(): void
    {
        $archive = MissionArtifactArchiveFactory::forTesting($this->base);
        $bytes = 'write-fail-body';
        $sha = hash('sha256', $bytes);
        if (! mkdir($archive->blobRoot(), 0775, true) && ! is_dir($archive->blobRoot())) {
            $this->fail('Could not create the isolated blob root.');
        }
        $blocker = $archive->blobRoot().DIRECTORY_SEPARATOR.substr($sha, 0, 2);
        file_put_contents($blocker, 'not-a-directory');

        $failed = $archive->ingestBytes($bytes, 'write:fail', 'blocked.txt', 'text_artifact', 'text', 'unit');
        $this->assertSame('failed', $failed['status']);
        $this->assertNull($failed['archive_id']);
        $this->assertSame('blob_dir', $failed['reason']);
        $this->assertSame(0, $archive->countAll());
        $this->assertFileDoesNotExist($blocker.'.partial');
    }

    public function test_poisoned_blob_path_is_not_read(): void
    {
        $archive = MissionArtifactArchiveFactory::forTesting($this->base);
        $outside = $this->base.DIRECTORY_SEPARATOR.'outside-secret.bin';
        file_put_contents($outside, 'SECRET');
        $stored = $archive->ingestBytes('hello', 'poison:1', 'hello.txt', 'text_artifact', 'text', 'unit');
        $this->assertSame('archived', $stored['status']);
        $id = (string) $stored['archive_id'];
        $this->assertSame('hello', $archive->readOriginalBytes($id));

        $pdo = new \PDO('sqlite:'.$archive->sqlitePath());
        $updated = $pdo->prepare('UPDATE mission_artifacts SET blob_relative_path = ? WHERE archive_id = ?');
        $updated->execute(['../outside-secret.bin', $id]);

        $this->assertNull($archive->readOriginalBytes($id));
        $integrity = $archive->verifyIntegrity($id);
        $this->assertFalse($integrity['ok']);
        $this->assertSame('SECRET', file_get_contents($outside));
    }

    public function test_export_refuses_a_symlink_that_points_outside_the_destination(): void
    {
        $archive = MissionArtifactArchiveFactory::forTesting($this->base);
        $stored = $archive->ingestBytes("symlink-guard\n", 'export:symlink', 'note.txt', 'text_artifact', 'text', 'unit');
        $this->assertSame('archived', $stored['status']);
        $dest = $this->base.DIRECTORY_SEPARATOR.'export-link';
        mkdir($dest);
        $outside = $this->base.DIRECTORY_SEPARATOR.'symlink-target.bin';
        file_put_contents($outside, 'SECRET');
        $link = $dest.DIRECTORY_SEPARATOR.'linked.txt';
        if (! $this->createFileSymlink($outside, $link)) {
            $this->fail('The environment did not create a file symlink, so the export symlink branch was not executed.');
        }

        $result = $archive->exportOriginal((string) $stored['archive_id'], $dest, 'linked.txt');

        $this->assertSame('failed', $result['status']);
        $this->assertSame('unsafe_name', $result['reason']);
        $this->assertSame('SECRET', file_get_contents($outside));
        $this->assertFalse(is_file($dest.DIRECTORY_SEPARATOR.'linked.txt') && ! is_link($dest.DIRECTORY_SEPARATOR.'linked.txt'));
    }

    public function test_blob_symlink_outside_the_root_is_not_read(): void
    {
        $archive = MissionArtifactArchiveFactory::forTesting($this->base);
        $outside = $this->base.DIRECTORY_SEPARATOR.'blob-outside.bin';
        file_put_contents($outside, 'SECRET');
        $stored = $archive->ingestBytes('hello', 'blob-link:outside', 'hello.txt', 'text_artifact', 'text', 'unit');
        $this->assertSame('archived', $stored['status']);
        $id = (string) $stored['archive_id'];
        $blob = $this->blobAbsolutePath($archive, $id);
        $this->assertTrue(unlink($blob));
        $this->assertTrue($this->createFileSymlink($outside, $blob));

        $this->assertNull($archive->readOriginalBytes($id));
        $integrity = $archive->verifyIntegrity($id);
        $this->assertFalse($integrity['ok']);
        $this->assertNull($integrity['actual']);
        $this->assertSame('SECRET', file_get_contents($outside));
    }

    public function test_blob_symlink_inside_the_root_is_not_followed(): void
    {
        $archive = MissionArtifactArchiveFactory::forTesting($this->base);
        $stored = $archive->ingestBytes('hello', 'blob-link:inside', 'hello.txt', 'text_artifact', 'text', 'unit');
        $this->assertSame('archived', $stored['status']);
        $id = (string) $stored['archive_id'];
        $blob = $this->blobAbsolutePath($archive, $id);
        $inside = $archive->blobRoot().DIRECTORY_SEPARATOR.'inside-regular.bin';
        file_put_contents($inside, 'INSIDE');
        $this->assertTrue(unlink($blob));
        $this->assertTrue($this->createFileSymlink($inside, $blob));

        $this->assertNull($archive->readOriginalBytes($id));
        $this->assertSame('INSIDE', file_get_contents($inside));
    }

    public function test_broken_blob_symlink_is_not_read(): void
    {
        $archive = MissionArtifactArchiveFactory::forTesting($this->base);
        $stored = $archive->ingestBytes('hello', 'blob-link:broken', 'hello.txt', 'text_artifact', 'text', 'unit');
        $this->assertSame('archived', $stored['status']);
        $id = (string) $stored['archive_id'];
        $blob = $this->blobAbsolutePath($archive, $id);
        $missing = $this->base.DIRECTORY_SEPARATOR.'missing-blob-target.bin';
        $this->assertTrue(unlink($blob));
        $this->assertTrue($this->createFileSymlink($missing, $blob));

        $this->assertNull($archive->readOriginalBytes($id));
        $this->assertFalse($archive->verifyIntegrity($id)['ok']);
    }

    private function blobAbsolutePath(FilesystemMissionArtifactArchive $archive, string $archiveId): string
    {
        $record = $archive->get($archiveId);
        $this->assertNotNull($record);

        return $archive->blobRoot().DIRECTORY_SEPARATOR.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $record->blobRelativePath);
    }

    private function createFileSymlink(string $target, string $link): bool
    {
        if (@symlink($target, $link) && is_link($link)) {
            return true;
        }
        if (is_link($link)) {
            return true;
        }
        if (PHP_OS_FAMILY !== 'Windows') {
            return false;
        }

        $command = 'cmd /c mklink '.escapeshellarg($link).' '.escapeshellarg($target);
        exec($command, $output, $code);

        return $code === 0 && is_link($link);
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
            if (is_link($path)) {
                @unlink($path);
            } elseif (is_dir($path)) {
                $this->rrmdir($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}
