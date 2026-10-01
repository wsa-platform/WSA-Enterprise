<?php

namespace App\Services\Agriculture\Research\Identity;

/**
 * External dependency / aggregator / resource identity reference.
 *
 * Explicitly not an ADR seat (`adr_id`), not a minted canonical source id,
 * and not a Stage-3 `sourceKey`. No promotion helpers are provided.
 */
final readonly class ExternalIdentityRef
{
    private function __construct(
        public ExternalIdentityKind $kind,
        public string $identifier,
    ) {}

    public static function of(ExternalIdentityKind $kind, string $identifier): self
    {
        $trimmed = trim($identifier);
        if ($trimmed === '') {
            throw new SourceIdentityInvariantViolation('External identity identifier must be non-empty.');
        }

        return new self($kind, $trimmed);
    }

    /**
     * Forbidden automatic promotion — Stage-3 sourceKey is a separate runtime namespace.
     */
    public function asStage3SourceKey(): never
    {
        throw new SourceIdentityInvariantViolation(
            'External aggregator/dependency identity must not be promoted to Stage-3 sourceKey.'
        );
    }

    /**
     * Forbidden automatic promotion — ADR membership is a separate governance namespace.
     */
    public function asAdrMembershipId(): never
    {
        throw new SourceIdentityInvariantViolation(
            'External identity must not be promoted to adr_id.'
        );
    }
}
