<?php

namespace App\Services\Agriculture\Research\Capability;

use App\Services\Agriculture\Research\Identity\AdrMembershipId;
use App\Services\Agriculture\Research\Identity\CanonicalSourceIdentityId;

/**
 * Cap subject binding — consumes IU-01 identity; never mints or merges it.
 *
 * Seat-scoped when canonical is null; canonical shared dossier only when a
 * minted canonical is supplied by Identity (SAME_AS proven outside Cap).
 */
final readonly class CapabilitySubject
{
    private function __construct(
        public AdrMembershipId $adrId,
        public ?CanonicalSourceIdentityId $canonicalIdentityId,
    ) {}

    public static function of(
        AdrMembershipId $adrId,
        ?CanonicalSourceIdentityId $canonicalIdentityId = null,
    ): self {
        if ($canonicalIdentityId !== null && $adrId->value === $canonicalIdentityId->value) {
            throw new CapabilityInvariantViolation(
                'Capability subject adr_id must not equal canonical_identity_id.'
            );
        }

        return new self($adrId, $canonicalIdentityId);
    }

    public static function seatScoped(AdrMembershipId $adrId): self
    {
        return self::of($adrId, null);
    }

    public function isSeatScoped(): bool
    {
        return $this->canonicalIdentityId === null;
    }

    public function hasCanonicalBinding(): bool
    {
        return $this->canonicalIdentityId !== null;
    }

    /**
     * @return array{adr_id: string, canonical_identity_id: ?string}
     */
    public function toArray(): array
    {
        return [
            'adr_id' => $this->adrId->value,
            'canonical_identity_id' => $this->canonicalIdentityId?->value,
        ];
    }
}
