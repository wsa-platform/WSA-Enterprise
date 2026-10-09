<?php

namespace Tests\Unit\Agriculture\Research\IntegrationClassification\Persistence;

use App\Models\CghiaIntegrationClassification;
use App\Services\Agriculture\Research\Identity\AdrMembershipId;
use App\Services\Agriculture\Research\Identity\CanonicalSourceIdentityId;
use App\Services\Agriculture\Research\Identity\SourceIdentityInvariantViolation;
use App\Services\Agriculture\Research\IntegrationClassification\ClassificationDecisionIdentity;
use App\Services\Agriculture\Research\IntegrationClassification\ClassificationStatus;
use App\Services\Agriculture\Research\IntegrationClassification\IntegrationBoundary;
use App\Services\Agriculture\Research\IntegrationClassification\IntegrationNature;
use App\Services\Agriculture\Research\IntegrationClassification\Persistence\EloquentIntegrationClassificationRepository;
use App\Services\Agriculture\Research\IntegrationClassification\Persistence\IntegrationClassificationIdempotency;
use App\Services\Agriculture\Research\IntegrationClassification\Persistence\IntegrationClassificationInvariantViolation;
use App\Services\Agriculture\Research\IntegrationClassification\Persistence\IntegrationClassificationLifecycleState;
use App\Services\Agriculture\Research\IntegrationClassification\Persistence\IntegrationClassificationPersistenceContract;
use App\Services\Agriculture\Research\IntegrationClassification\Persistence\IntegrationClassificationRecord;
use App\Services\Agriculture\Research\IntegrationClassification\Persistence\IntegrationClassificationRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * IC Persistence unit tests — synthetic fixture identifiers only (not production seats).
 */
final class IntegrationClassificationPersistenceTest extends TestCase
{
    use RefreshDatabase;

    private EloquentIntegrationClassificationRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new EloquentIntegrationClassificationRepository;
    }

    public function test_schema_table_exists_with_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable(IntegrationClassificationPersistenceContract::TABLE));
        foreach ([
            'id', 'adr_id', 'canonical_identity_id', 'identity_binding_ref',
            'classification_decision_identity', 'classification_status',
            'access_modality_claims', 'integration_nature', 'integration_boundary',
            'protocol_family', 'source_specific_requirement', 'source_specific_rationale',
            'path_family_hint', 'existing_adapter_reference', 'external_dependency_reference',
            'evidence_references', 'evidence_fingerprint',
            'license_reference', 'access_reference', 'reuse_reference', 'rationale',
            'decision_actor', 'verified_at', 'decision_timestamp',
            'lifecycle_state', 'schema_version', 'idempotency_key', 'superseded_by',
            'metadata', 'created_at', 'updated_at',
        ] as $column) {
            $this->assertTrue(Schema::hasColumn(IntegrationClassificationPersistenceContract::TABLE, $column), $column);
        }
        $this->assertFalse(Schema::hasColumn(IntegrationClassificationPersistenceContract::TABLE, 'activation_state'));
        $this->assertFalse(Schema::hasColumn(IntegrationClassificationPersistenceContract::TABLE, 'capability_state'));
        $this->assertFalse(Schema::hasColumn(IntegrationClassificationPersistenceContract::TABLE, 'path_status'));
        $this->assertFalse(Schema::hasColumn(IntegrationClassificationPersistenceContract::TABLE, 'fidelity_class'));
        $this->assertFalse(Schema::hasColumn(IntegrationClassificationPersistenceContract::TABLE, 'display_name'));
    }

    public function test_persist_and_load_decision(): void
    {
        $record = $this->persistFixture(
            adrId: 'ABSTRACT_IC_SEAT',
            decisionId: 'ic-dec-001',
            status: ClassificationStatus::CLASSIFIED,
            modalities: ['api', 'oai_pmh'],
            nature: IntegrationNature::SOURCE_NATIVE,
            boundary: IntegrationBoundary::PROTOCOL_FAMILY_ADAPTER,
            fingerprint: 'ic-ev-fp-001',
            idempotencyKey: 'ic:persist:001',
            evidenceRefs: ['evidence://ic/fixture/1'],
        );

        $loaded = $this->repo->findById($record->persistenceRecordId);
        $this->assertNotNull($loaded);
        $this->assertSame('ABSTRACT_IC_SEAT', $loaded->adrId->value);
        $this->assertNull($loaded->canonicalIdentityId);
        $this->assertSame(ClassificationStatus::CLASSIFIED, $loaded->classificationStatus);
        $this->assertSame(['api', 'oai_pmh'], $loaded->accessModalityClaims);
        $this->assertSame(IntegrationNature::SOURCE_NATIVE, $loaded->integrationNature);
        $this->assertSame(IntegrationClassificationLifecycleState::ACTIVE, $loaded->lifecycleState);
        $this->assertSame(['evidence://ic/fixture/1'], $loaded->evidenceReferences);
        $this->assertSame(IntegrationClassificationRecord::CURRENT_SCHEMA_VERSION, $loaded->schemaVersion);
    }

    public function test_missing_record_implies_unclassified_without_auto_create(): void
    {
        $this->assertNull(
            $this->repo->findCurrentByAdrId(AdrMembershipId::fromString('ABSTRACT_MISSING_SEAT'))
        );
        $this->assertSame(
            ClassificationStatus::UNCLASSIFIED,
            IntegrationClassificationRecord::missingImpliesUnclassified()
        );
        $this->assertSame(0, CghiaIntegrationClassification::query()->count());
    }

    public function test_rejects_persisting_unclassified_status(): void
    {
        $this->expectException(IntegrationClassificationInvariantViolation::class);
        $this->persistFixture(
            adrId: 'ABSTRACT_IC_SEAT',
            decisionId: 'ic-dec-unc',
            status: ClassificationStatus::UNCLASSIFIED,
            modalities: ['api'],
            nature: IntegrationNature::SOURCE_NATIVE,
            boundary: IntegrationBoundary::PROTOCOL_FAMILY_ADAPTER,
            fingerprint: 'fp-unc',
            idempotencyKey: 'ic:unc',
        );
    }

    public function test_all_persisted_classification_statuses_except_unclassified(): void
    {
        $statuses = [
            ClassificationStatus::CLASSIFIED,
            ClassificationStatus::PARTIALLY_CLASSIFIED,
            ClassificationStatus::UNCLASSIFIABLE,
            ClassificationStatus::NOT_APPLICABLE,
        ];
        foreach ($statuses as $status) {
            $record = $this->persistFixture(
                adrId: 'ABSTRACT_IC_SEAT_'.$status->value,
                decisionId: 'ic-dec-'.$status->value,
                status: $status,
                modalities: ['web_ui_search'],
                nature: IntegrationNature::NATURE_UNVERIFIED,
                boundary: IntegrationBoundary::MANUAL_STATIC,
                fingerprint: 'fp-'.$status->value,
                idempotencyKey: 'ic:status:'.$status->value,
            );
            $this->assertSame($status, $record->classificationStatus);
        }
    }

    public function test_empty_adr_id_rejected(): void
    {
        $this->expectException(SourceIdentityInvariantViolation::class);
        AdrMembershipId::fromString('   ');
    }

    public function test_empty_decision_identity_rejected(): void
    {
        $this->expectException(IntegrationClassificationInvariantViolation::class);
        ClassificationDecisionIdentity::fromString('');
    }

    public function test_canonical_identity_nullable_and_rejects_equal_adr(): void
    {
        $ok = $this->persistFixture(
            adrId: 'ABSTRACT_IC_SEAT',
            decisionId: 'ic-dec-can',
            status: ClassificationStatus::CLASSIFIED,
            modalities: ['api'],
            nature: IntegrationNature::SOURCE_NATIVE,
            boundary: IntegrationBoundary::PROTOCOL_FAMILY_ADAPTER,
            fingerprint: 'fp-can',
            idempotencyKey: 'ic:can',
            canonicalId: 'cid_ic_fixture_1',
        );
        $this->assertSame('cid_ic_fixture_1', $ok->canonicalIdentityId?->value);

        $this->expectException(SourceIdentityInvariantViolation::class);
        $this->persistFixture(
            adrId: 'ABSTRACT_IC_SEAT_EQ',
            decisionId: 'ic-dec-eq',
            status: ClassificationStatus::CLASSIFIED,
            modalities: ['api'],
            nature: IntegrationNature::SOURCE_NATIVE,
            boundary: IntegrationBoundary::PROTOCOL_FAMILY_ADAPTER,
            fingerprint: 'fp-eq',
            idempotencyKey: 'ic:eq',
            canonicalId: 'ABSTRACT_IC_SEAT_EQ',
        );
    }

    public function test_idempotency_replay_does_not_duplicate(): void
    {
        $first = $this->persistFixture(
            adrId: 'ABSTRACT_IC_SEAT',
            decisionId: 'ic-dec-replay',
            status: ClassificationStatus::CLASSIFIED,
            modalities: ['api'],
            nature: IntegrationNature::SOURCE_NATIVE,
            boundary: IntegrationBoundary::PROTOCOL_FAMILY_ADAPTER,
            fingerprint: 'fp-replay',
            idempotencyKey: 'ic:replay',
        );
        $second = $this->persistFixture(
            adrId: 'ABSTRACT_IC_SEAT',
            decisionId: 'ic-dec-replay',
            status: ClassificationStatus::CLASSIFIED,
            modalities: ['api'],
            nature: IntegrationNature::SOURCE_NATIVE,
            boundary: IntegrationBoundary::PROTOCOL_FAMILY_ADAPTER,
            fingerprint: 'fp-replay',
            idempotencyKey: 'ic:replay',
        );

        $this->assertTrue($first->persistenceRecordId->equals($second->persistenceRecordId));
        $this->assertSame(1, CghiaIntegrationClassification::query()->where('idempotency_key', 'ic:replay')->count());
    }

    public function test_idempotency_key_deterministic_and_changes_with_claims(): void
    {
        $a = IntegrationClassificationIdempotency::computeKey(
            'ABSTRACT_IC_SEAT',
            'fp-1',
            ['oai_pmh', 'api'],
            IntegrationNature::SOURCE_NATIVE->value,
            IntegrationBoundary::PROTOCOL_FAMILY_ADAPTER->value,
            'oai-pmh',
            false,
            'P12',
            'policy-v1',
        );
        $b = IntegrationClassificationIdempotency::computeKey(
            'ABSTRACT_IC_SEAT',
            'fp-1',
            ['api', 'oai_pmh'],
            IntegrationNature::SOURCE_NATIVE->value,
            IntegrationBoundary::PROTOCOL_FAMILY_ADAPTER->value,
            'oai-pmh',
            false,
            'P12',
            'policy-v1',
        );
        $c = IntegrationClassificationIdempotency::computeKey(
            'ABSTRACT_IC_SEAT',
            'fp-1',
            ['api', 'oai_pmh'],
            IntegrationNature::EXTERNAL_DEPENDENCY->value,
            IntegrationBoundary::PROTOCOL_FAMILY_ADAPTER->value,
            'oai-pmh',
            false,
            'P12',
            'policy-v1',
        );

        $this->assertSame($a, $b);
        $this->assertSame(64, strlen($a));
        $this->assertNotSame($a, $c);
    }

    public function test_second_active_persist_rejected_use_supersede(): void
    {
        $this->persistFixture(
            adrId: 'ABSTRACT_IC_SEAT',
            decisionId: 'ic-dec-a1',
            status: ClassificationStatus::PARTIALLY_CLASSIFIED,
            modalities: ['web_ui_search'],
            nature: IntegrationNature::MANUAL_ONLY,
            boundary: IntegrationBoundary::MANUAL_STATIC,
            fingerprint: 'fp-a1',
            idempotencyKey: 'ic:a1',
        );

        $this->expectException(IntegrationClassificationInvariantViolation::class);
        $this->persistFixture(
            adrId: 'ABSTRACT_IC_SEAT',
            decisionId: 'ic-dec-a2',
            status: ClassificationStatus::CLASSIFIED,
            modalities: ['api'],
            nature: IntegrationNature::SOURCE_NATIVE,
            boundary: IntegrationBoundary::PROTOCOL_FAMILY_ADAPTER,
            fingerprint: 'fp-a2',
            idempotencyKey: 'ic:a2',
        );
    }

    public function test_supersession_preserves_historical_payload_immutability(): void
    {
        $prior = $this->persistFixture(
            adrId: 'ABSTRACT_IC_SEAT',
            decisionId: 'ic-dec-v1',
            status: ClassificationStatus::PARTIALLY_CLASSIFIED,
            modalities: ['web_ui_search'],
            nature: IntegrationNature::MANUAL_ONLY,
            boundary: IntegrationBoundary::MANUAL_STATIC,
            fingerprint: 'fp-v1',
            idempotencyKey: 'ic:v1',
            license: 'license://v1',
            access: 'access://v1',
            reuse: 'reuse://v1',
        );

        $next = $this->repo->supersede(
            $prior->persistenceRecordId,
            AdrMembershipId::fromString('ABSTRACT_IC_SEAT'),
            ClassificationDecisionIdentity::fromString('ic-dec-v2'),
            ClassificationStatus::CLASSIFIED,
            ['api'],
            IntegrationNature::SOURCE_NATIVE,
            IntegrationBoundary::PROTOCOL_FAMILY_ADAPTER,
            false,
            'fp-v2',
            'ic:v2',
            'test-actor',
            '2026-10-02T01:00:00Z',
            '2026-10-02T01:00:00Z',
            evidenceReferences: ['evidence://ic/v2'],
            licenseReference: 'license://v2',
            accessReference: 'access://v2',
            reuseReference: 'reuse://v2',
        );

        $priorReloaded = $this->repo->findById($prior->persistenceRecordId);
        $this->assertNotNull($priorReloaded);
        $this->assertSame(IntegrationClassificationLifecycleState::SUPERSEDED, $priorReloaded->lifecycleState);
        $this->assertSame(ClassificationStatus::PARTIALLY_CLASSIFIED, $priorReloaded->classificationStatus);
        $this->assertSame('fp-v1', $priorReloaded->evidenceFingerprint);
        $this->assertSame(['web_ui_search'], $priorReloaded->accessModalityClaims);
        $this->assertSame('license://v1', $priorReloaded->licenseReference);
        $this->assertSame($next->persistenceRecordId->value, $priorReloaded->supersededBy?->value);
        $this->assertSame(IntegrationClassificationLifecycleState::ACTIVE, $next->lifecycleState);
        $this->assertSame(
            $next->persistenceRecordId->value,
            $this->repo->findCurrentByAdrId(AdrMembershipId::fromString('ABSTRACT_IC_SEAT'))?->persistenceRecordId->value
        );
        $this->assertSame(1, CghiaIntegrationClassification::query()->where('lifecycle_state', 'ACTIVE')->where('adr_id', 'ABSTRACT_IC_SEAT')->count());
    }

    public function test_supersession_rejects_adr_mismatch_and_non_active_prior(): void
    {
        $prior = $this->persistFixture(
            adrId: 'ABSTRACT_IC_SEAT_A',
            decisionId: 'ic-dec-a',
            status: ClassificationStatus::CLASSIFIED,
            modalities: ['api'],
            nature: IntegrationNature::SOURCE_NATIVE,
            boundary: IntegrationBoundary::PROTOCOL_FAMILY_ADAPTER,
            fingerprint: 'fp-a',
            idempotencyKey: 'ic:a',
        );

        try {
            $this->repo->supersede(
                $prior->persistenceRecordId,
                AdrMembershipId::fromString('ABSTRACT_IC_SEAT_B'),
                ClassificationDecisionIdentity::fromString('ic-dec-b'),
                ClassificationStatus::CLASSIFIED,
                ['api'],
                IntegrationNature::SOURCE_NATIVE,
                IntegrationBoundary::PROTOCOL_FAMILY_ADAPTER,
                false,
                'fp-b',
                'ic:b',
                'test-actor',
                '2026-10-02T01:00:00Z',
                '2026-10-02T01:00:00Z',
            );
            $this->fail('Expected adr mismatch');
        } catch (IntegrationClassificationInvariantViolation $e) {
            $this->assertStringContainsString('adr_id mismatch', $e->getMessage());
        }

        $this->repo->invalidate($prior->persistenceRecordId);
        $this->expectException(IntegrationClassificationInvariantViolation::class);
        $this->repo->supersede(
            $prior->persistenceRecordId,
            AdrMembershipId::fromString('ABSTRACT_IC_SEAT_A'),
            ClassificationDecisionIdentity::fromString('ic-dec-c'),
            ClassificationStatus::CLASSIFIED,
            ['api'],
            IntegrationNature::SOURCE_NATIVE,
            IntegrationBoundary::PROTOCOL_FAMILY_ADAPTER,
            false,
            'fp-c',
            'ic:c',
            'test-actor',
            '2026-10-02T02:00:00Z',
            '2026-10-02T02:00:00Z',
        );
    }

    public function test_invalidate_preserves_classification_payload(): void
    {
        $record = $this->persistFixture(
            adrId: 'ABSTRACT_IC_SEAT',
            decisionId: 'ic-dec-inv',
            status: ClassificationStatus::CLASSIFIED,
            modalities: ['api'],
            nature: IntegrationNature::SOURCE_NATIVE,
            boundary: IntegrationBoundary::PROTOCOL_FAMILY_ADAPTER,
            fingerprint: 'fp-inv',
            idempotencyKey: 'ic:inv',
        );

        $invalidated = $this->repo->invalidate($record->persistenceRecordId);
        $this->assertSame(IntegrationClassificationLifecycleState::INVALIDATED, $invalidated->lifecycleState);
        $this->assertSame(ClassificationStatus::CLASSIFIED, $invalidated->classificationStatus);
        $this->assertSame('fp-inv', $invalidated->evidenceFingerprint);
        $this->assertNull($this->repo->findCurrentByAdrId(AdrMembershipId::fromString('ABSTRACT_IC_SEAT')));
        $this->assertSame(
            ClassificationStatus::UNCLASSIFIED,
            IntegrationClassificationRecord::missingImpliesUnclassified()
        );
    }

    public function test_dual_seats_independent(): void
    {
        $a = $this->persistFixture(
            adrId: 'G1-FIXTURE-IC-01',
            decisionId: 'ic-dec-g1',
            status: ClassificationStatus::CLASSIFIED,
            modalities: ['api'],
            nature: IntegrationNature::SOURCE_NATIVE,
            boundary: IntegrationBoundary::PROTOCOL_FAMILY_ADAPTER,
            fingerprint: 'fp-g1',
            idempotencyKey: 'ic:g1',
        );
        $b = $this->persistFixture(
            adrId: 'G6-FIXTURE-IC-01',
            decisionId: 'ic-dec-g6',
            status: ClassificationStatus::CLASSIFIED,
            modalities: ['api'],
            nature: IntegrationNature::EXTERNAL_DEPENDENCY,
            boundary: IntegrationBoundary::EXTERNAL_AGGREGATOR,
            fingerprint: 'fp-g6',
            idempotencyKey: 'ic:g6',
            externalDependencyReference: 'aggregator://fixture',
        );

        $this->assertNotSame($a->adrId->value, $b->adrId->value);
        $this->assertSame(2, CghiaIntegrationClassification::query()->where('lifecycle_state', 'ACTIVE')->count());
        $this->assertSame(IntegrationNature::EXTERNAL_DEPENDENCY, $b->integrationNature);
        $this->assertNotSame(IntegrationNature::SOURCE_NATIVE, $b->integrationNature);
    }

    public function test_rejects_source_native_with_external_aggregator_boundary(): void
    {
        $this->expectException(IntegrationClassificationInvariantViolation::class);
        $this->persistFixture(
            adrId: 'ABSTRACT_IC_SEAT',
            decisionId: 'ic-dec-agg-native',
            status: ClassificationStatus::CLASSIFIED,
            modalities: ['api'],
            nature: IntegrationNature::SOURCE_NATIVE,
            boundary: IntegrationBoundary::EXTERNAL_AGGREGATOR,
            fingerprint: 'fp-bad',
            idempotencyKey: 'ic:bad-agg',
        );
    }

    public function test_license_access_reuse_remain_separate(): void
    {
        $record = $this->persistFixture(
            adrId: 'ABSTRACT_IC_SEAT',
            decisionId: 'ic-dec-lar',
            status: ClassificationStatus::CLASSIFIED,
            modalities: ['api'],
            nature: IntegrationNature::SOURCE_NATIVE,
            boundary: IntegrationBoundary::PROTOCOL_FAMILY_ADAPTER,
            fingerprint: 'fp-lar',
            idempotencyKey: 'ic:lar',
            license: 'license://x',
            access: 'access://y',
            reuse: 'reuse://z',
        );

        $this->assertSame('license://x', $record->licenseReference);
        $this->assertSame('access://y', $record->accessReference);
        $this->assertSame('reuse://z', $record->reuseReference);
        $this->assertNotSame($record->licenseReference, $record->accessReference);
        $this->assertNotSame($record->accessReference, $record->reuseReference);
    }

    public function test_path_family_hint_is_non_authoritative_storage_only(): void
    {
        $record = $this->persistFixture(
            adrId: 'ABSTRACT_IC_SEAT',
            decisionId: 'ic-dec-hint',
            status: ClassificationStatus::CLASSIFIED,
            modalities: ['api'],
            nature: IntegrationNature::SOURCE_NATIVE,
            boundary: IntegrationBoundary::PROTOCOL_FAMILY_ADAPTER,
            fingerprint: 'fp-hint',
            idempotencyKey: 'ic:hint',
            pathFamilyHint: 'P12',
        );
        $this->assertSame('P12', $record->pathFamilyHint);
        $this->assertFalse(Schema::hasColumn(IntegrationClassificationPersistenceContract::TABLE, 'path_status'));
        $this->assertFalse(Schema::hasColumn(IntegrationClassificationPersistenceContract::TABLE, 'eligibility_state'));
    }

    public function test_metadata_rejects_cap_path_d10_authority(): void
    {
        $this->expectException(IntegrationClassificationInvariantViolation::class);
        $this->persistFixture(
            adrId: 'ABSTRACT_IC_SEAT',
            decisionId: 'ic-dec-meta',
            status: ClassificationStatus::CLASSIFIED,
            modalities: ['api'],
            nature: IntegrationNature::SOURCE_NATIVE,
            boundary: IntegrationBoundary::PROTOCOL_FAMILY_ADAPTER,
            fingerprint: 'fp-meta',
            idempotencyKey: 'ic:meta',
            metadata: ['activation_state' => 'ACTIVE'],
        );
    }

    public function test_unknown_access_modality_rejected(): void
    {
        $this->expectException(IntegrationClassificationInvariantViolation::class);
        $this->persistFixture(
            adrId: 'ABSTRACT_IC_SEAT',
            decisionId: 'ic-dec-mod',
            status: ClassificationStatus::CLASSIFIED,
            modalities: ['not_a_real_modality'],
            nature: IntegrationNature::SOURCE_NATIVE,
            boundary: IntegrationBoundary::PROTOCOL_FAMILY_ADAPTER,
            fingerprint: 'fp-mod',
            idempotencyKey: 'ic:mod',
        );
    }

    public function test_transaction_rollback_on_supersede_failure_leaves_prior_active(): void
    {
        $prior = $this->persistFixture(
            adrId: 'ABSTRACT_IC_SEAT',
            decisionId: 'ic-dec-tx',
            status: ClassificationStatus::CLASSIFIED,
            modalities: ['api'],
            nature: IntegrationNature::SOURCE_NATIVE,
            boundary: IntegrationBoundary::PROTOCOL_FAMILY_ADAPTER,
            fingerprint: 'fp-tx',
            idempotencyKey: 'ic:tx',
        );

        try {
            DB::transaction(function () use ($prior): void {
                $this->repo->supersede(
                    $prior->persistenceRecordId,
                    AdrMembershipId::fromString('ABSTRACT_IC_SEAT'),
                    ClassificationDecisionIdentity::fromString('ic-dec-tx-2'),
                    ClassificationStatus::CLASSIFIED,
                    ['api'],
                    IntegrationNature::SOURCE_NATIVE,
                    IntegrationBoundary::PROTOCOL_FAMILY_ADAPTER,
                    false,
                    'fp-tx-2',
                    'ic:tx-2',
                    'test-actor',
                    '2026-10-02T02:00:00Z',
                    '2026-10-02T02:00:00Z',
                );
                throw new \RuntimeException('force-rollback');
            });
            $this->fail('Expected rollback');
        } catch (\RuntimeException $e) {
            $this->assertSame('force-rollback', $e->getMessage());
        }

        $current = $this->repo->findCurrentByAdrId(AdrMembershipId::fromString('ABSTRACT_IC_SEAT'));
        $this->assertNotNull($current);
        $this->assertTrue($current->persistenceRecordId->equals($prior->persistenceRecordId));
        $this->assertSame(IntegrationClassificationLifecycleState::ACTIVE, $current->lifecycleState);
        $this->assertSame(0, CghiaIntegrationClassification::query()->where('idempotency_key', 'ic:tx-2')->count());
    }

    public function test_identity_and_b7_tables_untouched(): void
    {
        $this->assertTrue(Schema::hasTable('cghia_identity_binding_records'));
        $this->assertTrue(Schema::hasTable('durable_correlation_records'));
        $this->assertSame(0, DB::table('cghia_identity_binding_records')->count());
        $this->assertSame(0, DB::table('durable_correlation_records')->count());

        $this->persistFixture(
            adrId: 'ABSTRACT_IC_SEAT',
            decisionId: 'ic-dec-iso',
            status: ClassificationStatus::CLASSIFIED,
            modalities: ['api'],
            nature: IntegrationNature::SOURCE_NATIVE,
            boundary: IntegrationBoundary::PROTOCOL_FAMILY_ADAPTER,
            fingerprint: 'fp-iso',
            idempotencyKey: 'ic:iso',
        );

        $this->assertSame(0, DB::table('cghia_identity_binding_records')->count());
        $this->assertSame(0, DB::table('durable_correlation_records')->count());
    }

    public function test_stage3_adapter_boundary_is_claim_only_column_not_registry(): void
    {
        $record = $this->persistFixture(
            adrId: 'ABSTRACT_IC_SEAT',
            decisionId: 'ic-dec-s3',
            status: ClassificationStatus::CLASSIFIED,
            modalities: ['api'],
            nature: IntegrationNature::EXTERNAL_DEPENDENCY,
            boundary: IntegrationBoundary::STAGE3_ADAPTER,
            fingerprint: 'fp-s3',
            idempotencyKey: 'ic:s3',
            existingAdapterReference: 'openalex',
        );
        $this->assertSame(IntegrationBoundary::STAGE3_ADAPTER, $record->integrationBoundary);
        $this->assertSame('openalex', $record->existingAdapterReference);
        $this->assertFalse(Schema::hasColumn(IntegrationClassificationPersistenceContract::TABLE, 'source_key'));
    }

    public function test_exactly_one_active_returns_current_record(): void
    {
        $record = $this->persistFixture(
            adrId: 'ABSTRACT_IC_CURRENT',
            decisionId: 'ic-dec-current',
            status: ClassificationStatus::CLASSIFIED,
            modalities: ['api'],
            nature: IntegrationNature::SOURCE_NATIVE,
            boundary: IntegrationBoundary::PROTOCOL_FAMILY_ADAPTER,
            fingerprint: 'fp-current',
            idempotencyKey: 'ic:current',
        );

        $current = $this->repo->findCurrentByAdrId(AdrMembershipId::fromString('ABSTRACT_IC_CURRENT'));
        $this->assertNotNull($current);
        $this->assertSame($record->persistenceRecordId->value, $current->persistenceRecordId->value);
        $this->assertSame('ic-dec-current', $current->classificationDecisionIdentity->value);
        $this->assertSame(IntegrationClassificationLifecycleState::ACTIVE, $current->lifecycleState);
    }

    public function test_no_active_and_unknown_adr_return_null_unclassified(): void
    {
        $this->assertNull(
            $this->repo->findCurrentByAdrId(AdrMembershipId::fromString('ABSTRACT_UNKNOWN_SEAT_IC'))
        );
        $this->assertSame(
            ClassificationStatus::UNCLASSIFIED,
            IntegrationClassificationRecord::missingImpliesUnclassified()
        );
        $this->assertSame(0, CghiaIntegrationClassification::query()->count());
    }

    public function test_only_superseded_returns_null_for_current(): void
    {
        $this->insertCorruptLifecycleRow(
            adrId: 'ABSTRACT_IC_SUPERSEDED_ONLY',
            decisionId: 'ic-dec-sup-only',
            idempotencyKey: 'ic:sup-only',
            fingerprint: 'fp-sup-only',
            lifecycle: IntegrationClassificationLifecycleState::SUPERSEDED,
        );

        $this->assertSame(
            1,
            CghiaIntegrationClassification::query()
                ->where('adr_id', 'ABSTRACT_IC_SUPERSEDED_ONLY')
                ->where('lifecycle_state', IntegrationClassificationLifecycleState::SUPERSEDED->value)
                ->count()
        );
        $this->assertSame(
            0,
            CghiaIntegrationClassification::query()
                ->where('adr_id', 'ABSTRACT_IC_SUPERSEDED_ONLY')
                ->where('lifecycle_state', IntegrationClassificationLifecycleState::ACTIVE->value)
                ->count()
        );
        $this->assertNull(
            $this->repo->findCurrentByAdrId(AdrMembershipId::fromString('ABSTRACT_IC_SUPERSEDED_ONLY'))
        );
    }

    public function test_only_invalidated_returns_null_for_current(): void
    {
        $record = $this->persistFixture(
            adrId: 'ABSTRACT_IC_INVALIDATED_ONLY',
            decisionId: 'ic-dec-inv-only',
            status: ClassificationStatus::CLASSIFIED,
            modalities: ['api'],
            nature: IntegrationNature::SOURCE_NATIVE,
            boundary: IntegrationBoundary::PROTOCOL_FAMILY_ADAPTER,
            fingerprint: 'fp-inv-only',
            idempotencyKey: 'ic:inv-only',
        );
        $this->repo->invalidate($record->persistenceRecordId);

        $this->assertSame(
            1,
            CghiaIntegrationClassification::query()
                ->where('adr_id', 'ABSTRACT_IC_INVALIDATED_ONLY')
                ->where('lifecycle_state', IntegrationClassificationLifecycleState::INVALIDATED->value)
                ->count()
        );
        $this->assertNull(
            $this->repo->findCurrentByAdrId(AdrMembershipId::fromString('ABSTRACT_IC_INVALIDATED_ONLY'))
        );
    }

    public function test_multiple_active_fail_closed_does_not_return_latest(): void
    {
        // Legacy corruption predating the single-ACTIVE index; the index would reject the second row.
        DB::statement('DROP INDEX '.IntegrationClassificationPersistenceContract::SINGLE_ACTIVE_INDEX);

        $this->insertCorruptLifecycleRow(
            adrId: 'ABSTRACT_IC_MULTI_ACTIVE',
            decisionId: 'ic-dec-multi-a',
            idempotencyKey: 'ic:multi-a',
            fingerprint: 'fp-multi-a',
            lifecycle: IntegrationClassificationLifecycleState::ACTIVE,
        );
        $this->insertCorruptLifecycleRow(
            adrId: 'ABSTRACT_IC_MULTI_ACTIVE',
            decisionId: 'ic-dec-multi-b',
            idempotencyKey: 'ic:multi-b',
            fingerprint: 'fp-multi-b',
            lifecycle: IntegrationClassificationLifecycleState::ACTIVE,
        );

        $this->assertSame(
            2,
            CghiaIntegrationClassification::query()
                ->where('adr_id', 'ABSTRACT_IC_MULTI_ACTIVE')
                ->where('lifecycle_state', IntegrationClassificationLifecycleState::ACTIVE->value)
                ->count()
        );

        $beforeCount = CghiaIntegrationClassification::query()->count();

        try {
            $this->repo->findCurrentByAdrId(AdrMembershipId::fromString('ABSTRACT_IC_MULTI_ACTIVE'));
            $this->fail('Expected IntegrationClassificationInvariantViolation for multiple ACTIVE rows.');
        } catch (IntegrationClassificationInvariantViolation $e) {
            $this->assertStringContainsString('Multiple ACTIVE', $e->getMessage());
        }

        $this->assertSame($beforeCount, CghiaIntegrationClassification::query()->count());
        $this->assertSame(
            2,
            CghiaIntegrationClassification::query()
                ->where('adr_id', 'ABSTRACT_IC_MULTI_ACTIVE')
                ->where('lifecycle_state', IntegrationClassificationLifecycleState::ACTIVE->value)
                ->count()
        );
    }

    public function test_find_current_read_path_does_not_write(): void
    {
        $this->persistFixture(
            adrId: 'ABSTRACT_IC_READ_ONLY',
            decisionId: 'ic-dec-ro',
            status: ClassificationStatus::CLASSIFIED,
            modalities: ['api'],
            nature: IntegrationNature::SOURCE_NATIVE,
            boundary: IntegrationBoundary::PROTOCOL_FAMILY_ADAPTER,
            fingerprint: 'fp-ro',
            idempotencyKey: 'ic:ro',
        );

        $before = CghiaIntegrationClassification::query()->get(['id', 'lifecycle_state', 'updated_at'])->toArray();
        $current = $this->repo->findCurrentByAdrId(AdrMembershipId::fromString('ABSTRACT_IC_READ_ONLY'));
        $this->assertNotNull($current);

        $after = CghiaIntegrationClassification::query()->get(['id', 'lifecycle_state', 'updated_at'])->toArray();
        $this->assertSame($before, $after);

        $missingBefore = CghiaIntegrationClassification::query()->count();
        $this->assertNull(
            $this->repo->findCurrentByAdrId(AdrMembershipId::fromString('ABSTRACT_IC_READ_MISSING'))
        );
        $this->assertSame($missingBefore, CghiaIntegrationClassification::query()->count());
    }

    public function test_container_resolves_integration_classification_repository(): void
    {
        $resolved = $this->app->make(IntegrationClassificationRepository::class);

        $this->assertInstanceOf(IntegrationClassificationRepository::class, $resolved);
        $this->assertInstanceOf(EloquentIntegrationClassificationRepository::class, $resolved);

        $again = $this->app->make(IntegrationClassificationRepository::class);
        $this->assertSame($resolved, $again);
    }

    /**
     * @param  list<string>  $modalities
     * @param  list<string>|null  $evidenceRefs
     * @param  array<string, mixed>|null  $metadata
     */
    private function persistFixture(
        string $adrId,
        string $decisionId,
        ClassificationStatus $status,
        array $modalities,
        IntegrationNature $nature,
        IntegrationBoundary $boundary,
        string $fingerprint,
        string $idempotencyKey,
        ?string $canonicalId = null,
        ?array $evidenceRefs = null,
        ?string $pathFamilyHint = null,
        ?string $externalDependencyReference = null,
        ?string $existingAdapterReference = null,
        ?string $license = null,
        ?string $access = null,
        ?string $reuse = null,
        ?array $metadata = null,
    ): IntegrationClassificationRecord {
        return $this->repo->persist(
            AdrMembershipId::fromString($adrId),
            ClassificationDecisionIdentity::fromString($decisionId),
            $status,
            $modalities,
            $nature,
            $boundary,
            false,
            $fingerprint,
            $idempotencyKey,
            'test-actor',
            '2026-10-02T00:00:00Z',
            '2026-10-02T00:00:00Z',
            $canonicalId === null ? null : CanonicalSourceIdentityId::fromString($canonicalId),
            null,
            null,
            null,
            $pathFamilyHint,
            $existingAdapterReference,
            $externalDependencyReference,
            $evidenceRefs,
            $license,
            $access,
            $reuse,
            null,
            $metadata,
        );
    }

    private function insertCorruptLifecycleRow(
        string $adrId,
        string $decisionId,
        string $idempotencyKey,
        string $fingerprint,
        IntegrationClassificationLifecycleState $lifecycle,
    ): void {
        CghiaIntegrationClassification::query()->create([
            'adr_id' => $adrId,
            'canonical_identity_id' => null,
            'identity_binding_ref' => null,
            'classification_decision_identity' => $decisionId,
            'classification_status' => ClassificationStatus::CLASSIFIED->value,
            'access_modality_claims' => ['api'],
            'integration_nature' => IntegrationNature::SOURCE_NATIVE->value,
            'integration_boundary' => IntegrationBoundary::PROTOCOL_FAMILY_ADAPTER->value,
            'protocol_family' => null,
            'source_specific_requirement' => false,
            'source_specific_rationale' => null,
            'path_family_hint' => null,
            'existing_adapter_reference' => null,
            'external_dependency_reference' => null,
            'evidence_references' => [],
            'evidence_fingerprint' => $fingerprint,
            'license_reference' => null,
            'access_reference' => null,
            'reuse_reference' => null,
            'rationale' => null,
            'decision_actor' => 'corruption-fixture',
            'verified_at' => '2026-10-02T00:00:00Z',
            'decision_timestamp' => '2026-10-02T00:00:00Z',
            'lifecycle_state' => $lifecycle->value,
            'schema_version' => IntegrationClassificationRecord::CURRENT_SCHEMA_VERSION,
            'idempotency_key' => $idempotencyKey,
            'superseded_by' => null,
            'metadata' => null,
        ]);
    }
}
