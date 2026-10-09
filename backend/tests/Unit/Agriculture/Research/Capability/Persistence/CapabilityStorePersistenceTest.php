<?php

namespace Tests\Unit\Agriculture\Research\Capability\Persistence;

use App\Models\CghiaCapabilityCell;
use App\Services\Agriculture\Research\Capability\CapabilityAccessMethod;
use App\Services\Agriculture\Research\Capability\CapabilityAndResultClass;
use App\Services\Agriculture\Research\Capability\CapabilityDimension;
use App\Services\Agriculture\Research\Capability\CapabilityEvidenceFreshness;
use App\Services\Agriculture\Research\Capability\CapabilityInvariantViolation;
use App\Services\Agriculture\Research\Capability\CapabilityRecord;
use App\Services\Agriculture\Research\Capability\CapabilityRecordId;
use App\Services\Agriculture\Research\Capability\CapabilityRequirementEvaluator;
use App\Services\Agriculture\Research\Capability\CapabilityRequirementSet;
use App\Services\Agriculture\Research\Capability\CapabilityState;
use App\Services\Agriculture\Research\Capability\CapabilitySubject;
use App\Services\Agriculture\Research\Capability\Persistence\CapabilityCellLifecycle;
use App\Services\Agriculture\Research\Capability\Persistence\CapabilityStoreRepository;
use App\Services\Agriculture\Research\Capability\Persistence\EloquentCapabilityStoreRepository;
use App\Services\Agriculture\Research\Identity\AdrMembershipId;
use App\Services\Agriculture\Research\Identity\CanonicalSourceIdentityId;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Cap Store Persistence (ADR-023 §8.16 CPD-A–D) — synthetic fixture identifiers only.
 */
final class CapabilityStorePersistenceTest extends TestCase
{
    use RefreshDatabase;

    private const TABLE = 'cghia_capability_cell_records';

    private EloquentCapabilityStoreRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new EloquentCapabilityStoreRepository;
    }

    protected function tearDown(): void
    {
        CghiaCapabilityCell::flushEventListeners();
        parent::tearDown();
    }

    public function test_schema_table_exists_with_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable(self::TABLE));
        foreach ([
            'id', 'capability_record_id', 'adr_id', 'canonical_identity_id',
            'dimension_family', 'dimension_code', 'seat_override_applied',
            'capability_state', 'evidence_freshness_class', 'snapshot_version', 'update_method',
            'limitation_text', 'verified_at', 'observed_at',
            'capability_evidence_refs', 'cell_lifecycle', 'created_at', 'updated_at',
        ] as $column) {
            $this->assertTrue(Schema::hasColumn(self::TABLE, $column), $column);
        }
        foreach (['lifecycle_state', 'superseded_by', 'idempotency_key', 'override_id', 'seat_id', 'dossier_id',
            'capability_decision_identity', 'source_key', 'eligibility_state', 'path_status', 'fidelity_class'] as $forbidden) {
            $this->assertFalse(Schema::hasColumn(self::TABLE, $forbidden), $forbidden);
        }
    }

    public function test_round_trip_preserves_fact_fields(): void
    {
        $subject = CapabilitySubject::of(
            AdrMembershipId::fromString('ABSTRACT_CAP_SEAT'),
            CanonicalSourceIdentityId::fromString('ABSTRACT_CANONICAL'),
        );
        $dimension = CapabilityDimension::scientificFacetContentAbout('facet.crop');
        $record = CapabilityRecord::create(
            CapabilityRecordId::fromString('cap-rt-1'),
            $subject,
            $dimension,
            CapabilityState::PARTIAL,
            CapabilityEvidenceFreshness::STALE,
            'snap-2026-10',
            'manual_review',
            'Only titles carry crop facets',
            '2026-10-01T00:00:00Z',
            '2026-10-02T00:00:00Z',
            true,
        );

        $this->repo->persistCurrent($record, ['evidence://cap/b', 'evidence://cap/a']);

        $loaded = $this->repo->findFact(CapabilityRecordId::fromString('cap-rt-1'));
        $this->assertNotNull($loaded);
        $this->assertSame($record->toArray(), $loaded->record->toArray());
        $this->assertSame(['evidence://cap/a', 'evidence://cap/b'], $loaded->evidenceRefs);
        $this->assertSame(CapabilityCellLifecycle::CURRENT, $loaded->lifecycle);
    }

    public function test_access_method_dimension_round_trips(): void
    {
        $subject = $this->seat('ABSTRACT_CAP_SEAT');
        $api = CapabilityDimension::accessMethod(CapabilityAccessMethod::OAI_PMH);
        $this->repo->persistCurrent($this->record('cap-am-1', $subject, $api, CapabilityState::VERIFIED), ['ev-1']);

        $loaded = $this->repo->findCurrentFact($subject, $api, false);
        $this->assertNotNull($loaded);
        $this->assertTrue($loaded->record->dimension->equals($api));
        $this->assertNull($loaded->record->subject->canonicalIdentityId);
    }

    public function test_verified_without_evidence_rejected(): void
    {
        $this->assertWriteRejectedWithoutEvidence(CapabilityState::VERIFIED);
    }

    public function test_partial_without_evidence_rejected(): void
    {
        $this->assertWriteRejectedWithoutEvidence(CapabilityState::PARTIAL);
    }

    public function test_unavailable_without_evidence_rejected(): void
    {
        $this->assertWriteRejectedWithoutEvidence(CapabilityState::UNAVAILABLE);
    }

    public function test_partial_without_limitation_rejected(): void
    {
        try {
            CapabilityRecord::create(
                CapabilityRecordId::fromString('cap-p-nolimit'),
                $this->seat('ABSTRACT_CAP_SEAT'),
                $this->api(),
                CapabilityState::PARTIAL,
            );
            $this->fail('PARTIAL without limitation_text accepted.');
        } catch (CapabilityInvariantViolation) {
            $this->addToAssertionCount(1);
        }

        $this->assertSame(0, CghiaCapabilityCell::query()->count());
    }

    public function test_persisted_partial_without_limitation_fails_closed_on_load(): void
    {
        $row = $this->rawRow('raw-partial', null, 'CURRENT');
        $row['capability_state'] = 'PARTIAL';
        DB::table(self::TABLE)->insert($row);

        $this->expectException(CapabilityInvariantViolation::class);
        $this->repo->findFact(CapabilityRecordId::fromString('raw-partial'));
    }

    public function test_unverified_and_not_applicable_may_omit_evidence(): void
    {
        $subject = $this->seat('ABSTRACT_CAP_SEAT');
        $a = $this->repo->persistCurrent(
            $this->record('cap-unv', $subject, CapabilityDimension::licenseConstraint('lic.cc-by'), CapabilityState::UNVERIFIED),
            [],
        );
        $b = $this->repo->persistCurrent(
            $this->record('cap-na', $subject, CapabilityDimension::licenseConstraint('lic.nc'), CapabilityState::NOT_APPLICABLE),
            [],
        );

        $this->assertSame([], $a->evidenceRefs);
        $this->assertSame([], $b->evidenceRefs);
        $this->assertSame(2, CghiaCapabilityCell::query()->count());
    }

    public function test_duplicate_ref_within_fact_rejected(): void
    {
        $this->expectException(CapabilityInvariantViolation::class);
        $this->repo->persistCurrent(
            $this->record('cap-dup-ref', $this->seat('ABSTRACT_CAP_SEAT'), $this->api(), CapabilityState::VERIFIED),
            ['ev-1', 'ev-1'],
        );
    }

    public function test_empty_whitespace_and_non_string_refs_rejected(): void
    {
        foreach ([[''], ['   '], [' ev-1'], [42], [null]] as $i => $refs) {
            try {
                $this->repo->persistCurrent(
                    $this->record('cap-bad-ref-'.$i, $this->seat('ABSTRACT_CAP_SEAT'), $this->api(), CapabilityState::UNVERIFIED),
                    $refs,
                );
                $this->fail('Malformed evidence ref accepted: '.json_encode($refs));
            } catch (CapabilityInvariantViolation) {
                $this->addToAssertionCount(1);
            }
        }
        $this->assertSame(0, CghiaCapabilityCell::query()->count());
    }

    public function test_same_ref_across_facts_permitted(): void
    {
        $subject = $this->seat('ABSTRACT_CAP_SEAT');
        $this->repo->persistCurrent($this->record('cap-shared-ref-1', $subject, $this->api(), CapabilityState::VERIFIED), ['ev-shared']);
        $this->repo->persistCurrent(
            $this->record('cap-shared-ref-2', $subject, CapabilityDimension::accessMethod(CapabilityAccessMethod::RSS), CapabilityState::UNAVAILABLE),
            ['ev-shared'],
        );

        $this->assertSame(2, CghiaCapabilityCell::query()->count());
    }

    public function test_duplicate_record_id_rejected(): void
    {
        $this->repo->persistCurrent($this->record('cap-dup-id', $this->seat('ABSTRACT_CAP_SEAT'), $this->api(), CapabilityState::VERIFIED), ['ev-1']);

        $this->expectException(CapabilityInvariantViolation::class);
        $this->repo->persistCurrent(
            $this->record('cap-dup-id', $this->seat('ABSTRACT_OTHER_SEAT'), $this->api(), CapabilityState::VERIFIED),
            ['ev-1'],
        );
    }

    public function test_second_current_for_same_natural_tuple_rejected(): void
    {
        $subject = $this->seat('ABSTRACT_CAP_SEAT');
        $this->repo->persistCurrent($this->record('cap-cur-1', $subject, $this->api(), CapabilityState::VERIFIED), ['ev-1']);

        try {
            $this->repo->persistCurrent($this->record('cap-cur-2', $subject, $this->api(), CapabilityState::UNAVAILABLE), ['ev-2']);
            $this->fail('Second CURRENT fact accepted.');
        } catch (CapabilityInvariantViolation) {
            $this->addToAssertionCount(1);
        }

        $this->assertSame(1, CghiaCapabilityCell::query()->count());
    }

    public function test_database_partial_unique_indexes_enforce_single_current(): void
    {
        foreach ([null, 'ABSTRACT_CANONICAL'] as $canonical) {
            DB::table(self::TABLE)->insert($this->rawRow('raw-a-'.($canonical ?? 'null'), $canonical, 'CURRENT'));
            DB::table(self::TABLE)->insert($this->rawRow('raw-h-'.($canonical ?? 'null'), $canonical, 'HISTORICAL'));

            try {
                DB::table(self::TABLE)->insert($this->rawRow('raw-b-'.($canonical ?? 'null'), $canonical, 'CURRENT'));
                $this->fail('Database accepted a second CURRENT row for canonical='.var_export($canonical, true));
            } catch (QueryException) {
                $this->addToAssertionCount(1);
            }
        }

        $this->assertSame(4, DB::table(self::TABLE)->count());
    }

    public function test_shared_and_override_current_coexist(): void
    {
        $subject = $this->seat('ABSTRACT_CAP_SEAT');
        $this->repo->persistCurrent($this->record('cap-shared', $subject, $this->api(), CapabilityState::VERIFIED), ['ev-1']);
        $this->repo->persistCurrent(
            $this->record('cap-override', $subject, $this->api(), CapabilityState::PARTIAL, 'Override limited', true),
            ['ev-2'],
        );

        $this->assertSame('cap-shared', $this->repo->findCurrentFact($subject, $this->api(), false)?->record->recordId->value);
        $this->assertSame('cap-override', $this->repo->findCurrentFact($subject, $this->api(), true)?->record->recordId->value);
        $this->assertCount(2, $this->repo->loadCurrentForSubject($subject));
    }

    public function test_supersession_archives_prior_and_inserts_replacement(): void
    {
        $subject = $this->seat('ABSTRACT_CAP_SEAT');
        $this->repo->persistCurrent($this->record('cap-v1', $subject, $this->api(), CapabilityState::UNVERIFIED), []);

        $new = $this->repo->supersedeCurrent(
            CapabilityRecordId::fromString('cap-v1'),
            $this->record('cap-v2', $subject, $this->api(), CapabilityState::VERIFIED),
            ['ev-v2'],
        );

        $this->assertSame(CapabilityCellLifecycle::CURRENT, $new->lifecycle);
        $prior = $this->repo->findFact(CapabilityRecordId::fromString('cap-v1'));
        $this->assertSame(CapabilityCellLifecycle::HISTORICAL, $prior?->lifecycle);
        $this->assertSame(CapabilityState::UNVERIFIED, $prior?->record->state);
        $this->assertSame('cap-v2', $this->repo->findCurrentFact($subject, $this->api(), false)?->record->recordId->value);
    }

    public function test_historical_facts_excluded_from_current_loads(): void
    {
        $subject = $this->seat('ABSTRACT_CAP_SEAT');
        $this->repo->persistCurrent($this->record('cap-h1', $subject, $this->api(), CapabilityState::UNAVAILABLE), ['ev-1']);
        $this->repo->supersedeCurrent(
            CapabilityRecordId::fromString('cap-h1'),
            $this->record('cap-h2', $subject, $this->api(), CapabilityState::VERIFIED),
            ['ev-2'],
        );

        $current = $this->repo->loadCurrentForSubject($subject);
        $this->assertCount(1, $current);
        $this->assertSame('cap-h2', $current[0]->recordId->value);
        $this->assertSame(2, CghiaCapabilityCell::query()->count());
    }

    public function test_failed_supersession_rolls_back_lifecycle_and_insert(): void
    {
        $subject = $this->seat('ABSTRACT_CAP_SEAT');
        $this->repo->persistCurrent($this->record('cap-rb-1', $subject, $this->api(), CapabilityState::UNVERIFIED), []);

        CghiaCapabilityCell::creating(static function (): void {
            throw new \RuntimeException('injected insert failure');
        });

        try {
            $this->repo->supersedeCurrent(
                CapabilityRecordId::fromString('cap-rb-1'),
                $this->record('cap-rb-2', $subject, $this->api(), CapabilityState::VERIFIED),
                ['ev-1'],
            );
            $this->fail('Injected failure did not propagate.');
        } catch (\RuntimeException $e) {
            $this->assertSame('injected insert failure', $e->getMessage());
        }

        CghiaCapabilityCell::flushEventListeners();

        $this->assertSame(CapabilityCellLifecycle::CURRENT, $this->repo->findFact(CapabilityRecordId::fromString('cap-rb-1'))?->lifecycle);
        $this->assertNull($this->repo->findFact(CapabilityRecordId::fromString('cap-rb-2')));
        $this->assertSame(1, CghiaCapabilityCell::query()->count());
    }

    public function test_supersession_with_mismatched_tuple_rejected(): void
    {
        $subject = $this->seat('ABSTRACT_CAP_SEAT');
        $this->repo->persistCurrent($this->record('cap-mm-1', $subject, $this->api(), CapabilityState::UNVERIFIED), []);

        $mismatches = [
            'subject' => $this->record('cap-mm-s', $this->seat('ABSTRACT_OTHER_SEAT'), $this->api(), CapabilityState::VERIFIED),
            'canonical' => $this->record('cap-mm-c', CapabilitySubject::of(
                AdrMembershipId::fromString('ABSTRACT_CAP_SEAT'),
                CanonicalSourceIdentityId::fromString('ABSTRACT_CANONICAL'),
            ), $this->api(), CapabilityState::VERIFIED),
            'dimension' => $this->record('cap-mm-d', $subject, CapabilityDimension::accessMethod(CapabilityAccessMethod::RSS), CapabilityState::VERIFIED),
            'override' => $this->record('cap-mm-o', $subject, $this->api(), CapabilityState::VERIFIED, null, true),
        ];

        foreach ($mismatches as $label => $replacement) {
            try {
                $this->repo->supersedeCurrent(CapabilityRecordId::fromString('cap-mm-1'), $replacement, ['ev-1']);
                $this->fail("Mismatched {$label} supersession accepted.");
            } catch (CapabilityInvariantViolation) {
                $this->addToAssertionCount(1);
            }
        }

        $this->assertSame(CapabilityCellLifecycle::CURRENT, $this->repo->findFact(CapabilityRecordId::fromString('cap-mm-1'))?->lifecycle);
        $this->assertSame(1, CghiaCapabilityCell::query()->count());
    }

    public function test_supersession_of_historical_or_unknown_prior_rejected(): void
    {
        $subject = $this->seat('ABSTRACT_CAP_SEAT');
        $this->repo->persistCurrent($this->record('cap-x1', $subject, $this->api(), CapabilityState::UNVERIFIED), []);
        $this->repo->supersedeCurrent(
            CapabilityRecordId::fromString('cap-x1'),
            $this->record('cap-x2', $subject, $this->api(), CapabilityState::VERIFIED),
            ['ev-1'],
        );

        foreach (['cap-x1', 'cap-missing'] as $priorId) {
            try {
                $this->repo->supersedeCurrent(
                    CapabilityRecordId::fromString($priorId),
                    $this->record('cap-x3-'.$priorId, $subject, $this->api(), CapabilityState::VERIFIED),
                    ['ev-1'],
                );
                $this->fail("Supersession of {$priorId} accepted.");
            } catch (CapabilityInvariantViolation) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_missing_current_returns_null_without_auto_create(): void
    {
        $subject = $this->seat('ABSTRACT_MISSING_SEAT');

        $this->assertNull($this->repo->findCurrentFact($subject, $this->api(), false));
        $this->assertSame([], $this->repo->loadCurrentForSubject($subject));
        $this->assertNull($this->repo->findFact(CapabilityRecordId::fromString('cap-none')));
        $this->assertSame(0, CghiaCapabilityCell::query()->count());

        $decision = (new CapabilityRequirementEvaluator)->evaluate(
            $subject,
            CapabilityRequirementSet::of([$this->api()]),
            $this->repo->loadCurrentForSubject($subject),
            'dec-missing',
            '2026-10-09T00:00:00Z',
        );
        $this->assertSame(CapabilityAndResultClass::HAS_UNVERIFIED, $decision->andResultClass);
        $this->assertTrue($decision->perCellOutcomes[0]['missing_record']);
        $this->assertSame(0, CghiaCapabilityCell::query()->count());
    }

    public function test_multiple_current_matches_fail_closed(): void
    {
        DB::statement('DROP INDEX cghia_cap_cells_current_seat_scoped_uq');
        DB::table(self::TABLE)->insert($this->rawRow('corrupt-1', null, 'CURRENT'));
        DB::table(self::TABLE)->insert($this->rawRow('corrupt-2', null, 'CURRENT'));

        $subject = $this->seat('ABSTRACT_CAP_SEAT');

        try {
            $this->repo->findCurrentFact($subject, $this->api(), false);
            $this->fail('findCurrentFact picked a row from an ambiguous CURRENT set.');
        } catch (CapabilityInvariantViolation) {
            $this->addToAssertionCount(1);
        }

        $this->expectException(CapabilityInvariantViolation::class);
        $this->repo->loadCurrentForSubject($subject);
    }

    public function test_load_current_uses_exact_subject_match(): void
    {
        $seat = $this->seat('ABSTRACT_CAP_SEAT');
        $canonicalBound = CapabilitySubject::of(
            AdrMembershipId::fromString('ABSTRACT_CAP_SEAT'),
            CanonicalSourceIdentityId::fromString('ABSTRACT_CANONICAL'),
        );
        $this->repo->persistCurrent($this->record('cap-seat', $seat, $this->api(), CapabilityState::VERIFIED), ['ev-1']);
        $this->repo->persistCurrent($this->record('cap-canon', $canonicalBound, $this->api(), CapabilityState::UNAVAILABLE), ['ev-2']);
        $this->repo->persistCurrent($this->record('cap-other', $this->seat('ABSTRACT_OTHER_SEAT'), $this->api(), CapabilityState::VERIFIED), ['ev-3']);

        $this->assertSame(['cap-seat'], array_map(fn (CapabilityRecord $r) => $r->recordId->value, $this->repo->loadCurrentForSubject($seat)));
        $this->assertSame(['cap-canon'], array_map(fn (CapabilityRecord $r) => $r->recordId->value, $this->repo->loadCurrentForSubject($canonicalBound)));
    }

    public function test_evaluator_restrictive_wins_over_persisted_shared_and_override(): void
    {
        $subject = $this->seat('ABSTRACT_CAP_SEAT');
        $this->repo->persistCurrent($this->record('cap-ev-shared', $subject, $this->api(), CapabilityState::VERIFIED), ['ev-1']);
        $this->repo->persistCurrent(
            $this->record('cap-ev-override', $subject, $this->api(), CapabilityState::PARTIAL, 'Override limited', true),
            ['ev-2'],
        );

        $decision = (new CapabilityRequirementEvaluator)->evaluate(
            $subject,
            CapabilityRequirementSet::of([$this->api()]),
            $this->repo->loadCurrentForSubject($subject),
            'dec-persisted-restrict',
            '2026-10-09T00:00:00Z',
        );

        $this->assertSame(CapabilityAndResultClass::HAS_PARTIAL, $decision->andResultClass);
        $this->assertTrue($decision->seatOverrideApplied);
        $this->assertSame('PARTIAL', $decision->perCellOutcomes[0]['state']);
    }

    public function test_reads_do_not_write(): void
    {
        $subject = $this->seat('ABSTRACT_CAP_SEAT');
        $this->repo->persistCurrent($this->record('cap-ro', $subject, $this->api(), CapabilityState::VERIFIED), ['ev-1']);
        $before = DB::table(self::TABLE)->get()->toArray();

        $this->repo->findFact(CapabilityRecordId::fromString('cap-ro'));
        $this->repo->findCurrentFact($subject, $this->api(), false);
        $this->repo->loadCurrentForSubject($subject);

        $this->assertEquals($before, DB::table(self::TABLE)->get()->toArray());
    }

    public function test_container_resolves_capability_store_repository(): void
    {
        $this->assertInstanceOf(EloquentCapabilityStoreRepository::class, app(CapabilityStoreRepository::class));
        $this->assertSame(app(CapabilityStoreRepository::class), app(CapabilityStoreRepository::class));
    }

    private function assertWriteRejectedWithoutEvidence(CapabilityState $state): void
    {
        $record = $this->record(
            'cap-noev-'.$state->value,
            $this->seat('ABSTRACT_CAP_SEAT'),
            $this->api(),
            $state,
            $state === CapabilityState::PARTIAL ? 'Documented limitation' : null,
        );

        try {
            $this->repo->persistCurrent($record, []);
            $this->fail("{$state->value} persisted without evidence.");
        } catch (CapabilityInvariantViolation) {
            $this->addToAssertionCount(1);
        }

        $this->assertSame(0, CghiaCapabilityCell::query()->count());
    }

    private function seat(string $adrId): CapabilitySubject
    {
        return CapabilitySubject::seatScoped(AdrMembershipId::fromString($adrId));
    }

    private function api(): CapabilityDimension
    {
        return CapabilityDimension::accessMethod(CapabilityAccessMethod::API);
    }

    private function record(
        string $id,
        CapabilitySubject $subject,
        CapabilityDimension $dimension,
        CapabilityState $state,
        ?string $limitation = null,
        bool $override = false,
    ): CapabilityRecord {
        return CapabilityRecord::create(
            CapabilityRecordId::fromString($id),
            $subject,
            $dimension,
            $state,
            CapabilityEvidenceFreshness::CURRENT,
            limitationText: $limitation,
            seatOverrideApplied: $override,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function rawRow(string $recordId, ?string $canonical, string $lifecycle): array
    {
        return [
            'capability_record_id' => $recordId,
            'adr_id' => 'ABSTRACT_CAP_SEAT',
            'canonical_identity_id' => $canonical,
            'dimension_family' => 'ACCESS_METHOD',
            'dimension_code' => 'api',
            'seat_override_applied' => false,
            'capability_state' => 'VERIFIED',
            'evidence_freshness_class' => 'CURRENT',
            'snapshot_version' => 'UNKNOWN',
            'update_method' => 'UNKNOWN',
            'limitation_text' => null,
            'verified_at' => null,
            'observed_at' => null,
            'capability_evidence_refs' => json_encode(['ev-raw']),
            'cell_lifecycle' => $lifecycle,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
