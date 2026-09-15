<?php

namespace App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat;

/**
 * Honest live-validation registry. QCL was live-validated in Phase 1–3.
 * RFN live validation remains PENDING/BLOCKED and cannot be flipped by config.
 */
final class FaoStatLiveValidationRegistry
{
    public const RFN_STATUS = 'PENDING_BLOCKED';

    /**
     * @return list<string>
     */
    public static function liveValidatedDomains(): array
    {
        return [FaoStatDomainCatalog::QCL];
    }

    public static function isLiveValidated(string $domain): bool
    {
        return strtoupper(trim($domain)) === FaoStatDomainCatalog::QCL;
    }

    public static function rfnLiveValidationStatus(): string
    {
        return self::RFN_STATUS;
    }
}
