<?php

declare(strict_types=1);

namespace App\Services\Agriculture\Research\Capability\CapVer;

use App\Services\Agriculture\Research\Capability\CapabilityDimension;
use App\Services\Agriculture\Research\Capability\CapabilityInvariantViolation;
use App\Services\Agriculture\Research\Capability\CapabilityState;
use DateTimeImmutable;
use DateTimeZone;

/**
 * Sole CapVer issuance authority for authorized assessments (OD-A-ID / §8.31.1).
 */
final class CapVerAssessmentIssuer
{
    public function __construct(
        private readonly CapVerAssessmentRegistry $registry,
    ) {}

    /**
     * @param  list<string>  $evidenceRefs
     */
    public function issue(
        string $adrId,
        CapabilityDimension $dimension,
        CapabilityState $state,
        array $evidenceRefs,
        ?DateTimeImmutable $issuedAt = null,
    ): CapVerAuthorizedAssessment {
        if ($state === CapabilityState::UNVERIFIED) {
            throw new CapabilityInvariantViolation('CapVer must not issue an assessment that persists UNVERIFIED.');
        }

        $refs = [];
        foreach ($evidenceRefs as $ref) {
            if (! is_string($ref) || trim($ref) === '') {
                throw new CapabilityInvariantViolation('CapVer evidence refs must be non-empty strings.');
            }
            $refs[] = trim($ref);
        }
        $refs = array_values(array_unique($refs));
        sort($refs);

        $at = ($issuedAt ?? new DateTimeImmutable('now'))->setTimezone(new DateTimeZone('UTC'));
        $lastError = null;
        for ($offsetMs = 0; $offsetMs < 1000; $offsetMs++) {
            $id = CapVerDecisionIdentifier::mint(
                $adrId,
                $dimension,
                $offsetMs === 0 ? $at : $at->modify('+'.$offsetMs.' milliseconds'),
            );
            $assessment = new CapVerAuthorizedAssessment(
                $id,
                $id->adrId,
                $dimension,
                $state,
                $refs,
                $id->issuedAtUtc,
            );
            try {
                $this->registry->register($assessment);

                return $assessment;
            } catch (CapabilityInvariantViolation $e) {
                $lastError = $e;
                if (! str_contains($e->getMessage(), 'already registered')) {
                    throw $e;
                }
            }
        }

        throw $lastError ?? new CapabilityInvariantViolation('CapVer decision identifier could not be minted uniquely.');
    }
}
