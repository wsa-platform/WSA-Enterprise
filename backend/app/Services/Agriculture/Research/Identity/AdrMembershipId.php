<?php

namespace App\Services\Agriculture\Research\Identity;

/**
 * ADR-023 membership seat identity (`adr_id`).
 *
 * Distinct from canonical source identity, Stage-3 sourceKey, article/record ids,
 * and external aggregator ids. Not a scientific entity id (CSQ).
 */
final readonly class AdrMembershipId
{
    private function __construct(
        public string $value,
    ) {}

    public static function fromString(string $value): self
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw new SourceIdentityInvariantViolation('adr_id must be a non-empty string.');
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
