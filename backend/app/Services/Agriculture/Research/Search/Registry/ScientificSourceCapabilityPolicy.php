<?php

namespace App\Services\Agriculture\Research\Search\Registry;

use DateTimeInterface;
use Illuminate\Support\Carbon;

/**
 * Deny-by-default capability enforcement for runtime registrations (ADR-023 §8.35).
 *
 * An operation is authorized only by unexpired VERIFIED_SUPPORTED evidence, or, for search on a
 * legacy Stage-3 registration, by the disclosed legacy operational basis. A missing declaration
 * is never read as support.
 */
final class ScientificSourceCapabilityPolicy
{
    public function decide(
        ScientificSourceRegistration $registration,
        ScientificSourceOperation $operation,
        ?DateTimeInterface $now = null,
    ): ScientificSourceCapabilityDecision {
        $declaration = $registration->capabilityFor($operation);
        if ($declaration === null) {
            return new ScientificSourceCapabilityDecision(
                $registration->sourceKey,
                $operation,
                false,
                ScientificSourceCapabilityDecision::REASON_NOT_DECLARED,
            );
        }

        [$allowed, $reason] = match ($declaration->status) {
            ScientificSourceCapabilityStatus::VERIFIED_SUPPORTED => $declaration->isExpiredAt($now ?? Carbon::now())
                ? [false, ScientificSourceCapabilityDecision::REASON_EVIDENCE_STALE]
                : [true, ScientificSourceCapabilityDecision::REASON_VERIFIED_CURRENT],
            ScientificSourceCapabilityStatus::LEGACY_OPERATIONAL_BASIS => [
                $registration->lifecycle === ScientificSourceLifecycle::LEGACY_STAGE3,
                ScientificSourceCapabilityDecision::REASON_LEGACY_OPERATIONAL_BASIS,
            ],
            default => [false, $declaration->status->value],
        };

        return new ScientificSourceCapabilityDecision(
            $registration->sourceKey,
            $operation,
            $allowed,
            $reason,
            $declaration->status,
            $declaration->evidenceReference,
        );
    }
}
