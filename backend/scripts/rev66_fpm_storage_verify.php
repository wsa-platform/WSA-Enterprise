<?php

/**
 * Rev 6.6 — StableAnswerResultSet storage verification under a target UID.
 *
 * Intended to run as the PHP-FPM worker user (www-data), not as root:
 *   docker exec -u www-data wsa-enterprise-backend-1 php scripts/rev66_fpm_storage_verify.php
 *
 * Creates isolated TEST-DATA only, then deletes those artifacts.
 */

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\Agriculture\Research\Synthesis\StableAnswerResultSetStore;

$outPath = $argv[1] ?? '/tmp/rev66_fpm_storage_verify.json';

$posixUser = function_exists('posix_geteuid') ? posix_geteuid() : null;
$posixName = function_exists('posix_getpwuid') && $posixUser !== null
    ? (posix_getpwuid($posixUser)['name'] ?? null)
    : null;

$root = storage_path('app/scientific_stable_results');
$orgId = 8;
$orgDir = $root.DIRECTORY_SEPARATOR.$orgId;

$report = [
    'revision' => '6.6',
    'created_at' => gmdate('c'),
    'runtime' => [
        'geteuid' => $posixUser,
        'username' => $posixName,
        'php_sapi' => PHP_SAPI,
        'is_root' => $posixUser === 0,
    ],
    'paths' => [
        'root' => $root,
        'org_dir' => $orgDir,
        'root_exists' => is_dir($root),
        'org_dir_exists' => is_dir($orgDir),
        'root_writable' => is_dir($root) && is_writable($root),
        'org_dir_writable' => is_dir($orgDir) ? is_writable($orgDir) : null,
    ],
    'stat' => [],
    'store' => [
        'create_ok' => false,
        'get_ok' => false,
        'result_set_id' => null,
        'cleanup_ok' => false,
    ],
    'ok' => false,
    'error' => null,
];

foreach ([$root, $orgDir] as $p) {
    if (! is_dir($p) && $p === $root) {
        continue;
    }
    if (! file_exists($p)) {
        continue;
    }
    $s = @stat($p);
    $report['stat'][basename($p) === 'scientific_stable_results' ? 'root' : 'org_'.$orgId] = $s ? [
        'mode_octal' => sprintf('%04o', $s['mode'] & 07777),
        'uid' => $s['uid'],
        'gid' => $s['gid'],
    ] : null;
}

try {
    if ($posixUser === 0) {
        throw new RuntimeException('Refusing to treat root execution as PHP-FPM worker proof. Re-run as www-data.');
    }

    $store = new StableAnswerResultSetStore();
    $set = $store->create(
        organizationId: $orgId,
        originalQuestion: 'TEST FIXTURE Rev 6.6 — storage permission probe',
        csqFingerprint: 'rev66-storage-probe',
        answers: [[
            'answer_id' => 'sha256:'.hash('sha256', 'rev66-probe'),
            'answer' => 'TEST-DATA rev66 storage probe answer',
            'result_id' => 'rev66-probe-r0',
            'evidence_ids' => ['rev66-probe-e0'],
            'source_ids' => ['rev66_test_fixture'],
            'position' => 0,
            'provenance' => ['test_fixture' => true, 'revision' => '6.6'],
        ]],
        references: [['title' => 'TEST REF rev66', 'test_fixture' => true]],
        observability: ['test_fixture' => true, 'revision' => '6.6'],
        status: 'ready',
        ttlSeconds: 600,
    );

    $report['store']['create_ok'] = true;
    $report['store']['result_set_id'] = $set->resultSetId;

    $loaded = $store->get($orgId, $set->resultSetId);
    $report['store']['get_ok'] = $loaded !== null
        && $loaded->resultSetId === $set->resultSetId
        && count($loaded->page(1)['answers']) === 1;

    $path = $orgDir.DIRECTORY_SEPARATOR.$set->resultSetId.'.json';
    if (is_file($path)) {
        $fs = @stat($path);
        $report['stat']['probe_file'] = $fs ? [
            'mode_octal' => sprintf('%04o', $fs['mode'] & 07777),
            'uid' => $fs['uid'],
            'gid' => $fs['gid'],
            'size' => $fs['size'],
        ] : null;
        $report['store']['cleanup_ok'] = @unlink($path);
    }

    $report['ok'] = $report['store']['create_ok']
        && $report['store']['get_ok']
        && $report['store']['cleanup_ok']
        && $report['paths']['root_writable'] === true;
} catch (Throwable $e) {
    $report['error'] = $e->getMessage();
    $report['ok'] = false;
}

$dir = dirname($outPath);
if (! is_dir($dir)) {
    mkdir($dir, 0755, true);
}
file_put_contents($outPath, json_encode($report, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)."\n");
fwrite(STDOUT, json_encode([
    'ok' => $report['ok'],
    'username' => $posixName,
    'geteuid' => $posixUser,
    'result_set_id' => $report['store']['result_set_id'],
    'error' => $report['error'],
    'report' => $outPath,
], JSON_UNESCAPED_SLASHES)."\n");

exit($report['ok'] ? 0 : 1);
