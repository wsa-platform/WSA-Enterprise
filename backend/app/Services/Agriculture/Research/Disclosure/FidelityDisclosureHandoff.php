<?php

namespace App\Services\Agriculture\Research\Disclosure;

use App\Services\Agriculture\Research\Projection\ProjectionFidelityClass;
use App\Services\Agriculture\Research\Projection\ProjectionIdentity;

/**
 * Immutable B8 C9→Composer fidelity disclosure handoff (schema_version = 1).
 *
 * Semantic disclosures only — not UI wording, R6, confidence, Cap, Path, or CSQ.
 */
final readonly class FidelityDisclosureHandoff
{
    public const SCHEMA_VERSION = 1;

    public const OBSERVABILITY_KEY = 'c9_fidelity_disclosures';

    /**
     * @param  list<FidelityDisclosureRecord>  $disclosures
     */
    private function __construct(
        public ProjectionIdentity $projectionIdentity,
        public ProjectionFidelityClass $aggregateFidelityClass,
        public array $disclosures,
        public bool $qualificationRequired,
        public bool $unqualifiedExactScientificClaimForbidden,
        public int $schemaVersion,
    ) {}

    /**
     * @param  list<FidelityDisclosureRecord>  $disclosures
     */
    public static function create(
        ProjectionIdentity $projectionIdentity,
        ProjectionFidelityClass $aggregateFidelityClass,
        array $disclosures,
        bool $qualificationRequired,
        bool $unqualifiedExactScientificClaimForbidden,
        int $schemaVersion = self::SCHEMA_VERSION,
    ): self {
        foreach ($disclosures as $record) {
            if (! $record instanceof FidelityDisclosureRecord) {
                throw new FidelityDisclosureInvariantViolation(
                    'FidelityDisclosureHandoff disclosures must be FidelityDisclosureRecord instances.'
                );
            }
        }

        $handoff = new self(
            $projectionIdentity,
            $aggregateFidelityClass,
            array_values($disclosures),
            $qualificationRequired,
            $unqualifiedExactScientificClaimForbidden,
            $schemaVersion,
        );
        FidelityDisclosureDomainContract::assertValidHandoff($handoff);

        return $handoff;
    }

    /**
     * Structured fragment for Composer observability attach (no UI phrasing).
     *
     * @return array<string, mixed>
     */
    public function toObservabilityFragment(): array
    {
        return [
            self::OBSERVABILITY_KEY => $this->toArray(),
            'c9_qualification_required' => $this->qualificationRequired,
            'c9_unqualified_exact_scientific_claim_forbidden' => $this->unqualifiedExactScientificClaimForbidden,
        ];
    }

    /**
     * Minimal consumer helper: merge structured handoff into existing observability.
     *
     * @param  array<string, mixed>  $observability
     * @return array<string, mixed>
     */
    public function mergeIntoObservability(array $observability): array
    {
        FidelityDisclosureDomainContract::assertObservabilityHasNoR6AuthorityKeys($this->toObservabilityFragment());

        return array_merge($observability, $this->toObservabilityFragment());
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'projection_identity' => $this->projectionIdentity->value,
            'aggregate_fidelity_class' => $this->aggregateFidelityClass->value,
            'disclosures' => array_map(
                static fn (FidelityDisclosureRecord $r) => $r->toArray(),
                $this->disclosures,
            ),
            'qualification_required' => $this->qualificationRequired,
            'unqualified_exact_scientific_claim_forbidden' => $this->unqualifiedExactScientificClaimForbidden,
            'schema_version' => $this->schemaVersion,
        ];
    }
}
