<?php

namespace App\Services\Agriculture\Research\Path;

/**
 * Stable path_decision_identity — distinct from adr_id / path_id / Cap decision id.
 */
final readonly class PathDecisionId
{
    private function __construct(
        public string $value,
    ) {}

    public static function fromString(string $value): self
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw new PathInvariantViolation('path_decision_identity must be a non-empty string.');
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
