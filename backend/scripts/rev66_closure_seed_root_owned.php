<?php

/**
 * Rev 6.6 Closure helper — create an isolated root-owned historical JSON fixture.
 * Must run as root, then be consumed by rev66_closure_verify.php as www-data.
 *
 * Usage:
 *   docker exec -u root wsa-enterprise-backend-1 php scripts/rev66_closure_seed_root_owned.php
 */

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$manifest = '/tmp/rev66_closure_hist_manifest.json';
$iso = storage_path('framework/cache/rev66_closure_hist_'.bin2hex(random_bytes(4)));
$orgId = 8;
$orgDir = $iso.DIRECTORY_SEPARATOR.(string) $orgId;
$id = 'srs_rev66_closure_hist';

if (! mkdir($orgDir, 0775, true) && ! is_dir($orgDir)) {
    fwrite(STDERR, "mkdir failed\n");
    exit(1);
}

$path = $orgDir.DIRECTORY_SEPARATOR.$id.'.json';
$payload = [
    'result_set_id' => $id,
    'organization_id' => $orgId,
    'original_question' => 'TEST FIXTURE closure historical root-owned',
    'csq_fingerprint' => 'rev66-closure-hist',
    'answers' => [[
        'answer' => 'TEST-DATA historical root-owned answer',
        'result_id' => 'hist-root-r0',
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
file_put_contents($path, json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
chown($iso, 'root');
chown($orgDir, 'www-data'); // parent writable by www-data so rename/unlink works
chown($path, 'root');
chmod($iso, 0775);
chmod($orgDir, 2775);
chmod($path, 0644); // others-readable; owner root

$stat = stat($path);
$out = [
    'isolated_root' => $iso,
    'org_dir' => $orgDir,
    'file' => $path,
    'result_set_id' => $id,
    'organization_id' => $orgId,
    'stat' => [
        'mode_octal' => sprintf('%04o', $stat['mode'] & 07777),
        'uid' => $stat['uid'],
        'gid' => $stat['gid'],
    ],
];
file_put_contents($manifest, json_encode($out, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)."\n");
fwrite(STDOUT, json_encode(['ok' => true, 'manifest' => $manifest] + $out, JSON_UNESCAPED_SLASHES)."\n");
