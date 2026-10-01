<?php

namespace App\Services\Agriculture\Research\Identity;

/**
 * Two distinct ADR seats sharing one minted canonical identity (SAME_AS).
 *
 * Seats remain distinct: adr_id(A) ≠ adr_id(B). Does not merge memberships.
 */
final readonly class DualMembershipBinding
{
    private function __construct(
        public AdrMembershipId $seatA,
        public AdrMembershipId $seatB,
        public CanonicalSourceIdentityId $sharedCanonicalIdentityId,
    ) {}

    public static function shareCanonical(
        AdrMembershipId $seatA,
        AdrMembershipId $seatB,
        CanonicalSourceIdentityId $sharedCanonicalIdentityId,
    ): self {
        if ($seatA->equals($seatB)) {
            throw new SourceIdentityInvariantViolation(
                'Dual membership requires two distinct adr_id seats.'
            );
        }

        return new self($seatA, $seatB, $sharedCanonicalIdentityId);
    }

    /**
     * @return list<AdrMembershipId>
     */
    public function seats(): array
    {
        return [$this->seatA, $this->seatB];
    }
}
