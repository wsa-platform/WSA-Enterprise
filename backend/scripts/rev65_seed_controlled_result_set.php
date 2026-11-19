<?php

/**
 * Rev 6.5 — isolated controlled StableAnswerResultSet seed for browser E2E.
 *
 * Creates TEST-DATA-ONLY answers (not scientific findings) in the real store
 * under the public organization. Writes a JSON manifest for Playwright.
 *
 * Usage (inside backend container):
 *   php scripts/rev65_seed_controlled_result_set.php [/path/to/manifest.json]
 */

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\Agriculture\Research\Synthesis\StableAnswerResultSet;
use App\Services\Agriculture\Research\Synthesis\StableAnswerResultSetStore;
use App\Services\Tenancy\PublicTenantResolver;

$outPath = $argv[1] ?? storage_path('app/e2e-tmp/rev65_controlled_result_set.json');

$tenant = app(PublicTenantResolver::class)->bindPublicTenant();
$orgId = $tenant->organizationId;

$store = new StableAnswerResultSetStore();
$answers = [];
for ($i = 0; $i < 21; $i++) {
    $answers[] = [
        'answer_id' => 'sha256:'.hash('sha256', 'rev65-test-answer-'.$i),
        'answer' => 'TEST-DATA independent answer '.$i,
        'result_id' => 'rev65-test-r'.$i,
        'evidence_ids' => ['rev65-test-e'.$i],
        'source_ids' => ['rev65_test_fixture'],
        'position' => $i,
        'provenance' => [
            'test_fixture' => true,
            'revision' => '6.5',
            'label' => 'CONTROLLED_TEST_DATA_NOT_SCIENTIFIC',
        ],
    ];
}

$set = $store->create(
    organizationId: $orgId,
    originalQuestion: 'TEST FIXTURE Rev 6.5 — not a scientific claim',
    csqFingerprint: 'rev65-controlled-fp',
    answers: $answers,
    references: [[
        'title' => 'TEST REF — controlled fixture',
        'test_fixture' => true,
        'revision' => '6.5',
    ]],
    observability: [
        'test_fixture' => true,
        'revision' => '6.5',
        'label' => 'CONTROLLED_TEST_DATA_NOT_SCIENTIFIC',
    ],
    status: 'ready',
    ttlSeconds: 3600,
);

// Ensure PHP-FPM (www-data) can read Results for browser E2E after root-seeded fixtures.
$storeRoot = storage_path('app/scientific_stable_results');
if (is_dir($storeRoot)) {
    @chmod($storeRoot, 0775);
}
$orgDir = $storeRoot.DIRECTORY_SEPARATOR.$orgId;
if (is_dir($orgDir)) {
    @chmod($orgDir, 0775);
}
$setPath = $orgDir.DIRECTORY_SEPARATOR.$set->resultSetId.'.json';
if (is_file($setPath)) {
    @chmod($setPath, 0664);
}

$page1 = $set->page(1);
$page2 = $set->page(2);
$page3 = $set->page(3);

$manifest = [
    'revision' => '6.5',
    'label' => 'CONTROLLED_TEST_DATA_NOT_SCIENTIFIC',
    'organization_id' => $orgId,
    'result_set_id' => $set->resultSetId,
    'total_answers' => $set->totalAnswers(),
    'total_pages' => $set->totalPages(),
    'page_size' => StableAnswerResultSet::PAGE_SIZE,
    'expires_at' => $set->expiresAt,
    'page_counts' => [
        'page1' => count($page1['answers']),
        'page2' => count($page2['answers']),
        'page3' => count($page3['answers']),
    ],
    'expected_first_answers' => [
        'page1' => (string) ($page1['answers'][0]['answer'] ?? ''),
        'page2' => (string) ($page2['answers'][0]['answer'] ?? ''),
        'page3' => (string) ($page3['answers'][0]['answer'] ?? ''),
    ],
    'page1' => $page1,
    'created_at' => gmdate('c'),
];

$dir = dirname($outPath);
if (! is_dir($dir)) {
    mkdir($dir, 0755, true);
}

file_put_contents(
    $outPath,
    json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)."\n",
);

fwrite(STDOUT, json_encode([
    'ok' => true,
    'result_set_id' => $set->resultSetId,
    'organization_id' => $orgId,
    'total_answers' => $set->totalAnswers(),
    'manifest' => $outPath,
], JSON_UNESCAPED_SLASHES)."\n");
