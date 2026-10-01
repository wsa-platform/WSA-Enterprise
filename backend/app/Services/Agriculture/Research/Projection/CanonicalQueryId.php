<?php

namespace App\Services\Agriculture\Research\Projection;

/**
 * Reference to CSQ / canonical query identity — Projection consumes; does not mint CSQ.
 */
final readonly class CanonicalQueryId
{
    private function __construct(
        public string $value,
    ) {}

    public static function fromString(string $value): self
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw new ProjectionInvariantViolation('canonical_query_id must be a non-empty string.');
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
