<?php

namespace Tests\Unit\Agriculture\Research\Identity\Persistence;

use App\Models\CghiaIdentityBinding;
use App\Services\Agriculture\Research\Identity\AdrMembershipId;
use App\Services\Agriculture\Research\Identity\CanonicalSourceIdentityId;
use App\Services\Agriculture\Research\Identity\IdentityBindingStatus;
use App\Services\Agriculture\Research\Identity\IdentityDecisionIdentity;
use App\Services\Agriculture\Research\Identity\Persistence\EloquentIdentityBindingRepository;
use App\Services\Agriculture\Research\Identity\Persistence\IdentityBindingInvariantViolation;
use App\Services\Agriculture\Research\Identity\Persistence\IdentityBindingLifecycleState;
use App\Services\Agriculture\Research\Identity\Persistence\IdentityBindingPersistenceContract;
use App\Services\Agriculture\Research\Identity\Persistence\IdentityBindingRecord;
use App\Services\Agriculture\Research\Identity\SourceIdentityInvariantViolation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * FS-01-ID — Identity Binding Persistence unit tests.
 *
 * ABSTRACT_FIRST_SEAT / synthetic fixture identifiers only — not production sources.
 */
final class IdentityBindingPersistenceTest extends TestCase
{
    use RefreshDatabase;

    private EloquentIdentityBindingRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new EloquentIdentityBindingRepository;
    }

    public function test_schema_table_exists_with_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable(IdentityBindingPersistenceContract::TABLE));
        foreach ([
            'id',
            'adr_id',
            'canonical_identity_id',
            'identity_status',
            'identity_decision_identity',
            'identity_evidence_fingerprint',
            'identity_evidence_refs',
            'original_source_identifier',
            'aggregator_record_identifier',
            'lifecycle_state',
            'schema_version',
            'idempotency_key',
            'superseded_by',
            'decision_actor',
            'decision_timestamp',
            'metadata',
            'created_at',
            'updated_at',
        ] as $column) {
            $this->assertTrue(Schema::hasColumn(IdentityBindingPersistenceContract::TABLE, $column), $column);
        }
        $this->assertFalse(Schema::hasColumn(IdentityBindingPersistenceContract::TABLE, 'display_name'));
    }

    public function test_persist_and_load_identity_binding(): void
    {
        $record = $this->persistFixture(
            adrId: 'ABSTRACT_FIRST_SEAT',
            status: IdentityBindingStatus::BOUND,
            decisionId: 'id-dec-001',
            fingerprint: 'ev-fp-001',
            idempotencyKey: 'fs01:identity:abstract:001',
            evidenceRefs: ['evidence://fixture/1'],
        );

        $loaded = $this->repo->findById($record->persistenceRecordId);
        $this->assertNotNull($loaded);
        $this->assertSame('ABSTRACT_FIRST_SEAT', $loaded->adrId->value);
        $this->assertNull($loaded->canonicalIdentityId);
        $this->assertSame(IdentityBindingStatus::BOUND, $loaded->identityStatus);
        $this->assertSame('id-dec-001', $loaded->identityDecisionIdentity->value);
        $this->assertSame('ev-fp-001', $loaded->identityEvidenceFingerprint);
        $this->assertSame(['evidence://fixture/1'], $loaded->identityEvidenceRefs);
        $this->assertSame(IdentityBindingLifecycleState::ACTIVE, $loaded->lifecycleState);
        $this->assertSame(IdentityBindingRecord::CURRENT_SCHEMA_VERSION, $loaded->schemaVersion);
        $this->assertSame('test-actor', $loaded->decisionActor);
    }

    public function test_canonical_identity_id_nullable_default_null(): void
    {
        $record = $this->persistFixture(
            adrId: 'ABSTRACT_FIRST_SEAT',
            status: IdentityBindingStatus::CANDIDATE,
            decisionId: 'id-dec-null-can',
            fingerprint: 'ev-fp-null',
            idempotencyKey: 'fs01:identity:null-canonical',
        );

        $this->assertNull($record->canonicalIdentityId);
        $this->assertNull($record->toArray()['canonical_identity_id']);
        $this->assertNull(
            CghiaIdentityBinding::query()->where('idempotency_key', 'fs01:identity:null-canonical')->value('canonical_identity_id')
        );
    }

    public function test_canonical_identity_persists_when_governed_and_distinct(): void
    {
        $record = $this->persistFixture(
            adrId: 'ABSTRACT_FIRST_SEAT',
            status: IdentityBindingStatus::BOUND,
            decisionId: 'id-dec-can',
            fingerprint: 'ev-fp-can',
            idempotencyKey: 'fs01:identity:with-canonical',
            canonicalId: 'cid_fixture_opaque_1',
        );

        $this->assertSame('cid_fixture_opaque_1', $record->canonicalIdentityId?->value);
        $this->assertNotSame($record->adrId->value, $record->canonicalIdentityId?->value);
    }

    public function test_rejects_adr_id_equal_canonical_identity_id(): void
    {
        // Namespace distinctness is owned by IU-01 SourceIdentityDomainContract;
        // persistence must not re-author that invariant as IdentityBindingInvariantViolation.
        $this->expectException(SourceIdentityInvariantViolation::class);
        $this->persistFixture(
            adrId: 'ABSTRACT_FIRST_SEAT',
            status: IdentityBindingStatus::BOUND,
            decisionId: 'id-dec-eq',
            fingerprint: 'ev-fp-eq',
            idempotencyKey: 'fs01:identity:eq',
            canonicalId: 'ABSTRACT_FIRST_SEAT',
        );
    }

    public function test_identity_status_contract_values(): void
    {
        foreach (IdentityBindingStatus::cases() as $status) {
            $key = 'fs01:identity:status:'.$status->value;
            $record = $this->persistFixture(
                adrId: 'ABSTRACT_FIRST_SEAT_'.$status->value,
                status: $status,
                decisionId: 'id-dec-'.$status->value,
                fingerprint: 'ev-fp-'.$status->value,
                idempotencyKey: $key,
            );
            $this->assertSame($status, $record->identityStatus);
        }
    }

    public function test_idempotency_replay_does_not_duplicate(): void
    {
        $first = $this->persistFixture(
            adrId: 'ABSTRACT_FIRST_SEAT',
            status: IdentityBindingStatus::BOUND,
            decisionId: 'id-dec-replay',
            fingerprint: 'ev-fp-replay',
            idempotencyKey: 'fs01:identity:replay',
        );
        $second = $this->persistFixture(
            adrId: 'ABSTRACT_FIRST_SEAT',
            status: IdentityBindingStatus::BOUND,
            decisionId: 'id-dec-replay',
            fingerprint: 'ev-fp-replay',
            idempotencyKey: 'fs01:identity:replay',
        );

        $this->assertTrue($first->persistenceRecordId->equals($second->persistenceRecordId));
        $this->assertSame(1, CghiaIdentityBinding::query()->where('idempotency_key', 'fs01:identity:replay')->count());
    }

    public function test_database_unique_constraint_on_idempotency_key(): void
    {
        $this->persistFixture(
            adrId: 'ABSTRACT_FIRST_SEAT',
            status: IdentityBindingStatus::BOUND,
            decisionId: 'id-dec-u',
            fingerprint: 'ev-fp-u',
            idempotencyKey: 'fs01:identity:unique',
        );

        $this->expectException(\Illuminate\Database\QueryException::class);
        CghiaIdentityBinding::query()->create(
            IdentityBindingRecord::draftAttributes(
                AdrMembershipId::fromString('ABSTRACT_FIRST_SEAT'),
                IdentityBindingStatus::BOUND,
                IdentityDecisionIdentity::fromString('id-dec-u2'),
                'ev-fp-u2',
                'fs01:identity:unique',
                'test-actor',
                '2026-10-02T00:00:00Z',
            )
        );
    }

    public function test_concurrent_unique_violation_resolves_to_existing_record(): void
    {
        $this->persistFixture(
            adrId: 'ABSTRACT_FIRST_SEAT',
            status: IdentityBindingStatus::BOUND,
            decisionId: 'id-dec-race',
            fingerprint: 'ev-fp-race',
            idempotencyKey: 'fs01:identity:race',
        );

        try {
            CghiaIdentityBinding::query()->create(
                IdentityBindingRecord::draftAttributes(
                    AdrMembershipId::fromString('ABSTRACT_FIRST_SEAT'),
                    IdentityBindingStatus::BOUND,
                    IdentityDecisionIdentity::fromString('id-dec-race-2'),
                    'ev-fp-race-2',
                    'fs01:identity:race',
                    'test-actor',
                    '2026-10-02T00:00:00Z',
                )
            );
            $this->fail('Expected unique violation');
        } catch (\Illuminate\Database\QueryException) {
            $replay = $this->persistFixture(
                adrId: 'ABSTRACT_FIRST_SEAT',
                status: IdentityBindingStatus::BOUND,
                decisionId: 'id-dec-race',
                fingerprint: 'ev-fp-race',
                idempotencyKey: 'fs01:identity:race',
            );
            $this->assertSame(1, CghiaIdentityBinding::query()->where('idempotency_key', 'fs01:identity:race')->count());
            $this->assertSame('fs01:identity:race', $replay->idempotencyKey);
        }
    }

    public function test_supersession_preserves_historical_row_immutability(): void
    {
        $prior = $this->persistFixture(
            adrId: 'ABSTRACT_FIRST_SEAT',
            status: IdentityBindingStatus::CANDIDATE,
            decisionId: 'id-dec-v1',
            fingerprint: 'ev-fp-v1',
            idempotencyKey: 'fs01:identity:v1',
        );

        $next = $this->repo->supersede(
            $prior->persistenceRecordId,
            AdrMembershipId::fromString('ABSTRACT_FIRST_SEAT'),
            IdentityBindingStatus::BOUND,
            IdentityDecisionIdentity::fromString('id-dec-v2'),
            'ev-fp-v2',
            'fs01:identity:v2',
            'test-actor',
            '2026-10-02T01:00:00Z',
            identityEvidenceRefs: ['evidence://fixture/v2'],
        );

        $priorReloaded = $this->repo->findById($prior->persistenceRecordId);
        $this->assertNotNull($priorReloaded);
        $this->assertSame(IdentityBindingLifecycleState::SUPERSEDED, $priorReloaded->lifecycleState);
        $this->assertSame(IdentityBindingStatus::CANDIDATE, $priorReloaded->identityStatus);
        $this->assertSame('ev-fp-v1', $priorReloaded->identityEvidenceFingerprint);
        $this->assertSame($next->persistenceRecordId->value, $priorReloaded->supersededBy?->value);

        $this->assertSame(IdentityBindingLifecycleState::ACTIVE, $next->lifecycleState);
        $this->assertSame(IdentityBindingStatus::BOUND, $next->identityStatus);
        $this->assertSame(
            $next->persistenceRecordId->value,
            $this->repo->findCurrentByAdrId(AdrMembershipId::fromString('ABSTRACT_FIRST_SEAT'))?->persistenceRecordId->value
        );
    }

    public function test_supersession_rejects_adr_id_mismatch(): void
    {
        $prior = $this->persistFixture(
            adrId: 'ABSTRACT_FIRST_SEAT_A',
            status: IdentityBindingStatus::BOUND,
            decisionId: 'id-dec-a',
            fingerprint: 'ev-fp-a',
            idempotencyKey: 'fs01:identity:a',
        );

        $this->expectException(IdentityBindingInvariantViolation::class);
        $this->repo->supersede(
            $prior->persistenceRecordId,
            AdrMembershipId::fromString('ABSTRACT_FIRST_SEAT_B'),
            IdentityBindingStatus::BOUND,
            IdentityDecisionIdentity::fromString('id-dec-b'),
            'ev-fp-b',
            'fs01:identity:b',
            'test-actor',
            '2026-10-02T01:00:00Z',
        );
    }

    public function test_dual_seats_remain_independent(): void
    {
        $g1 = $this->persistFixture(
            adrId: 'G1-FIXTURE-01',
            status: IdentityBindingStatus::BOUND,
            decisionId: 'id-dec-g1',
            fingerprint: 'ev-fp-g1',
            idempotencyKey: 'fs01:identity:g1',
            metadata: ['provider_hint' => 'shared-provider-fixture'],
        );
        $g6 = $this->persistFixture(
            adrId: 'G6-FIXTURE-01',
            status: IdentityBindingStatus::BOUND,
            decisionId: 'id-dec-g6',
            fingerprint: 'ev-fp-g6',
            idempotencyKey: 'fs01:identity:g6',
            metadata: ['provider_hint' => 'shared-provider-fixture', 'endpoint_hint' => 'https://example.test/shared'],
        );

        $this->assertNotSame($g1->adrId->value, $g6->adrId->value);
        $this->assertFalse($g1->persistenceRecordId->equals($g6->persistenceRecordId));
        $this->assertSame(2, CghiaIdentityBinding::query()->where('lifecycle_state', 'ACTIVE')->count());
        $this->assertNull($g1->canonicalIdentityId);
        $this->assertNull($g6->canonicalIdentityId);
    }

    public function test_shared_provider_or_endpoint_metadata_does_not_merge_identities(): void
    {
        $a = $this->persistFixture(
            adrId: 'ABSTRACT_SEAT_A',
            status: IdentityBindingStatus::BOUND,
            decisionId: 'id-dec-sa',
            fingerprint: 'ev-fp-sa',
            idempotencyKey: 'fs01:identity:sa',
            metadata: ['provider_hint' => 'P', 'endpoint_hint' => 'E', 'adapter_hint' => 'A'],
        );
        $b = $this->persistFixture(
            adrId: 'ABSTRACT_SEAT_B',
            status: IdentityBindingStatus::BOUND,
            decisionId: 'id-dec-sb',
            fingerprint: 'ev-fp-sb',
            idempotencyKey: 'fs01:identity:sb',
            metadata: ['provider_hint' => 'P', 'endpoint_hint' => 'E', 'adapter_hint' => 'A'],
        );

        $this->assertNotSame($a->persistenceRecordId->value, $b->persistenceRecordId->value);
        $this->assertNull($a->canonicalIdentityId);
        $this->assertNull($b->canonicalIdentityId);
    }

    public function test_aggregator_identity_separated_from_original_source(): void
    {
        $record = $this->persistFixture(
            adrId: 'ABSTRACT_FIRST_SEAT',
            status: IdentityBindingStatus::BOUND,
            decisionId: 'id-dec-agg',
            fingerprint: 'ev-fp-agg',
            idempotencyKey: 'fs01:identity:agg',
            originalSourceIdentifier: 'native-source-fixture-1',
            aggregatorRecordIdentifier: 'aggregator-hit-fixture-1',
        );

        $this->assertSame('native-source-fixture-1', $record->originalSourceIdentifier);
        $this->assertSame('aggregator-hit-fixture-1', $record->aggregatorRecordIdentifier);
        $this->assertNotSame($record->originalSourceIdentifier, $record->aggregatorRecordIdentifier);
    }

    public function test_rejects_aggregator_equal_original_source(): void
    {
        $this->expectException(IdentityBindingInvariantViolation::class);
        $this->persistFixture(
            adrId: 'ABSTRACT_FIRST_SEAT',
            status: IdentityBindingStatus::BOUND,
            decisionId: 'id-dec-agg-eq',
            fingerprint: 'ev-fp-agg-eq',
            idempotencyKey: 'fs01:identity:agg-eq',
            originalSourceIdentifier: 'same-id',
            aggregatorRecordIdentifier: 'same-id',
        );
    }

    public function test_metadata_rejects_capability_and_display_name_authority_keys(): void
    {
        $this->expectException(IdentityBindingInvariantViolation::class);
        $this->persistFixture(
            adrId: 'ABSTRACT_FIRST_SEAT',
            status: IdentityBindingStatus::BOUND,
            decisionId: 'id-dec-meta',
            fingerprint: 'ev-fp-meta',
            idempotencyKey: 'fs01:identity:meta-bad',
            metadata: ['display_name' => 'should-not-be-authority'],
        );
    }

    public function test_metadata_rejects_cap_state_under_status_key(): void
    {
        $this->expectException(IdentityBindingInvariantViolation::class);
        IdentityBindingPersistenceContract::assertMetadataNonAuthoritative([
            'status' => 'VERIFIED',
        ]);
    }

    public function test_no_display_name_uniqueness_constraint_exists(): void
    {
        $indexes = Schema::getIndexes(IdentityBindingPersistenceContract::TABLE);
        foreach ($indexes as $index) {
            $cols = $index['columns'] ?? [];
            $this->assertNotContains('display_name', $cols);
        }
    }

    public function test_invalidate_lifecycle(): void
    {
        $record = $this->persistFixture(
            adrId: 'ABSTRACT_FIRST_SEAT',
            status: IdentityBindingStatus::DEFERRED,
            decisionId: 'id-dec-inv',
            fingerprint: 'ev-fp-inv',
            idempotencyKey: 'fs01:identity:inv',
        );

        $invalidated = $this->repo->invalidate($record->persistenceRecordId);
        $this->assertSame(IdentityBindingLifecycleState::INVALIDATED, $invalidated->lifecycleState);
        $this->assertSame(IdentityBindingStatus::DEFERRED, $invalidated->identityStatus);
    }

    public function test_transaction_rollback_on_supersede_failure_leaves_prior_active(): void
    {
        $prior = $this->persistFixture(
            adrId: 'ABSTRACT_FIRST_SEAT',
            status: IdentityBindingStatus::BOUND,
            decisionId: 'id-dec-tx',
            fingerprint: 'ev-fp-tx',
            idempotencyKey: 'fs01:identity:tx',
        );

        try {
            DB::transaction(function () use ($prior): void {
                $this->repo->supersede(
                    $prior->persistenceRecordId,
                    AdrMembershipId::fromString('ABSTRACT_FIRST_SEAT'),
                    IdentityBindingStatus::CONFLICT,
                    IdentityDecisionIdentity::fromString('id-dec-tx-2'),
                    'ev-fp-tx-2',
                    'fs01:identity:tx-2',
                    'test-actor',
                    '2026-10-02T02:00:00Z',
                );
                throw new \RuntimeException('force-rollback');
            });
            $this->fail('Expected rollback');
        } catch (\RuntimeException $e) {
            $this->assertSame('force-rollback', $e->getMessage());
        }

        $current = $this->repo->findCurrentByAdrId(AdrMembershipId::fromString('ABSTRACT_FIRST_SEAT'));
        $this->assertNotNull($current);
        $this->assertTrue($current->persistenceRecordId->equals($prior->persistenceRecordId));
        $this->assertSame(IdentityBindingLifecycleState::ACTIVE, $current->lifecycleState);
        $this->assertSame(0, CghiaIdentityBinding::query()->where('idempotency_key', 'fs01:identity:tx-2')->count());
    }

    public function test_b7_table_untouched_by_identity_store(): void
    {
        $this->assertTrue(Schema::hasTable('durable_correlation_records'));
        $this->assertSame(0, DB::table('durable_correlation_records')->count());
        $this->persistFixture(
            adrId: 'ABSTRACT_FIRST_SEAT',
            status: IdentityBindingStatus::BOUND,
            decisionId: 'id-dec-b7',
            fingerprint: 'ev-fp-b7',
            idempotencyKey: 'fs01:identity:b7-iso',
        );
        $this->assertSame(0, DB::table('durable_correlation_records')->count());
    }

    public function test_domain_membership_rejects_equal_namespaces_independently(): void
    {
        $this->expectException(SourceIdentityInvariantViolation::class);
        \App\Services\Agriculture\Research\Identity\AdrSourceMembership::create(
            AdrMembershipId::fromString('X'),
            CanonicalSourceIdentityId::fromString('X'),
        );
    }

    /**
     * @param  list<string>|null  $evidenceRefs
     * @param  array<string, mixed>|null  $metadata
     */
    private function persistFixture(
        string $adrId,
        IdentityBindingStatus $status,
        string $decisionId,
        string $fingerprint,
        string $idempotencyKey,
        ?string $canonicalId = null,
        ?array $evidenceRefs = null,
        ?string $originalSourceIdentifier = null,
        ?string $aggregatorRecordIdentifier = null,
        ?array $metadata = null,
    ): IdentityBindingRecord {
        return $this->repo->persist(
            AdrMembershipId::fromString($adrId),
            $status,
            IdentityDecisionIdentity::fromString($decisionId),
            $fingerprint,
            $idempotencyKey,
            'test-actor',
            '2026-10-02T00:00:00Z',
            $canonicalId === null ? null : CanonicalSourceIdentityId::fromString($canonicalId),
            $evidenceRefs,
            $originalSourceIdentifier,
            $aggregatorRecordIdentifier,
            $metadata,
        );
    }
}
