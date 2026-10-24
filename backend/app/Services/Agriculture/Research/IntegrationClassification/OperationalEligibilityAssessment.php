<?php

declare(strict_types=1);

namespace App\Services\Agriculture\Research\IntegrationClassification;

final class OperationalEligibilityAssessment
{
    /**
     * @param  list<string>  $blockers
     */
    public function __construct(
        public readonly string $seatId,
        public readonly string $identity,
        public readonly string $classification,
        public readonly string $endpointTest,
        public readonly string $integration,
        public readonly bool $operationallyEligible,
        public readonly array $blockers,
    ) {}
}
