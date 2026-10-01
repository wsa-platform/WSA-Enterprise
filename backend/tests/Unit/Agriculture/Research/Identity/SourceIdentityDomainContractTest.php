<?php

namespace Tests\Unit\Agriculture\Research\Identity;

use App\Services\Agriculture\Research\Identity\AdrMembershipId;
use App\Services\Agriculture\Research\Identity\AdrSourceMembership;
use App\Services\Agriculture\Research\Identity\CanonicalSourceIdentityId;
use App\Services\Agriculture\Research\Identity\CompositeSourceStructure;
use App\Services\Agriculture\Research\Identity\DualMembershipBinding;
use App\Services\Agriculture\Research\Identity\ExternalIdentityKind;
use App\Services\Agriculture\Research\Identity\ExternalIdentityRef;
use App\Services\Agriculture\Research\Identity\SourceIdentityDomainContract;
use App\Services\Agriculture\Research\Identity\SourceIdentityInvariantViolation;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * IU-01 — Source Identity Domain Contract unit tests only.
 */
final class SourceIdentityDomainContractTest extends TestCase
{
    public function test_adr_id_is_independent_of_canonical_identity_id(): void
    {
        $adr = AdrMembershipId::fromString('G3-01');
        $canonical = CanonicalSourceIdentityId::fromString('cid_apis_journal_opaque');
        $membership = AdrSourceMembership::create($adr, $canonical, label: 'APIS', resourceClass: 'JOURNAL');

        $this->assertSame('G3-01', $membership->adrId->value);
        $this->assertSame('cid_apis_journal_opaque', $membership->canonicalIdentityId?->value);
        $this->assertNotSame($membership->adrId->value, $membership->canonicalIdentityId?->value);
        $this->assertTrue($membership->hasCanonicalIdentity());
    }

    public function test_canonical_identity_id_may_be_null(): void
    {
        $membership = AdrSourceMembership::create(
            AdrMembershipId::fromString('G3-01'),
            null,
            label: 'APIS',
        );

        $this->assertNull($membership->canonicalIdentityId);
        $this->assertFalse($membership->hasCanonicalIdentity());
        $this->assertNull($membership->toArray()['canonical_identity_id']);
    }

    public function test_dual_membership_shares_canonical_without_merging_adr_ids(): void
    {
        $seatA = AdrMembershipId::fromString('G1-01');
        $seatB = AdrMembershipId::fromString('G6-01');
        $canonical = CanonicalSourceIdentityId::fromString('cid_shared_demo');

        $binding = DualMembershipBinding::shareCanonical($seatA, $seatB, $canonical);

        $this->assertFalse($binding->seatA->equals($binding->seatB));
        $this->assertSame('cid_shared_demo', $binding->sharedCanonicalIdentityId->value);
        $this->assertCount(2, $binding->seats());

        $membershipA = AdrSourceMembership::create($seatA, $canonical);
        $membershipB = AdrSourceMembership::create($seatB, $canonical);
        $this->assertSame($membershipA->canonicalIdentityId?->value, $membershipB->canonicalIdentityId?->value);
        $this->assertNotSame($membershipA->adrId->value, $membershipB->adrId->value);
    }

    public function test_composite_parent_member_without_flattening(): void
    {
        $parent = AdrMembershipId::fromString('G2-25');
        $memberA = AdrMembershipId::fromString('G2-25-M1');
        $memberB = AdrMembershipId::fromString('G2-25-M2');

        $composite = CompositeSourceStructure::of($parent, [$memberA, $memberB]);
        $structured = $composite->toStructuredArray();

        $this->assertSame('G2-25', $structured['parent_adr_id']);
        $this->assertSame(['G2-25-M1', 'G2-25-M2'], $structured['member_adr_ids']);
        $this->assertArrayHasKey('parent_adr_id', $structured);
        $this->assertArrayHasKey('member_adr_ids', $structured);
        $this->assertNotContains('G2-25', $structured['member_adr_ids']);
    }

    public function test_external_identity_does_not_become_adr_identity(): void
    {
        $external = ExternalIdentityRef::of(
            ExternalIdentityKind::EXTERNAL_AGGREGATOR,
            'openalex-work-W123',
        );

        $this->expectException(SourceIdentityInvariantViolation::class);
        $this->expectExceptionMessage('External identity must not be promoted to adr_id');
        $external->asAdrMembershipId();
    }

    public function test_external_aggregator_identifier_does_not_become_stage3_source_key(): void
    {
        $external = ExternalIdentityRef::of(
            ExternalIdentityKind::EXTERNAL_AGGREGATOR,
            'openalex',
        );

        $this->expectException(SourceIdentityInvariantViolation::class);
        $this->expectExceptionMessage('must not be promoted to Stage-3 sourceKey');
        $external->asStage3SourceKey();
    }

    public function test_source_identity_does_not_carry_scientific_entity_semantics(): void
    {
        $membership = AdrSourceMembership::create(
            AdrMembershipId::fromString('G3-01'),
            null,
            label: 'APIS',
            resourceClass: 'JOURNAL',
        );

        $payload = $membership->toArray();
        foreach (SourceIdentityDomainContract::FORBIDDEN_SCIENTIFIC_FACET_KEYS as $facet) {
            $this->assertArrayNotHasKey($facet, $payload);
        }

        $ref = new ReflectionClass(AdrSourceMembership::class);
        $props = array_map(static fn ($p) => $p->getName(), $ref->getProperties());
        $this->assertNotContains('species', $props);
        $this->assertNotContains('disease', $props);
        $this->assertNotContains('dose', $props);
        $this->assertNotContains('pathogen', $props);
    }

    public function test_identity_contract_does_not_auto_promote_capability_state(): void
    {
        $this->expectException(SourceIdentityInvariantViolation::class);
        $this->expectExceptionMessage('Capability Store states');

        SourceIdentityDomainContract::assertIdentityPayloadClean([
            'adr_id' => 'G3-01',
            'capability_state' => 'VERIFIED',
        ]);
    }

    public function test_identity_contract_does_not_mutate_or_reference_csq(): void
    {
        $membership = AdrSourceMembership::create(AdrMembershipId::fromString('G3-01'));

        $ref = new ReflectionClass($membership);
        foreach ($ref->getMethods() as $method) {
            $name = strtolower($method->getName());
            $this->assertStringNotContainsString('csq', $name);
            $this->assertStringNotContainsString('canonicalscientific', $name);
            $this->assertStringNotContainsString('mutatequestion', $name);
        }

        SourceIdentityDomainContract::assertIdentityPayloadClean($membership->toArray());
        $this->addToAssertionCount(1);
    }

    public function test_invariant_violations_are_rejected_clearly(): void
    {
        $this->expectException(SourceIdentityInvariantViolation::class);
        $this->expectExceptionMessage('adr_id must be a non-empty string');
        AdrMembershipId::fromString('   ');
    }

    public function test_adr_id_must_not_equal_canonical_identity_id_string(): void
    {
        $this->expectException(SourceIdentityInvariantViolation::class);
        $this->expectExceptionMessage('adr_id must not equal canonical_identity_id');

        AdrSourceMembership::create(
            AdrMembershipId::fromString('SAME-TOKEN'),
            CanonicalSourceIdentityId::fromString('SAME-TOKEN'),
        );
    }

    public function test_dual_membership_rejects_identical_seats(): void
    {
        $seat = AdrMembershipId::fromString('G1-01');
        $this->expectException(SourceIdentityInvariantViolation::class);
        DualMembershipBinding::shareCanonical(
            $seat,
            $seat,
            CanonicalSourceIdentityId::fromString('cid_x'),
        );
    }

    public function test_namespaces_remain_distinct_from_stage3_source_key(): void
    {
        $this->expectException(SourceIdentityInvariantViolation::class);
        $this->expectExceptionMessage('Stage-3 sourceKey must not equal adr_id');

        SourceIdentityDomainContract::assertDistinctNamespaces(
            AdrMembershipId::fromString('openalex'),
            null,
            null,
            'openalex',
        );
    }
}
