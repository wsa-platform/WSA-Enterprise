<?php

namespace App\Services\Agriculture\Research\Projection;

/**
 * Opaque projection_identity — distinct from adr_id / Cap / Path / CSQ / sourceKey.
 */
final readonly class ProjectionIdentity
{
    private function __construct(
        public string $value,
    ) {}

    public static function fromString(string $value): self
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw new ProjectionInvariantViolation('projection_identity must be a non-empty string.');
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
