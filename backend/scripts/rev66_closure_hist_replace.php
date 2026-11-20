<?php

/**
 * Rev 6.6 Closure — www-data read + atomic replace of a root-owned historical JSON
 * prepared by rev66_closure_seed_root_owned.php.
 */

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\Agriculture\Research\Synthesis\StableAnswerResultSet;
use App\Services\Agriculture\Research\Synthesis\StableAnswerResultSetStore;

$manifestPath = '/tmp/rev66_closure_hist_manifest.json';
$outPath = $argv[1] ?? '/tmp/rev66_closure_hist_replace.json';
$posixUser = function_exists('posix_geteuid') ? posix_geteuid() : null;

$report = [
    'revision' => '6.6-closure-hist',
    'created_at' => gmdate('c'),
    'runtime' => [
        'geteuid' => $posixUser,
        'username' => function_exists('posix_getpwuid') && $posixUser !== null
            ? (posix_getpwuid($posixUser)['name'] ?? null)
            : null,
        'is_root' => $posixUser === 0,
    ],
    'ok' => false,
    'error' => null,
];

try {
    if ($posixUser === 0) {
        throw new RuntimeException('Run as www-data.');
    }
    if (! is_file($manifestPath)) {
        throw new RuntimeException('Missing hist manifest; run seed_root_owned first.');
    }
    $m = json_decode((string) file_get_contents($manifestPath), true);
    if (! is_array($m)) {
        throw new RuntimeException('Invalid hist manifest.');
    }

    $iso = (string) $m['isolated_root'];
    $orgId = (int) $m['organization_id'];
    $id = (string) $m['result_set_id'];
    $path = (string) $m['file'];

    $statBefore = @stat($path);
    $report['stat_before'] = $statBefore ? [
        'mode_octal' => sprintf('%04o', $statBefore['mode'] & 07777),
        'uid' => $statBefore['uid'],
        'gid' => $statBefore['gid'],
    ] : null;
    $report['was_root_owned'] = ($statBefore['uid'] ?? null) === 0;

    $store = new StableAnswerResultSetStore($iso);
    $loaded = $store->get($orgId, $id);
    $report['read_ok'] = $loaded !== null
        && str_contains((string) ($loaded->page(1)['answers'][0]['answer'] ?? ''), 'historical root-owned');

    $ref = new ReflectionClass($store);
    $write = $ref->getMethod('write');
    $write->setAccessible(true);
    $replacement = new StableAnswerResultSet(
        resultSetId: $id,
        organizationId: $orgId,
        originalQuestion: 'TEST FIXTURE closure replaced over root-owned',
        csqFingerprint: 'rev66-closure-hist',
        answers: [[
            'answer' => 'TEST-DATA replaced over historical root-owned',
            'result_id' => 'hist-root-r0-replaced',
            'evidence_ids' => ['hist-e0'],
            'source_ids' => ['hist'],
            'position' => 0,
            'provenance' => ['test_fixture' => true, 'replaced' => true],
        ]],
        references: [],
        createdAt: gmdate('c'),
        expiresAt: gmdate('c', time() + 3600),
        observability: ['test_fixture' => true],
        status: 'ready',
    );
    $write->invoke($store, $replacement);

    $after = $store->get($orgId, $id);
    $statAfter = @stat($path);
    $tmpLeft = glob(dirname($path).DIRECTORY_SEPARATOR.'*.tmp.*') ?: [];
    $report['replace_ok'] = $after !== null
        && str_contains((string) ($after->page(1)['answers'][0]['answer'] ?? ''), 'replaced over historical');
    $report['stat_after'] = $statAfter ? [
        'mode_octal' => sprintf('%04o', $statAfter['mode'] & 07777),
        'uid' => $statAfter['uid'],
        'gid' => $statAfter['gid'],
    ] : null;
    $report['no_tmp_left'] = $tmpLeft === [];
    $report['unlink_cleanup_ok'] = @unlink($path);

    // Cleanup isolated tree (best-effort as www-data; root dirs may remain).
    @rmdir(dirname($path));
    @rmdir($iso);

    $report['ok'] = ($report['was_root_owned'] ?? false)
        && ($report['read_ok'] ?? false)
        && ($report['replace_ok'] ?? false)
        && ($report['no_tmp_left'] ?? false)
        && ($report['unlink_cleanup_ok'] ?? false);
} catch (Throwable $e) {
    $report['error'] = $e->getMessage();
    $report['ok'] = false;
}

file_put_contents($outPath, json_encode($report, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)."\n");
fwrite(STDOUT, json_encode([
    'ok' => $report['ok'],
    'error' => $report['error'],
    'report' => $outPath,
], JSON_UNESCAPED_SLASHES)."\n");
exit($report['ok'] ? 0 : 1);
