<?php

/**
 * Rev 6.6 — isolated directory recreation test.
 *
 * Uses a temporary storage root under storage/app/e2e-tmp (NOT the live
 * scientific_stable_results tree). Exercises StableAnswerResultSetStore after
 * the directory is removed and recreated with restrictive ownership.
 *
 * Usage:
 *   docker exec -u www-data wsa-enterprise-backend-1 php scripts/rev66_isolated_dir_recreate_verify.php
 */

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\Agriculture\Research\Synthesis\StableAnswerResultSetStore;

$outPath = $argv[1] ?? '/tmp/rev66_isolated_dir_recreate.json';
// Prefer Laravel framework temp (known writable by www-data on the bind mount).
$isolatedRoot = storage_path('framework/cache/rev66_iso_'.getmypid().'_'.bin2hex(random_bytes(3)));

$posixUser = function_exists('posix_geteuid') ? posix_geteuid() : null;
$posixName = function_exists('posix_getpwuid') && $posixUser !== null
    ? (posix_getpwuid($posixUser)['name'] ?? null)
    : null;

$rmTree = static function (string $dir) use (&$rmTree): void {
    if (! is_dir($dir)) {
        return;
    }
    $entries = @scandir($dir);
    if ($entries === false) {
        throw new RuntimeException('Unable to scan directory for cleanup: '.$dir);
    }
    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        $path = $dir.DIRECTORY_SEPARATOR.$entry;
        if (is_dir($path)) {
            $rmTree($path);
        } elseif (! @unlink($path)) {
            throw new RuntimeException('Unable to delete file during cleanup: '.$path);
        }
    }
    if (! @rmdir($dir)) {
        throw new RuntimeException('Unable to remove directory during cleanup: '.$dir);
    }
};

$report = [
    'revision' => '6.6',
    'created_at' => gmdate('c'),
    'runtime' => [
        'geteuid' => $posixUser,
        'username' => $posixName,
        'is_root' => $posixUser === 0,
    ],
    'isolated_root' => $isolatedRoot,
    'live_root_untouched' => true,
    'steps' => [],
    'ok' => false,
    'error' => null,
];

$liveRoot = storage_path('app/scientific_stable_results');
$liveMarker = is_dir($liveRoot) ? (count(scandir($liveRoot)) ?: 0) : -1;

try {
    if ($posixUser === 0) {
        throw new RuntimeException('Refusing root execution for isolated www-data write proof.');
    }

    // Do not chmod(2775) as www-data on Docker Desktop bind mounts — host
    // permission emulation can clear owner execute and make the dir unlistable.
    // Entrypoint (root) applies 2775 on the live scientific_stable_results path.
    if (is_dir($isolatedRoot)) {
        $rmTree($isolatedRoot);
        $report['steps'][] = 'removed_prior_isolated_root';
    }

    if (! mkdir($isolatedRoot, 0775, true) && ! is_dir($isolatedRoot)) {
        throw new RuntimeException('Unable to create isolated root.');
    }
    $report['steps'][] = 'recreated_isolated_root_0775';

    $store = new StableAnswerResultSetStore($isolatedRoot);
    $set = $store->create(
        organizationId: 8,
        originalQuestion: 'TEST FIXTURE Rev 6.6 isolated recreate',
        csqFingerprint: 'rev66-isolated',
        answers: [[
            'answer' => 'TEST-DATA isolated answer 0',
            'result_id' => 'rev66-iso-r0',
            'evidence_ids' => ['rev66-iso-e0'],
            'source_ids' => ['rev66_iso'],
            'position' => 0,
            'provenance' => ['test_fixture' => true, 'revision' => '6.6'],
        ]],
    );
    $report['steps'][] = 'store_create';
    $loaded = $store->get(8, $set->resultSetId);
    if ($loaded === null) {
        throw new RuntimeException('Store get failed after isolated recreate.');
    }
    $report['steps'][] = 'store_get';
    $report['result_set_id'] = $set->resultSetId;

    $liveMarkerAfter = is_dir($liveRoot) ? (count(scandir($liveRoot)) ?: 0) : -1;
    $report['live_root_untouched'] = $liveMarker === $liveMarkerAfter;
    if (! $report['live_root_untouched']) {
        throw new RuntimeException('Live scientific_stable_results directory listing changed unexpectedly.');
    }

    $rmTree($isolatedRoot);
    $report['steps'][] = 'cleaned_isolated_root';
    $report['ok'] = true;
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
    'error' => $report['error'],
    'report' => $outPath,
], JSON_UNESCAPED_SLASHES)."\n");

exit($report['ok'] ? 0 : 1);
