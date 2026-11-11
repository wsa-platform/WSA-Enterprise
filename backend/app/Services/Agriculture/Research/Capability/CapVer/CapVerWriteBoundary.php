<?php

declare(strict_types=1);

namespace App\Services\Agriculture\Research\Capability\CapVer;

use App\Services\Agriculture\Research\Capability\CapabilityInvariantViolation;
use App\Services\Agriculture\Research\Capability\CapabilityRecord;
use App\Services\Agriculture\Research\Capability\CapabilityState;

/**
 * Enforcement above the repository and at the defensive write boundary (OD-A-ID).
 *
 * CapVer alone grants VERIFIED. UNVERIFIED is never persisted. Absence remains UNVERIFIED.
 * A caller-supplied formatted identifier without a registry hit is rejected.
 */
final class CapVerWriteBoundary
{
    public function __construct(
        private readonly CapVerAssessmentRegistry $registry,
    ) {}

    public function assertAuthorizedWrite(CapabilityRecord $record, ?CapVerAuthorizedAssessment $assessment): void
    {
        if ($record->state === CapabilityState::UNVERIFIED) {
            throw new CapabilityInvariantViolation('UNVERIFIED capability state must not be persisted.');
        }

        if ($assessment === null) {
            throw new CapabilityInvariantViolation(
                'Capability persistence requires a CapVer-authorized assessment.'
            );
        }

        // Re-resolve so a forged CapVerAuthorizedAssessment object cannot bypass the registry.
        $resolved = $this->registry->resolve($assessment->decisionId->value);
        $adrId = $record->subject->adrId->value;
        if (! $resolved->allows($adrId, $record->dimension, $record->state)) {
            throw new CapabilityInvariantViolation(
                'CapVer assessment does not authorize this source, dimension, and state.'
            );
        }
    }

    public function assertAuthorizedWriteFromIdentifier(CapabilityRecord $record, ?string $decisionId): void
    {
        if ($decisionId === null || trim($decisionId) === '') {
            $this->assertAuthorizedWrite($record, null);

            return;
        }

        $assessment = $this->registry->resolve($decisionId);
        $this->assertAuthorizedWrite($record, $assessment);
    }
}
