<?php

declare(strict_types=1);

namespace App\Services\Agriculture\Research\Capability\CapVer;

use App\Services\Agriculture\Research\Capability\CapabilityInvariantViolation;

/**
 * Resolves CapVer decision identifiers to issued assessments.
 * A formatted identifier alone is never sufficient.
 */
final class CapVerAssessmentRegistry
{
    /** @var array<string, CapVerAuthorizedAssessment> */
    private array $byId = [];

    public function register(CapVerAuthorizedAssessment $assessment): void
    {
        $id = $assessment->decisionId->value;
        if (isset($this->byId[$id])) {
            throw new CapabilityInvariantViolation('CapVer decision identifier is already registered.');
        }
        $this->byId[$id] = $assessment;
    }

    public function resolve(string $decisionId): CapVerAuthorizedAssessment
    {
        $parsed = CapVerDecisionIdentifier::parse($decisionId);
        $assessment = $this->byId[$parsed->value] ?? null;
        if ($assessment === null) {
            throw new CapabilityInvariantViolation(
                'CapVer decision identifier does not resolve to an authorized assessment.'
            );
        }

        return $assessment;
    }

    public function find(string $decisionId): ?CapVerAuthorizedAssessment
    {
        try {
            return $this->resolve($decisionId);
        } catch (CapabilityInvariantViolation) {
            return null;
        }
    }
}
