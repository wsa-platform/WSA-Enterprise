<?php

namespace App\Services\Agriculture\Research\Identity;

/**
 * Composite parent with explicit member seats (no flattening).
 *
 * Parent eligibility/capability is not implied for members and vice versa
 * (those layers are out of IU-01 scope); this structure only preserves identity graph.
 */
final readonly class CompositeSourceStructure
{
    /**
     * @param  list<AdrMembershipId>  $memberSeatIds
     */
    private function __construct(
        public AdrMembershipId $parentSeatId,
        public array $memberSeatIds,
    ) {}

    /**
     * @param  list<AdrMembershipId>  $memberSeatIds
     */
    public static function of(AdrMembershipId $parentSeatId, array $memberSeatIds): self
    {
        if ($memberSeatIds === []) {
            throw new SourceIdentityInvariantViolation(
                'Composite source must declare at least one member seat.'
            );
        }

        $seen = [];
        $normalized = [];
        foreach ($memberSeatIds as $member) {
            if (! $member instanceof AdrMembershipId) {
                throw new SourceIdentityInvariantViolation(
                    'Composite members must be AdrMembershipId instances.'
                );
            }
            if ($member->equals($parentSeatId)) {
                throw new SourceIdentityInvariantViolation(
                    'Composite member adr_id must not equal parent adr_id.'
                );
            }
            if (isset($seen[$member->value])) {
                throw new SourceIdentityInvariantViolation(
                    'Composite member seats must be unique.'
                );
            }
            $seen[$member->value] = true;
            $normalized[] = $member;
        }

        return new self($parentSeatId, $normalized);
    }

    /**
     * @return list<string>
     */
    public function memberAdrIdValues(): array
    {
        return array_map(
            static fn (AdrMembershipId $id): string => $id->value,
            $this->memberSeatIds,
        );
    }

    /**
     * Flattening is forbidden — no API returns a merged seat list that drops parent/member roles.
     *
     * @return array{parent_adr_id: string, member_adr_ids: list<string>}
     */
    public function toStructuredArray(): array
    {
        return [
            'parent_adr_id' => $this->parentSeatId->value,
            'member_adr_ids' => $this->memberAdrIdValues(),
        ];
    }
}
