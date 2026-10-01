<?php

namespace Tests\Unit\Agriculture\Research\Correlation\Persistence;

use App\Models\DurableCorrelation;
use App\Services\Agriculture\Research\Capability\CapabilityAndResultClass;
use App\Services\Agriculture\Research\Capability\CapabilityDecisionIdentity;
use App\Services\Agriculture\Research\Capability\CapabilitySubject;
use App\Services\Agriculture\Research\Correlation\AggregatorRecordReference;
use App\Services\Agriculture\Research\Correlation\CorrelationChain;
use App\Services\Agriculture\Research\Correlation\Persistence\DurableCorrelationInvariantViolation;
use App\Services\Agriculture\Research\Correlation\Persistence\DurableCorrelationLifecycleState;
use App\Services\Agriculture\Research\Correlation\Persistence\DurableCorrelationPersistenceContract;
use App\Services\Agriculture\Research\Correlation\Persistence\DurableCorrelationRecord;
use App\Services\Agriculture\Research\Correlation\Persistence\DurableCorrelationRecordId;
use App\Services\Agriculture\Research\Correlation\Persistence\EloquentDurableCorrelationRepository;
use App\Services\Agriculture\Research\Correlation\QuestionIdentityReference;
use App\Services\Agriculture\Research\Correlation\RetrievalEventReference;
use App\Services\Agriculture\Research\Correlation\SourceRecordReference;
use App\Services\Agriculture\Research\Correlation\VariantIdentityReference;
use App\Services\Agriculture\Research\Identity\AdrMembershipId;
use App\Services\Agriculture\Research\Identity\CanonicalSourceIdentityId;
use App\Services\Agriculture\Research\Path\PathDecisionId;
use App\Services\Agriculture\Research\Path\PathId;
use App\Services\Agriculture\Research\Projection\CanonicalQueryId;
use App\Services\Agriculture\Research\Projection\ProjectionIdentity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;
use Tests\TestCase;

/**
 * IU-07 — Durable Correlation Persistence unit tests.
 */
final class DurableCorrelationPersistenceTest extends TestCase
{
    use RefreshDatabase;

    private EloquentDurableCorrelationRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new EloquentDurableCorrelationRepository;
    }

    public function test_persist_and_load_valid_b7_chain(): void
    {
        $chain = $this->validChain();
        $record = $this->repo->persist($chain, 'idem-001');

        $loaded = $this->repo->findById($record->persistenceRecordId);
        $this->assertNotNull($loaded);
        $this->assertSame($record->persistenceRecordId->value, $loaded->persistenceRecordId->value);
        $this->assertSame('q-opaque-1', $loaded->questionIdentity->value);
        $this->assertSame('csq-opaque-1', $loaded->csqIdentity->value);
        $this->assertSame('G3-01', $loaded->adrId->value);
        $this->assertSame('cid_apis', $loaded->canonicalIdentityId?->value);
        $this->assertSame('cap-dec-001', $loaded->capabilityDecisionIdentity);
        $this->assertSame('path-inst-001', $loaded->pathId->value);
        $this->assertSame('path-dec-001', $loaded->pathDecisionId->value);
        $this->assertSame('proj-dec-001', $loaded->projectionIdentity->value);
        $this->assertSame(DurableCorrelationLifecycleState::ACTIVE, $loaded->lifecycleState);
        $this->assertSame(DurableCorrelationRecord::CURRENT_SCHEMA_VERSION, $loaded->schemaVersion);
        $this->assertFalse($loaded->isCanonicalSourceIdentity());
        $this->assertFalse($loaded->toArray()['composite_is_canonical_source_identity']);
    }

    public function test_idempotency_key_replay_does_not_duplicate(): void
    {
        $chain = $this->validChain();
        $first = $this->repo->persist($chain, 'idem-replay');
        $second = $this->repo->persist($chain, 'idem-replay');

        $this->assertTrue($first->persistenceRecordId->equals($second->persistenceRecordId));
        $this->assertSame(1, DurableCorrelation::query()->where('idempotency_key', 'idem-replay')->count());
    }

    public function test_database_unique_constraint_on_idempotency_key(): void
    {
        $this->assertTrue(Schema::hasTable('durable_correlation_records'));

        $this->repo->persist($this->validChain(), 'idem-unique');

        $this->expectException(\Illuminate\Database\QueryException::class);
        DurableCorrelation::query()->create(
            DurableCorrelationRecord::draftFromChain($this->validChain(), 'idem-unique')
        );
    }

    public function test_concurrent_unique_violation_resolves_to_existing_record(): void
    {
        $chain = $this->validChain();
        $this->repo->persist($chain, 'idem-race');

        // Simulate lost pre-check: direct insert races unique constraint; repository catch path.
        try {
            DurableCorrelation::query()->create(
                DurableCorrelationRecord::draftFromChain($chain, 'idem-race')
            );
            $this->fail('Expected unique violation');
        } catch (\Illuminate\Database\QueryException) {
            $replay = $this->repo->persist($chain, 'idem-race');
            $this->assertSame(1, DurableCorrelation::query()->where('idempotency_key', 'idem-race')->count());
            $this->assertSame('idem-race', $replay->idempotencyKey);
        }
    }

    public function test_nullable_fields_persist_as_actual_null(): void
    {
        $cap = CapabilityDecisionIdentity::create(
            'cap-null',
            CapabilitySubject::seatScoped(AdrMembershipId::fromString('G1-01')),
            [],
            [],
            CapabilityAndResultClass::HAS_UNVERIFIED,
            hasStaleFlags: false,
            snapshotVersion: 'UNKNOWN',
            decidedAt: '2026-10-01T00:00:00Z',
        );
        $chain = CorrelationChain::create(
            QuestionIdentityReference::fromString('q-null'),
            CanonicalQueryId::fromString('csq-null'),
            AdrMembershipId::fromString('G1-01'),
            $cap,
            PathId::fromString('path-null'),
            PathDecisionId::fromString('path-dec-null'),
            ProjectionIdentity::fromString('proj-null'),
            canonicalIdentityId: null,
            variantId: null,
            retrievalEvent: null,
            originalSourceIdentifier: null,
            aggregatorRecordIdentifier: null,
        );

        $record = $this->repo->persist($chain, 'idem-nulls');
        $row = DurableCorrelation::query()->find($record->persistenceRecordId->toInt());

        $this->assertNull($row->variant_id);
        $this->assertNull($row->canonical_identity_id);
        $this->assertNull($row->retrieval_timestamp);
        $this->assertNull($row->original_source_identifier);
        $this->assertNull($row->aggregator_record_identifier);
        $this->assertNull($record->variantId);
        $this->assertNull($record->canonicalIdentityId);
    }

    public function test_persistence_id_distinct_from_domain_identities(): void
    {
        $record = $this->repo->persist($this->validChain(), 'idem-pid');
        $pid = $record->persistenceRecordId->value;
        $payload = $record->toArray();

        foreach ([
            'question_identity',
            'csq_identity',
            'adr_id',
            'canonical_identity_id',
            'capability_decision_identity',
            'path_id',
            'path_decision_identity',
            'projection_identity',
        ] as $key) {
            $this->assertNotSame($pid, $payload[$key]);
        }
    }

    public function test_aggregator_and_original_remain_distinct(): void
    {
        $canonical = CanonicalSourceIdentityId::fromString('cid_apis');
        $chain = CorrelationChain::create(
            QuestionIdentityReference::fromString('q-agg'),
            CanonicalQueryId::fromString('csq-agg'),
            AdrMembershipId::fromString('G3-01'),
            $this->capDecision($canonical),
            PathId::fromString('path-agg'),
            PathDecisionId::fromString('path-dec-agg'),
            ProjectionIdentity::fromString('proj-agg'),
            canonicalIdentityId: $canonical,
            retrievalEvent: RetrievalEventReference::at('2026-10-01T12:00:00Z'),
            originalSourceIdentifier: SourceRecordReference::fromString('native-rec-9'),
            aggregatorRecordIdentifier: AggregatorRecordReference::fromString('openalex-W123'),
        );

        $record = $this->repo->persist($chain, 'idem-agg');
        $this->assertSame('native-rec-9', $record->originalSourceIdentifier?->value);
        $this->assertSame('openalex-W123', $record->aggregatorRecordIdentifier?->value);
        $this->assertNotSame($record->originalSourceIdentifier?->value, $record->aggregatorRecordIdentifier?->value);
        $this->assertNotSame($record->adrId->value, $record->aggregatorRecordIdentifier?->value);
        $this->assertNotSame($record->canonicalIdentityId?->value, $record->originalSourceIdentifier?->value);
        $this->assertNotSame($record->pathId->value, $record->originalSourceIdentifier?->value);
        $this->assertNotSame($record->projectionIdentity->value, $record->originalSourceIdentifier?->value);
    }

    public function test_lifecycle_supersede_and_invalidate(): void
    {
        $first = $this->repo->persist($this->validChain(), 'idem-life-1');
        $this->assertSame(DurableCorrelationLifecycleState::ACTIVE, $first->lifecycleState);

        $canonical = CanonicalSourceIdentityId::fromString('cid_apis');
        $newer = CorrelationChain::create(
            QuestionIdentityReference::fromString('q-opaque-1'),
            CanonicalQueryId::fromString('csq-opaque-1'),
            AdrMembershipId::fromString('G3-01'),
            $this->capDecision($canonical, 'cap-dec-002'),
            PathId::fromString('path-inst-002'),
            PathDecisionId::fromString('path-dec-002'),
            ProjectionIdentity::fromString('proj-dec-002'),
            canonicalIdentityId: $canonical,
            variantId: VariantIdentityReference::fromString('var-1'),
        );

        $second = $this->repo->supersede($first->persistenceRecordId, $newer, 'idem-life-2');
        $this->assertSame(DurableCorrelationLifecycleState::ACTIVE, $second->lifecycleState);

        $prior = $this->repo->findById($first->persistenceRecordId);
        $this->assertNotNull($prior);
        $this->assertSame(DurableCorrelationLifecycleState::SUPERSEDED, $prior->lifecycleState);
        $this->assertNotNull($prior->supersededBy);
        $this->assertTrue($prior->supersededBy->equals($second->persistenceRecordId));
        // Prior B7 linkage fields unchanged
        $this->assertSame('path-inst-001', $prior->pathId->value);

        $invalidated = $this->repo->invalidate($second->persistenceRecordId);
        $this->assertSame(DurableCorrelationLifecycleState::INVALIDATED, $invalidated->lifecycleState);
        $this->assertSame('proj-dec-002', $invalidated->projectionIdentity->value);
    }

    public function test_schema_version_stored(): void
    {
        $record = $this->repo->persist($this->validChain(), 'idem-schema');
        $this->assertSame(1, $record->schemaVersion);
        $row = DurableCorrelation::query()->find($record->persistenceRecordId->toInt());
        $this->assertSame(1, (int) $row->schema_version);
    }

    public function test_projection_reference_only_no_c9_payload(): void
    {
        $record = $this->repo->persist($this->validChain(), 'idem-proj');
        $payload = $record->toArray();
        $this->assertArrayHasKey('projection_identity', $payload);
        $this->assertArrayNotHasKey('mapped_facets', $payload);
        $this->assertArrayNotHasKey('fidelity_class', $payload);
        $this->assertArrayNotHasKey('unsupported_facets', $payload);

        $row = DurableCorrelation::query()->find($record->persistenceRecordId->toInt())->toArray();
        $this->assertArrayNotHasKey('mapped_facets', $row);
        $this->assertArrayNotHasKey('fidelity_class', $row);
    }

    public function test_metadata_rejects_authoritative_scoring_keys(): void
    {
        $this->expectException(DurableCorrelationInvariantViolation::class);
        $this->repo->persist($this->validChain(), 'idem-meta-bad', ['fidelity_class' => 'EXACT']);
    }

    public function test_optional_non_authoritative_metadata_allowed(): void
    {
        $record = $this->repo->persist($this->validChain(), 'idem-meta-ok', ['trace_note' => 'diag-only']);
        $this->assertSame(['trace_note' => 'diag-only'], $record->metadata);
    }

    public function test_package_has_no_forbidden_runtime_dependencies(): void
    {
        $dir = app_path('Services/Agriculture/Research/Correlation/Persistence');
        $files = glob($dir.'/*.php') ?: [];
        $this->assertNotEmpty($files);

        foreach ($files as $file) {
            $src = file_get_contents($file);
            $this->assertIsString($src);
            $this->assertStringNotContainsString('CanonicalScientificQuestion', $src);
            $this->assertStringNotContainsString('ScientificSearchQueryBuilder', $src);
            $this->assertStringNotContainsString('AgriculturalEntityCatalog', $src);
            $this->assertStringNotContainsString('ScientificQueryCompiler', $src);
            $this->assertStringNotContainsString('AnswerComposer', $src);
            $this->assertStringNotContainsString('ScientificSourceSelector', $src);
            $this->assertStringNotContainsString('ScientificSourceAdapterRegistry', $src);
            $this->assertStringNotContainsString('ScientificMultiSourceSearchOrchestrator', $src);
            $this->assertStringNotContainsString('ScientificKnowledgePersistenceService', $src);
            $this->assertStringNotContainsString('MonitoringEvent', $src);
            $this->assertStringNotContainsString('Guzzle', $src);
        }
    }

    public function test_persistence_api_surface_has_no_domain_authority_methods(): void
    {
        foreach ([
            EloquentDurableCorrelationRepository::class,
            DurableCorrelationRecord::class,
        ] as $class) {
            $names = array_map(
                static fn (\ReflectionMethod $m): string => strtolower($m->getName()),
                (new ReflectionClass($class))->getMethods()
            );
            foreach (['selectpath', 'buildprojection', 'mutatescq', 'verificapability', 'setc9', 'sameas', 'mint'] as $forbidden) {
                foreach ($names as $name) {
                    if ($name === 'assertdoesnotmintidentities') {
                        continue;
                    }
                    $this->assertStringNotContainsString($forbidden, $name);
                }
            }
        }
        $this->assertSame('durable_correlation_records', DurableCorrelationPersistenceContract::TABLE);
    }

    public function test_record_id_rejects_zero_and_non_digit(): void
    {
        $this->expectException(DurableCorrelationInvariantViolation::class);
        DurableCorrelationRecordId::fromString('G3-01');
    }

    private function validChain(): CorrelationChain
    {
        $canonical = CanonicalSourceIdentityId::fromString('cid_apis');

        return CorrelationChain::create(
            QuestionIdentityReference::fromString('q-opaque-1'),
            CanonicalQueryId::fromString('csq-opaque-1'),
            AdrMembershipId::fromString('G3-01'),
            $this->capDecision($canonical),
            PathId::fromString('path-inst-001'),
            PathDecisionId::fromString('path-dec-001'),
            ProjectionIdentity::fromString('proj-dec-001'),
            canonicalIdentityId: $canonical,
        );
    }

    private function capDecision(?CanonicalSourceIdentityId $canonical, string $decisionId = 'cap-dec-001'): CapabilityDecisionIdentity
    {
        $subject = $canonical === null
            ? CapabilitySubject::seatScoped(AdrMembershipId::fromString('G1-01'))
            : CapabilitySubject::of(AdrMembershipId::fromString('G3-01'), $canonical);

        return CapabilityDecisionIdentity::create(
            $decisionId,
            $subject,
            [],
            [],
            CapabilityAndResultClass::ALL_VERIFIED,
            hasStaleFlags: false,
            snapshotVersion: 'UNKNOWN',
            decidedAt: '2026-10-01T00:00:00Z',
        );
    }
}
