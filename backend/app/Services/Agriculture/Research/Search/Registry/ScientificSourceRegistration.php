<?php

namespace App\Services\Agriculture\Research\Search\Registry;

use App\Contracts\ScientificSourceAdapterInterface;
use App\Contracts\ScientificSourceAdapterV2Interface;
use App\Services\Agriculture\Research\Identity\AdrMembershipId;
use App\Services\Agriculture\Research\Onboarding\AcceptedOnboardingDecisionCatalog;
use App\Services\Agriculture\Research\Onboarding\SeatActivationState;
use App\Services\Agriculture\Research\Path\PathFamily;
use App\Services\Agriculture\Research\Search\Family\ScientificSourceFamilyAdapter;
use App\Services\Agriculture\Research\Search\Family\ScientificSourceFamilyProfile;

/**
 * One runtime source registration: the single place a Stage-3 sourceKey is declared.
 *
 * Registration is not activation, not membership, and not a Capability fact
 * (ADR-023 D-10.3, D-10.7). The sourceKey is a Stage-3 namespace, distinct from `adr_id`.
 */
final readonly class ScientificSourceRegistration
{
    /** v1 stays supported while adapters migrate to v2 (ADR-023 §8.36). */
    public const SUPPORTED_CONTRACT_VERSIONS = [
        ScientificSourceAdapterInterface::CONTRACT_VERSION,
        ScientificSourceAdapterV2Interface::V2_CONTRACT_VERSION,
    ];

    /**
     * @param  class-string<ScientificSourceAdapterInterface>  $adapterClass
     * @param  list<ScientificSourceCapabilityDeclaration>  $capabilities  at most one per operation
     * @param  ScientificSourceFamilyProfile|null  $familyProfile  required by, and only by, a source-family adapter (ADR-023 §8.39)
     */
    public function __construct(
        public string $sourceKey,
        public string $adapterClass,
        public string $adapterContractVersion,
        public ScientificSourceLifecycle $lifecycle,
        public ScientificSourceAccessFamily $accessFamily,
        public ScientificSourceSelectionRole $selectionRole,
        public ScientificSourceExecutionGroup $executionGroup,
        public ScientificSourceEnablement $enablement,
        public PathFamily $pathFamily,
        public ?AdrMembershipId $adrId = null,
        public array $capabilities = [],
        public ?ScientificSourceFamilyProfile $familyProfile = null,
    ) {
        if (preg_match('/^[a-z][a-z0-9_]*$/', $sourceKey) !== 1) {
            throw new ScientificSourceRegistryInvariantViolation(
                "sourceKey must be lower-case snake_case; got '{$sourceKey}'."
            );
        }
        if (! is_subclass_of($adapterClass, ScientificSourceAdapterInterface::class)) {
            throw new ScientificSourceRegistryInvariantViolation(
                "Adapter for '{$sourceKey}' must implement ScientificSourceAdapterInterface; got '{$adapterClass}'."
            );
        }
        if (! in_array($adapterContractVersion, self::SUPPORTED_CONTRACT_VERSIONS, true)) {
            throw new ScientificSourceRegistryInvariantViolation(
                "Unsupported adapter contract version '{$adapterContractVersion}' for '{$sourceKey}'; supported: '"
                .implode("', '", self::SUPPORTED_CONTRACT_VERSIONS)."'."
            );
        }
        if ($adapterContractVersion === ScientificSourceAdapterV2Interface::V2_CONTRACT_VERSION
            && ! is_subclass_of($adapterClass, ScientificSourceAdapterV2Interface::class)) {
            throw new ScientificSourceRegistryInvariantViolation(
                "Adapter for '{$sourceKey}' declares contract '{$adapterContractVersion}' but does not implement ScientificSourceAdapterV2Interface."
            );
        }
        $isFamilyAdapter = is_subclass_of($adapterClass, ScientificSourceFamilyAdapter::class);
        if ($isFamilyAdapter !== ($familyProfile !== null)) {
            throw new ScientificSourceRegistryInvariantViolation(
                "Adapter for '{$sourceKey}': a source-family adapter requires a family profile, and only a source-family adapter takes one."
            );
        }
        if ($familyProfile !== null) {
            if ($familyProfile->sourceKey() !== $sourceKey) {
                throw new ScientificSourceRegistryInvariantViolation(
                    "Family profile '{$familyProfile->sourceKey()}' cannot serve registration '{$sourceKey}'."
                );
            }
            if ($familyProfile->accessFamily() !== $accessFamily || ! $adapterClass::servesAccessFamily($accessFamily)) {
                throw new ScientificSourceRegistryInvariantViolation(
                    "Registration '{$sourceKey}' declares access family '{$accessFamily->value}' but its profile or adapter belongs to another family."
                );
            }
        }
        if ($adrId !== null) {
            if ($lifecycle !== ScientificSourceLifecycle::GOVERNED_ONBOARDED) {
                throw new ScientificSourceRegistryInvariantViolation(
                    "ADR seat '{$adrId->value}' cannot be registered for '{$sourceKey}' under lifecycle '{$lifecycle->value}'; GOVERNED_ONBOARDED is required (ADR-023 D-10.11)."
                );
            }
            $contract = AcceptedOnboardingDecisionCatalog::forAdrId($adrId->value);
            if ($contract === null
                || $contract->activationState !== SeatActivationState::ACTIVE
                || $contract->stage3Relationship !== $sourceKey) {
                throw new ScientificSourceRegistryInvariantViolation(
                    "ADR seat '{$adrId->value}' cannot be registered for '{$sourceKey}': an accepted ACTIVE Onboarding Contract bound to this sourceKey is required first (ADR-023 D-10.11)."
                );
            }
        } elseif ($lifecycle === ScientificSourceLifecycle::GOVERNED_ONBOARDED) {
            throw new ScientificSourceRegistryInvariantViolation(
                "Governed registration '{$sourceKey}' requires an adr_id binding."
            );
        }
        $operations = [];
        foreach ($capabilities as $capability) {
            if (! $capability instanceof ScientificSourceCapabilityDeclaration) {
                throw new ScientificSourceRegistryInvariantViolation(
                    "Capabilities of '{$sourceKey}' must be ScientificSourceCapabilityDeclaration instances."
                );
            }
            $op = $capability->operation->value;
            if (isset($operations[$op])) {
                throw new ScientificSourceRegistryInvariantViolation(
                    "Duplicate capability declaration '{$op}' for '{$sourceKey}'."
                );
            }
            if ($capability->status === ScientificSourceCapabilityStatus::LEGACY_OPERATIONAL_BASIS
                && $lifecycle !== ScientificSourceLifecycle::LEGACY_STAGE3) {
                throw new ScientificSourceRegistryInvariantViolation(
                    "Only legacy Stage-3 registrations may use the legacy operational basis; '{$sourceKey}' is '{$lifecycle->value}'."
                );
            }
            $operations[$op] = true;
        }
    }

    public function isEnabled(): bool
    {
        return $this->enablement->isEnabled();
    }

    public function capabilityFor(ScientificSourceOperation $operation): ?ScientificSourceCapabilityDeclaration
    {
        foreach ($this->capabilities as $capability) {
            if ($capability->operation === $operation) {
                return $capability;
            }
        }

        return null;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'source_key' => $this->sourceKey,
            'adapter_class' => $this->adapterClass,
            'adapter_contract_version' => $this->adapterContractVersion,
            'lifecycle' => $this->lifecycle->value,
            'access_family' => $this->accessFamily->value,
            'selection_role' => $this->selectionRole->value,
            'execution_group' => $this->executionGroup->value,
            'path_family' => $this->pathFamily->value,
            'adr_id' => $this->adrId?->value,
            'capabilities' => array_map(
                static fn (ScientificSourceCapabilityDeclaration $c): array => $c->toArray(),
                $this->capabilities,
            ),
            'family_profile' => $this->familyProfile?->toArray(),
        ];
    }
}
