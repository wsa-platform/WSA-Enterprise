<?php

namespace App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat;

/**
 * Operational snapshot for Phase 5. No secrets. RFN live validation is not claimed passed.
 */
final class FaoStatOperationalStatus
{
    /**
     * @return array<string, mixed>
     */
    public static function snapshot(): array
    {
        return [
            'faostat_enabled_default' => false,
            'qcl_default_active' => true,
            'qcl_activation' => FaoStatActivationPolicy::activationState(FaoStatDomainCatalog::QCL),
            'rfn_active' => FaoStatActivationPolicy::isActive('RFN'),
            'rfn_activation' => FaoStatActivationPolicy::activationState('RFN'),
            'rfn_live_validation' => FaoStatLiveValidationRegistry::rfnLiveValidationStatus(),
            'fenix_retained' => true,
            'active_domains' => FaoStatActivationPolicy::activeDomains(),
            'live_validated_domains' => FaoStatLiveValidationRegistry::liveValidatedDomains(),
        ];
    }
}
