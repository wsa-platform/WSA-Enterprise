<?php

namespace App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat;

/**
 * Phase 3-A Model B: layered FAOSTAT registration (not a second provider).
 *
 * AgriculturalProviderRegistry (ADR-001):
 * - Capability / intelligence descriptors
 * - Selection by provider type and required capabilities
 * - Bridges Stage-3 adapters via ScientificAdapterBridgeProvider
 *
 * ScientificSourceAdapterRegistry (Stage 3 runtime):
 * - Concrete ScientificSourceAdapterInterface instances
 * - fao_stat is exclusively FaoStatDeveloperPortalAdapter
 *
 * Shared identity and activation:
 * - Canonical source key: FaoStatRuntimePolicy::canonicalSourceKey() === 'fao_stat'
 * - Sole enablement flag: agricultural_intelligence.faostat.enabled
 * - No FENIX runtime path
 *
 * These registries must not introduce contradictory identities or duplicate
 * portal execution paths for the same search request.
 */
final class FaoStatProviderArchitecture
{
    public const MODEL = 'B_LAYERED_REGISTRIES';

    public const CANONICAL_SOURCE_KEY = FaoStatDeveloperPortalAdapter::SOURCE_KEY;

    public static function canonicalSourceKey(): string
    {
        return FaoStatRuntimePolicy::canonicalSourceKey();
    }

    public static function canonicalAdapterClass(): string
    {
        return FaoStatRuntimePolicy::canonicalAdapterClass();
    }

    public static function activationFlag(): string
    {
        return 'agricultural_intelligence.faostat.enabled';
    }
}
