<?php

declare(strict_types=1);

/**
 * Runs EloquentIntegrationClassificationRepository::invalidate() in its own PHP process for
 * IntegrationClassificationPostgresConcurrencyTest, so the call can block on a PostgreSQL row lock
 * while the test process holds it and later commits.
 *
 * Usage: php ic-invalidate-worker.php <persistence_record_id>
 * Prints "pid <backend pid>" once connected, then one JSON line with the outcome.
 */

use App\Services\Agriculture\Research\IntegrationClassification\Persistence\EloquentIntegrationClassificationRepository;
use App\Services\Agriculture\Research\IntegrationClassification\Persistence\IntegrationClassificationRecordId;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 2).'/bootstrap-pgsql.php';

$app = require dirname(__DIR__, 3).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$connection = DB::connection();
$database = (string) $connection->selectOne('SELECT current_database() AS name')->name;
if ($connection->getName() !== 'pgsql_testing' || $connection->getDriverName() !== 'pgsql' || $database !== getenv('DB_TEST_DATABASE')) {
    fwrite(STDERR, "IC invalidate worker refused: not connected to the guarded PostgreSQL test database.\n");
    exit(1);
}

// Hang guard only: a timeout fails the calling test, it never satisfies it.
$connection->statement("SET lock_timeout = '30s'");

fwrite(STDOUT, 'pid '.$connection->selectOne('SELECT pg_backend_pid() AS pid')->pid."\n");
fflush(STDOUT);

try {
    $record = (new EloquentIntegrationClassificationRepository)->invalidate(
        IntegrationClassificationRecordId::fromInt((int) ($argv[1] ?? 0))
    );
    $result = ['outcome' => 'returned', 'lifecycle_state' => $record->lifecycleState->value];
} catch (Throwable $e) {
    $result = [
        'outcome' => 'threw',
        'class' => $e::class,
        'message' => $e->getMessage(),
        'sqlstate' => $e instanceof QueryException ? ($e->errorInfo[0] ?? null) : null,
    ];
}

fwrite(STDOUT, json_encode($result)."\n");
