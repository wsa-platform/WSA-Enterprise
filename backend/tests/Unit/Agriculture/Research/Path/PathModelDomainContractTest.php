<?php

namespace Tests\Unit\Agriculture\Research\Path;

use App\Services\Agriculture\Research\Capability\CapabilityAndResultClass;
use App\Services\Agriculture\Research\Capability\CapabilityDecisionIdentity;
use App\Services\Agriculture\Research\Capability\CapabilitySubject;
use App\Services\Agriculture\Research\Identity\AdrMembershipId;
use App\Services\Agriculture\Research\Identity\CanonicalSourceIdentityId;
use App\Services\Agriculture\Research\Path\CapabilityToPathEligibilityMapper;
use App\Services\Agriculture\Research\Path\PathDecisionId;
use App\Services\Agriculture\Research\Path\PathDecisionIdentity;
use App\Services\Agriculture\Research\Path\PathEligibilityState;
use App\Services\Agriculture\Research\Path\PathFallbackLayer;
use App\Services\Agriculture\Research\Path\PathFallbackRelationship;
use App\Services\Agriculture\Research\Path\PathFamily;
use App\Services\Agriculture\Research\Path\PathId;
use App\Services\Agriculture\Research\Path\PathInvariantViolation;
use App\Services\Agriculture\Research\Path\PathLimitation;
use App\Services\Agriculture\Research\Path\PathModelDomainContract;
use App\Services\Agriculture\Research\Path\PathStatus;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * IU-03 — Path Model Domain Contract unit tests only.
 */
final class PathModelDomainContractTest extends TestCase
{
    public function test_all_path_families_p01_through_p18_are_present(): void
    {
        $values = array_map(static fn (PathFamily $f) => $f->value, PathFamily::allOrdered());
        $expected = [];
        for ($i = 1; $i <= 18; $i++) {
            $expected[] = 'P'.str_pad((string) $i, 2, '0', STR_PAD_LEFT);
        }

        $this->assertSame($expected, $values);
        $this->assertCount(18, PathFamily::cases());
        $this->assertNull(PathFamily::tryFrom('P19'));
    }

    public function test_path_id_rejects_empty_and_must_not_equal_adr_id(): void
    {
        $this->expectException(PathInvariantViolation::class);
        PathId::fromString('   ');
    }

    public function test_path_id_not_equal_adr_id_on_decision(): void
    {
        $cap = $this->capDecision(CapabilityAndResultClass::ALL_VERIFIED, 'G1-01');
        $this->expectException(PathInvariantViolation::class);
        $this->expectExceptionMessage('path_id must not equal adr_id');

        PathModelDomainContract::bindFromCapabilityDecision(
            PathDecisionId::fromString('pd-1'),
            PathId::fromString('G1-01'),
            PathFamily::P01,
            $cap,
            PathStatus::SELECTED,
            decidedAt: '2026-10-01T00:00:00Z',
        );
    }

    public function test_invalid_path_family_string_is_rejected_by_enum(): void
    {
        $this->assertNull(PathFamily::tryFrom('P19'));
        $this->assertNull(PathFamily::tryFrom('API'));
        $this->assertSame(PathFamily::P17, PathFamily::tryFrom('P17'));
    }

    public function test_cap_and_result_maps_to_eligibility(): void
    {
        $mapper = new CapabilityToPathEligibilityMapper();

        $this->assertSame(
            PathEligibilityState::ELIGIBLE,
            $mapper->map(CapabilityAndResultClass::ALL_VERIFIED),
        );
        $this->assertSame(
            PathEligibilityState::CONDITIONAL,
            $mapper->map(CapabilityAndResultClass::HAS_PARTIAL),
        );
        $this->assertSame(
            PathEligibilityState::DEFERRED,
            $mapper->map(CapabilityAndResultClass::HAS_UNVERIFIED),
        );
        $this->assertSame(
            PathEligibilityState::INELIGIBLE,
            $mapper->map(CapabilityAndResultClass::HAS_UNAVAILABLE),
        );
    }

    public function test_eligible_does_not_auto_mean_selected(): void
    {
        $cap = $this->capDecision(CapabilityAndResultClass::ALL_VERIFIED, 'G1-02');
        $decision = PathModelDomainContract::bindFromCapabilityDecision(
            PathDecisionId::fromString('pd-eligible-deferred'),
            PathId::fromString('path-inst-g1-02-p01'),
            PathFamily::P01,
            $cap,
            PathStatus::DEFERRED_PENDING_CAPABILITY,
            decidedAt: '2026-10-01T00:00:00Z',
        );

        $this->assertSame(PathEligibilityState::ELIGIBLE, $decision->eligibilityState);
        $this->assertSame(PathStatus::DEFERRED_PENDING_CAPABILITY, $decision->status);
        $this->assertFalse($decision->isVerifiedAutomationPair());
        $this->assertNotSame($decision->eligibilityState->value, $decision->status->value);
    }

    public function test_eligibility_status_orthogonality_pairs(): void
    {
        $cases = [
            [CapabilityAndResultClass::ALL_VERIFIED, PathStatus::SELECTED, PathEligibilityState::ELIGIBLE],
            [CapabilityAndResultClass::ALL_VERIFIED, PathStatus::DEFERRED_PENDING_CAPABILITY, PathEligibilityState::ELIGIBLE],
            [CapabilityAndResultClass::HAS_PARTIAL, PathStatus::CONDITIONALLY_SELECTED, PathEligibilityState::CONDITIONAL],
            [CapabilityAndResultClass::HAS_UNVERIFIED, PathStatus::DEFERRED_PENDING_CAPABILITY, PathEligibilityState::DEFERRED],
        ];

        foreach ($cases as [$andClass, $status, $expectedEligibility]) {
            $cap = $this->capDecision($andClass, 'G1-03');
            $decision = PathModelDomainContract::bindFromCapabilityDecision(
                PathDecisionId::fromString('pd-'.$andClass->value.'-'.$status->value),
                PathId::fromString('path-'.$andClass->value.'-'.$status->value),
                PathFamily::P03,
                $cap,
                $status,
                decidedAt: '2026-10-01T00:00:00Z',
            );
            $this->assertSame($expectedEligibility, $decision->eligibilityState);
            $this->assertSame($status, $decision->status);
        }
    }

    public function test_ineligible_cannot_be_selected(): void
    {
        $cap = $this->capDecision(CapabilityAndResultClass::HAS_UNAVAILABLE, 'G1-04');
        $this->expectException(PathInvariantViolation::class);
        $this->expectExceptionMessage('INELIGIBLE path cannot have status SELECTED');

        PathModelDomainContract::bindFromCapabilityDecision(
            PathDecisionId::fromString('pd-bad'),
            PathId::fromString('path-bad'),
            PathFamily::P01,
            $cap,
            PathStatus::SELECTED,
            decidedAt: '2026-10-01T00:00:00Z',
        );
    }

    public function test_path_decision_identity_distinct_from_other_ids(): void
    {
        $cap = $this->capDecision(
            CapabilityAndResultClass::ALL_VERIFIED,
            'G2-01',
            CanonicalSourceIdentityId::fromString('cid_shared'),
            capabilityDecisionId: 'cap-dec-xyz',
        );

        $decision = PathModelDomainContract::bindFromCapabilityDecision(
            PathDecisionId::fromString('path-dec-abc'),
            PathId::fromString('path-inst-001'),
            PathFamily::P17,
            $cap,
            PathStatus::SELECTED,
            decidedAt: '2026-10-01T12:00:00Z',
        );

        $payload = $decision->toArray();
        $this->assertSame('path-dec-abc', $payload['path_decision_identity']);
        $this->assertSame('path-inst-001', $payload['path_id']);
        $this->assertSame('G2-01', $payload['adr_id']);
        $this->assertSame('cid_shared', $payload['canonical_identity_id']);
        $this->assertSame('cap-dec-xyz', $payload['capability_decision_identity']);
        $this->assertNotSame($payload['path_decision_identity'], $payload['path_id']);
        $this->assertNotSame($payload['path_decision_identity'], $payload['adr_id']);
        $this->assertNotSame($payload['path_decision_identity'], $payload['capability_decision_identity']);
        $this->assertNotSame($payload['path_id'], $payload['adr_id']);
        $this->assertArrayNotHasKey('fidelity_class', $payload);
        $this->assertArrayNotHasKey('sourceKey', $payload);
    }

    public function test_path_decision_identity_rejects_collapse_with_capability_decision_id(): void
    {
        $cap = $this->capDecision(CapabilityAndResultClass::ALL_VERIFIED, 'G2-02', capabilityDecisionId: 'same-token');
        $this->expectException(PathInvariantViolation::class);
        $this->expectExceptionMessage('path_decision_identity must not equal capability_decision_identity');

        PathModelDomainContract::bindFromCapabilityDecision(
            PathDecisionId::fromString('same-token'),
            PathId::fromString('path-other'),
            PathFamily::P01,
            $cap,
            PathStatus::SELECTED,
            decidedAt: '2026-10-01T00:00:00Z',
        );
    }

    public function test_consumes_capability_decision_and_result_class(): void
    {
        $cap = $this->capDecision(CapabilityAndResultClass::HAS_PARTIAL, 'G2-03');
        $this->assertSame(CapabilityAndResultClass::HAS_PARTIAL, $cap->andResultClass);

        $decision = PathModelDomainContract::bindFromCapabilityDecision(
            PathDecisionId::fromString('pd-partial'),
            PathId::fromString('path-partial'),
            PathFamily::P08,
            $cap,
            PathStatus::CONDITIONALLY_SELECTED,
            limitations: [PathLimitation::of('PARTIAL_SCOPE', 'Boolean only')],
            decidedAt: '2026-10-01T00:00:00Z',
        );

        $this->assertSame(PathEligibilityState::CONDITIONAL, $decision->eligibilityState);
        $this->assertTrue($decision->isVerifiedAutomationPair());
        $this->assertSame('cap-dec-default', $decision->capabilityDecisionId);
    }

    public function test_fallback_layers_l1_l2_l3_relationship(): void
    {
        $rel = PathFallbackRelationship::cghiaDefault();
        $this->assertSame(['L1', 'L2', 'L3'], $rel->layerValues());
        $this->assertTrue($rel->forbidsFidelityIncrease);
        $this->assertTrue($rel->autoExecuteForbidden);
        $this->assertTrue($rel->precedes(PathFallbackLayer::L1, PathFallbackLayer::L2));
        $this->assertTrue($rel->precedes(PathFallbackLayer::L2, PathFallbackLayer::L3));

        $this->expectException(PathInvariantViolation::class);
        PathFallbackRelationship::of([PathFallbackLayer::L3, PathFallbackLayer::L1]);
    }

    public function test_stale_cap_flags_add_limitation_without_promoting_eligibility(): void
    {
        $cap = CapabilityDecisionIdentity::create(
            'cap-stale',
            CapabilitySubject::seatScoped(AdrMembershipId::fromString('G3-01')),
            [],
            [],
            CapabilityAndResultClass::ALL_VERIFIED,
            hasStaleFlags: true,
            snapshotVersion: 'v1',
            decidedAt: '2026-10-01T00:00:00Z',
        );

        $decision = PathModelDomainContract::bindFromCapabilityDecision(
            PathDecisionId::fromString('pd-stale'),
            PathId::fromString('path-stale'),
            PathFamily::P01,
            $cap,
            PathStatus::SELECTED,
            decidedAt: '2026-10-01T00:00:00Z',
        );

        $this->assertSame(PathEligibilityState::ELIGIBLE, $decision->eligibilityState);
        $this->assertTrue($decision->capabilityStaleFlagsPresent);
        $codes = array_map(static fn (PathLimitation $l) => $l->code, $decision->limitations);
        $this->assertContains('CAP_STALE_EVIDENCE', $codes);
    }

    public function test_payload_rejects_csq_and_fidelity_keys(): void
    {
        $this->expectException(PathInvariantViolation::class);
        PathModelDomainContract::assertPathPayloadClean([
            'fidelity_class' => 'EXACT',
        ]);
    }

    public function test_package_has_no_runtime_selector_or_http_methods(): void
    {
        $ref = new ReflectionClass(PathModelDomainContract::class);
        foreach ($ref->getMethods() as $method) {
            $name = strtolower($method->getName());
            $this->assertStringNotContainsString('http', $name);
            $this->assertStringNotContainsString('execute', $name);
            $this->assertStringNotContainsString('selectsources', $name);
            $this->assertStringNotContainsString('adapter', $name);
            $this->assertStringNotContainsString('project', $name);
        }

        $this->assertTrue(true);
    }

    public function test_external_and_manual_family_flags(): void
    {
        $this->assertTrue(PathFamily::P12->isExternalDiscoveryFamily());
        $this->assertTrue(PathFamily::P18->isManualFamily());
        $this->assertFalse(PathFamily::P01->isManualFamily());
        $this->assertStringContainsString('REST/API', PathFamily::P01->designLabel());
    }

    public function test_eligibility_and_status_are_separate_enums(): void
    {
        $eligibilityValues = array_map(static fn ($c) => $c->value, PathEligibilityState::cases());
        $statusValues = array_map(static fn ($c) => $c->value, PathStatus::cases());

        $this->assertSame(['ELIGIBLE', 'CONDITIONAL', 'DEFERRED', 'INELIGIBLE'], $eligibilityValues);
        $this->assertSame(
            ['SELECTED', 'CONDITIONALLY_SELECTED', 'DEFERRED_PENDING_CAPABILITY'],
            $statusValues,
        );
        $this->assertEmpty(array_intersect($eligibilityValues, $statusValues));
    }

    private function capDecision(
        CapabilityAndResultClass $andClass,
        string $adrId,
        ?CanonicalSourceIdentityId $canonical = null,
        string $capabilityDecisionId = 'cap-dec-default',
    ): CapabilityDecisionIdentity {
        $subject = CapabilitySubject::of(AdrMembershipId::fromString($adrId), $canonical);

        return CapabilityDecisionIdentity::create(
            $capabilityDecisionId,
            $subject,
            [],
            [],
            $andClass,
            hasStaleFlags: false,
            snapshotVersion: 'UNKNOWN',
            decidedAt: '2026-10-01T00:00:00Z',
        );
    }
}
