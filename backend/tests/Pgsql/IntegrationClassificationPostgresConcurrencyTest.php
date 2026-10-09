<?php

namespace Tests\Pgsql;

use App\Models\CghiaIntegrationClassification;
use App\Services\Agriculture\Research\Identity\AdrMembershipId;
use App\Services\Agriculture\Research\IntegrationClassification\Persistence\EloquentIntegrationClassificationRepository;
use App\Services\Agriculture\Research\IntegrationClassification\Persistence\IntegrationClassificationActiveConflict;
use App\Services\Agriculture\Research\IntegrationClassification\Persistence\IntegrationClassificationIdempotencyConflict;
use App\Services\Agriculture\Research\IntegrationClassification\Persistence\IntegrationClassificationInvariantViolation;
use App\Services\Agriculture\Research\IntegrationClassification\Persistence\IntegrationClassificationLifecycleState;
use App\Services\Agriculture\Research\IntegrationClassification\Persistence\IntegrationClassificationPersistenceContract;
use App\Services\Agriculture\Research\IntegrationClassification\Persistence\IntegrationClassificationRecord;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\IntegrationClassificationFixtures;
use Tests\TestCase;

/**
 * IC Persistence on PostgreSQL with real commits and independent connections
 * (phpunit.pgsql.xml only). The competing request runs on PEER from a model event at the
 * exact interleaving point; lock waits are bounded by lock_timeout instead of sleeping.
 * A competitor that must wait for a lock and then continue runs in a worker process
 * (tests/Pgsql/bin); the test proceeds only once PostgreSQL reports that process blocked by it.
 */
final class IntegrationClassificationPostgresConcurrencyTest extends TestCase
{
    use IntegrationClassificationFixtures;

    private const PEER = 'pgsql_testing_peer';

    private const TABLE = IntegrationClassificationPersistenceContract::TABLE;

    private const WORKER_LOCK_WAIT_DEADLINE_SECONDS = 20.0;

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
        CghiaIntegrationClassification::flushEventListeners();
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

    public function test_single_active_index_is_a_unique_partial_index_on_adr_id(): void
    {
        $definition = DB::selectOne(
            'SELECT indexdef FROM pg_indexes WHERE schemaname = current_schema() AND tablename = ? AND indexname = ?',
            [self::TABLE, IntegrationClassificationPersistenceContract::SINGLE_ACTIVE_INDEX]
        )?->indexdef;

        $this->assertIsString($definition);
        $this->assertStringContainsString('CREATE UNIQUE INDEX', $definition);
        $this->assertStringContainsString('(adr_id)', $definition);
        $this->assertStringContainsString("WHERE ((lifecycle_state)::text = 'ACTIVE'::text)", $definition);
    }

    public function test_driver_messages_name_the_violated_constraint(): void
    {
        $this->insertRawRow('ABSTRACT_IC_PG_MSG', 'ic:pg:msg:a', IntegrationClassificationLifecycleState::ACTIVE);

        $singleActive = $this->captureQueryException(
            fn () => $this->insertRawRow('ABSTRACT_IC_PG_MSG', 'ic:pg:msg:b', IntegrationClassificationLifecycleState::ACTIVE)
        );
        $this->assertSame('23505', $singleActive->errorInfo[0]);
        $this->assertStringContainsString('"'.IntegrationClassificationPersistenceContract::SINGLE_ACTIVE_INDEX.'"', $singleActive->errorInfo[2]);

        $duplicateKey = $this->captureQueryException(
            fn () => $this->insertRawRow('ABSTRACT_IC_PG_MSG_OTHER', 'ic:pg:msg:a', IntegrationClassificationLifecycleState::SUPERSEDED)
        );
        $this->assertSame('23505', $duplicateKey->errorInfo[0]);
        $this->assertStringContainsString('"'.self::TABLE.'_idempotency_key_unique"', $duplicateKey->errorInfo[2]);
    }

    public function test_postgres_rejects_statements_after_an_error_inside_a_transaction(): void
    {
        $this->insertRawRow('ABSTRACT_IC_PG_ABORT', 'ic:pg:abort:a', IntegrationClassificationLifecycleState::ACTIVE);

        DB::beginTransaction();
        try {
            $violation = $this->captureQueryException(
                fn () => $this->insertRawRow('ABSTRACT_IC_PG_ABORT', 'ic:pg:abort:b', IntegrationClassificationLifecycleState::ACTIVE)
            );
            $this->assertSame('23505', $violation->errorInfo[0]);

            $aborted = $this->captureQueryException(fn () => DB::select('SELECT 1'));
            $this->assertSame('25P02', $aborted->errorInfo[0]);
        } finally {
            DB::rollBack();
        }
    }

    public function test_concurrent_first_persist_with_different_keys_leaves_one_active(): void
    {
        $this->beforeFirstInsert(fn () => $this->onPeer(fn () => $this->repo->persist(...$this->payload([
            'idempotencyKey' => 'ic:pg:race:b',
            'evidenceFingerprint' => 'fp-pg-race-b',
        ]))));

        try {
            $this->repo->persist(...$this->payload(['idempotencyKey' => 'ic:pg:race:a']));
            $this->fail('Expected IntegrationClassificationActiveConflict.');
        } catch (IntegrationClassificationActiveConflict $e) {
            $previous = $e->getPrevious();
            $this->assertInstanceOf(QueryException::class, $previous);
            $this->assertSame('23505', $previous->errorInfo[0]);
        }

        $active = $this->activeRows('ABSTRACT_IC_INTEGRITY');
        $this->assertCount(1, $active);
        $this->assertSame('ic:pg:race:b', $active[0]->idempotency_key);
        $this->assertSame(0, $this->rowsWithKey('ic:pg:race:a'));
    }

    public function test_concurrent_same_key_same_contract_replays_the_committed_record(): void
    {
        $peerRecord = null;
        $this->beforeFirstInsert(function () use (&$peerRecord): void {
            $peerRecord = $this->onPeer(fn () => $this->repo->persist(...$this->payload()));
        });

        $record = $this->repo->persist(...$this->payload());

        $this->assertInstanceOf(IntegrationClassificationRecord::class, $peerRecord);
        $this->assertTrue($record->persistenceRecordId->equals($peerRecord->persistenceRecordId));
        $this->assertSame(1, $this->rowsWithKey('ic:integrity'));
    }

    public function test_concurrent_same_key_different_contract_raises_idempotency_conflict(): void
    {
        $this->beforeFirstInsert(fn () => $this->onPeer(fn () => $this->repo->persist(...$this->payload([
            'evidenceFingerprint' => 'fp-pg-other-contract',
        ]))));

        try {
            $this->repo->persist(...$this->payload());
            $this->fail('Expected IntegrationClassificationIdempotencyConflict.');
        } catch (IntegrationClassificationIdempotencyConflict $e) {
            $this->assertStringContainsString('evidence_fingerprint', $e->getMessage());
        }

        $this->assertSame(1, CghiaIntegrationClassification::query()->count());
    }

    public function test_unique_violation_inside_caller_transaction_does_not_poison_it(): void
    {
        $this->beforeFirstInsert(fn () => $this->onPeer(fn () => $this->repo->persist(...$this->payload())));

        $record = DB::transaction(function (): IntegrationClassificationRecord {
            $record = $this->repo->persist(...$this->payload());
            DB::select('SELECT 1');

            return $record;
        });

        $this->assertSame('ic:integrity', $record->idempotencyKey);
        $this->assertSame(1, $this->rowsWithKey('ic:integrity'));
    }

    public function test_supersede_sql_error_rolls_back_and_surfaces_original_sqlstate(): void
    {
        $prior = $this->repo->persist(...$this->payload());

        $this->beforeFirstInsert(fn () => DB::statement('SELECT 1/0'));

        $error = $this->captureQueryException(fn () => $this->repo->supersede(...$this->supersedePayload($prior)));
        $this->assertSame('22012', $error->errorInfo[0]);

        DB::select('SELECT 1');
        $reloaded = $this->repo->findById($prior->persistenceRecordId);
        $this->assertSame(IntegrationClassificationLifecycleState::ACTIVE, $reloaded?->lifecycleState);
        $this->assertNull($reloaded?->supersededBy);
        $this->assertSame(1, CghiaIntegrationClassification::query()->count());
    }

    public function test_supersede_key_collision_with_committed_record_of_another_adr_raises_idempotency_conflict(): void
    {
        $prior = $this->repo->persist(...$this->payload());

        $competitor = null;
        $this->beforeFirstInsert(function () use (&$competitor): void {
            $competitor = $this->onPeer(fn () => $this->repo->persist(...$this->payload([
                'adrId' => AdrMembershipId::fromString('ABSTRACT_IC_INTEGRITY_OTHER'),
                'idempotencyKey' => 'ic:integrity:v2',
            ])));
        });

        try {
            $this->repo->supersede(...$this->supersedePayload($prior));
            $this->fail('Expected IntegrationClassificationIdempotencyConflict.');
        } catch (IntegrationClassificationIdempotencyConflict $e) {
            $this->assertStringContainsString('mismatched fields: [adr_id, evidence_fingerprint]', $e->getMessage());
        }

        $this->assertInstanceOf(IntegrationClassificationRecord::class, $competitor);
        $reloadedPrior = $this->repo->findById($prior->persistenceRecordId);
        $this->assertSame(IntegrationClassificationLifecycleState::ACTIVE, $reloadedPrior?->lifecycleState);
        $this->assertNull($reloadedPrior?->supersededBy);
        $active = $this->activeRows('ABSTRACT_IC_INTEGRITY');
        $this->assertCount(1, $active);
        $this->assertSame($prior->persistenceRecordId->toInt(), (int) $active[0]->id);
        $this->assertSame('ABSTRACT_IC_INTEGRITY_OTHER', DB::table(self::TABLE)->where('idempotency_key', 'ic:integrity:v2')->value('adr_id'));
        $this->assertSame(2, CghiaIntegrationClassification::query()->count());
    }

    public function test_concurrent_supersede_of_same_prior_is_serialized_by_row_lock(): void
    {
        $prior = $this->repo->persist(...$this->payload());
        $this->boundPeerLockWait();
        $competitor = $this->supersedePayload($prior, [
            'newIdempotencyKey' => 'ic:pg:supersede:b',
            'evidenceFingerprint' => 'fp-pg-supersede-b',
        ]);

        $blocked = null;
        $this->beforeFirstInsert(function () use ($competitor, &$blocked): void {
            $blocked = $this->captureQueryException(fn () => $this->onPeer(fn () => $this->repo->supersede(...$competitor)));
        });

        $winner = $this->repo->supersede(...$this->supersedePayload($prior, ['newIdempotencyKey' => 'ic:pg:supersede:a']));

        $this->assertInstanceOf(QueryException::class, $blocked);
        $this->assertSame('55P03', $blocked->errorInfo[0]);

        try {
            $this->onPeer(fn () => $this->repo->supersede(...$competitor));
            $this->fail('Expected the late supersede to be rejected.');
        } catch (IntegrationClassificationInvariantViolation $e) {
            $this->assertStringContainsString('not ACTIVE', $e->getMessage());
        }

        $active = $this->activeRows('ABSTRACT_IC_INTEGRITY');
        $this->assertCount(1, $active);
        $this->assertSame($winner->persistenceRecordId->toInt(), (int) $active[0]->id);
        $this->assertSame($winner->persistenceRecordId->value, $this->repo->findById($prior->persistenceRecordId)?->supersededBy?->value);
        $this->assertSame(0, $this->rowsWithKey('ic:pg:supersede:b'));
    }

    public function test_invalidate_blocked_by_supersede_rechecks_lifecycle_after_commit_without_lost_update(): void
    {
        $prior = $this->repo->persist(...$this->payload());

        $worker = null;
        $this->beforeFirstInsert(function () use ($prior, &$worker): void {
            $worker = $this->startInvalidateWorker($prior->persistenceRecordId->toInt());
            $this->awaitWorkerBlockedByThisConnection($worker);
        });

        $next = $this->repo->supersede(...$this->supersedePayload($prior));
        $result = $this->finishWorker($worker);

        $this->assertSame('threw', $result['outcome'], 'invalidate overwrote the committed supersede: '.json_encode($result));
        $this->assertSame(IntegrationClassificationInvariantViolation::class, $result['class']);
        $this->assertStringContainsString('only ACTIVE', $result['message']);

        $reloadedPrior = $this->repo->findById($prior->persistenceRecordId);
        $this->assertSame(IntegrationClassificationLifecycleState::SUPERSEDED, $reloadedPrior?->lifecycleState);
        $this->assertTrue($reloadedPrior?->supersededBy?->equals($next->persistenceRecordId));
        $this->assertSame(IntegrationClassificationLifecycleState::ACTIVE, $this->repo->findById($next->persistenceRecordId)?->lifecycleState);
        $this->assertCount(1, $this->activeRows('ABSTRACT_IC_INTEGRITY'));
        $this->assertSame(2, CghiaIntegrationClassification::query()->count());
    }

    /**
     * Starting from ACTIVE, persist() rejects at its ACTIVE pre-check before writing anything: an
     * open supersede leaves the committed prior visibly ACTIVE, a committed one leaves the replacement.
     */
    #[DataProvider('openSupersedeInterleavings')]
    public function test_persist_racing_open_supersede_is_rejected_and_supersede_commits(string $event, string $competitorKey): void
    {
        $prior = $this->repo->persist(...$this->payload());

        $competitor = null;
        $this->onceOnModelEvent($event, function () use ($competitorKey, &$competitor): void {
            try {
                $competitor = $this->onPeer(fn () => $this->repo->persist(...$this->payload([
                    'idempotencyKey' => $competitorKey,
                    'evidenceFingerprint' => 'fp-pg-persist-vs-supersede',
                ])));
            } catch (\Throwable $e) {
                $competitor = $e;
            }
        });

        $next = $this->repo->supersede(...$this->supersedePayload($prior));

        $this->assertInstanceOf(IntegrationClassificationActiveConflict::class, $competitor);
        $this->assertSupersedeCommittedAlone($prior, $next);
        $this->assertSame($competitorKey === 'ic:integrity:v2' ? 1 : 0, $this->rowsWithKey($competitorKey));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function openSupersedeInterleavings(): array
    {
        return [
            'prior already SUPERSEDED, replacement not inserted' => ['creating', 'ic:pg:persist-vs-supersede'],
            'replacement inserted, pointer not set' => ['created', 'ic:pg:persist-vs-supersede'],
            'same key as the uncommitted replacement' => ['created', 'ic:integrity:v2'],
        ];
    }

    public function test_persist_in_transaction_opened_before_supersede_commits_is_rejected(): void
    {
        $prior = $this->repo->persist(...$this->payload());

        DB::beginTransaction();
        try {
            DB::select('SELECT 1');
            $next = $this->onPeer(fn () => $this->repo->supersede(...$this->supersedePayload($prior)));

            try {
                $this->repo->persist(...$this->payload([
                    'idempotencyKey' => 'ic:pg:persist-vs-supersede',
                    'evidenceFingerprint' => 'fp-pg-persist-vs-supersede',
                ]));
                $this->fail('Expected IntegrationClassificationActiveConflict.');
            } catch (IntegrationClassificationActiveConflict) {
            }

            DB::select('SELECT 1');
        } finally {
            DB::rollBack();
        }

        $this->assertSupersedeCommittedAlone($prior, $next);
        $this->assertSame(0, $this->rowsWithKey('ic:pg:persist-vs-supersede'));
    }

    public function test_supersede_waits_for_invalidate_and_then_rejects_without_lost_update(): void
    {
        $prior = $this->repo->persist(...$this->payload());
        $this->boundPeerLockWait();

        $blocked = null;
        DB::transaction(function () use ($prior, &$blocked): void {
            $this->repo->invalidate($prior->persistenceRecordId);
            $blocked = $this->captureQueryException(fn () => $this->onPeer(fn () => $this->repo->supersede(...$this->supersedePayload($prior))));
        });

        $this->assertInstanceOf(QueryException::class, $blocked);
        $this->assertSame('55P03', $blocked->errorInfo[0]);

        try {
            $this->onPeer(fn () => $this->repo->supersede(...$this->supersedePayload($prior)));
            $this->fail('Expected the late supersede to be rejected.');
        } catch (IntegrationClassificationInvariantViolation $e) {
            $this->assertStringContainsString('not ACTIVE', $e->getMessage());
        }

        $this->assertSame(IntegrationClassificationLifecycleState::INVALIDATED, $this->repo->findById($prior->persistenceRecordId)?->lifecycleState);
        $this->assertSame([], $this->activeRows('ABSTRACT_IC_INTEGRITY'));
        $this->assertSame(1, CghiaIntegrationClassification::query()->count());
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

    private function boundPeerLockWait(): void
    {
        DB::connection(self::PEER)->statement("SET lock_timeout = '500ms'");
    }

    /**
     * Runs $callback once, inside the repository transaction, right before its first INSERT.
     */
    private function beforeFirstInsert(callable $callback): void
    {
        $this->onceOnModelEvent('creating', $callback);
    }

    private function onceOnModelEvent(string $event, callable $callback): void
    {
        $fired = false;
        $listener = function () use (&$fired, $callback): void {
            if ($fired) {
                return;
            }
            $fired = true;
            $callback();
        };

        match ($event) {
            'creating' => CghiaIntegrationClassification::creating($listener),
            'created' => CghiaIntegrationClassification::created($listener),
        };
    }

    /**
     * @return array{process: resource, stdout: resource, stderr: resource, pid: int}
     */
    private function startInvalidateWorker(int $recordId): array
    {
        $process = proc_open(
            [PHP_BINARY, base_path('tests/Pgsql/bin/ic-invalidate-worker.php'), (string) $recordId],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
        );
        $this->assertIsResource($process);
        fclose($pipes[0]);

        $line = fgets($pipes[1]);
        if (! is_string($line) || preg_match('/^pid (\d+)$/', trim($line), $match) !== 1) {
            $this->fail('IC invalidate worker did not start: '.trim((string) $line.stream_get_contents($pipes[2])));
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
                $this->fail('IC invalidate worker finished without waiting for the lock: '.stream_get_contents($worker['stdout']));
            }
            usleep(10_000);
        } while (microtime(true) < $deadline);

        $this->fail('IC invalidate worker was never blocked by this connection.');
    }

    /**
     * @param  array{process: resource, stdout: resource, stderr: resource, pid: int}|null  $worker
     * @return array<string, mixed>
     */
    private function finishWorker(?array $worker): array
    {
        $this->assertNotNull($worker, 'The competing invalidate was never started.');

        $output = trim((string) stream_get_contents($worker['stdout']));
        $result = json_decode($output, true);
        $this->assertIsArray($result, 'IC invalidate worker output: '.$output.' '.stream_get_contents($worker['stderr']));

        return $result;
    }

    private function assertSupersedeCommittedAlone(IntegrationClassificationRecord $prior, IntegrationClassificationRecord $next): void
    {
        $reloadedPrior = $this->repo->findById($prior->persistenceRecordId);
        $this->assertSame(IntegrationClassificationLifecycleState::SUPERSEDED, $reloadedPrior?->lifecycleState);
        $this->assertTrue($reloadedPrior?->supersededBy?->equals($next->persistenceRecordId));
        foreach (IntegrationClassificationPersistenceContract::REPLAY_CONTRACT_FIELDS as $field) {
            $this->assertSame($prior->toArray()[$field] ?? null, $reloadedPrior?->toArray()[$field] ?? null, "prior {$field} changed.");
        }

        $active = $this->activeRows('ABSTRACT_IC_INTEGRITY');
        $this->assertCount(1, $active);
        $this->assertSame($next->persistenceRecordId->toInt(), (int) $active[0]->id);
        $this->assertNull($active[0]->superseded_by);

        $target = DB::table(self::TABLE)->where('id', $reloadedPrior?->supersededBy?->toInt())->first();
        $this->assertNotNull($target);
        $this->assertNotSame((int) $target->id, $prior->persistenceRecordId->toInt());
        $this->assertSame('ABSTRACT_IC_INTEGRITY', $target->adr_id);
        $this->assertSame(2, CghiaIntegrationClassification::query()->count());
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

    private function captureQueryException(callable $callback): QueryException
    {
        try {
            $callback();
        } catch (QueryException $e) {
            return $e;
        }

        $this->fail('Expected a QueryException.');
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
