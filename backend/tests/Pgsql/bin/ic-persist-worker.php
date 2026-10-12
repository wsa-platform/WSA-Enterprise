<?php

declare(strict_types=1);

/**
 * Runs IC repository writes in their own PHP process for IntegrationClassificationPostgresIsolationTest,
 * so a write can block on a lock or index entry held by the test process's uncommitted transaction.
 *
 * Usage: php ic-persist-worker.php '<json job>'
 *   {"op": "persist", "overrides": {...}, "isolation": "repeatable read"|"serializable"|null}
 *   {"op": "supersede", "steps": [{"prior": <id>, "overrides": {...}}, ...]}   (all steps in one transaction)
 * "adrId" overrides are plain strings. Prints "pid <backend pid>" once connected, then one JSON line
 * with the outcome and the number of INSERT attempts the worker made.
 */

use App\Models\CghiaIntegrationClassification;
use App\Services\Agriculture\Research\Identity\AdrMembershipId;
use App\Services\Agriculture\Research\IntegrationClassification\Persistence\EloquentIntegrationClassificationRepository;
use App\Services\Agriculture\Research\IntegrationClassification\Persistence\IntegrationClassificationRecord;
use App\Services\Agriculture\Research\IntegrationClassification\Persistence\IntegrationClassificationRecordId;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\Support\IntegrationClassificationFixtures;

require dirname(__DIR__, 2).'/bootstrap-pgsql.php';

$app = require dirname(__DIR__, 3).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$connection = DB::connection();
$database = (string) $connection->selectOne('SELECT current_database() AS name')->name;
if ($connection->getName() !== 'pgsql_testing' || $connection->getDriverName() !== 'pgsql' || $database !== getenv('DB_TEST_DATABASE')) {
    fwrite(STDERR, "IC persist worker refused: not connected to the guarded PostgreSQL test database.\n");
    exit(1);
}

$job = json_decode((string) ($argv[1] ?? ''), true);
if (! is_array($job) || ! in_array($job['op'] ?? null, ['persist', 'supersede'], true)) {
    fwrite(STDERR, "IC persist worker refused: invalid job.\n");
    exit(1);
}

// Hang guard only: a timeout fails the calling test, it never satisfies it.
$connection->statement("SET lock_timeout = '30s'");

// Counts INSERT attempts, including ones that later fail on a unique index.
$inserts = 0;
CghiaIntegrationClassification::creating(function () use (&$inserts): void {
    $inserts++;
});

$fixtures = new class
{
    use IntegrationClassificationFixtures {
        payload as public;
        supersedePayload as public;
    }
};

$withAdr = static function (array $overrides): array {
    if (isset($overrides['adrId'])) {
        $overrides['adrId'] = AdrMembershipId::fromString((string) $overrides['adrId']);
    }

    return $overrides;
};

fwrite(STDOUT, 'pid '.$connection->selectOne('SELECT pg_backend_pid() AS pid')->pid."\n");
fflush(STDOUT);

$repo = new EloquentIntegrationClassificationRepository;

try {
    if ($job['op'] === 'persist') {
        $persist = static fn (): IntegrationClassificationRecord => $repo->persist(...$fixtures->payload($withAdr($job['overrides'] ?? [])));
        $isolation = $job['isolation'] ?? null;
        $record = $isolation === null ? $persist() : DB::transaction(function () use ($persist, $isolation): IntegrationClassificationRecord {
            DB::statement('SET TRANSACTION ISOLATION LEVEL '.match ($isolation) {
                'repeatable read' => 'REPEATABLE READ',
                'serializable' => 'SERIALIZABLE',
            });

            return $persist();
        });
    } else {
        $record = DB::transaction(function () use ($repo, $fixtures, $withAdr, $job): IntegrationClassificationRecord {
            $last = null;
            foreach ($job['steps'] as $step) {
                $prior = $repo->findById(IntegrationClassificationRecordId::fromInt((int) $step['prior']));
                if ($prior === null) {
                    throw new RuntimeException('IC persist worker: prior '.$step['prior'].' not found.');
                }
                $last = $repo->supersede(...$fixtures->supersedePayload($prior, $withAdr($step['overrides'] ?? [])));
            }

            return $last;
        });
    }
    $result = [
        'outcome' => 'returned',
        'record_id' => $record->persistenceRecordId->toInt(),
        'lifecycle_state' => $record->lifecycleState->value,
    ];
} catch (Throwable $e) {
    $previous = $e->getPrevious();
    $result = [
        'outcome' => 'threw',
        'class' => $e::class,
        'message' => $e->getMessage(),
        'sqlstate' => $e instanceof QueryException ? ($e->errorInfo[0] ?? null) : null,
        'previous_sqlstate' => $previous instanceof QueryException ? ($previous->errorInfo[0] ?? null) : null,
    ];
}

$result['inserts'] = $inserts;
fwrite(STDOUT, json_encode($result)."\n");
