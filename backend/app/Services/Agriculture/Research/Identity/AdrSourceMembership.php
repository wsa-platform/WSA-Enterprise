<?php

namespace App\Services\Agriculture\Research\Identity;

/**
 * ADR-023 source membership seat (domain contract).
 *
 * Owns WHO/WHAT the ADR seat is. Does not encode Cap states, Path, Projection,
 * scientific entity facets, or Stage-3 sourceKey.
 *
 * @phpstan-type MembershipPayload array{
 *     adr_id: string,
 *     canonical_identity_id: ?string,
 *     label: ?string,
 *     resource_class: ?string
 * }
 */
final readonly class AdrSourceMembership
{
    private function __construct(
        public AdrMembershipId $adrId,
        public ?CanonicalSourceIdentityId $canonicalIdentityId,
        public ?string $label,
        public ?string $resourceClass,
    ) {}

    public static function create(
        AdrMembershipId $adrId,
        ?CanonicalSourceIdentityId $canonicalIdentityId = null,
        ?string $label = null,
        ?string $resourceClass = null,
    ): self {
        $normalizedLabel = self::normalizeOptionalString($label);
        $normalizedClass = self::normalizeOptionalString($resourceClass);

        // adr_id must never be treated as identical to canonical by string coincidence alone.
        if ($canonicalIdentityId !== null && $adrId->value === $canonicalIdentityId->value) {
            throw new SourceIdentityInvariantViolation(
                'adr_id must not equal canonical_identity_id; seats and canonicals are distinct namespaces.'
            );
        }

        return new self($adrId, $canonicalIdentityId, $normalizedLabel, $normalizedClass);
    }

    public function withCanonicalIdentity(?CanonicalSourceIdentityId $canonicalIdentityId): self
    {
        return self::create($this->adrId, $canonicalIdentityId, $this->label, $this->resourceClass);
    }

    public function hasCanonicalIdentity(): bool
    {
        return $this->canonicalIdentityId !== null;
    }

    /**
     * @return MembershipPayload
     */
    public function toArray(): array
    {
        return [
            'adr_id' => $this->adrId->value,
            'canonical_identity_id' => $this->canonicalIdentityId?->value,
            'label' => $this->label,
            'resource_class' => $this->resourceClass,
        ];
    }

    private static function normalizeOptionalString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
