<?php

/**
 * Rev 10.7.1 mission-artifact ingest + verify (FilesystemMissionArtifactArchive).
 */

declare(strict_types=1);

use App\Services\Architecture\MissionArtifactArchive\MissionArtifactArchiveFactory;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$repoRoot = dirname(__DIR__);
$docsRoot = dirname($repoRoot).DIRECTORY_SEPARATOR.'docs';
if (! is_dir($docsRoot)) {
    $docsRoot = $repoRoot.DIRECTORY_SEPARATOR.'docs';
}
$sse = $docsRoot.DIRECTORY_SEPARATOR.'architecture'.DIRECTORY_SEPARATOR.'science-source-expansion';

$missionId = '2026-11-11-PHASE-1-REV10.7.1';
$archive = MissionArtifactArchiveFactory::default();
$archive->ensureSchema();

$results = [
    'sqlite' => $archive->sqlitePath(),
    'blob_root' => $archive->blobRoot(),
    'count_before' => $archive->countAll(),
    'ingests' => [],
];

$targets = [
    ['file' => '2026-11-11-PHASE-1-EXIT-09-POSTGRESQL-TEST-REPORT.v10.7.1.json', 'id' => 'exit09-report:v10.7.1', 'label' => 'exit09_pg_report_v1071'],
    ['file' => '2026-11-11-PHASE-1-FINAL-ACCEPTANCE-MATRIX.v10.7.1.json', 'id' => 'acceptance-matrix:v10.7.1', 'label' => 'acceptance_matrix_v1071'],
    ['file' => '2026-11-11-PHASE-1-DISCREPANCY-REGISTER.v10.7.1.json', 'id' => 'discrepancy-register:v10.7.1', 'label' => 'discrepancy_register_v1071'],
    ['file' => '2026-11-11-PHASE-1-REV10.7-EXTERNAL-BLOCKERS.v10.7.1.json', 'id' => 'external-blockers:v10.7.1', 'label' => 'external_blockers_v1071'],
    ['file' => '2026-11-11-PHASE-1-TIER1-ARCHIVAL-INVENTORY.v10.7.1.json', 'id' => 'tier1-inventory:v10.7.1', 'label' => 'tier1_inventory_v1071'],
    ['file' => '2026-11-11-PHASE-1-PROJECT-STATE-SNAPSHOT.v10.7.1.json', 'id' => 'project-state-snapshot:v10.7.1', 'label' => 'project_state_snapshot_v1071'],
    ['file' => '2026-11-11-PHASE-1-REV10.7.1-DOCUMENTATION-GIT-RECONCILIATION.json', 'id' => 'docs-git-reconciliation:v10.7.1', 'label' => 'docs_git_reconciliation_v1071'],
    ['file' => '2026-11-11-PHASE-1-REV10.7.1-SESSION-REPORT.ar.md', 'id' => 'session-report-ar:v10.7.1', 'label' => 'session_report_ar_v1071'],
];

foreach ($targets as $t) {
    $path = $sse.DIRECTORY_SEPARATOR.$t['file'];
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
        'mission:'.$missionId.':'.$t['id'],
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
        $integrity = $archive->verifyIntegrity((string) $ingest['archive_id']);
        $entry['integrity'] = $integrity;
        $entry['verification'] = (($entry['hash_match'] ?? false) && ($integrity['ok'] ?? false))
            ? 'ARCHIVED_AND_VERIFIED'
            : 'ARCHIVE_VERIFICATION_FAILED';
    } else {
        $entry['verification'] = 'ARCHIVE_VERIFICATION_FAILED';
    }
    $results['ingests'][] = $entry;
}

$results['count_after'] = $archive->countAll();
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
