<?php

declare(strict_types=1);

namespace App\Services\Agriculture\Research\Capability\CapVer;

use App\Services\Agriculture\Research\Capability\CapabilityDimension;
use App\Services\Agriculture\Research\Capability\CapabilityInvariantViolation;
use App\Services\Agriculture\Research\Capability\CapabilityState;

/**
 * Authorized CapVer assessment record. Issued only by CapVerAssessmentIssuer.
 */
final readonly class CapVerAuthorizedAssessment
{
    /**
     * @param  list<string>  $evidenceRefs
     */
    public function __construct(
        public CapVerDecisionIdentifier $decisionId,
        public string $adrId,
        public CapabilityDimension $dimension,
        public CapabilityState $authorizedState,
        public array $evidenceRefs,
        public string $issuedAtUtc,
    ) {
        if ($authorizedState === CapabilityState::UNVERIFIED) {
            throw new CapabilityInvariantViolation('CapVer must not authorize UNVERIFIED persistence.');
        }
        if ($this->adrId !== $decisionId->adrId) {
            throw new CapabilityInvariantViolation('CapVer assessment adr_id does not match its decision identifier.');
        }
        if (! $decisionId->matchesDimension($dimension)) {
            throw new CapabilityInvariantViolation('CapVer assessment dimension does not match its decision identifier.');
        }
        if ($this->evidenceRefs === [] && in_array($authorizedState, [
            CapabilityState::VERIFIED,
            CapabilityState::PARTIAL,
            CapabilityState::UNAVAILABLE,
        ], true)) {
            throw new CapabilityInvariantViolation('CapVer assessment for this state requires evidence refs.');
        }
    }

    public function allows(string $adrId, CapabilityDimension $dimension, CapabilityState $state): bool
    {
        return $this->adrId === $adrId
            && $this->dimension->equals($dimension)
            && $this->authorizedState === $state;
    }
}
