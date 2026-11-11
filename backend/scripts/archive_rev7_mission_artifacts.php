<?php

/**
 * One-shot Rev7 mission-artifact ingest + verify (FilesystemMissionArtifactArchive).
 * Uses MissionArtifactArchiveFactory::default() — distinct from protected CapVer/LocalIndex DBs.
 */

declare(strict_types=1);

use App\Services\Architecture\MissionArtifactArchive\MissionArtifactArchiveFactory;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$repoRoot = dirname(__DIR__, 1);
$docsRoot = dirname($repoRoot).DIRECTORY_SEPARATOR.'docs';
if (! is_dir($docsRoot)) {
    $docsRoot = $repoRoot.DIRECTORY_SEPARATOR.'docs';
}

$promptPath = $docsRoot.DIRECTORY_SEPARATOR.'architecture'.DIRECTORY_SEPARATOR.'science-source-expansion'.DIRECTORY_SEPARATOR.'2026-11-11-MASTER-ENGINEERING-PROMPT-REVISION-7.md';
$snapshotPath = $docsRoot.DIRECTORY_SEPARATOR.'architecture'.DIRECTORY_SEPARATOR.'science-source-expansion'.DIRECTORY_SEPARATOR.'2026-11-11-PHASE-1-PROJECT-STATE-SNAPSHOT.v7.json';
$progressPath = $docsRoot.DIRECTORY_SEPARATOR.'phase-1-progress.json';
$reportPath = $docsRoot.DIRECTORY_SEPARATOR.'architecture'.DIRECTORY_SEPARATOR.'science-source-expansion'.DIRECTORY_SEPARATOR.'2026-11-11-PHASE-1-REV4-GAP-CLOSURE-ENGINEERING-REPORT.ar.md';

$missionId = '2026-11-11-PHASE-1-REV7';
$archive = MissionArtifactArchiveFactory::default();
$archive->ensureSchema();

$results = [
    'sqlite' => $archive->sqlitePath(),
    'blob_root' => $archive->blobRoot(),
    'count_before' => $archive->countAll(),
    'ingests' => [],
];

$targets = [
    [
        'path' => $promptPath,
        'identity' => 'mission:'.$missionId.':master-prompt:revision-7:v1',
        'label' => 'master_prompt_rev7',
    ],
    [
        'path' => $snapshotPath,
        'identity' => 'mission:'.$missionId.':project-state-snapshot:v7',
        'label' => 'project_state_snapshot_v7',
    ],
    [
        'path' => $progressPath,
        'identity' => 'mission:'.$missionId.':phase-1-progress:v1',
        'label' => 'phase_1_progress_json',
    ],
    [
        'path' => $reportPath,
        'identity' => 'mission:'.$missionId.':gap-closure-report-rev4-ar:v1',
        'label' => 'gap_closure_report_ar',
    ],
];

foreach ($targets as $t) {
    $path = $t['path'];
    $entry = [
        'label' => $t['label'],
        'path' => $path,
        'exists' => is_file($path),
    ];
    if (! is_file($path)) {
        $entry['status'] = 'missing_source';
        $results['ingests'][] = $entry;
        continue;
    }
    $bytes = file_get_contents($path);
    $sha = hash('sha256', $bytes === false ? '' : $bytes);
    $entry['source_sha256'] = $sha;
    $ingest = $archive->ingestFile(
        $path,
        $t['identity'],
        'controlled_import',
        $missionId,
        null,
        [],
        $t['label'],
    );
    $entry['ingest_status'] = $ingest['status'] ?? null;
    $entry['archive_id'] = $ingest['archive_id'] ?? null;
    if (! empty($ingest['archive_id'])) {
        $rec = $archive->get((string) $ingest['archive_id']);
        $entry['read_back'] = $rec !== null;
        $entry['stored_sha256'] = $rec?->contentSha256;
        $entry['hash_match'] = $rec !== null && hash_equals($sha, $rec->contentSha256);
        $entry['verification'] = ($entry['hash_match'] ?? false) ? 'ARCHIVED_AND_VERIFIED' : 'ARCHIVE_VERIFICATION_FAILED';
    } else {
        $entry['verification'] = 'ARCHIVE_VERIFICATION_FAILED';
    }
    $results['ingests'][] = $entry;
}

$results['count_after'] = $archive->countAll();
$results['mission_list_count'] = count($archive->listByMission($missionId));

echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), PHP_EOL;
