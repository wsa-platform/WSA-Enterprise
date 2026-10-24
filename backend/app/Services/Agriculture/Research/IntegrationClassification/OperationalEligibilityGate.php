<?php

declare(strict_types=1);

namespace App\Services\Agriculture\Research\IntegrationClassification;

/**
 * Separates classification evidence from endpoint testing and operational eligibility.
 *
 * This encodes ADR-023 §8.30.4, §8.30.5, §8.40.1 and §8.40.2. It does not send
 * HTTP, and a classification record cannot satisfy the endpoint-test dimension.
 */
final class OperationalEligibilityGate
{
    public const NOT_RUN = 'NOT_RUN';

    public const DOCUMENTED = 'DOCUMENTED';

    public const VERIFIED = 'VERIFIED';

    public const BLOCKED = 'BLOCKED';

    public const ENDPOINT_KIND_CLASSIFICATION_RECORD = 'classification_record';

    /** @var list<string> */
    private const STATUSES = [
        self::NOT_RUN,
        self::DOCUMENTED,
        self::VERIFIED,
        self::BLOCKED,
    ];

    /**
     * @param  list<string>  $suppliedFacets
     * @param  list<string>  $resolvedCapabilityCells
     */
    public function evaluate(
        string $seatId,
        string $identity,
        string $classification,
        string $endpointTest,
        string $integration,
        ?string $endpointEvidenceKind = null,
        array $suppliedFacets = [],
        array $resolvedCapabilityCells = [],
    ): OperationalEligibilityAssessment {
        $this->assertSeatId($seatId);
        foreach ([
            'identity' => $identity,
            'classification' => $classification,
            'endpoint_test' => $endpointTest,
            'integration' => $integration,
        ] as $dimension => $status) {
            $this->assertStatus($dimension, $status);
        }

        $blockers = [];
        $effectiveEndpoint = $endpointTest;
        if ($endpointTest === self::VERIFIED && $endpointEvidenceKind === self::ENDPOINT_KIND_CLASSIFICATION_RECORD) {
            $effectiveEndpoint = self::BLOCKED;
            $blockers[] = 'endpoint_test_cannot_be_a_classification_record';
        }

        foreach ([
            'identity' => $identity,
            'classification' => $classification,
            'endpoint_test' => $effectiveEndpoint,
            'integration' => $integration,
        ] as $dimension => $status) {
            if ($status !== self::VERIFIED) {
                $blockers[] = $dimension.'_not_verified';
            }
        }
        foreach ($suppliedFacets as $facet) {
            if (! is_string($facet) || ! in_array($facet, $resolvedCapabilityCells, true)) {
                $blockers[] = 'unsupported_facet';
                break;
            }
        }

        return new OperationalEligibilityAssessment(
            $seatId,
            $identity,
            $classification,
            $effectiveEndpoint,
            $integration,
            $blockers === [],
            $blockers,
        );
    }

    /**
     * The state recorded for every seat by §8.30.5: a documentary proposal exists,
     * identity is not verified, and no endpoint or integration test was run.
     */
    public function fromDocumentaryInventory(string $seatId): OperationalEligibilityAssessment
    {
        return $this->evaluate($seatId, self::NOT_RUN, self::DOCUMENTED, self::NOT_RUN, self::NOT_RUN);
    }

    private function assertSeatId(string $seatId): void
    {
        if (preg_match('/^G[1-6]-\d{2}$/', $seatId) !== 1) {
            throw new \InvalidArgumentException('Seat id is not an adr_id.');
        }
    }

    private function assertStatus(string $dimension, string $status): void
    {
        if (! in_array($status, self::STATUSES, true)) {
            throw new \InvalidArgumentException("Unsupported status for {$dimension}.");
        }
    }
}
