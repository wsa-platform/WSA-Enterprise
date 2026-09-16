<?php

namespace App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat;

/**
 * Canonical FAOSTAT statistical-provider state for the Research Agent.
 *
 * Sole flag: agricultural_intelligence.faostat.enabled (FAOSTAT_ENABLED).
 * There is no Fenix alias and no Developer Portal → Fenix fallback.
 */
final class FaoStatRuntimePolicy
{
    public static function isEnabled(): bool
    {
        return filter_var(config('agricultural_intelligence.faostat.enabled', false), FILTER_VALIDATE_BOOL);
    }

    public static function canonicalSourceKey(): string
    {
        return FaoStatDeveloperPortalAdapter::SOURCE_KEY;
    }

    public static function canonicalAdapterClass(): string
    {
        return FaoStatDeveloperPortalAdapter::class;
    }
}
