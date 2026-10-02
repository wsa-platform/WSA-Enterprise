<?php

namespace App\Services\Agriculture\Research\Identity;

/**
 * Opaque identity decision identity (historical immutable id for a binding decision).
 */
final readonly class IdentityDecisionIdentity
{
    private function __construct(
        public string $value,
    ) {}

    public static function fromString(string $value): self
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw new SourceIdentityInvariantViolation(
                'identity_decision_identity must be a non-empty string.'
            );
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
