<?php

namespace Tests\Unit\Agriculture\Research\Capability;

use App\Services\Agriculture\Research\Capability\CapabilityAccessMethod;
use App\Services\Agriculture\Research\Capability\CapabilityAndResultClass;
use App\Services\Agriculture\Research\Capability\CapabilityDecisionIdentity;
use App\Services\Agriculture\Research\Capability\CapabilityDimension;
use App\Services\Agriculture\Research\Capability\CapabilityEvidenceFreshness;
use App\Services\Agriculture\Research\Capability\CapabilityInvariantViolation;
use App\Services\Agriculture\Research\Capability\CapabilityRecord;
use App\Services\Agriculture\Research\Capability\CapabilityRecordId;
use App\Services\Agriculture\Research\Capability\CapabilityRequirementEvaluator;
use App\Services\Agriculture\Research\Capability\CapabilityRequirementSet;
use App\Services\Agriculture\Research\Capability\CapabilityState;
use App\Services\Agriculture\Research\Capability\CapabilityStoreDomainContract;
use App\Services\Agriculture\Research\Capability\CapabilitySubject;
use App\Services\Agriculture\Research\Capability\HistoricalGo1CapabilityState;
use App\Services\Agriculture\Research\Identity\AdrMembershipId;
use App\Services\Agriculture\Research\Identity\CanonicalSourceIdentityId;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * IU-02 — Capability Store Domain Contract unit tests only.
 */
final class CapabilityStoreDomainContractTest extends TestCase
{
    public function test_cap_v2_states_are_exclusive(): void
    {
        $values = array_map(static fn (CapabilityState $s) => $s->value, CapabilityState::cases());

        $this->assertSame(
            ['VERIFIED', 'PARTIAL', 'UNVERIFIED', 'UNAVAILABLE', 'NOT_APPLICABLE'],
            $values,
        );
        $this->assertNotContains('SUPPORTED', $values);
        $this->assertNotContains('UNKNOWN', $values);
        $this->assertNotContains('STALE', $values);
    }

    public function test_subject_binds_adr_id_with_nullable_canonical(): void
    {
        $seat = CapabilitySubject::seatScoped(AdrMembershipId::fromString('G3-01'));
        $this->assertTrue($seat->isSeatScoped());
        $this->assertNull($seat->canonicalIdentityId);

        $bound = CapabilitySubject::of(
            AdrMembershipId::fromString('G3-01'),
            CanonicalSourceIdentityId::fromString('cid_apis'),
        );
        $this->assertTrue($bound->hasCanonicalBinding());
        $this->assertSame('G3-01', $bound->toArray()['adr_id']);
        $this->assertSame('cid_apis', $bound->toArray()['canonical_identity_id']);
    }

    public function test_subject_rejects_adr_equal_canonical(): void
    {
        $this->expectException(CapabilityInvariantViolation::class);
        CapabilitySubject::of(
            AdrMembershipId::fromString('SAME'),
            CanonicalSourceIdentityId::fromString('SAME'),
        );
    }

    public function test_missing_record_resolves_to_unverified(): void
    {
        $this->assertSame(
            CapabilityState::UNVERIFIED,
            CapabilityStoreDomainContract::stateForMissingRecord(),
        );

        $subject = CapabilitySubject::seatScoped(AdrMembershipId::fromString('G1-01'));
        $dim = CapabilityDimension::accessMethod(CapabilityAccessMethod::API);
        $evaluator = new CapabilityRequirementEvaluator();

        $this->assertSame(
            CapabilityState::UNVERIFIED,
            $evaluator->resolveState($subject, $dim, []),
        );
    }

    public function test_required_capabilities_use_and_semantics(): void
    {
        $subject = CapabilitySubject::seatScoped(AdrMembershipId::fromString('G1-02'));
        $api = CapabilityDimension::accessMethod(CapabilityAccessMethod::API);
        $oai = CapabilityDimension::accessMethod(CapabilityAccessMethod::OAI_PMH);
        $meta = CapabilityDimension::accessMethod(CapabilityAccessMethod::METADATA_ACCESS);

        $requirements = CapabilityRequirementSet::of([$api, $oai, $meta]);
        $records = [
            $this->record($subject, $api, CapabilityState::VERIFIED, 'cap-api'),
            $this->record($subject, $oai, CapabilityState::VERIFIED, 'cap-oai'),
            // meta missing → UNVERIFIED
        ];

        $decision = (new CapabilityRequirementEvaluator())->evaluate(
            $subject,
            $requirements,
            $records,
            'dec-and-1',
            '2026-10-01T00:00:00Z',
        );

        $this->assertSame(CapabilityAndResultClass::HAS_UNVERIFIED, $decision->andResultClass);
        $this->assertCount(3, $decision->perCellOutcomes);
        $missing = array_values(array_filter(
            $decision->perCellOutcomes,
            static fn (array $o) => $o['missing_record'] === true,
        ));
        $this->assertCount(1, $missing);
        $this->assertSame('UNVERIFIED', $missing[0]['state']);
    }

    public function test_optional_enrichments_excluded_from_and(): void
    {
        $subject = CapabilitySubject::seatScoped(AdrMembershipId::fromString('G1-03'));
        $api = CapabilityDimension::accessMethod(CapabilityAccessMethod::API);
        $ft = CapabilityDimension::accessMethod(CapabilityAccessMethod::FULL_TEXT_ACCESS);

        $requirements = CapabilityRequirementSet::of([$api], [$ft]);
        $records = [
            $this->record($subject, $api, CapabilityState::VERIFIED, 'cap-api-2'),
            // full text missing — optional only
        ];

        $decision = (new CapabilityRequirementEvaluator())->evaluate(
            $subject,
            $requirements,
            $records,
            'dec-opt-1',
            '2026-10-01T00:00:00Z',
        );

        $this->assertSame(CapabilityAndResultClass::ALL_VERIFIED, $decision->andResultClass);
        $this->assertContains($ft->key(), $decision->optionalEnrichmentKeys);
        $this->assertCount(1, $decision->perCellOutcomes);
    }

    public function test_partial_yields_has_partial_not_all_verified(): void
    {
        $subject = CapabilitySubject::seatScoped(AdrMembershipId::fromString('G1-04'));
        $api = CapabilityDimension::accessMethod(CapabilityAccessMethod::API);

        $record = CapabilityRecord::create(
            CapabilityRecordId::fromString('cap-partial'),
            $subject,
            $api,
            CapabilityState::PARTIAL,
            CapabilityEvidenceFreshness::CURRENT,
            limitationText: 'Boolean AND only; no field filters',
        );

        $decision = (new CapabilityRequirementEvaluator())->evaluate(
            $subject,
            CapabilityRequirementSet::of([$api]),
            [$record],
            'dec-partial',
            '2026-10-01T00:00:00Z',
        );

        $this->assertSame(CapabilityAndResultClass::HAS_PARTIAL, $decision->andResultClass);
        $this->assertFalse($record->state->maySatisfyVerifiedAutomation());
        $this->assertTrue($record->state->maySupportConditionalOnly());
    }

    public function test_unavailable_blocks_and(): void
    {
        $subject = CapabilitySubject::seatScoped(AdrMembershipId::fromString('G1-05'));
        $api = CapabilityDimension::accessMethod(CapabilityAccessMethod::API);
        $records = [$this->record($subject, $api, CapabilityState::UNAVAILABLE, 'cap-unavail')];

        $decision = (new CapabilityRequirementEvaluator())->evaluate(
            $subject,
            CapabilityRequirementSet::of([$api]),
            $records,
            'dec-unavail',
            '2026-10-01T00:00:00Z',
        );

        $this->assertSame(CapabilityAndResultClass::HAS_UNAVAILABLE, $decision->andResultClass);
    }

    public function test_stale_is_freshness_metadata_not_cap_state(): void
    {
        $values = array_map(static fn (CapabilityState $s) => $s->value, CapabilityState::cases());
        $this->assertNotContains('STALE', $values);

        $freshness = array_map(
            static fn (CapabilityEvidenceFreshness $f) => $f->value,
            CapabilityEvidenceFreshness::cases(),
        );
        $this->assertSame(['CURRENT', 'STALE', 'UNKNOWN'], $freshness);

        $subject = CapabilitySubject::seatScoped(AdrMembershipId::fromString('G1-06'));
        $api = CapabilityDimension::accessMethod(CapabilityAccessMethod::API);
        $record = CapabilityRecord::create(
            CapabilityRecordId::fromString('cap-stale'),
            $subject,
            $api,
            CapabilityState::VERIFIED,
            CapabilityEvidenceFreshness::STALE,
            snapshotVersion: 'v1',
        );

        $this->assertTrue($record->isStaleEvidence());
        $this->assertSame(CapabilityState::VERIFIED, $record->state);

        $this->expectException(CapabilityInvariantViolation::class);
        CapabilityStoreDomainContract::assertNoStaleAutoPromotionToVerified(
            CapabilityState::VERIFIED,
            CapabilityEvidenceFreshness::STALE,
            autoPromoteRequested: true,
        );
    }

    public function test_unknown_snapshot_and_update_method_allowed(): void
    {
        $subject = CapabilitySubject::seatScoped(AdrMembershipId::fromString('G1-07'));
        $record = CapabilityRecord::create(
            CapabilityRecordId::fromString('cap-unk'),
            $subject,
            CapabilityDimension::accessMethod(CapabilityAccessMethod::RSS),
            CapabilityState::UNVERIFIED,
        );

        $this->assertTrue($record->hasUnknownSnapshotVersion());
        $this->assertTrue($record->hasUnknownUpdateMethod());
        $this->assertSame('UNKNOWN', $record->snapshotVersion);
        $this->assertSame('UNKNOWN', $record->updateMethod);
    }

    public function test_go1_historical_vocabulary_is_separate(): void
    {
        $go1 = array_map(static fn ($c) => $c->value, HistoricalGo1CapabilityState::cases());
        $this->assertContains('SUPPORTED', $go1);
        $this->assertContains('UNKNOWN', $go1);

        $capRef = new ReflectionClass(HistoricalGo1CapabilityState::class);
        $methods = array_map(static fn ($m) => $m->getName(), $capRef->getMethods());
        $this->assertNotContains('toCapV2', $methods);
        $this->assertNotContains('fromCapV2', $methods);

        $this->expectException(CapabilityInvariantViolation::class);
        CapabilityStoreDomainContract::assertCapabilityPayloadClean([
            'capability_state' => 'SUPPORTED',
        ]);
    }

    public function test_content_about_not_equal_query_constrainable(): void
    {
        $about = CapabilityDimension::scientificFacetContentAbout('entity');
        $constrain = CapabilityDimension::scientificFacetQueryConstrainable('entity');

        $this->assertFalse($about->equals($constrain));
        $this->assertTrue($about->isContentAboutFacet());
        $this->assertTrue($constrain->isQueryConstrainableFacet());
        $this->assertNotSame($about->key(), $constrain->key());
    }

    public function test_payload_rejects_path_eligibility_and_status(): void
    {
        $this->expectException(CapabilityInvariantViolation::class);
        $this->expectExceptionMessage('forbidden authority key [eligibility_state]');
        CapabilityStoreDomainContract::assertCapabilityPayloadClean([
            'eligibility_state' => 'ELIGIBLE',
        ]);
    }

    public function test_payload_rejects_path_status(): void
    {
        $this->expectException(CapabilityInvariantViolation::class);
        $this->expectExceptionMessage('forbidden authority key [path_status]');
        CapabilityStoreDomainContract::assertCapabilityPayloadClean([
            'path_status' => 'SELECTED',
        ]);
    }

    public function test_payload_rejects_csq_mutation_keys(): void
    {
        $this->expectException(CapabilityInvariantViolation::class);
        CapabilityStoreDomainContract::assertCapabilityPayloadClean([
            'dose' => '10mg',
        ]);
    }

    public function test_capability_decision_identity_shape(): void
    {
        $subject = CapabilitySubject::of(
            AdrMembershipId::fromString('G2-01'),
            CanonicalSourceIdentityId::fromString('cid_shared'),
        );
        $api = CapabilityDimension::accessMethod(CapabilityAccessMethod::API);
        $decision = (new CapabilityRequirementEvaluator())->evaluate(
            $subject,
            CapabilityRequirementSet::of([$api]),
            [$this->record($subject, $api, CapabilityState::VERIFIED, 'cap-dec')],
            'dec-shape-1',
            '2026-10-01T12:00:00Z',
        );

        $payload = $decision->toArray();
        $this->assertSame('dec-shape-1', $payload['decision_id']);
        $this->assertSame('G2-01', $payload['subject_adr_id']);
        $this->assertSame('cid_shared', $payload['subject_canonical_identity_id']);
        $this->assertSame('ALL_VERIFIED', $payload['and_result_class']);
        $this->assertArrayHasKey('required_cell_outcomes', $payload);
        $this->assertArrayHasKey('optional_enrichments', $payload);
        $this->assertArrayHasKey('stale_flags', $payload);
        $this->assertArrayNotHasKey('eligibility_state', $payload);
        $this->assertArrayNotHasKey('path_status', $payload);
        $this->assertArrayNotHasKey('fidelity_class', $payload);
    }

    public function test_restrictive_wins_on_duplicate_cells(): void
    {
        $subject = CapabilitySubject::seatScoped(AdrMembershipId::fromString('G2-02'));
        $api = CapabilityDimension::accessMethod(CapabilityAccessMethod::API);
        $shared = $this->record($subject, $api, CapabilityState::VERIFIED, 'cap-shared');
        $override = CapabilityRecord::create(
            CapabilityRecordId::fromString('cap-override'),
            $subject,
            $api,
            CapabilityState::PARTIAL,
            CapabilityEvidenceFreshness::CURRENT,
            limitationText: 'Seat override partial API',
            seatOverrideApplied: true,
        );

        $decision = (new CapabilityRequirementEvaluator())->evaluate(
            $subject,
            CapabilityRequirementSet::of([$api]),
            [$shared, $override],
            'dec-restrict',
            '2026-10-01T00:00:00Z',
        );

        $this->assertSame(CapabilityAndResultClass::HAS_PARTIAL, $decision->andResultClass);
        $this->assertTrue($decision->seatOverrideApplied);
        $this->assertSame(
            CapabilityState::PARTIAL,
            CapabilityState::moreRestrictive(CapabilityState::VERIFIED, CapabilityState::PARTIAL),
        );
    }

    public function test_partial_requires_limitation_text(): void
    {
        $this->expectException(CapabilityInvariantViolation::class);
        CapabilityRecord::create(
            CapabilityRecordId::fromString('cap-bad-partial'),
            CapabilitySubject::seatScoped(AdrMembershipId::fromString('G2-03')),
            CapabilityDimension::accessMethod(CapabilityAccessMethod::API),
            CapabilityState::PARTIAL,
        );
    }

    public function test_contract_does_not_own_identity_minting_methods(): void
    {
        $ref = new ReflectionClass(CapabilityStoreDomainContract::class);
        foreach ($ref->getMethods() as $method) {
            $name = strtolower($method->getName());
            $this->assertStringNotContainsString('mint', $name);
            $this->assertStringNotContainsString('sameas', $name);
            $this->assertStringNotContainsString('mergeadr', $name);
        }

        $subject = CapabilitySubject::seatScoped(AdrMembershipId::fromString('G3-01'));
        CapabilityStoreDomainContract::assertSubjectConsumesIdentityOnly($subject);
        $this->addToAssertionCount(1);
    }

    public function test_all_verified_when_every_required_cell_verified(): void
    {
        $subject = CapabilitySubject::seatScoped(AdrMembershipId::fromString('G2-04'));
        $a = CapabilityDimension::accessMethod(CapabilityAccessMethod::API);
        $b = CapabilityDimension::accessMethod(CapabilityAccessMethod::METADATA_ACCESS);
        $decision = (new CapabilityRequirementEvaluator())->evaluate(
            $subject,
            CapabilityRequirementSet::of([$a, $b]),
            [
                $this->record($subject, $a, CapabilityState::VERIFIED, 'a'),
                $this->record($subject, $b, CapabilityState::VERIFIED, 'b'),
            ],
            'dec-ok',
            '2026-10-01T00:00:00Z',
        );

        $this->assertInstanceOf(CapabilityDecisionIdentity::class, $decision);
        $this->assertSame(CapabilityAndResultClass::ALL_VERIFIED, $decision->andResultClass);
        $this->assertFalse($decision->hasStaleFlags);
    }

    private function record(
        CapabilitySubject $subject,
        CapabilityDimension $dimension,
        CapabilityState $state,
        string $id,
    ): CapabilityRecord {
        return CapabilityRecord::create(
            CapabilityRecordId::fromString($id),
            $subject,
            $dimension,
            $state,
            CapabilityEvidenceFreshness::CURRENT,
            snapshotVersion: 'test-snap-1',
            updateMethod: 'manual_capver',
        );
    }
}
