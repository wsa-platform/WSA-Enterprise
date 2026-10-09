<?php

namespace Tests\Unit\Agriculture\Research\IntegrationClassification\Persistence;

use App\Models\CghiaIntegrationClassification;
use App\Services\Agriculture\Research\Identity\AdrMembershipId;
use App\Services\Agriculture\Research\Identity\CanonicalSourceIdentityId;
use App\Services\Agriculture\Research\IntegrationClassification\ClassificationDecisionIdentity;
use App\Services\Agriculture\Research\IntegrationClassification\ClassificationStatus;
use App\Services\Agriculture\Research\IntegrationClassification\IntegrationBoundary;
use App\Services\Agriculture\Research\IntegrationClassification\IntegrationNature;
use App\Services\Agriculture\Research\IntegrationClassification\Persistence\EloquentIntegrationClassificationRepository;
use App\Services\Agriculture\Research\IntegrationClassification\Persistence\IntegrationClassificationActiveConflict;
use App\Services\Agriculture\Research\IntegrationClassification\Persistence\IntegrationClassificationIdempotencyConflict;
use App\Services\Agriculture\Research\IntegrationClassification\Persistence\IntegrationClassificationInvariantViolation;
use App\Services\Agriculture\Research\IntegrationClassification\Persistence\IntegrationClassificationLifecycleState;
use App\Services\Agriculture\Research\IntegrationClassification\Persistence\IntegrationClassificationPersistenceContract;
use App\Services\Agriculture\Research\IntegrationClassification\Persistence\IntegrationClassificationRecord;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\IntegrationClassificationFixtures;
use Tests\TestCase;

/**
 * IC Persistence integrity (ADR-023 D1–D6) — engine-agnostic; runs on sqlite (phpunit.xml)
 * and on PostgreSQL (phpunit.pgsql.xml). Synthetic fixture identifiers only.
 */
final class IntegrationClassificationPersistenceIntegrityTest extends TestCase
{
    use IntegrationClassificationFixtures;
    use RefreshDatabase;

    private const TABLE = IntegrationClassificationPersistenceContract::TABLE;

    private const MIGRATION = 'migrations/2026_10_09_190000_add_single_active_index_to_cghia_integration_classification_records.php';

    private EloquentIntegrationClassificationRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new EloquentIntegrationClassificationRepository;
    }

    protected function tearDown(): void
    {
        CghiaIntegrationClassification::flushEventListeners();
        parent::tearDown();
    }

    // --- D1: database-level single-ACTIVE constraint -------------------------------------------

    public function test_single_active_partial_unique_index_exists(): void
    {
        $this->assertTrue($this->singleActiveIndexExists());
    }

    public function test_database_rejects_second_active_row_for_same_adr(): void
    {
        $this->insertRawRow('ABSTRACT_IC_DB_ACTIVE', 'ic:db:a', IntegrationClassificationLifecycleState::ACTIVE);

        try {
            DB::transaction(fn () => $this->insertRawRow('ABSTRACT_IC_DB_ACTIVE', 'ic:db:b', IntegrationClassificationLifecycleState::ACTIVE));
            $this->fail('Expected the single-ACTIVE index to reject the second ACTIVE row.');
        } catch (QueryException $e) {
            $this->assertContains((string) $e->errorInfo[0], ['23505', '23000']);
        }

        $this->assertSame(1, $this->activeCount('ABSTRACT_IC_DB_ACTIVE'));
    }

    public function test_database_allows_history_rows_and_independent_seats(): void
    {
        $this->insertRawRow('ABSTRACT_IC_DB_HIST', 'ic:db:h1', IntegrationClassificationLifecycleState::SUPERSEDED);
        $this->insertRawRow('ABSTRACT_IC_DB_HIST', 'ic:db:h2', IntegrationClassificationLifecycleState::SUPERSEDED);
        $this->insertRawRow('ABSTRACT_IC_DB_HIST', 'ic:db:h3', IntegrationClassificationLifecycleState::INVALIDATED);
        $this->insertRawRow('ABSTRACT_IC_DB_HIST', 'ic:db:h4', IntegrationClassificationLifecycleState::ACTIVE);
        $this->insertRawRow('ABSTRACT_IC_DB_OTHER', 'ic:db:o1', IntegrationClassificationLifecycleState::ACTIVE);

        $this->assertSame(1, $this->activeCount('ABSTRACT_IC_DB_HIST'));
        $this->assertSame(1, $this->activeCount('ABSTRACT_IC_DB_OTHER'));
        $this->assertSame(5, CghiaIntegrationClassification::query()->count());
    }

    public function test_zero_active_is_valid_and_not_auto_created(): void
    {
        $this->insertRawRow('ABSTRACT_IC_DB_ZERO', 'ic:db:z1', IntegrationClassificationLifecycleState::INVALIDATED);

        $this->assertNull($this->repo->findCurrentByAdrId(AdrMembershipId::fromString('ABSTRACT_IC_DB_ZERO')));
        $this->assertSame(1, CghiaIntegrationClassification::query()->count());
    }

    // --- D2: error classification and rollback -------------------------------------------------

    public function test_persist_translates_single_active_index_violation_to_active_conflict(): void
    {
        $fired = false;
        CghiaIntegrationClassification::creating(function () use (&$fired): void {
            if ($fired) {
                return;
            }
            $fired = true;
            $this->insertRawRow('ABSTRACT_IC_RACE', 'ic:race:competitor', IntegrationClassificationLifecycleState::ACTIVE);
        });

        try {
            $this->repo->persist(...$this->payload([
                'adrId' => AdrMembershipId::fromString('ABSTRACT_IC_RACE'),
                'idempotencyKey' => 'ic:race:a',
            ]));
            $this->fail('Expected IntegrationClassificationActiveConflict.');
        } catch (IntegrationClassificationActiveConflict $e) {
            $this->assertInstanceOf(QueryException::class, $e->getPrevious());
            $this->assertStringContainsString('second ACTIVE', $e->getMessage());
        }

        $this->assertTrue($fired);
        $this->assertSame(0, CghiaIntegrationClassification::query()->where('idempotency_key', 'ic:race:a')->count());
        $this->assertLifecycleGraphIntegrity();
    }

    public function test_supersede_insert_failure_rolls_back_and_preserves_original_error(): void
    {
        $prior = $this->repo->persist(...$this->payload());

        $attempts = 0;
        CghiaIntegrationClassification::creating(function () use (&$attempts): void {
            $attempts++;
            throw new \RuntimeException('insert-failed');
        });

        try {
            $this->repo->supersede(...$this->supersedePayload($prior));
            $this->fail('Expected the injected insert failure.');
        } catch (\RuntimeException $e) {
            $this->assertSame('insert-failed', $e->getMessage());
        }

        $this->assertSame(1, $attempts, 'A failed write must not be retried.');
        $this->assertPriorUntouchedAndActive($prior);
        $this->assertSame(0, CghiaIntegrationClassification::query()->where('idempotency_key', 'ic:integrity:v2')->count());
        $this->assertLifecycleGraphIntegrity();
    }

    public function test_supersede_pointer_update_failure_rolls_back_whole_transition(): void
    {
        $prior = $this->repo->persist(...$this->payload());

        CghiaIntegrationClassification::updating(function (CghiaIntegrationClassification $row): void {
            if ($row->isDirty('superseded_by') && $row->superseded_by !== null) {
                throw new \RuntimeException('pointer-write-failed');
            }
        });

        try {
            $this->repo->supersede(...$this->supersedePayload($prior));
            $this->fail('Expected the injected pointer failure.');
        } catch (\RuntimeException $e) {
            $this->assertSame('pointer-write-failed', $e->getMessage());
        }

        $this->assertPriorUntouchedAndActive($prior);
        $this->assertSame(0, CghiaIntegrationClassification::query()->where('idempotency_key', 'ic:integrity:v2')->count());
        $this->assertLifecycleGraphIntegrity();
    }

    public function test_supersede_translates_single_active_index_violation_to_active_conflict(): void
    {
        $prior = $this->repo->persist(...$this->payload());

        $fired = false;
        CghiaIntegrationClassification::creating(function () use (&$fired): void {
            if ($fired) {
                return;
            }
            $fired = true;
            $this->insertRawRow('ABSTRACT_IC_INTEGRITY', 'ic:race:supersede-competitor', IntegrationClassificationLifecycleState::ACTIVE);
        });

        try {
            $this->repo->supersede(...$this->supersedePayload($prior));
            $this->fail('Expected IntegrationClassificationActiveConflict.');
        } catch (IntegrationClassificationActiveConflict $e) {
            $previous = $e->getPrevious();
            $this->assertInstanceOf(QueryException::class, $previous);
            $this->assertContains((string) $previous->errorInfo[0], ['23505', '23000']);
            $this->assertStringContainsString('another ACTIVE', $e->getMessage());
        }

        $this->assertTrue($fired);
        $this->assertPriorUntouchedAndActive($prior);
        $this->assertSame(0, CghiaIntegrationClassification::query()->where('idempotency_key', 'ic:integrity:v2')->count());
        $this->assertSame(0, CghiaIntegrationClassification::query()->where('idempotency_key', 'ic:race:supersede-competitor')->count());
        $this->assertLifecycleGraphIntegrity();
    }

    public function test_supersede_key_violation_without_committed_owner_rethrows_original_error(): void
    {
        $prior = $this->repo->persist(...$this->payload());

        $fired = false;
        CghiaIntegrationClassification::creating(function () use (&$fired): void {
            if ($fired) {
                return;
            }
            $fired = true;
            $this->insertRawRow('ABSTRACT_IC_INTEGRITY_OTHER', 'ic:integrity:v2', IntegrationClassificationLifecycleState::SUPERSEDED);
        });

        try {
            $this->repo->supersede(...$this->supersedePayload($prior));
            $this->fail('Expected the unique violation to propagate.');
        } catch (QueryException $e) {
            $this->assertContains((string) $e->errorInfo[0], ['23505', '23000']);
        }

        $this->assertTrue($fired);
        $this->assertPriorUntouchedAndActive($prior);
        $this->assertSame(0, CghiaIntegrationClassification::query()->where('idempotency_key', 'ic:integrity:v2')->count());
        $this->assertLifecycleGraphIntegrity();
    }

    public function test_retry_after_partial_supersede_failure_succeeds_once(): void
    {
        $prior = $this->repo->persist(...$this->payload());

        $failNext = true;
        CghiaIntegrationClassification::creating(function () use (&$failNext): void {
            if ($failNext) {
                $failNext = false;
                throw new \RuntimeException('transient-insert-failure');
            }
        });

        try {
            $this->repo->supersede(...$this->supersedePayload($prior));
            $this->fail('Expected the injected transient failure.');
        } catch (\RuntimeException $e) {
            $this->assertSame('transient-insert-failure', $e->getMessage());
        }
        $this->assertPriorUntouchedAndActive($prior);

        $next = $this->repo->supersede(...$this->supersedePayload($prior));
        $again = $this->repo->supersede(...$this->supersedePayload($prior));

        $this->assertTrue($again->persistenceRecordId->equals($next->persistenceRecordId));
        $this->assertSame(1, CghiaIntegrationClassification::query()->where('idempotency_key', 'ic:integrity:v2')->count());
        $this->assertSame(1, $this->activeCount('ABSTRACT_IC_INTEGRITY'));
        $this->assertLifecycleGraphIntegrity();
    }

    public function test_retry_after_failed_persist_succeeds_without_duplicate(): void
    {
        $failNext = true;
        CghiaIntegrationClassification::creating(function () use (&$failNext): void {
            if ($failNext) {
                $failNext = false;
                throw new \RuntimeException('transient-insert-failure');
            }
        });

        try {
            $this->repo->persist(...$this->payload());
            $this->fail('Expected the injected transient failure.');
        } catch (\RuntimeException $e) {
            $this->assertSame('transient-insert-failure', $e->getMessage());
        }
        $this->assertSame(0, CghiaIntegrationClassification::query()->count());

        $first = $this->repo->persist(...$this->payload());
        $replayed = $this->repo->persist(...$this->payload());

        $this->assertTrue($first->persistenceRecordId->equals($replayed->persistenceRecordId));
        $this->assertSame(1, CghiaIntegrationClassification::query()->count());
    }

    public function test_retry_after_active_conflict_is_deterministic_and_writes_nothing(): void
    {
        $this->repo->persist(...$this->payload());
        $before = $this->tableSnapshot();

        foreach ([1, 2] as $attempt) {
            try {
                $this->repo->persist(...$this->payload([
                    'idempotencyKey' => 'ic:integrity:second-active',
                    'evidenceFingerprint' => 'fp-integrity-second',
                ]));
                $this->fail("Attempt {$attempt}: expected IntegrationClassificationActiveConflict.");
            } catch (IntegrationClassificationActiveConflict $e) {
                $this->assertStringContainsString('use supersede()', $e->getMessage());
            }
        }

        $this->assertSame($before, $this->tableSnapshot());
    }

    public function test_invalidate_rejects_non_active_and_keeps_payload(): void
    {
        $prior = $this->repo->persist(...$this->payload());
        $this->repo->supersede(...$this->supersedePayload($prior));

        try {
            $this->repo->invalidate($prior->persistenceRecordId);
            $this->fail('Expected rejection of invalidating a SUPERSEDED record.');
        } catch (IntegrationClassificationInvariantViolation $e) {
            $this->assertStringContainsString('only ACTIVE', $e->getMessage());
        }

        $reloaded = $this->repo->findById($prior->persistenceRecordId);
        $this->assertSame(IntegrationClassificationLifecycleState::SUPERSEDED, $reloaded?->lifecycleState);
        $this->assertLifecycleGraphIntegrity();
    }

    public function test_persist_after_invalidate_creates_new_active_and_keeps_history(): void
    {
        $adrId = AdrMembershipId::fromString('ABSTRACT_IC_INTEGRITY');
        $first = $this->repo->persist(...$this->payload());

        $invalidated = $this->repo->invalidate($first->persistenceRecordId);
        $this->assertSame(IntegrationClassificationLifecycleState::INVALIDATED, $invalidated->lifecycleState);
        $this->assertNull($this->repo->findCurrentByAdrId($adrId));
        $this->assertSame(0, $this->activeCount('ABSTRACT_IC_INTEGRITY'));
        $invalidatedRow = $this->tableSnapshot();

        $nextPayload = $this->payload([
            'idempotencyKey' => 'ic:integrity:after-invalidate',
            'evidenceFingerprint' => 'fp-integrity-after-invalidate',
        ]);
        $next = $this->repo->persist(...$nextPayload);

        $this->assertFalse($next->persistenceRecordId->equals($first->persistenceRecordId));
        $this->assertSame(IntegrationClassificationLifecycleState::ACTIVE, $next->lifecycleState);
        $this->assertTrue($this->repo->findCurrentByAdrId($adrId)?->persistenceRecordId->equals($next->persistenceRecordId));
        $this->assertSame($invalidatedRow, array_slice($this->tableSnapshot(), 0, 1));
        $this->assertNull($this->repo->findById($first->persistenceRecordId)?->supersededBy);

        $afterNext = $this->tableSnapshot();
        $replay = $this->repo->persist(...$nextPayload);

        $this->assertTrue($replay->persistenceRecordId->equals($next->persistenceRecordId));
        $this->assertSame(IntegrationClassificationLifecycleState::ACTIVE, $replay->lifecycleState);
        $this->assertSame($afterNext, $this->tableSnapshot());
        $this->assertCount(2, $afterNext);
        $this->assertSame(1, $this->activeCount('ABSTRACT_IC_INTEGRITY'));
        $this->assertLifecycleGraphIntegrity();
    }

    // --- D3: idempotency replay requires contract match ----------------------------------------

    public function test_identical_replay_returns_original_without_writes(): void
    {
        $first = $this->repo->persist(...$this->payload());
        $before = $this->tableSnapshot();

        $second = $this->repo->persist(...$this->payload());

        $this->assertTrue($first->persistenceRecordId->equals($second->persistenceRecordId));
        $this->assertSame($before, $this->tableSnapshot());
    }

    public function test_normalization_equivalent_payload_replays(): void
    {
        $first = $this->repo->persist(...$this->payload());

        $second = $this->repo->persist(...$this->payload([
            'accessModalityClaims' => [' oai_pmh ', 'api', 'api'],
            'licenseReference' => '  license://integrity  ',
            'idempotencyKey' => '  ic:integrity  ',
        ]));

        $this->assertTrue($first->persistenceRecordId->equals($second->persistenceRecordId));
        $this->assertSame(1, CghiaIntegrationClassification::query()->count());
    }

    public function test_reordered_evidence_references_replay_without_writes(): void
    {
        $first = $this->repo->persist(...$this->payload());
        $before = $this->tableSnapshot();

        $second = $this->repo->persist(...$this->payload([
            'evidenceReferences' => ['evidence://integrity/b', 'evidence://integrity/a'],
        ]));

        $this->assertTrue($first->persistenceRecordId->equals($second->persistenceRecordId));
        $this->assertSame(['evidence://integrity/a', 'evidence://integrity/b'], $second->evidenceReferences);
        $this->assertSame($before, $this->tableSnapshot());
    }

    public function test_evidence_references_keep_stored_order_and_replay_in_any_order(): void
    {
        $stored = ['evidence://integrity/b', 'evidence://integrity/a'];
        $first = $this->repo->persist(...$this->payload(['evidenceReferences' => $stored]));
        $before = $this->tableSnapshot();

        foreach ([
            ['evidence://integrity/a', 'evidence://integrity/b'],
            $stored,
            ['  evidence://integrity/a  ', 'evidence://integrity/b'],
        ] as $references) {
            $replayed = $this->repo->persist(...$this->payload(['evidenceReferences' => $references]));

            $this->assertTrue($replayed->persistenceRecordId->equals($first->persistenceRecordId));
            $this->assertSame($stored, $replayed->evidenceReferences);
        }

        $this->assertSame($before, $this->tableSnapshot());
    }

    /**
     * @param  list<string>  $references
     */
    #[DataProvider('changedEvidenceReferenceSets')]
    public function test_changed_evidence_reference_set_raises_idempotency_conflict(array $references): void
    {
        $this->repo->persist(...$this->payload());
        $before = $this->tableSnapshot();

        try {
            $this->repo->persist(...$this->payload(['evidenceReferences' => $references]));
            $this->fail('Expected IntegrationClassificationIdempotencyConflict.');
        } catch (IntegrationClassificationIdempotencyConflict $e) {
            $this->assertStringContainsString('mismatched fields: [evidence_references]', $e->getMessage());
        }

        $this->assertSame($before, $this->tableSnapshot());
    }

    /**
     * @return array<string, array{list<string>}>
     */
    public static function changedEvidenceReferenceSets(): array
    {
        return [
            'one reference replaced' => [['evidence://integrity/a', 'evidence://integrity/c']],
            'reference added' => [['evidence://integrity/a', 'evidence://integrity/b', 'evidence://integrity/c']],
            'duplicate reference is not merged' => [['evidence://integrity/a', 'evidence://integrity/b', 'evidence://integrity/b']],
            'empty set' => [[]],
        ];
    }

    /**
     * @param  array<string, mixed>  $override
     */
    #[DataProvider('contractFieldMutations')]
    public function test_replay_with_different_contract_field_raises_idempotency_conflict(string $field, array $override): void
    {
        $this->repo->persist(...$this->payload());
        $before = $this->tableSnapshot();

        try {
            $this->repo->persist(...$this->payload($override));
            $this->fail("Expected IntegrationClassificationIdempotencyConflict for {$field}.");
        } catch (IntegrationClassificationIdempotencyConflict $e) {
            $this->assertStringContainsString($field, $e->getMessage());
        }

        $this->assertSame($before, $this->tableSnapshot());
    }

    /**
     * @return array<string, array{string, array<string, mixed>}>
     */
    public static function contractFieldMutations(): array
    {
        return [
            'adr_id' => ['adr_id', ['adrId' => AdrMembershipId::fromString('ABSTRACT_IC_INTEGRITY_OTHER')]],
            'classification_decision_identity' => ['classification_decision_identity', ['classificationDecisionIdentity' => ClassificationDecisionIdentity::fromString('ic-dec-integrity-other')]],
            'classification_status' => ['classification_status', ['classificationStatus' => ClassificationStatus::PARTIALLY_CLASSIFIED]],
            'canonical_identity_id' => ['canonical_identity_id', ['canonicalIdentityId' => CanonicalSourceIdentityId::fromString('cid_ic_integrity_other')]],
            'canonical_identity_id null' => ['canonical_identity_id', ['canonicalIdentityId' => null]],
            'identity_binding_ref' => ['identity_binding_ref', ['identityBindingRef' => 'binding://integrity/other']],
            'access_modality_claims' => ['access_modality_claims', ['accessModalityClaims' => ['api']]],
            'integration_nature' => ['integration_nature', ['integrationNature' => IntegrationNature::EXTERNAL_DEPENDENCY]],
            'integration_boundary' => ['integration_boundary', ['integrationBoundary' => IntegrationBoundary::STAGE3_ADAPTER]],
            'protocol_family' => ['protocol_family', ['protocolFamily' => 'sru']],
            'source_specific_requirement' => ['source_specific_requirement', ['sourceSpecificRequirement' => true]],
            'path_family_hint' => ['path_family_hint', ['pathFamilyHint' => 'P13']],
            'existing_adapter_reference' => ['existing_adapter_reference', ['existingAdapterReference' => 'adapter://integrity/other']],
            'external_dependency_reference' => ['external_dependency_reference', ['externalDependencyReference' => 'dependency://integrity/other']],
            'evidence_references' => ['evidence_references', ['evidenceReferences' => ['evidence://integrity/a']]],
            'evidence_fingerprint' => ['evidence_fingerprint', ['evidenceFingerprint' => 'fp-integrity-other']],
            'license_reference' => ['license_reference', ['licenseReference' => 'license://integrity/other']],
            'access_reference' => ['access_reference', ['accessReference' => 'access://integrity/other']],
            'reuse_reference' => ['reuse_reference', ['reuseReference' => 'reuse://integrity/other']],
        ];
    }

    /**
     * @param  array<string, mixed>  $override
     */
    #[DataProvider('nonContractFieldChanges')]
    public function test_replay_ignores_non_contract_fields_and_keeps_stored_values(array $override): void
    {
        $first = $this->repo->persist(...$this->payload());
        $before = $this->tableSnapshot();

        $second = $this->repo->persist(...$this->payload($override));

        $this->assertTrue($first->persistenceRecordId->equals($second->persistenceRecordId));
        $this->assertSame($first->toArray(), $second->toArray());
        $this->assertSame($before, $this->tableSnapshot());
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function nonContractFieldChanges(): array
    {
        return [
            'rationale' => [['rationale' => 'different free text']],
            'source_specific_rationale' => [['sourceSpecificRationale' => 'different source-specific text']],
            'decision_actor' => [['decisionActor' => 'another-actor']],
            'verified_at' => [['verifiedAt' => '2026-10-10T00:00:00Z']],
            'decision_timestamp' => [['decisionTimestamp' => '2026-10-10T00:00:00Z']],
            'metadata' => [['metadata' => ['note' => 'changed']]],
        ];
    }

    public function test_replay_of_superseded_record_returns_it_with_visible_lifecycle_and_no_reactivation(): void
    {
        $prior = $this->repo->persist(...$this->payload());
        $next = $this->repo->supersede(...$this->supersedePayload($prior));
        $before = $this->tableSnapshot();

        $replayed = $this->repo->persist(...$this->payload());

        $this->assertTrue($replayed->persistenceRecordId->equals($prior->persistenceRecordId));
        $this->assertSame(IntegrationClassificationLifecycleState::SUPERSEDED, $replayed->lifecycleState);
        $this->assertSame($next->persistenceRecordId->value, $replayed->supersededBy?->value);
        $this->assertSame($before, $this->tableSnapshot());
        $this->assertSame(
            $next->persistenceRecordId->value,
            $this->repo->findCurrentByAdrId($prior->adrId)?->persistenceRecordId->value
        );
    }

    public function test_replay_of_invalidated_record_returns_it_with_visible_lifecycle_and_no_reactivation(): void
    {
        $record = $this->repo->persist(...$this->payload());
        $this->repo->invalidate($record->persistenceRecordId);
        $before = $this->tableSnapshot();

        $replayed = $this->repo->persist(...$this->payload());

        $this->assertTrue($replayed->persistenceRecordId->equals($record->persistenceRecordId));
        $this->assertSame(IntegrationClassificationLifecycleState::INVALIDATED, $replayed->lifecycleState);
        $this->assertSame($before, $this->tableSnapshot());
        $this->assertNull($this->repo->findCurrentByAdrId($record->adrId));
    }

    public function test_reordered_replay_of_superseded_record_does_not_reactivate_it(): void
    {
        $prior = $this->repo->persist(...$this->payload());
        $next = $this->repo->supersede(...$this->supersedePayload($prior));
        $before = $this->tableSnapshot();

        $replayed = $this->repo->persist(...$this->payload([
            'evidenceReferences' => ['evidence://integrity/b', 'evidence://integrity/a'],
        ]));

        $this->assertTrue($replayed->persistenceRecordId->equals($prior->persistenceRecordId));
        $this->assertSame(IntegrationClassificationLifecycleState::SUPERSEDED, $replayed->lifecycleState);
        $this->assertSame($next->persistenceRecordId->value, $replayed->supersededBy?->value);
        $this->assertSame($before, $this->tableSnapshot());
    }

    // --- D4: supersede replay and lifecycle graph ----------------------------------------------

    public function test_supersede_retry_after_success_returns_replacement_without_writes(): void
    {
        $prior = $this->repo->persist(...$this->payload());
        $next = $this->repo->supersede(...$this->supersedePayload($prior));
        $before = $this->tableSnapshot();

        $again = $this->repo->supersede(...$this->supersedePayload($prior, ['decisionActor' => 'retrying-actor']));

        $this->assertTrue($again->persistenceRecordId->equals($next->persistenceRecordId));
        $this->assertSame($before, $this->tableSnapshot());
        $this->assertLifecycleGraphIntegrity();
    }

    public function test_supersede_retry_with_reordered_evidence_references_returns_replacement_without_writes(): void
    {
        $prior = $this->repo->persist(...$this->payload());
        $next = $this->repo->supersede(...$this->supersedePayload($prior));
        $before = $this->tableSnapshot();

        $again = $this->repo->supersede(...$this->supersedePayload($prior, [
            'evidenceReferences' => ['evidence://integrity/b', 'evidence://integrity/a'],
        ]));

        $this->assertTrue($again->persistenceRecordId->equals($next->persistenceRecordId));
        $this->assertSame(IntegrationClassificationLifecycleState::ACTIVE, $again->lifecycleState);
        $this->assertSame($before, $this->tableSnapshot());
        $this->assertLifecycleGraphIntegrity();
    }

    public function test_supersede_retry_after_later_supersession_returns_original_replacement(): void
    {
        $p1 = $this->repo->persist(...$this->payload());
        $p2 = $this->repo->supersede(...$this->supersedePayload($p1));
        $p3 = $this->repo->supersede(...$this->supersedePayload($p2, [
            'newIdempotencyKey' => 'ic:integrity:v3',
            'evidenceFingerprint' => 'fp-integrity-v3',
        ]));
        $before = $this->tableSnapshot();

        $replayed = $this->repo->supersede(...$this->supersedePayload($p1));

        $this->assertTrue($replayed->persistenceRecordId->equals($p2->persistenceRecordId));
        $this->assertSame(IntegrationClassificationLifecycleState::SUPERSEDED, $replayed->lifecycleState);
        $this->assertSame($p3->persistenceRecordId->value, $replayed->supersededBy?->value);
        $this->assertSame($before, $this->tableSnapshot());
        $this->assertLifecycleGraphIntegrity();
    }

    public function test_supersede_retry_with_different_payload_raises_conflict(): void
    {
        $prior = $this->repo->persist(...$this->payload());
        $this->repo->supersede(...$this->supersedePayload($prior));
        $before = $this->tableSnapshot();

        $this->expectException(IntegrationClassificationIdempotencyConflict::class);
        try {
            $this->repo->supersede(...$this->supersedePayload($prior, ['evidenceFingerprint' => 'fp-integrity-v2-changed']));
        } finally {
            $this->assertSame($before, $this->tableSnapshot());
        }
    }

    public function test_supersede_rejects_key_of_prior_itself_without_self_reference(): void
    {
        $prior = $this->repo->persist(...$this->payload());

        try {
            $this->repo->supersede(...$this->supersedePayload($prior, ['newIdempotencyKey' => 'ic:integrity']));
            $this->fail('Expected IntegrationClassificationIdempotencyConflict for the prior key.');
        } catch (IntegrationClassificationIdempotencyConflict $e) {
            $this->assertStringContainsString('prior record itself', $e->getMessage());
        }

        $this->assertPriorUntouchedAndActive($prior);
        $this->assertLifecycleGraphIntegrity();
    }

    public function test_supersede_rejects_key_bound_to_another_adr(): void
    {
        $prior = $this->repo->persist(...$this->payload());
        $other = $this->repo->persist(...$this->payload([
            'adrId' => AdrMembershipId::fromString('ABSTRACT_IC_INTEGRITY_OTHER'),
            'idempotencyKey' => 'ic:integrity:other-seat',
        ]));

        try {
            $this->repo->supersede(...$this->supersedePayload($prior, ['newIdempotencyKey' => 'ic:integrity:other-seat']));
            $this->fail('Expected IntegrationClassificationIdempotencyConflict for a cross-adr key.');
        } catch (IntegrationClassificationIdempotencyConflict $e) {
            $this->assertStringContainsString('adr_id', $e->getMessage());
        }

        $this->assertPriorUntouchedAndActive($prior);
        $this->assertSame(
            IntegrationClassificationLifecycleState::ACTIVE,
            $this->repo->findById($other->persistenceRecordId)?->lifecycleState
        );
        $this->assertLifecycleGraphIntegrity();
    }

    public function test_supersede_rejects_historical_key_so_no_cycle_can_form(): void
    {
        $p1 = $this->repo->persist(...$this->payload());
        $p2 = $this->repo->supersede(...$this->supersedePayload($p1));
        $p3 = $this->repo->supersede(...$this->supersedePayload($p2, [
            'newIdempotencyKey' => 'ic:integrity:v3',
            'evidenceFingerprint' => 'fp-integrity-v3',
        ]));
        $before = $this->tableSnapshot();

        try {
            // Same contract as p2, so only the replacement-link check can reject it.
            $this->repo->supersede(...$this->supersedePayload($p3));
            $this->fail('Expected IntegrationClassificationIdempotencyConflict for a historical key.');
        } catch (IntegrationClassificationIdempotencyConflict $e) {
            $this->assertStringContainsString('not the replacement', $e->getMessage());
        }

        $this->assertSame($before, $this->tableSnapshot());
        $this->assertSame(
            $p3->persistenceRecordId->value,
            $this->repo->findCurrentByAdrId($p1->adrId)?->persistenceRecordId->value
        );
        $this->assertLifecycleGraphIntegrity();
    }

    public function test_supersede_of_non_active_prior_with_fresh_key_is_rejected(): void
    {
        $prior = $this->repo->persist(...$this->payload());
        $this->repo->invalidate($prior->persistenceRecordId);
        $before = $this->tableSnapshot();

        try {
            $this->repo->supersede(...$this->supersedePayload($prior));
            $this->fail('Expected rejection of superseding an INVALIDATED prior.');
        } catch (IntegrationClassificationInvariantViolation $e) {
            $this->assertStringContainsString('not ACTIVE', $e->getMessage());
        }

        $this->assertSame($before, $this->tableSnapshot());
    }

    public function test_supersede_validates_new_payload_before_touching_prior(): void
    {
        $prior = $this->repo->persist(...$this->payload());

        try {
            $this->repo->supersede(...$this->supersedePayload($prior, ['classificationStatus' => ClassificationStatus::UNCLASSIFIED]));
            $this->fail('Expected UNCLASSIFIED rejection.');
        } catch (IntegrationClassificationInvariantViolation $e) {
            $this->assertStringContainsString('UNCLASSIFIED', $e->getMessage());
        }

        $this->assertPriorUntouchedAndActive($prior);
    }

    // --- D6: migration preflight on historical data --------------------------------------------

    public function test_migration_preflight_names_conflicting_adr_ids_and_changes_nothing(): void
    {
        $migration = $this->migration();
        $migration->down();
        $this->assertFalse($this->singleActiveIndexExists());

        $this->insertRawRow('ABSTRACT_IC_DUP_X', 'ic:dup:x1', IntegrationClassificationLifecycleState::ACTIVE);
        $this->insertRawRow('ABSTRACT_IC_DUP_X', 'ic:dup:x2', IntegrationClassificationLifecycleState::ACTIVE);
        $this->insertRawRow('ABSTRACT_IC_DUP_Y', 'ic:dup:y1', IntegrationClassificationLifecycleState::ACTIVE);
        $this->insertRawRow('ABSTRACT_IC_DUP_Y', 'ic:dup:y2', IntegrationClassificationLifecycleState::ACTIVE);
        $this->insertRawRow('ABSTRACT_IC_DUP_Y', 'ic:dup:y3', IntegrationClassificationLifecycleState::ACTIVE);
        $this->insertRawRow('ABSTRACT_IC_DUP_Z', 'ic:dup:z1', IntegrationClassificationLifecycleState::ACTIVE);
        $this->insertRawRow('ABSTRACT_IC_DUP_Z', 'ic:dup:z0', IntegrationClassificationLifecycleState::SUPERSEDED);
        $before = $this->tableSnapshot();

        try {
            $migration->up();
            $this->fail('Expected the preflight to fail on duplicate ACTIVE rows.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('ABSTRACT_IC_DUP_X (2 ACTIVE)', $e->getMessage());
            $this->assertStringContainsString('ABSTRACT_IC_DUP_Y (3 ACTIVE)', $e->getMessage());
            $this->assertStringNotContainsString('ABSTRACT_IC_DUP_Z', $e->getMessage());
            $this->assertStringContainsString('No data was changed', $e->getMessage());
        }

        $this->assertSame($before, $this->tableSnapshot());
        $this->assertFalse($this->singleActiveIndexExists());
    }

    public function test_migration_succeeds_on_valid_history_without_changing_rows(): void
    {
        $migration = $this->migration();
        $migration->down();

        $this->insertRawRow('ABSTRACT_IC_VALID_A', 'ic:valid:a1', IntegrationClassificationLifecycleState::SUPERSEDED);
        $this->insertRawRow('ABSTRACT_IC_VALID_A', 'ic:valid:a2', IntegrationClassificationLifecycleState::INVALIDATED);
        $this->insertRawRow('ABSTRACT_IC_VALID_A', 'ic:valid:a3', IntegrationClassificationLifecycleState::ACTIVE);
        $this->insertRawRow('ABSTRACT_IC_VALID_B', 'ic:valid:b1', IntegrationClassificationLifecycleState::INVALIDATED);
        $before = $this->tableSnapshot();

        $migration->up();

        $this->assertTrue($this->singleActiveIndexExists());
        $this->assertSame($before, $this->tableSnapshot());
    }

    public function test_migration_down_and_up_on_empty_table(): void
    {
        $migration = $this->migration();

        $migration->down();
        $this->assertFalse($this->singleActiveIndexExists());

        $migration->up();
        $this->assertTrue($this->singleActiveIndexExists());
        $this->assertSame(0, CghiaIntegrationClassification::query()->count());
    }

    // --- helpers -------------------------------------------------------------------------------

    private function migration(): Migration
    {
        return require database_path(self::MIGRATION);
    }

    private function singleActiveIndexExists(): bool
    {
        $name = IntegrationClassificationPersistenceContract::SINGLE_ACTIVE_INDEX;

        return match (DB::connection()->getDriverName()) {
            'pgsql' => DB::select('SELECT 1 FROM pg_indexes WHERE schemaname = current_schema() AND indexname = ?', [$name]) !== [],
            'sqlite' => DB::select("SELECT 1 FROM sqlite_master WHERE type = 'index' AND name = ?", [$name]) !== [],
        };
    }

    private function activeCount(string $adrId): int
    {
        return CghiaIntegrationClassification::query()
            ->where('adr_id', $adrId)
            ->where('lifecycle_state', IntegrationClassificationLifecycleState::ACTIVE->value)
            ->count();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function tableSnapshot(): array
    {
        return DB::table(self::TABLE)->orderBy('id')->get()->map(fn (object $row): array => (array) $row)->all();
    }

    private function assertPriorUntouchedAndActive(IntegrationClassificationRecord $prior): void
    {
        $reloaded = $this->repo->findById($prior->persistenceRecordId);
        $this->assertNotNull($reloaded);
        $this->assertSame(IntegrationClassificationLifecycleState::ACTIVE, $reloaded->lifecycleState);
        $this->assertNull($reloaded->supersededBy);
        $this->assertSame($prior->toArray(), $reloaded->toArray());
    }

    private function assertLifecycleGraphIntegrity(): void
    {
        $rows = CghiaIntegrationClassification::query()->orderBy('id')->get()->keyBy('id');
        $activeByAdr = [];

        foreach ($rows as $row) {
            if ($row->lifecycle_state === IntegrationClassificationLifecycleState::ACTIVE->value) {
                $activeByAdr[$row->adr_id] = ($activeByAdr[$row->adr_id] ?? 0) + 1;
            }

            if ($row->superseded_by === null) {
                continue;
            }

            $target = $rows->get($row->superseded_by);
            $this->assertNotNull($target, "superseded_by of row {$row->id} is dangling.");
            $this->assertSame(IntegrationClassificationLifecycleState::SUPERSEDED->value, $row->lifecycle_state);
            $this->assertSame($row->adr_id, $target->adr_id, "superseded_by of row {$row->id} crosses adr_id.");
            $this->assertGreaterThan($row->id, $target->id, "superseded_by of row {$row->id} does not point forward.");
        }

        foreach ($activeByAdr as $adrId => $count) {
            $this->assertSame(1, $count, "adr_id {$adrId} has {$count} ACTIVE rows.");
        }
    }
}
