<?php

declare(strict_types=1);

/**
 * Runs local-index ingestion and search in their own PHP process for
 * ScientificRecordStorePostgresTest, so durability is observed across processes and a commit can
 * block on, or die inside, a transaction (ADR-023 §8.41).
 *
 * Every service is resolved from the application container, as production resolves it.
 *
 * Usage: php scientific-record-store-worker.php '<json job>'
 *   {"op": "snapshot", "source": "dataset_example", "fixture": "<file>", "full_text": bool}
 *   {"op": "hold", "source": ..., "fixture": ...}   commits inside an outer transaction, prints "held", then waits to be killed
 *   {"op": "harvest", "responses": ["<file>"|<status>, ...], "max_pages": int}
 *   {"op": "search", "source": ..., "query": "...", "limit": int}
 * Prints "pid <backend pid>" once connected, then one JSON line with the outcome.
 */

use App\Services\Agriculture\Research\Search\Contract\ScientificSourceUsageStatus;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalIndexIngestionService;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalIndexScientificSourceAdapter;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalIngestionReport;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalStoreFailure;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Support\LocalIndexFixtures;

require dirname(__DIR__, 2).'/bootstrap-pgsql.php';

$app = require dirname(__DIR__, 3).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

// LocalIndex must use the Guard-certified pgsql_testing connection (not CapVer /
// scientific_records SQLite isolation paths). Rebind singletons after config pin.
config(['agricultural_intelligence.local_index.connection' => 'pgsql_testing']);
foreach ([
    \App\Services\Agriculture\Research\Search\LocalIndex\ScientificRecordStore::class,
    \App\Services\Agriculture\Research\Search\LocalIndex\LocalScientificIndex::class,
    \App\Services\Agriculture\Research\Search\LocalIndex\SqliteFtsScientificIndex::class,
    \App\Services\Agriculture\Research\Search\LocalIndex\LocalIndexSearchEngine::class,
    \App\Services\Agriculture\Research\Search\LocalIndex\LocalIndexIngestionService::class,
] as $abstract) {
    if ($app->bound($abstract)) {
        $app->forgetInstance($abstract);
    }
}

$connection = DB::connection();
$database = (string) $connection->selectOne('SELECT current_database() AS name')->name;
if ($connection->getName() !== 'pgsql_testing' || $connection->getDriverName() !== 'pgsql' || $database !== getenv('DB_TEST_DATABASE')) {
    fwrite(STDERR, "Record store worker refused: not connected to the guarded PostgreSQL test database.\n");
    exit(1);
}

$job = json_decode((string) ($argv[1] ?? ''), true);
if (! is_array($job) || ! in_array($job['op'] ?? null, ['snapshot', 'hold', 'harvest', 'search'], true)) {
    fwrite(STDERR, "Record store worker refused: invalid job.\n");
    exit(1);
}

// Hang guard only: a timeout fails the calling test, it never satisfies it.
$connection->statement("SET lock_timeout = '30s'");
Http::preventStrayRequests();

$fixtures = new class
{
    use LocalIndexFixtures {
        fixtureLicensing as public;
        fixtureOaiProfile as public;
        fixtureBulkProfile as public;
        fixturePath as public;
        fakeOaiResponses as public;
    }
};

$source = (string) ($job['source'] ?? 'dataset_example');
$profile = $fixtures::fixtureBulkProfile(
    $source,
    ($job['full_text'] ?? false) === true ? $fixtures::fixtureLicensing(fullText: ScientificSourceUsageStatus::PERMITTED) : null,
);
$now = new DateTimeImmutable((string) ($job['at'] ?? '2026-10-15T09:00:00+00:00'));
$report = static fn (LocalIngestionReport $r): array => [
    'status' => $r->status,
    'generation' => $r->generation,
    'counts' => $r->counts,
    'refusals' => $r->refusals,
    'failure' => $r->failure,
    'pages' => $r->pages,
    'checkpoint' => $r->checkpoint?->toArray(),
    'checkpoint_token' => $r->checkpoint?->resumptionToken,
];

fwrite(STDOUT, 'pid '.$connection->selectOne('SELECT pg_backend_pid() AS pid')->pid."\n");
fflush(STDOUT);

try {
    $result = match ($job['op']) {
        'snapshot' => ['outcome' => 'returned'] + $report(app(LocalIndexIngestionService::class)->ingestSnapshot($profile, $fixtures::fixturePath((string) $job['fixture']), $now)),
        'hold' => (static function () use ($profile, $fixtures, $job, $now, $report): never {
            DB::beginTransaction();
            $held = $report(app(LocalIndexIngestionService::class)->ingestSnapshot($profile, $fixtures::fixturePath((string) $job['fixture']), $now));
            fwrite(STDOUT, json_encode(['outcome' => 'held'] + $held)."\n");
            fflush(STDOUT);
            sleep(120);
            fwrite(STDERR, "Record store worker was not killed while holding its transaction.\n");
            exit(2);
        })(),
        'harvest' => (static function () use ($fixtures, $job, $now, $report): array {
            $fixtures::fakeOaiResponses($job['responses']);
            $harvest = $report(app(LocalIndexIngestionService::class)->harvestOaiPmh($fixtures::fixtureOaiProfile(), $now, (int) ($job['max_pages'] ?? 100)));
            $requests = Http::recorded()->map(static function (array $pair): array {
                /** @var Request $request */
                $request = $pair[0];
                parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

                return $query;
            })->all();

            return ['outcome' => 'returned', 'requests' => $requests] + $harvest;
        })(),
        'search' => (static function () use ($profile, $job): array {
            $adapter = app()->make(LocalIndexScientificSourceAdapter::class, ['profile' => $profile]);
            $outcome = $adapter->search((string) $job['query'], (int) ($job['limit'] ?? 10));

            return [
                'outcome' => 'returned',
                'status' => $outcome->status,
                'error' => $outcome->error,
                'index_generation' => $outcome->observability['index_generation'] ?? null,
                'results' => array_map(static fn (ScientificSearchResult $r): array => [
                    'source' => $r->sourceKey,
                    'identifier' => $r->primaryProvenance()?->originalIdentifier,
                    'title' => $r->title,
                    'doi' => $r->doi,
                    'abstract' => $r->abstract,
                    'adapter' => $r->primaryProvenance()?->adapterClass,
                    'access_family' => $r->primaryProvenance()?->accessFamily,
                    'transformations' => $r->primaryProvenance()?->transformations,
                    'licensing' => $r->primaryProvenance()?->effectiveLicensing()->toArray(),
                ], $outcome->results),
            ];
        })(),
    };
} catch (LocalStoreFailure $e) {
    $result = ['outcome' => 'threw', 'class' => $e::class, 'reason' => $e->reason, 'message' => $e->getMessage()];
} catch (Throwable $e) {
    $result = ['outcome' => 'threw', 'class' => $e::class, 'message' => $e->getMessage()];
}

fwrite(STDOUT, json_encode($result)."\n");
