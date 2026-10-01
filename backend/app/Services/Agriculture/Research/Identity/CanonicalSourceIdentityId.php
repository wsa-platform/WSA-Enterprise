<?php

namespace App\Services\Agriculture\Research\Identity;

/**
 * Opaque canonical source identity (`canonical_identity_id`).
 *
 * Nullable at the membership layer until governance minting. Never equal to adr_id
 * by construction — callers must keep seats and canonicals separate.
 */
final readonly class CanonicalSourceIdentityId
{
    private function __construct(
        public string $value,
    ) {}

    public static function fromString(string $value): self
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw new SourceIdentityInvariantViolation('canonical_identity_id must be a non-empty string when present.');
        }

        return new self($trimmed);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
