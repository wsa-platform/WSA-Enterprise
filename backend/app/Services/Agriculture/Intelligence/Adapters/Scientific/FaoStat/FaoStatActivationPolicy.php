<?php

namespace App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat;

/**
 * Explicit production activation. Discovery/metadata/codes/mocked tests never activate a domain.
 * RFN cannot become ACTIVE while live validation is PENDING_BLOCKED.
 */
final class FaoStatActivationPolicy
{
    /**
     * Domains allowed for production /data search.
     *
     * @return list<string>
     */
    public static function activeDomains(): array
    {
        $requested = FaoStatConfigurationValidator::sanitizeDomainList(
            config('agricultural_intelligence.faostat.allowed_domains', [FaoStatDomainCatalog::QCL]),
            false,
        )['accepted'];
        $verified = FaoStatDomainCatalog::verifiedDomains();
        $live = FaoStatLiveValidationRegistry::liveValidatedDomains();

        $out = [];
        foreach ($requested as $code) {
            if (in_array($code, $verified, true) && in_array($code, $live, true)) {
                $out[] = $code;
            }
        }

        return $out === [] ? [FaoStatDomainCatalog::QCL] : array_values(array_unique($out));
    }

    public static function isActive(string $domain): bool
    {
        return in_array(strtoupper(trim($domain)), self::activeDomains(), true);
    }

    public static function activationState(string $domain): string
    {
        $code = strtoupper(trim($domain));
        if (! FaoStatConfigurationValidator::isFaostatDomainCode($code)) {
            return FaoStatDomainActivationState::FAILED;
        }
        if (self::isActive($code)) {
            return FaoStatDomainActivationState::ACTIVE;
        }
        if ($code === 'RFN') {
            return FaoStatDomainActivationState::DISABLED;
        }
        if (FaoStatDomainCatalog::isVerified($code) && FaoStatLiveValidationRegistry::isLiveValidated($code)) {
            return FaoStatDomainActivationState::ACTIVATABLE;
        }
        if (FaoStatDomainCatalog::isVerified($code)) {
            return FaoStatDomainActivationState::VERIFIED;
        }

        return FaoStatDomainActivationState::DISABLED;
    }

    public static function assertCanActivate(string $domain): void
    {
        $code = strtoupper(trim($domain));
        if (! self::isActive($code)) {
            throw new FaoStatPortalException(
                FaoStatErrorCategory::DOMAIN_NOT_ALLOWED,
                $code === 'RFN' ? 'rfn_live_validation_pending' : 'domain_not_active',
            );
        }
    }
}
