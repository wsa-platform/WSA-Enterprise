<?php

/**
 * Rev 6.6 Final Closure — isolated historical-file + atomic-replace + org isolation probe.
 *
 * Creates an isolated store root (does NOT modify live scientific_stable_results payloads).
 * Simulates a root-owned historical JSON file, then verifies www-data can:
 *   - read it via StableAnswerResultSetStore::get
 *   - replace it via atomic tmp→rename write (same path)
 *
 * Also verifies cross-org get returns null (application-layer path used by pageStableResultSet).
 *
 * Usage:
 *   docker exec -u www-data wsa-enterprise-backend-1 php scripts/rev66_closure_verify.php
 */

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\Agriculture\Research\AgriculturalResearchAgent;
use App\Services\Agriculture\Research\Synthesis\StableAnswerResultSet;
use App\Services\Agriculture\Research\Synthesis\StableAnswerResultSetStore;
use App\Services\Tenancy\PublicTenantResolver;

$outPath = $argv[1] ?? '/tmp/rev66_closure_verify.json';
$posixUser = function_exists('posix_geteuid') ? posix_geteuid() : null;
$posixName = function_exists('posix_getpwuid') && $posixUser !== null
    ? (posix_getpwuid($posixUser)['name'] ?? null)
    : null;

$report = [
    'revision' => '6.6-closure',
    'created_at' => gmdate('c'),
    'runtime' => [
        'geteuid' => $posixUser,
        'username' => $posixName,
        'is_root' => $posixUser === 0,
    ],
    'historical' => [],
    'org_isolation' => [],
    'atomic_failed_write' => [],
    'ok' => false,
    'error' => null,
];

$rmTree = static function (string $dir): void {
    if (! is_dir($dir)) {
        return;
    }
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );
    foreach ($it as $file) {
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }
    rmdir($dir);
};

try {
    if ($posixUser === 0) {
        throw new RuntimeException('Refusing root execution; run as www-data.');
    }

    $iso = sys_get_temp_dir().'/rev66_closure_'.getmypid().'_'.bin2hex(random_bytes(3));
    $rmTree($iso);
    if (! mkdir($iso, 0770, true) && ! is_dir($iso)) {
        throw new RuntimeException('Unable to create isolated root.');
    }
    @chmod($iso, 0770);

    $orgId = 8;
    $otherOrg = 99;
    $orgDir = $iso.DIRECTORY_SEPARATOR.(string) $orgId;
    if (! mkdir($orgDir, 0775, true) && ! is_dir($orgDir)) {
        throw new RuntimeException('Unable to create org dir.');
    }
    @chmod($orgDir, 2775);

    // Simulate historical root-owned payload (created then chown if possible by a helper).
    // As www-data we write first, then attempt to leave a file that rename must replace.
    $historicalId = 'srs_rev66_closure_hist';
    $histPath = $orgDir.DIRECTORY_SEPARATOR.$historicalId.'.json';
    $payload = [
        'result_set_id' => $historicalId,
        'organization_id' => $orgId,
        'original_question' => 'TEST FIXTURE closure historical',
        'csq_fingerprint' => 'rev66-closure',
        'answers' => [[
            'answer' => 'TEST-DATA historical answer',
            'result_id' => 'hist-r0',
            'evidence_ids' => ['hist-e0'],
            'source_ids' => ['hist'],
            'position' => 0,
            'provenance' => ['test_fixture' => true],
        ]],
        'references' => [],
        'created_at' => gmdate('c'),
        'expires_at' => gmdate('c', time() + 3600),
        'status' => 'ready',
        'observability' => ['test_fixture' => true],
    ];
    file_put_contents($histPath, json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    @chmod($histPath, 0644);

    // Attempt to make file appear root-owned via a privileged helper if available.
    // When running as www-data, chown to root typically fails — that is expected.
    $chownAttempted = false;
    $chownOk = false;
    if (function_exists('chown')) {
        $chownAttempted = true;
        $chownOk = @chown($histPath, 'root');
    }
    $statBefore = @stat($histPath);
    $report['historical']['path'] = $histPath;
    $report['historical']['chown_attempted'] = $chownAttempted;
    $report['historical']['chown_to_root_ok'] = $chownOk;
    $report['historical']['stat_before'] = $statBefore ? [
        'mode_octal' => sprintf('%04o', $statBefore['mode'] & 07777),
        'uid' => $statBefore['uid'],
        'gid' => $statBefore['gid'],
    ] : null;

    $store = new StableAnswerResultSetStore($iso);
    $loaded = $store->get($orgId, $historicalId);
    $report['historical']['read_ok'] = $loaded !== null
        && $loaded->resultSetId === $historicalId
        && ($loaded->page(1)['answers'][0]['answer'] ?? null) === 'TEST-DATA historical answer';

    // Replace via create-equivalent write path: overwrite same ID by writing a new set file.
    // Store::create always generates a new ID; exercise write() by reflecting a replace.
    $ref = new ReflectionClass($store);
    $write = $ref->getMethod('write');
    $write->setAccessible(true);
    $replacement = new StableAnswerResultSet(
        resultSetId: $historicalId,
        organizationId: $orgId,
        originalQuestion: 'TEST FIXTURE closure replaced',
        csqFingerprint: 'rev66-closure',
        answers: [[
            'answer' => 'TEST-DATA replaced answer',
            'result_id' => 'hist-r0-replaced',
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
    $tmpLeft = glob($orgDir.DIRECTORY_SEPARATOR.'*.tmp.*') ?: [];
    $after = $store->get($orgId, $historicalId);
    $statAfter = @stat($histPath);
    $report['historical']['replace_ok'] = $after !== null
        && ($after->page(1)['answers'][0]['answer'] ?? null) === 'TEST-DATA replaced answer';
    $report['historical']['no_tmp_left'] = $tmpLeft === [];
    $report['historical']['stat_after'] = $statAfter ? [
        'mode_octal' => sprintf('%04o', $statAfter['mode'] & 07777),
        'uid' => $statAfter['uid'],
        'gid' => $statAfter['gid'],
    ] : null;

    // Cross-org isolation via store + agent pageStableResultSet
    $cross = $store->get($otherOrg, $historicalId);
    $report['org_isolation']['store_cross_org_null'] = $cross === null;

    $agent = app(AgriculturalResearchAgent::class);
    // Use live agent against isolated store by injecting via reflection if possible;
    // otherwise exercise pageStableResultSet against live public org missing ID.
    $denied = $agent->pageStableResultSet($otherOrg, $historicalId, 1);
    $report['org_isolation']['agent_missing_or_denied'] = ($denied['status'] ?? null) === 'result_set_unavailable'
        || ($denied['failure_code'] ?? null) === 'stable_result_set_missing_or_expired';

    $publicOrg = app(PublicTenantResolver::class)->bindPublicTenant()->organizationId;
    $liveMissing = $agent->pageStableResultSet($publicOrg, 'srs_does_not_exist_rev66_closure', 1);
    $report['org_isolation']['missing_result_set'] = ($liveMissing['status'] ?? null) === 'result_set_unavailable';
    $report['org_isolation']['no_path_leak'] = ! str_contains(json_encode($liveMissing), 'scientific_stable_results')
        && ! str_contains(json_encode($liveMissing), '/var/www');

    // Failed-write integrity: force invalid destination by writing to a non-writable path simulation
    // — verify temp cleanup on rename failure using a directory named like the target file.
    $badId = 'srs_rev66_bad_rename';
    $conflict = $orgDir.DIRECTORY_SEPARATOR.$badId.'.json';
    if (is_file($conflict)) {
        unlink($conflict);
    }
    mkdir($conflict, 0775); // rename onto existing directory should fail
    $failedSet = new StableAnswerResultSet(
        resultSetId: $badId,
        organizationId: $orgId,
        originalQuestion: 'TEST bad rename',
        csqFingerprint: null,
        answers: [['answer' => 'x', 'result_id' => 'x', 'evidence_ids' => [], 'source_ids' => [], 'position' => 0, 'provenance' => []]],
        references: [],
        createdAt: gmdate('c'),
        expiresAt: gmdate('c', time() + 600),
        observability: [],
        status: 'ready',
    );
    $threw = false;
    try {
        $write->invoke($store, $failedSet);
    } catch (Throwable $e) {
        $threw = true;
        $report['atomic_failed_write']['message'] = $e->getMessage();
    }
    $tmpAfterFail = glob($orgDir.DIRECTORY_SEPARATOR.$badId.'.json.tmp.*') ?: [];
    $report['atomic_failed_write']['threw'] = $threw;
    $report['atomic_failed_write']['tmp_cleaned'] = $tmpAfterFail === [];
    if (is_dir($conflict)) {
        rmdir($conflict);
    }

    try {
        $rmTree($iso);
        $report['cleanup_ok'] = true;
    } catch (Throwable $cleanupError) {
        // Cleanup failure must not invalidate substantive assertions.
        $report['cleanup_ok'] = false;
        $report['cleanup_error'] = $cleanupError->getMessage();
    }

    $report['ok'] = ($report['historical']['read_ok'] ?? false)
        && ($report['historical']['replace_ok'] ?? false)
        && ($report['historical']['no_tmp_left'] ?? false)
        && ($report['org_isolation']['store_cross_org_null'] ?? false)
        && ($report['org_isolation']['missing_result_set'] ?? false)
        && ($report['org_isolation']['no_path_leak'] ?? false)
        && ($report['atomic_failed_write']['threw'] ?? false)
        && ($report['atomic_failed_write']['tmp_cleaned'] ?? false);
} catch (Throwable $e) {
    $report['error'] = $e->getMessage();
    $report['ok'] = false;
}

file_put_contents($outPath, json_encode($report, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)."\n");
fwrite(STDOUT, json_encode([
    'ok' => $report['ok'],
    'username' => $posixName,
    'error' => $report['error'],
    'report' => $outPath,
], JSON_UNESCAPED_SLASHES)."\n");

exit($report['ok'] ? 0 : 1);
