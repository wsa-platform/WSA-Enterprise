<?php

namespace Tests\Pgsql;

use App\Models\CghiaIntegrationClassification;
use App\Services\Agriculture\Research\Identity\AdrMembershipId;
use App\Services\Agriculture\Research\IntegrationClassification\Persistence\EloquentIntegrationClassificationRepository;
use App\Services\Agriculture\Research\IntegrationClassification\Persistence\IntegrationClassificationActiveConflict;
use App\Services\Agriculture\Research\IntegrationClassification\Persistence\IntegrationClassificationIdempotencyConflict;
use App\Services\Agriculture\Research\IntegrationClassification\Persistence\IntegrationClassificationLifecycleState;
use App\Services\Agriculture\Research\IntegrationClassification\Persistence\IntegrationClassificationPersistenceContract;
use App\Services\Agriculture\Research\IntegrationClassification\Persistence\IntegrationClassificationRecord;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\IntegrationClassificationFixtures;
use Tests\TestCase;

/**
 * IC Persistence on PostgreSQL against competitors that are still uncommitted when they collide
 * (phpunit.pgsql.xml only). The test process holds an open transaction; the competitor runs in a
 * worker process (tests/Pgsql/bin/ic-persist-worker.php) and the test commits or rolls back only
 * once PostgreSQL reports the worker blocked by this connection.
 */
final class IntegrationClassificationPostgresIsolationTest extends TestCase
{
    use IntegrationClassificationFixtures;

    private const PEER = 'pgsql_testing_peer';

    private const TABLE = IntegrationClassificationPersistenceContract::TABLE;

    private const WORKER_LOCK_WAIT_DEADLINE_SECONDS = 20.0;

    private const WORKER_FINISH_DEADLINE_SECONDS = 60.0;

    private static bool $migrated = false;

    private EloquentIntegrationClassificationRepository $repo;

    /** @var list<array{process: resource, stdout: resource, stderr: resource, pid: int}> */
    private array $workers = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertDisposablePostgresTestDatabase();

        if (! self::$migrated) {
            Artisan::call('migrate:fresh', ['--force' => true]);
            self::$migrated = true;
        }

        config(['database.connections.'.self::PEER => config('database.connections.pgsql_testing')]);
        $this->deleteIcRows();
        $this->repo = new EloquentIntegrationClassificationRepository;
    }

    protected function tearDown(): void
    {
        while (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        foreach ($this->workers as $worker) {
            if (proc_get_status($worker['process'])['running']) {
                proc_terminate($worker['process']);
            }
            fclose($worker['stdout']);
            fclose($worker['stderr']);
            proc_close($worker['process']);
        }
        $this->workers = [];
        $this->deleteIcRows();
        DB::purge(self::PEER);

        parent::tearDown();
    }

    public function test_uncommitted_competitor_commit_makes_waiting_persist_raise_active_conflict(): void
    {
        DB::beginTransaction();
        $mine = $this->repo->persist(...$this->payload(['idempotencyKey' => 'ic:pg:open:a']));

        $worker = $this->startWorker(['op' => 'persist', 'overrides' => [
            'idempotencyKey' => 'ic:pg:open:b',
            'evidenceFingerprint' => 'fp-pg-open-b',
        ]]);
        $this->awaitWorkerBlockedByThisConnection($worker);
        DB::commit();
        $result = $this->finishWorker($worker);

        $this->assertSame('threw', $result['outcome'], json_encode($result));
        $this->assertSame(IntegrationClassificationActiveConflict::class, $result['class']);
        $this->assertSame('23505', $result['previous_sqlstate']);
        $this->assertSame(1, $result['inserts']);

        $active = $this->activeRows('ABSTRACT_IC_INTEGRITY');
        $this->assertCount(1, $active);
        $this->assertSame($mine->persistenceRecordId->toInt(), (int) $active[0]->id);
        $this->assertSame(0, $this->rowsWithKey('ic:pg:open:b'));
    }

    public function test_uncommitted_competitor_rollback_lets_waiting_persist_succeed(): void
    {
        DB::beginTransaction();
        $this->repo->persist(...$this->payload(['idempotencyKey' => 'ic:pg:open:a']));

        $worker = $this->startWorker(['op' => 'persist', 'overrides' => [
            'idempotencyKey' => 'ic:pg:open:b',
            'evidenceFingerprint' => 'fp-pg-open-b',
        ]]);
        $this->awaitWorkerBlockedByThisConnection($worker);
        DB::rollBack();
        $result = $this->finishWorker($worker);

        $this->assertSame('returned', $result['outcome'], json_encode($result));
        $this->assertSame(IntegrationClassificationLifecycleState::ACTIVE->value, $result['lifecycle_state']);

        $active = $this->activeRows('ABSTRACT_IC_INTEGRITY');
        $this->assertCount(1, $active);
        $this->assertSame($result['record_id'], (int) $active[0]->id);
        $this->assertSame(0, $this->rowsWithKey('ic:pg:open:a'));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    #[DataProvider('sameKeyCompetitors')]
    public function test_uncommitted_same_key_competitor_is_replayed_or_rejected_after_commit(array $overrides, ?string $mismatchedField): void
    {
        DB::beginTransaction();
        $mine = $this->repo->persist(...$this->payload());

        $worker = $this->startWorker(['op' => 'persist', 'overrides' => $overrides]);
        $this->awaitWorkerBlockedByThisConnection($worker);
        DB::commit();
        $result = $this->finishWorker($worker);

        if ($mismatchedField === null) {
            $this->assertSame('returned', $result['outcome'], json_encode($result));
            $this->assertSame($mine->persistenceRecordId->toInt(), $result['record_id']);
        } else {
            $this->assertSame('threw', $result['outcome'], json_encode($result));
            $this->assertSame(IntegrationClassificationIdempotencyConflict::class, $result['class']);
            $this->assertStringContainsString($mismatchedField, $result['message']);
        }
        $this->assertSame(1, $result['inserts']);
        $this->assertSame(1, $this->rowsWithKey('ic:integrity'));
        $this->assertSame(1, CghiaIntegrationClassification::query()->count());
    }

    /**
     * @return array<string, array{array<string, mixed>, ?string}>
     */
    public static function sameKeyCompetitors(): array
    {
        return [
            'same contract' => [[], null],
            'evidence references reordered' => [['evidenceReferences' => ['evidence://integrity/b', 'evidence://integrity/a']], null],
            'evidence reference duplicated' => [['evidenceReferences' => ['evidence://integrity/a', 'evidence://integrity/b', 'evidence://integrity/b']], 'evidence_references'],
            'evidence fingerprint changed' => [['evidenceFingerprint' => 'fp-pg-open-other'], 'evidence_fingerprint'],
        ];
    }

    /**
     * The repository assumes READ COMMITTED; a stricter caller must still fail closed: no second row,
     * no second ACTIVE, and an exception rather than a silent replay of a record it cannot see. With
     * the same key the replay lookup runs in the caller's snapshot, so the raw unique violation surfaces.
     */
    #[DataProvider('stricterIsolationCompetitors')]
    public function test_waiting_persist_under_stricter_isolation_fails_closed(string $isolation, string $key, string $expectedClass): void
    {
        DB::beginTransaction();
        $mine = $this->repo->persist(...$this->payload());

        $worker = $this->startWorker(['op' => 'persist', 'isolation' => $isolation, 'overrides' => [
            'idempotencyKey' => $key,
            'evidenceFingerprint' => 'fp-pg-open-strict',
        ]]);
        $this->awaitWorkerBlockedByThisConnection($worker);
        DB::commit();
        $result = $this->finishWorker($worker);

        $this->assertSame('threw', $result['outcome'], json_encode($result));
        $this->assertSame($expectedClass, $result['class'], json_encode($result));
        $this->assertSame('23505', $result['sqlstate'] ?? $result['previous_sqlstate'], json_encode($result));

        $active = $this->activeRows('ABSTRACT_IC_INTEGRITY');
        $this->assertCount(1, $active);
        $this->assertSame($mine->persistenceRecordId->toInt(), (int) $active[0]->id);
        $this->assertSame(1, CghiaIntegrationClassification::query()->count());
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function stricterIsolationCompetitors(): array
    {
        return [
            'repeatable read, different key' => ['repeatable read', 'ic:pg:open:strict', IntegrationClassificationActiveConflict::class],
            'repeatable read, same key' => ['repeatable read', 'ic:integrity', UniqueConstraintViolationException::class],
            'serializable, different key' => ['serializable', 'ic:pg:open:strict', IntegrationClassificationActiveConflict::class],
            'serializable, same key' => ['serializable', 'ic:integrity', UniqueConstraintViolationException::class],
        ];
    }

    /**
     * Under READ COMMITTED the waiter re-reads the prior after the row lock and finds the committed
     * replacement by key, so it replays without attempting an INSERT; the unique-violation recovery
     * branch of supersede() is not reached.
     */
    public function test_waiting_supersede_of_same_prior_with_same_key_replays_without_insert(): void
    {
        $prior = $this->repo->persist(...$this->payload());

        DB::beginTransaction();
        $next = $this->repo->supersede(...$this->supersedePayload($prior));

        $worker = $this->startWorker(['op' => 'supersede', 'steps' => [['prior' => $prior->persistenceRecordId->toInt()]]]);
        $this->awaitWorkerBlockedByThisConnection($worker);
        DB::commit();
        $result = $this->finishWorker($worker);

        $this->assertSame('returned', $result['outcome'], json_encode($result));
        $this->assertSame($next->persistenceRecordId->toInt(), $result['record_id']);
        $this->assertSame(0, $result['inserts']);
        $this->assertSame(2, CghiaIntegrationClassification::query()->count());
        $this->assertTrue($this->repo->findById($prior->persistenceRecordId)?->supersededBy?->equals($next->persistenceRecordId));
    }

    public function test_opposite_order_supersede_composition_deadlock_rolls_back_one_side_entirely(): void
    {
        $priorA = $this->repo->persist(...$this->payload());
        $priorB = $this->repo->persist(...$this->payload([
            'adrId' => AdrMembershipId::fromString('ABSTRACT_IC_INTEGRITY_B'),
            'idempotencyKey' => 'ic:pg:deadlock:b',
        ]));

        DB::beginTransaction();
        DB::statement("SET LOCAL lock_timeout = '20s'");
        $this->repo->supersede(...$this->supersedePayload($priorA, ['newIdempotencyKey' => 'ic:pg:deadlock:a:test']));

        $worker = $this->startWorker(['op' => 'supersede', 'steps' => [
            ['prior' => $priorB->persistenceRecordId->toInt(), 'overrides' => ['newIdempotencyKey' => 'ic:pg:deadlock:b:worker']],
            ['prior' => $priorA->persistenceRecordId->toInt(), 'overrides' => ['newIdempotencyKey' => 'ic:pg:deadlock:a:worker']],
        ]]);
        $this->awaitWorkerBlockedByThisConnection($worker);

        $testDeadlocked = false;
        try {
            $this->repo->supersede(...$this->supersedePayload($priorB, ['newIdempotencyKey' => 'ic:pg:deadlock:b:test']));
        } catch (\Throwable $e) {
            $this->assertSame('40P01', $this->sqlState($e), $e::class.': '.$e->getMessage());
            $testDeadlocked = true;
        }
        $testDeadlocked ? DB::rollBack() : DB::commit();
        $result = $this->finishWorker($worker);

        $workerDeadlocked = $result['outcome'] === 'threw' && ($result['sqlstate'] ?? $result['previous_sqlstate']) === '40P01';
        $this->assertTrue($testDeadlocked xor $workerDeadlocked, 'exactly one side must be the deadlock victim: '.json_encode($result));
        if ($testDeadlocked) {
            $this->assertSame('returned', $result['outcome'], json_encode($result));
        }

        $winner = $testDeadlocked ? 'worker' : 'test';
        foreach (['a' => $priorA, 'b' => $priorB] as $suffix => $prior) {
            $active = $this->activeRows($prior->adrId->value);
            $this->assertCount(1, $active);
            $this->assertSame("ic:pg:deadlock:{$suffix}:{$winner}", $active[0]->idempotency_key);
            $reloaded = $this->repo->findById($prior->persistenceRecordId);
            $this->assertSame(IntegrationClassificationLifecycleState::SUPERSEDED, $reloaded?->lifecycleState);
            $this->assertSame((int) $active[0]->id, $reloaded?->supersededBy?->toInt());
        }
        $this->assertSame(4, CghiaIntegrationClassification::query()->count());
    }

    public function test_reader_during_open_supersede_sees_exactly_the_committed_prior(): void
    {
        $prior = $this->repo->persist(...$this->payload());
        $adrId = AdrMembershipId::fromString('ABSTRACT_IC_INTEGRITY');

        DB::beginTransaction();
        $next = $this->repo->supersede(...$this->supersedePayload($prior));
        $during = $this->onPeer(fn () => $this->repo->findCurrentByAdrId($adrId));
        DB::commit();
        $after = $this->onPeer(fn () => $this->repo->findCurrentByAdrId($adrId));

        $this->assertTrue($during?->persistenceRecordId->equals($prior->persistenceRecordId));
        $this->assertSame(IntegrationClassificationLifecycleState::ACTIVE, $during?->lifecycleState);
        $this->assertTrue($after?->persistenceRecordId->equals($next->persistenceRecordId));
    }

    private function assertDisposablePostgresTestDatabase(): void
    {
        $expected = (string) getenv('DB_TEST_DATABASE');

        $this->assertSame('pgsql_testing', config('database.default'), 'PostgreSQL suite must run on pgsql_testing.');
        $this->assertSame('pgsql', DB::connection()->getDriverName(), 'PostgreSQL suite must not fall back to another driver.');

        $current = (string) DB::selectOne('SELECT current_database() AS name')->name;
        $this->assertSame($expected, $current);
        $this->assertStringEndsWith('_test', $current);
        $this->assertNotContains($current, ['wsa_enterprise']);
    }

    private function deleteIcRows(): void
    {
        DB::connection('pgsql_testing')->table(self::TABLE)->delete();
    }

    /**
     * @param  array<string, mixed>  $job
     * @return array{process: resource, stdout: resource, stderr: resource, pid: int}
     */
    private function startWorker(array $job): array
    {
        $process = proc_open(
            [PHP_BINARY, base_path('tests/Pgsql/bin/ic-persist-worker.php'), json_encode($job)],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
        );
        $this->assertIsResource($process);
        fclose($pipes[0]);

        $line = fgets($pipes[1]);
        if (! is_string($line) || preg_match('/^pid (\d+)$/', trim($line), $match) !== 1) {
            $this->fail('IC persist worker did not start: '.trim((string) $line.stream_get_contents($pipes[2])));
        }

        $worker = ['process' => $process, 'stdout' => $pipes[1], 'stderr' => $pipes[2], 'pid' => (int) $match[1]];
        $this->workers[] = $worker;

        return $worker;
    }

    /**
     * Polls PostgreSQL's own lock-wait graph; proceeds only when this connection blocks the worker.
     *
     * @param  array{process: resource, stdout: resource, stderr: resource, pid: int}  $worker
     */
    private function awaitWorkerBlockedByThisConnection(array $worker): void
    {
        $self = (int) DB::selectOne('SELECT pg_backend_pid() AS pid')->pid;
        $deadline = microtime(true) + self::WORKER_LOCK_WAIT_DEADLINE_SECONDS;

        do {
            $blocked = DB::connection(self::PEER)->selectOne(
                'SELECT ? = ANY(pg_blocking_pids(?)) AS blocked',
                [$self, $worker['pid']]
            )->blocked;
            if ($blocked) {
                return;
            }
            if (! proc_get_status($worker['process'])['running']) {
                $this->fail('IC persist worker finished without waiting for the lock: '.stream_get_contents($worker['stdout']));
            }
            usleep(10_000);
        } while (microtime(true) < $deadline);

        $this->fail('IC persist worker was never blocked by this connection.');
    }

    /**
     * Reads the worker's outcome within a deadline and requires a clean exit, so a hung or crashed
     * worker fails the test instead of being read as an outcome.
     *
     * @param  array{process: resource, stdout: resource, stderr: resource, pid: int}  $worker
     * @return array<string, mixed>
     */
    private function finishWorker(array $worker): array
    {
        $deadline = microtime(true) + self::WORKER_FINISH_DEADLINE_SECONDS;
        $output = '';
        while (! feof($worker['stdout'])) {
            if (microtime(true) >= $deadline) {
                proc_terminate($worker['process']);
                $this->fail('IC persist worker did not finish in time: '.$output);
            }
            $read = [$worker['stdout']];
            $write = $except = null;
            if (stream_select($read, $write, $except, 1) > 0) {
                $output .= (string) fread($worker['stdout'], 8192);
            }
        }

        do {
            $status = proc_get_status($worker['process']);
            if (! $status['running']) {
                break;
            }
            usleep(10_000);
        } while (microtime(true) < $deadline);

        $stderr = (string) stream_get_contents($worker['stderr']);
        $this->assertFalse($status['running'], 'IC persist worker did not exit: '.$output);
        $this->assertSame(0, $status['exitcode'], 'IC persist worker exited abnormally: '.$output.' '.$stderr);

        $result = json_decode(trim($output), true);
        $this->assertIsArray($result, 'IC persist worker output: '.$output.' '.$stderr);
        $this->assertContains($result['outcome'] ?? null, ['returned', 'threw'], 'IC persist worker output: '.$output);

        return $result;
    }

    private function sqlState(\Throwable $e): ?string
    {
        for ($cause = $e; $cause !== null; $cause = $cause->getPrevious()) {
            if ($cause instanceof QueryException) {
                return $cause->errorInfo[0] ?? null;
            }
        }

        return null;
    }

    private function onPeer(callable $callback): mixed
    {
        $previous = DB::getDefaultConnection();
        DB::setDefaultConnection(self::PEER);
        try {
            return $callback();
        } finally {
            DB::setDefaultConnection($previous);
        }
    }

    /**
     * @return list<object>
     */
    private function activeRows(string $adrId): array
    {
        return DB::table(self::TABLE)
            ->where('adr_id', $adrId)
            ->where('lifecycle_state', IntegrationClassificationLifecycleState::ACTIVE->value)
            ->orderBy('id')
            ->get()
            ->all();
    }

    private function rowsWithKey(string $key): int
    {
        return DB::table(self::TABLE)->where('idempotency_key', $key)->count();
    }
}
