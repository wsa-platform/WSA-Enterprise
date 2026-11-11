<?php

namespace App\Services\Agriculture\Research\Onboarding;

use App\Services\Agriculture\Research\Capability\CapabilityAccessMethod;
use App\Services\Agriculture\Research\Capability\CapabilityState;
use App\Services\Agriculture\Research\Identity\AdrMembershipId;
use App\Services\Agriculture\Research\Identity\CanonicalSourceIdentityId;
use App\Services\Agriculture\Research\Path\PathEligibilityState;
use App\Services\Agriculture\Research\Path\PathStatus;
use App\Services\Agriculture\Research\Search\Contract\ScientificSourceLicensingDeclaration;
use App\Services\Agriculture\Research\Search\Contract\ScientificSourceUsage;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceOperation;
use DateTimeImmutable;

/**
 * One Source Onboarding Contract decision for exactly one `adr_id` (ADR-023 D-10.11, §8.11).
 *
 * Orchestration and audit only: every field references the authority that owns it (Identity,
 * IC, Capability, Path, Projection, Coexistence) and never redefines it (§8.11.3). A decision
 * is immutable; a change is a new decision that supersedes the previous one (§8.11.5).
 *
 * Eligibility needs every owning decision: a seat whose identity, classification, Capability,
 * Path, Projection or licence is unresolved can only be NOT_READY, UNVERIFIED, SUSPENDED or
 * DEFERRED. A verified Capability alone never makes a seat eligible.
 */
final readonly class ScientificSourceOnboardingContract
{
    public const CONTRACT_VERSION = 'scientific_source_onboarding_contract.v1';

    public const ACCESS_STATES = [
        'MACHINE_OR_BULK_ACCESS_DOCUMENTED',
        'WEB_OR_CONTENT_ACCESS_ONLY_DOCUMENTED',
        'NOT_DOCUMENTED',
    ];

    public const SELECTOR_NOT_SELECTABLE = 'NOT_SELECTABLE';

    /**
     * @param  list<string>  $identityEvidenceRefs
     * @param  array<string, CapabilityState>  $capabilityDimensionStates  keyed by ScientificSourceOperation value
     * @param  list<string>  $capabilityEvidenceRefs
     * @param  list<CapabilityAccessMethod>  $accessMethods
     * @param  list<string>  $correlationHooks
     */
    public function __construct(
        public string $onboardingDecisionId,
        public AdrMembershipId $adrId,
        public ?CanonicalSourceIdentityId $canonicalIdentityId,
        public array $identityEvidenceRefs,
        public ?string $integrationClassificationRef,
        public ?string $capabilityDecisionIdentity,
        public array $capabilityDimensionStates,
        public array $capabilityEvidenceRefs,
        public array $accessMethods,
        public ScientificSourceLicensingDeclaration $licensing,
        public string $accessState,
        public ?string $pathId,
        public ?string $pathDecisionIdentity,
        public ?PathEligibilityState $pathEligibilityState,
        public ?PathStatus $pathStatus,
        public ?string $projectionIdentity,
        public ?string $fidelityClass,
        public ?string $facetAccounting,
        public ?string $stage3Relationship,
        public string $selectorEligibility,
        public SeatActivationState $activationState,
        public ?string $groupWaveLabel,
        public string $decisionActor,
        public DateTimeImmutable $decisionTimestamp,
        public string $rationale,
        public string $stalenessPolicyRef,
        public array $correlationHooks = [],
        public ?string $supersedesDecisionId = null,
    ) {
        self::assertIdentifier('onboarding_decision_id', $onboardingDecisionId);
        foreach (['decision_actor' => $decisionActor, 'rationale' => $rationale, 'staleness_policy_ref' => $stalenessPolicyRef] as $field => $value) {
            if (trim($value) === '') {
                throw new OnboardingContractViolation("Onboarding {$field} must be non-empty.");
            }
        }
        foreach ([
            'integration_classification_ref' => $integrationClassificationRef,
            'capability_decision_identity' => $capabilityDecisionIdentity,
            'path_id' => $pathId,
            'path_decision_identity' => $pathDecisionIdentity,
            'projection_identity' => $projectionIdentity,
            'fidelity_class' => $fidelityClass,
            'facet_accounting' => $facetAccounting,
            'stage3_relationship' => $stage3Relationship,
            'group_wave_label' => $groupWaveLabel,
            'supersedes_decision_id' => $supersedesDecisionId,
        ] as $field => $value) {
            if ($value !== null && trim($value) === '') {
                throw new OnboardingContractViolation("Onboarding {$field} must be null or non-empty.");
            }
        }
        if ($supersedesDecisionId === $onboardingDecisionId) {
            throw new OnboardingContractViolation('An onboarding decision cannot supersede itself.');
        }
        self::assertStringList('identity_evidence_ref', $identityEvidenceRefs);
        self::assertStringList('capability_evidence_ref', $capabilityEvidenceRefs);
        self::assertStringList('correlation_hooks', $correlationHooks);
        foreach ($capabilityDimensionStates as $operation => $state) {
            if (! is_string($operation) || ScientificSourceOperation::tryFrom($operation) === null) {
                throw new OnboardingContractViolation("Unknown capability dimension '{$operation}'.");
            }
            if (! $state instanceof CapabilityState) {
                throw new OnboardingContractViolation("Capability dimension '{$operation}' must hold a Cap v2 CapabilityState.");
            }
        }
        $methods = [];
        foreach ($accessMethods as $method) {
            if (! $method instanceof CapabilityAccessMethod) {
                throw new OnboardingContractViolation('Access methods must be CapabilityAccessMethod values.');
            }
            if (isset($methods[$method->value])) {
                throw new OnboardingContractViolation("Duplicate access method '{$method->value}'.");
            }
            $methods[$method->value] = true;
        }
        if (! in_array($accessState, self::ACCESS_STATES, true)) {
            throw new OnboardingContractViolation("Unknown access_state '{$accessState}'.");
        }
        if (($pathStatus !== null || $pathEligibilityState !== null || $pathDecisionIdentity !== null) && $pathId === null) {
            throw new OnboardingContractViolation('Path decision, eligibility or status requires a path_id.');
        }
        if ($capabilityDimensionStates !== [] && $capabilityDecisionIdentity === null
            && array_filter($capabilityDimensionStates, static fn (CapabilityState $s): bool => $s !== CapabilityState::UNVERIFIED) !== []) {
            throw new OnboardingContractViolation('A capability state other than UNVERIFIED requires a capability_decision_identity.');
        }
        if ($selectorEligibility !== self::SELECTOR_NOT_SELECTABLE && $activationState !== SeatActivationState::ACTIVE) {
            throw new OnboardingContractViolation('Only an ACTIVE seat may carry a selector eligibility other than NOT_SELECTABLE.');
        }
        if ($activationState->claimsEligibility()) {
            $this->assertEligibilityPrerequisites();
        }
    }

    public function claimsEligibility(): bool
    {
        return $this->activationState->claimsEligibility();
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'contract_version' => self::CONTRACT_VERSION,
            'onboarding_decision_id' => $this->onboardingDecisionId,
            'adr_id' => $this->adrId->value,
            'canonical_identity_id' => $this->canonicalIdentityId?->value,
            'identity_evidence_ref' => $this->identityEvidenceRefs,
            'integration_classification_ref' => $this->integrationClassificationRef,
            'capability_decision_identity' => $this->capabilityDecisionIdentity,
            'capability_dimension_states' => array_map(static fn (CapabilityState $s): string => $s->value, $this->capabilityDimensionStates),
            'capability_evidence_ref' => $this->capabilityEvidenceRefs,
            'access_methods' => array_map(static fn (CapabilityAccessMethod $m): string => $m->value, $this->accessMethods),
            'license_state' => $this->licensing->licenceIdentifier === null ? 'UNRESOLVED' : 'DECLARED',
            'access_state' => $this->accessState,
            'reuse_state' => $this->licensing->toArray()['usages'],
            'path_id' => $this->pathId,
            'path_decision_identity' => $this->pathDecisionIdentity,
            'path_eligibility_state' => $this->pathEligibilityState?->value,
            'path_status' => $this->pathStatus?->value,
            'projection_identity' => $this->projectionIdentity,
            'fidelity_class' => $this->fidelityClass,
            'facet_accounting' => $this->facetAccounting,
            'stage3_relationship' => $this->stage3Relationship,
            'selector_eligibility' => $this->selectorEligibility,
            'activation_state' => $this->activationState->value,
            'group_wave_label' => $this->groupWaveLabel,
            'decision_actor' => $this->decisionActor,
            'decision_timestamp' => $this->decisionTimestamp->format(DATE_ATOM),
            'rationale' => $this->rationale,
            'staleness_policy_ref' => $this->stalenessPolicyRef,
            'correlation_hooks' => $this->correlationHooks,
            'supersedes_decision_id' => $this->supersedesDecisionId,
        ];
    }

    private function assertEligibilityPrerequisites(): void
    {
        $state = $this->activationState->value;
        $missing = [];
        if ($this->canonicalIdentityId === null) {
            $missing[] = 'canonical_identity_id';
        }
        if ($this->identityEvidenceRefs === []) {
            $missing[] = 'identity_evidence_ref';
        }
        if ($this->integrationClassificationRef === null) {
            $missing[] = 'integration_classification_ref';
        }
        if ($this->capabilityDecisionIdentity === null) {
            $missing[] = 'capability_decision_identity';
        }
        if ($this->pathId === null || $this->pathDecisionIdentity === null || $this->pathEligibilityState === null) {
            $missing[] = 'path decision';
        }
        if ($this->projectionIdentity === null) {
            $missing[] = 'projection_identity';
        }
        if (! $this->licensing->permits(ScientificSourceUsage::RETRIEVAL)) {
            $missing[] = 'licence permitting retrieval';
        }
        if ($missing !== []) {
            throw new OnboardingContractViolation(
                "Seat '{$this->adrId->value}' cannot be {$state}; missing: ".implode(', ', $missing).'.'
            );
        }
        $searchState = $this->capabilityDimensionStates[ScientificSourceOperation::SEARCH->value] ?? CapabilityState::UNVERIFIED;
        $requiredPath = $this->activationState === SeatActivationState::CONDITIONALLY_ELIGIBLE
            ? [PathEligibilityState::ELIGIBLE, PathEligibilityState::CONDITIONAL]
            : [PathEligibilityState::ELIGIBLE];
        if (! in_array($this->pathEligibilityState, $requiredPath, true)) {
            throw new OnboardingContractViolation(
                "Seat '{$this->adrId->value}' cannot be {$state} with path eligibility '{$this->pathEligibilityState?->value}'."
            );
        }
        $searchOk = $this->activationState === SeatActivationState::CONDITIONALLY_ELIGIBLE
            ? in_array($searchState, [CapabilityState::VERIFIED, CapabilityState::PARTIAL], true)
            : $searchState === CapabilityState::VERIFIED;
        if (! $searchOk) {
            throw new OnboardingContractViolation(
                "Seat '{$this->adrId->value}' cannot be {$state} with search capability '{$searchState->value}'."
            );
        }
        if ($this->activationState === SeatActivationState::ACTIVE && $this->stage3Relationship === null) {
            throw new OnboardingContractViolation("An ACTIVE seat '{$this->adrId->value}' needs the runtime sourceKey it is bound to.");
        }
    }

    private static function assertIdentifier(string $field, string $value): void
    {
        if (preg_match('/^[A-Za-z0-9][A-Za-z0-9._:\/-]{2,127}$/', $value) !== 1) {
            throw new OnboardingContractViolation("Onboarding {$field} '{$value}' is not a valid identifier.");
        }
    }

    /** @param  array<mixed>  $values */
    private static function assertStringList(string $field, array $values): void
    {
        if (! array_is_list($values)) {
            throw new OnboardingContractViolation("Onboarding {$field} must be a list.");
        }
        foreach ($values as $value) {
            if (! is_string($value) || trim($value) === '') {
                throw new OnboardingContractViolation("Onboarding {$field} entries must be non-empty strings.");
            }
        }
    }
}
