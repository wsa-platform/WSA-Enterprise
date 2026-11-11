<?php

namespace App\Services\Agriculture\Research\Search\Registry;

use App\Contracts\ScientificSourceAdapterV2Interface;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatDeveloperPortalAdapter;
use App\Services\Agriculture\Research\Onboarding\CurrentMembershipNearReadySeatCatalog;
use App\Services\Agriculture\Research\Onboarding\NextGateEligibleSeatCatalog;
use App\Services\Agriculture\Research\Path\PathFamily;
use App\Services\Agriculture\Research\Search\Adapters\ConsensusScientificSourceAdapter;
use App\Services\Agriculture\Research\Search\Adapters\CrossRefScientificSourceAdapter;
use App\Services\Agriculture\Research\Search\Adapters\OpenAlexScientificSourceAdapter;
use App\Services\Agriculture\Research\Search\Adapters\SemanticScholarScientificSourceAdapter;

/**
 * Canonical runtime source catalog (ADR-023 P1-EXIT-06, increment R1).
 *
 * The only declaration of Stage-3 sourceKeys. The adapter registry, the selector, the
 * orchestrator, and the coexistence identity bridge resolve sources through it.
 * Declaration order is significant: it is the registry order and the selection order.
 *
 * It holds runtime registrations only. ADR-023 membership stays in the CURRENT membership
 * register (D-09); the per-source query profiles stay in ScientificSourceProfileCatalog.
 * Governed ADR seats enter through NextGateEligibleSeatCatalog and
 * CurrentMembershipNearReadySeatCatalog activations (D-10.11).
 */
final class ScientificSourceCatalog
{
    /** Evidence of the owner decision that lets the five legacy sources keep searching (never VERIFIED). */
    public const LEGACY_BASIS_EVIDENCE = 'ADR-023 §8.35.1 owner decision legacy_basis (2026-10-14)';

    /** @var list<string> */
    public const LEGACY_STAGE3_KEYS = ['openalex', 'crossref', 'semantic_scholar', 'consensus', 'fao_stat'];

    private static ?self $default = null;

    /** @var array<string, ScientificSourceRegistration> */
    private readonly array $registrations;

    /** @var array<string, ScientificSourceRegistration> keyed by adr_id value for governed seats */
    private readonly array $registrationsByAdrId;

    /**
     * @param  list<ScientificSourceRegistration>|null  $registrations  null = production registrations
     */
    public function __construct(?array $registrations = null)
    {
        $byKey = [];
        $byAdr = [];
        foreach ($registrations ?? self::productionRegistrations() as $registration) {
            if (! $registration instanceof ScientificSourceRegistration) {
                throw new ScientificSourceRegistryInvariantViolation('Catalog entries must be ScientificSourceRegistration instances.');
            }
            if (isset($byKey[$registration->sourceKey])) {
                throw new ScientificSourceRegistryInvariantViolation(
                    "Duplicate registration for sourceKey '{$registration->sourceKey}'."
                );
            }
            $byKey[$registration->sourceKey] = $registration;
            if ($registration->adrId !== null) {
                $adr = $registration->adrId->value;
                if (isset($byAdr[$adr])) {
                    throw new ScientificSourceRegistryInvariantViolation(
                        "Duplicate registration for adr_id '{$adr}'."
                    );
                }
                $byAdr[$adr] = $registration;
            }
        }

        $this->registrations = $byKey;
        $this->registrationsByAdrId = $byAdr;
    }

    public static function default(): self
    {
        return self::$default ??= new self();
    }

    /** Test seam: drop the cached default so productionRegistrations are re-read. */
    public static function forgetDefault(): void
    {
        self::$default = null;
    }

    /** @return array<string, ScientificSourceRegistration> */
    public function all(): array
    {
        return $this->registrations;
    }

    /** @return list<string> */
    public function sourceKeys(): array
    {
        return array_keys($this->registrations);
    }

    public function has(string $sourceKey): bool
    {
        return isset($this->registrations[$sourceKey]);
    }

    public function get(string $sourceKey): ?ScientificSourceRegistration
    {
        return $this->registrations[$sourceKey] ?? null;
    }

    /**
     * Resolve a governed registration by ADR membership id (P1-EXIT-06 / §8.24.2 adr_id keying increment).
     *
     * Legacy EXTERNAL_ONLY sources have no adr_id and are not present in this index.
     * This method does not claim IC / Cap record references are attached — those remain a separate gap.
     */
    public function getByAdrId(string $adrId): ?ScientificSourceRegistration
    {
        return $this->registrationsByAdrId[$adrId] ?? null;
    }

    /** @return list<string> */
    public function adrIds(): array
    {
        return array_keys($this->registrationsByAdrId);
    }

    /** @return list<string> */
    public function keysWithRole(ScientificSourceSelectionRole $role): array
    {
        return $this->keysMatching(
            static fn (ScientificSourceRegistration $r): bool => $r->selectionRole === $role,
        );
    }

    /** @return list<string> */
    public function enabledKeysWithRole(ScientificSourceSelectionRole $role): array
    {
        return $this->keysMatching(
            static fn (ScientificSourceRegistration $r): bool => $r->selectionRole === $role && $r->isEnabled(),
        );
    }

    /** @return list<string> */
    public function keysInExecutionGroup(ScientificSourceExecutionGroup $group): array
    {
        return $this->keysMatching(
            static fn (ScientificSourceRegistration $r): bool => $r->executionGroup === $group,
        );
    }

    public function isInExecutionGroup(string $sourceKey, ScientificSourceExecutionGroup $group): bool
    {
        return $this->get($sourceKey)?->executionGroup === $group;
    }

    public function pathFamilyFor(string $sourceKey): ?PathFamily
    {
        return $this->get($sourceKey)?->pathFamily;
    }

    /**
     * @param  callable(ScientificSourceRegistration): bool  $predicate
     * @return list<string>
     */
    private function keysMatching(callable $predicate): array
    {
        $keys = [];
        foreach ($this->registrations as $key => $registration) {
            if ($predicate($registration)) {
                $keys[] = $key;
            }
        }

        return $keys;
    }

    /** @return list<ScientificSourceRegistration> */
    private static function productionRegistrations(): array
    {
        $version = ScientificSourceAdapterV2Interface::V2_CONTRACT_VERSION;
        $legacySearch = [ScientificSourceCapabilityDeclaration::legacyOperationalBasis(self::LEGACY_BASIS_EVIDENCE)];

        $legacy = [
            new ScientificSourceRegistration(
                sourceKey: 'openalex',
                adapterClass: OpenAlexScientificSourceAdapter::class,
                adapterContractVersion: $version,
                lifecycle: ScientificSourceLifecycle::LEGACY_STAGE3,
                accessFamily: ScientificSourceAccessFamily::REST_API,
                selectionRole: ScientificSourceSelectionRole::INTERNET_FIRST_DEFAULT,
                executionGroup: ScientificSourceExecutionGroup::INDEPENDENT_SCHOLARLY,
                enablement: new ConfigFlagSourceEnablement('agricultural_intelligence.openalex.enabled', true),
                pathFamily: PathFamily::P12,
                capabilities: $legacySearch,
            ),
            new ScientificSourceRegistration(
                sourceKey: 'crossref',
                adapterClass: CrossRefScientificSourceAdapter::class,
                adapterContractVersion: $version,
                lifecycle: ScientificSourceLifecycle::LEGACY_STAGE3,
                accessFamily: ScientificSourceAccessFamily::REST_API,
                selectionRole: ScientificSourceSelectionRole::INTERNET_FIRST_DEFAULT,
                executionGroup: ScientificSourceExecutionGroup::INDEPENDENT_SCHOLARLY,
                enablement: new ConfigFlagSourceEnablement('agricultural_intelligence.crossref.enabled', true),
                pathFamily: PathFamily::P11,
                capabilities: $legacySearch,
            ),
            new ScientificSourceRegistration(
                sourceKey: 'semantic_scholar',
                adapterClass: SemanticScholarScientificSourceAdapter::class,
                adapterContractVersion: $version,
                lifecycle: ScientificSourceLifecycle::LEGACY_STAGE3,
                accessFamily: ScientificSourceAccessFamily::REST_API,
                selectionRole: ScientificSourceSelectionRole::INTERNET_FIRST_DEFAULT,
                executionGroup: ScientificSourceExecutionGroup::INDEPENDENT_SCHOLARLY,
                enablement: new ConfigFlagSourceEnablement('agricultural_intelligence.semantic_scholar.enabled', true),
                pathFamily: PathFamily::P13,
                capabilities: $legacySearch,
            ),
            new ScientificSourceRegistration(
                sourceKey: 'consensus',
                adapterClass: ConsensusScientificSourceAdapter::class,
                adapterContractVersion: $version,
                lifecycle: ScientificSourceLifecycle::LEGACY_STAGE3,
                accessFamily: ScientificSourceAccessFamily::REST_API,
                selectionRole: ScientificSourceSelectionRole::OPTIONAL_EXPLICIT_ONLY,
                executionGroup: ScientificSourceExecutionGroup::SEQUENTIAL,
                enablement: new AlwaysEnabledSourceEnablement(),
                pathFamily: PathFamily::P12,
                capabilities: $legacySearch,
            ),
            new ScientificSourceRegistration(
                sourceKey: FaoStatDeveloperPortalAdapter::SOURCE_KEY,
                adapterClass: FaoStatDeveloperPortalAdapter::class,
                adapterContractVersion: $version,
                lifecycle: ScientificSourceLifecycle::LEGACY_STAGE3,
                accessFamily: ScientificSourceAccessFamily::REST_API,
                selectionRole: ScientificSourceSelectionRole::STATISTICAL_CONSIDERATION,
                executionGroup: ScientificSourceExecutionGroup::STATISTICAL,
                enablement: new FaoStatRuntimeSourceEnablement(),
                pathFamily: PathFamily::P01,
                capabilities: $legacySearch,
            ),
        ];

        return array_merge(
            $legacy,
            NextGateEligibleSeatCatalog::activatedRegistrations(),
            CurrentMembershipNearReadySeatCatalog::activatedRegistrations(),
        );
    }
}
