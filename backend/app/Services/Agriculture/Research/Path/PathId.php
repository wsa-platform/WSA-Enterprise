<?php

namespace App\Services\Agriculture\Research\Path;

/**
 * Path instance identity (`path_id`) — ≠ adr_id, ≠ path_family, ≠ path_decision_identity.
 */
final readonly class PathId
{
    private function __construct(
        public string $value,
    ) {}

    public static function fromString(string $value): self
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw new PathInvariantViolation('path_id must be a non-empty string.');
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
