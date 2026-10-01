<?php

namespace App\Services\Agriculture\Research\Disclosure;

use App\Services\Agriculture\Research\Projection\ProjectionFacetDisposition;
use App\Services\Agriculture\Research\Projection\ProjectionFidelityClass;
use App\Services\Agriculture\Research\Projection\ProjectionIdentity;

/**
 * Single B8 semantic fidelity disclosure record (OD-01).
 *
 * Not evidence score, confidence, R6, Cap, Path, or source identity.
 */
final readonly class FidelityDisclosureRecord
{
    private function __construct(
        public string $code,
        public ProjectionFidelityClass $c9Class,
        public ?string $facetCode,
        public bool $identityCritical,
        public bool $mandatory,
        public bool $qualificationRequired,
        public ProjectionIdentity $projectionIdentity,
        public ?ProjectionFacetDisposition $disposition,
        public ?string $reason,
    ) {}

    public static function aggregate(
        ProjectionFidelityClass $c9Class,
        ProjectionIdentity $projectionIdentity,
    ): self {
        FidelityDisclosureCodes::assertNonExactClass($c9Class);

        return new self(
            FidelityDisclosureCodes::AGGREGATE_NON_EXACT,
            $c9Class,
            null,
            true,
            true,
            true,
            $projectionIdentity,
            null,
            null,
        );
    }

    public static function identityCriticalLoss(
        ProjectionFidelityClass $c9Class,
        string $facetCode,
        ProjectionIdentity $projectionIdentity,
        ?ProjectionFacetDisposition $disposition = null,
        ?string $reason = null,
    ): self {
        FidelityDisclosureCodes::assertNonExactClass($c9Class);
        $facet = trim($facetCode);
        if ($facet === '') {
            throw new FidelityDisclosureInvariantViolation(
                'C9_IDENTITY_CRITICAL_LOSS requires a non-empty facet_code.'
            );
        }

        return new self(
            FidelityDisclosureCodes::IDENTITY_CRITICAL_LOSS,
            $c9Class,
            $facet,
            true,
            true,
            true,
            $projectionIdentity,
            $disposition,
            self::normalizeOptional($reason),
        );
    }

    public static function nonIdentityFacetLoss(
        ProjectionFidelityClass $c9Class,
        string $facetCode,
        ProjectionIdentity $projectionIdentity,
        ?ProjectionFacetDisposition $disposition = null,
        ?string $reason = null,
    ): self {
        FidelityDisclosureCodes::assertNonExactClass($c9Class);
        $facet = trim($facetCode);
        if ($facet === '') {
            throw new FidelityDisclosureInvariantViolation(
                'C9_NON_IDENTITY_FACET_LOSS requires a non-empty facet_code.'
            );
        }

        return new self(
            FidelityDisclosureCodes::NON_IDENTITY_FACET_LOSS,
            $c9Class,
            $facet,
            false,
            false,
            false,
            $projectionIdentity,
            $disposition,
            self::normalizeOptional($reason),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'c9_class' => $this->c9Class->value,
            'facet_code' => $this->facetCode,
            'identity_critical' => $this->identityCritical,
            'mandatory' => $this->mandatory,
            'qualification_required' => $this->qualificationRequired,
            'projection_identity' => $this->projectionIdentity->value,
            'disposition' => $this->disposition?->value,
            'reason' => $this->reason,
        ];
    }

    private static function normalizeOptional(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
