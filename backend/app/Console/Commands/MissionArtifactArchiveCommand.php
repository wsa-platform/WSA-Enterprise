<?php

namespace App\Console\Commands;

use App\Services\Architecture\MissionArtifactArchive\AdrEmbeddedExcerptImporter;
use App\Services\Architecture\MissionArtifactArchive\ControlledMissionArtifactImporter;
use App\Services\Architecture\MissionArtifactArchive\MissionArtifactArchiveFactory;
use Illuminate\Console\Command;

/**
 * CLI for durable mission-artifact archive (prompts/reports/JSON/manifests).
 * Never activates scientific sources. Never archives secrets.
 */
final class MissionArtifactArchiveCommand extends Command
{
    protected $signature = 'mission-artifacts
        {action : ensure|import|search|show|verify|export|latest-report|list-mission|import-adr-excerpts}
        {--roots=* : Import roots (absolute or relative to repo)}
        {--manifest= : Import manifest JSON path}
        {--checkpoint= : Import checkpoint JSON path}
        {--max=80 : Max files per import run}
        {--dry-run : Inventory only}
        {--query= : Search query}
        {--id= : Archive ID}
        {--mission=2026-10-17-SOURCE-EXPANSION-MISSION : Mission id}
        {--dest= : Export destination directory}
        {--name= : Export file name}';

    protected $description = 'Mission artifact archive: import/search/show/verify/export (D-08 durable layer)';

    public function handle(): int
    {
        $action = strtolower(trim((string) $this->argument('action')));
        $archive = MissionArtifactArchiveFactory::default();
        $archive->ensureSchema();

        return match ($action) {
            'ensure' => $this->ensure($archive),
            'import' => $this->import($archive),
            'search' => $this->search($archive),
            'show' => $this->show($archive),
            'verify' => $this->verify($archive),
            'export' => $this->export($archive),
            'latest-report' => $this->latestReport($archive),
            'list-mission' => $this->listMission($archive),
            'import-adr-excerpts' => $this->importAdrExcerpts($archive),
            default => $this->failUnknown($action),
        };
    }

    private function importAdrExcerpts(\App\Services\Architecture\MissionArtifactArchive\FilesystemMissionArtifactArchive $archive): int
    {
        $adr = base_path('../docs/architecture/ADR-023-scientific-source-expansion.md');
        if (! is_file($adr)) {
            $adr = dirname(base_path()).DIRECTORY_SEPARATOR.'docs'.DIRECTORY_SEPARATOR.'architecture'.DIRECTORY_SEPARATOR.'ADR-023-scientific-source-expansion.md';
        }
        $report = (new AdrEmbeddedExcerptImporter($archive))->importAdr($adr, max(1, min(80, (int) $this->option('max'))));
        $this->line(json_encode($report, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return ($report['status'] ?? '') === 'completed' ? self::SUCCESS : self::FAILURE;
    }

    private function ensure(\App\Services\Architecture\MissionArtifactArchive\FilesystemMissionArtifactArchive $archive): int
    {
        $this->line(json_encode([
            'status' => 'ok',
            'sqlite' => $archive->sqlitePath(),
            'blob_root' => $archive->blobRoot(),
            'count' => $archive->countAll(),
        ], JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }

    private function import(\App\Services\Architecture\MissionArtifactArchive\FilesystemMissionArtifactArchive $archive): int
    {
        $roots = $this->option('roots');
        if (! is_array($roots) || $roots === []) {
            $roots = [
                base_path('../docs/architecture/science-source-expansion/2026-10-17-SOURCE-EXPANSION-MISSION'),
            ];
        }
        $absRoots = [];
        foreach ($roots as $root) {
            $p = (string) $root;
            if (! preg_match('#^[A-Za-z]:[\\\\/]|^/#', $p)) {
                $p = base_path($p);
            }
            // Prefer repo docs path when relative from backend
            if (! is_dir($p)) {
                $alt = dirname(base_path()).DIRECTORY_SEPARATOR.ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, (string) $root), DIRECTORY_SEPARATOR);
                if (is_dir($alt)) {
                    $p = $alt;
                }
            }
            if (is_dir($p)) {
                $absRoots[] = $p;
            }
        }
        $manifest = (string) ($this->option('manifest') ?: storage_path('app/mission_artifact_archive/import_manifest.v1.json'));
        $checkpoint = (string) ($this->option('checkpoint') ?: storage_path('app/mission_artifact_archive/import_checkpoint.v1.json'));
        $report = (new ControlledMissionArtifactImporter($archive))->inventoryAndImport(
            $absRoots,
            $manifest,
            $checkpoint,
            max(1, min(500, (int) $this->option('max'))),
            (bool) $this->option('dry-run'),
        );
        $this->line(json_encode([
            'status' => 'ok',
            'discovered' => $report['discovered'] ?? null,
            'eligible' => $report['eligible'] ?? null,
            'totals' => $report['totals'] ?? null,
            'completed' => $report['completed'] ?? null,
            'pending_eligible' => $report['pending_eligible'] ?? null,
            'aho_discovered' => $report['aho_discovered'] ?? 0,
            'manifest' => $manifest,
            'checkpoint' => $checkpoint,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }

    private function search(\App\Services\Architecture\MissionArtifactArchive\FilesystemMissionArtifactArchive $archive): int
    {
        $q = (string) $this->option('query');
        $rows = $archive->search($q, 20);
        $this->line(json_encode([
            'count' => count($rows),
            'results' => array_map(static fn ($r) => [
                'archive_id' => $r->archiveId,
                'title' => $r->title,
                'artifact_type' => $r->artifactType,
                'mission_id' => $r->missionId,
                'sha256' => $r->contentSha256,
                'is_superseded' => $r->isSuperseded,
            ], $rows),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }

    private function show(\App\Services\Architecture\MissionArtifactArchive\FilesystemMissionArtifactArchive $archive): int
    {
        $id = (string) $this->option('id');
        $rec = $archive->get($id);
        if ($rec === null) {
            $this->error('not_found');

            return self::FAILURE;
        }
        $this->line(json_encode($rec->toArray(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }

    private function verify(\App\Services\Architecture\MissionArtifactArchive\FilesystemMissionArtifactArchive $archive): int
    {
        $id = (string) $this->option('id');
        $this->line(json_encode($archive->verifyIntegrity($id), JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }

    private function export(\App\Services\Architecture\MissionArtifactArchive\FilesystemMissionArtifactArchive $archive): int
    {
        $id = (string) $this->option('id');
        $dest = (string) $this->option('dest');
        $name = (string) ($this->option('name') ?: 'artifact.bin');
        $this->line(json_encode($archive->exportOriginal($id, $dest, $name), JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }

    private function latestReport(\App\Services\Architecture\MissionArtifactArchive\FilesystemMissionArtifactArchive $archive): int
    {
        $mission = (string) $this->option('mission');
        $rec = $archive->latestByType($mission, 'engineering_report');
        $this->line(json_encode($rec?->toArray(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return $rec !== null ? self::SUCCESS : self::FAILURE;
    }

    private function listMission(\App\Services\Architecture\MissionArtifactArchive\FilesystemMissionArtifactArchive $archive): int
    {
        $mission = (string) $this->option('mission');
        $rows = $archive->listByMission($mission);
        $this->line(json_encode([
            'mission_id' => $mission,
            'count' => count($rows),
            'titles' => array_map(static fn ($r) => [
                'archive_id' => $r->archiveId,
                'title' => $r->title,
                'type' => $r->artifactType,
            ], $rows),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }

    private function failUnknown(string $action): int
    {
        $this->error("Unknown action '{$action}'");

        return self::FAILURE;
    }
}
