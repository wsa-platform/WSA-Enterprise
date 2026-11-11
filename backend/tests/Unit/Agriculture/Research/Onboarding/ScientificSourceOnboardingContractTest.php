<?php

namespace Tests\Unit\Agriculture\Research\Onboarding;

use App\Services\Agriculture\Research\Capability\CapabilityAccessMethod;
use App\Services\Agriculture\Research\Capability\CapabilityState;
use App\Services\Agriculture\Research\Identity\AdrMembershipId;
use App\Services\Agriculture\Research\Identity\CanonicalSourceIdentityId;
use App\Services\Agriculture\Research\Onboarding\OnboardingContractViolation;
use App\Services\Agriculture\Research\Onboarding\ScientificSourceOnboardingContract;
use App\Services\Agriculture\Research\Onboarding\SeatActivationState;
use App\Services\Agriculture\Research\Path\PathEligibilityState;
use App\Services\Agriculture\Research\Path\PathStatus;
use App\Services\Agriculture\Research\Search\Contract\ScientificSourceLicensingDeclaration;
use App\Services\Agriculture\Research\Search\Contract\ScientificSourceUsage;
use App\Services\Agriculture\Research\Search\Contract\ScientificSourceUsageStatus;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * ADR-023 §8.40 (R6): the Source Onboarding Contract record follows the frozen §8.11.3 field
 * matrix, keeps the §8.11.4 namespaces apart, and refuses eligibility without every owning decision.
 */
class ScientificSourceOnboardingContractTest extends TestCase
{
    public function test_the_record_exports_exactly_the_frozen_field_matrix(): void
    {
        $exported = self::contract()->toArray();

        $this->assertSame([
            'contract_version', 'onboarding_decision_id', 'adr_id', 'canonical_identity_id', 'identity_evidence_ref',
            'integration_classification_ref', 'capability_decision_identity', 'capability_dimension_states',
            'capability_evidence_ref', 'access_methods', 'license_state', 'access_state', 'reuse_state', 'path_id',
            'path_decision_identity', 'path_eligibility_state', 'path_status', 'projection_identity', 'fidelity_class',
            'facet_accounting', 'stage3_relationship', 'selector_eligibility', 'activation_state', 'group_wave_label',
            'decision_actor', 'decision_timestamp', 'rationale', 'staleness_policy_ref', 'correlation_hooks',
            'supersedes_decision_id',
        ], array_keys($exported));
        $this->assertSame('NOT_READY', $exported['activation_state']);
        $this->assertNull($exported['canonical_identity_id']);
        $this->assertSame('UNRESOLVED', $exported['license_state']);
        $this->assertSame('unresolved', $exported['reuse_state']['retrieval']);
        $this->assertSame('NOT_SELECTABLE', $exported['selector_eligibility']);
        $this->assertFalse(self::contract()->claimsEligibility());
    }

    public function test_a_fully_decided_seat_may_be_eligible(): void
    {
        $contract = self::contract(...self::eligible());

        $this->assertTrue($contract->claimsEligibility());
        $this->assertSame('ELIGIBLE', $contract->toArray()['activation_state']);
    }

    public function test_a_conditionally_eligible_seat_accepts_partial_search_and_a_conditional_path(): void
    {
        $contract = self::contract(...array_merge(self::eligible(), [
            'activationState' => SeatActivationState::CONDITIONALLY_ELIGIBLE,
            'pathEligibilityState' => PathEligibilityState::CONDITIONAL,
            'capabilityDimensionStates' => ['search' => CapabilityState::PARTIAL],
        ]));

        $this->assertTrue($contract->claimsEligibility());
    }

    /** @return array<string, array{array<string, mixed>, string}> */
    public static function ineligibleClaims(): array
    {
        return [
            'eligible without identity or classification' => [['canonicalIdentityId' => null, 'identityEvidenceRefs' => [], 'integrationClassificationRef' => null], 'missing: canonical_identity_id, identity_evidence_ref, integration_classification_ref.'],
            'active without a canonical identity' => [['activationState' => SeatActivationState::ACTIVE, 'canonicalIdentityId' => null], 'cannot be ACTIVE; missing: canonical_identity_id.'],
            'unknown licence' => [['licensing' => ScientificSourceLicensingDeclaration::unresolved()], 'licence permitting retrieval'],
            'conditional licence' => [['licensing' => self::licensing(ScientificSourceUsageStatus::CONDITIONAL)], 'licence permitting retrieval'],
            'no projection' => [['projectionIdentity' => null], 'projection_identity'],
            'no path decision' => [['pathDecisionIdentity' => null], 'path decision'],
            'verified capability on an ineligible path' => [['pathEligibilityState' => PathEligibilityState::INELIGIBLE], "path eligibility 'INELIGIBLE'"],
            'eligible on a conditional path' => [['pathEligibilityState' => PathEligibilityState::CONDITIONAL], "path eligibility 'CONDITIONAL'"],
            'eligible with partial search' => [['capabilityDimensionStates' => ['search' => CapabilityState::PARTIAL]], "search capability 'PARTIAL'"],
            'eligible with missing search state' => [['capabilityDimensionStates' => []], "search capability 'UNVERIFIED'"],
            'active without a runtime binding' => [['activationState' => SeatActivationState::ACTIVE, 'stage3Relationship' => null], 'runtime sourceKey'],
        ];
    }

    /** @param  array<string, mixed>  $override */
    #[DataProvider('ineligibleClaims')]
    public function test_eligibility_is_refused_without_every_owning_decision(array $override, string $message): void
    {
        $this->expectException(OnboardingContractViolation::class);
        $this->expectExceptionMessage($message);
        self::contract(...array_merge(self::eligible(), $override));
    }

    /** @return array<string, array{array<string, mixed>}> */
    public static function malformedRecords(): array
    {
        return [
            'empty decision id' => [['onboardingDecisionId' => '']],
            'decision id with spaces' => [['onboardingDecisionId' => 'onb r6']],
            'blank rationale' => [['rationale' => '  ']],
            'blank actor' => [['decisionActor' => '']],
            'blank optional reference' => [['integrationClassificationRef' => ' ']],
            'supersedes itself' => [['supersedesDecisionId' => 'onb-test-G1-01']],
            'unknown capability dimension' => [['capabilityDimensionStates' => ['harvest' => CapabilityState::UNVERIFIED]]],
            'registry status instead of a Cap v2 state' => [['capabilityDimensionStates' => ['search' => 'VERIFIED']]],
            'verified capability without a capability decision' => [['capabilityDimensionStates' => ['search' => CapabilityState::VERIFIED]]],
            'duplicate access method' => [['accessMethods' => [CapabilityAccessMethod::API, CapabilityAccessMethod::API]]],
            'unknown access state' => [['accessState' => 'LIVE']],
            'path status without a path' => [['pathStatus' => PathStatus::SELECTED]],
            'selectable while not active' => [['selectorEligibility' => 'SELECTABLE']],
            'evidence reference not a string' => [['identityEvidenceRefs' => [42]]],
        ];
    }

    /** @param  array<string, mixed>  $override */
    #[DataProvider('malformedRecords')]
    public function test_a_malformed_record_is_rejected(array $override): void
    {
        $this->expectException(OnboardingContractViolation::class);
        self::contract(...$override);
    }

    /** @return array<string, mixed> */
    private static function eligible(): array
    {
        return [
            'canonicalIdentityId' => CanonicalSourceIdentityId::fromString('csi-test-0001'),
            'identityEvidenceRefs' => ['https://repository.example.org/about'],
            'integrationClassificationRef' => 'ic-test-0001',
            'capabilityDecisionIdentity' => 'cap-test-0001',
            'capabilityDimensionStates' => ['search' => CapabilityState::VERIFIED],
            'licensing' => self::licensing(ScientificSourceUsageStatus::PERMITTED),
            'pathId' => 'path-test-0001',
            'pathDecisionIdentity' => 'path-decision-test-0001',
            'pathEligibilityState' => PathEligibilityState::ELIGIBLE,
            'projectionIdentity' => 'projection-test-0001',
            'stage3Relationship' => 'repository_example',
            'activationState' => SeatActivationState::ELIGIBLE,
        ];
    }

    private static function licensing(ScientificSourceUsageStatus $retrieval): ScientificSourceLicensingDeclaration
    {
        return new ScientificSourceLicensingDeclaration(
            [ScientificSourceUsage::RETRIEVAL->value => $retrieval],
            termsReference: 'https://repository.example.org/terms',
            evidenceReference: 'test fixture',
        );
    }

    private static function contract(mixed ...$override): ScientificSourceOnboardingContract
    {
        return new ScientificSourceOnboardingContract(...array_merge([
            'onboardingDecisionId' => 'onb-test-G1-01',
            'adrId' => AdrMembershipId::fromString('G1-01'),
            'canonicalIdentityId' => null,
            'identityEvidenceRefs' => [],
            'integrationClassificationRef' => null,
            'capabilityDecisionIdentity' => null,
            'capabilityDimensionStates' => ['search' => CapabilityState::UNVERIFIED],
            'capabilityEvidenceRefs' => [],
            'accessMethods' => [CapabilityAccessMethod::BULK_DOWNLOAD],
            'licensing' => ScientificSourceLicensingDeclaration::unresolved(),
            'accessState' => 'MACHINE_OR_BULK_ACCESS_DOCUMENTED',
            'pathId' => null,
            'pathDecisionIdentity' => null,
            'pathEligibilityState' => null,
            'pathStatus' => null,
            'projectionIdentity' => null,
            'fidelityClass' => null,
            'facetAccounting' => null,
            'stage3Relationship' => null,
            'selectorEligibility' => ScientificSourceOnboardingContract::SELECTOR_NOT_SELECTABLE,
            'activationState' => SeatActivationState::NOT_READY,
            'groupWaveLabel' => null,
            'decisionActor' => 'test',
            'decisionTimestamp' => new DateTimeImmutable('2026-10-15T00:00:00+00:00'),
            'rationale' => 'test record',
            'stalenessPolicyRef' => 'test policy',
        ], $override));
    }
}
