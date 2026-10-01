<?php

namespace App\Services\Agriculture\Research\Path;

use App\Services\Agriculture\Research\Capability\CapabilityAndResultClass;

/**
 * Cap AND result → Path eligibility_state mapping (Path Model Design + IU-03 auth gate).
 *
 * Does not select status. Does not mutate Cap states.
 */
final class CapabilityToPathEligibilityMapper
{
    public function map(CapabilityAndResultClass $andResultClass): PathEligibilityState
    {
        return match ($andResultClass) {
            CapabilityAndResultClass::ALL_VERIFIED => PathEligibilityState::ELIGIBLE,
            CapabilityAndResultClass::HAS_PARTIAL => PathEligibilityState::CONDITIONAL,
            CapabilityAndResultClass::HAS_UNVERIFIED => PathEligibilityState::DEFERRED,
            CapabilityAndResultClass::HAS_UNAVAILABLE => PathEligibilityState::INELIGIBLE,
        };
    }

    /**
     * Suggested status consistent with eligibility — never auto-applied as Cap promotion.
     * Selection policy remains separate; callers may choose differently when domain allows.
     */
    public function suggestedStatus(PathEligibilityState $eligibility): PathStatus
    {
        return match ($eligibility) {
            PathEligibilityState::ELIGIBLE => PathStatus::SELECTED,
            PathEligibilityState::CONDITIONAL => PathStatus::CONDITIONALLY_SELECTED,
            PathEligibilityState::DEFERRED => PathStatus::DEFERRED_PENDING_CAPABILITY,
            PathEligibilityState::INELIGIBLE => PathStatus::DEFERRED_PENDING_CAPABILITY,
        };
    }
}
