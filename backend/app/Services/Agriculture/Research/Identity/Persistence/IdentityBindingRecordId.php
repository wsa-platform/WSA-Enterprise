<?php

namespace App\Services\Agriculture\Research\Identity\Persistence;

/**
 * Storage-owned persistence_record_id for identity binding rows.
 *
 * Encoding: unsigned bigint matching Laravel `$table->id()`.
 * NOT a domain identity (not adr_id / canonical_identity_id / identity_decision_identity).
 */
final readonly class IdentityBindingRecordId
{
    private function __construct(
        public string $value,
    ) {}

    public static function fromString(string $value): self
    {
        $trimmed = trim($value);
        if ($trimmed === '' || ! ctype_digit($trimmed) || $trimmed === '0') {
            throw new IdentityBindingInvariantViolation(
                'persistence_record_id must be a positive integer string (storage bigint encoding).'
            );
        }

        return new self($trimmed);
    }

    public static function fromInt(int $value): self
    {
        if ($value < 1) {
            throw new IdentityBindingInvariantViolation(
                'persistence_record_id must be a positive integer (storage bigint encoding).'
            );
        }

        return new self((string) $value);
    }

    public function toInt(): int
    {
        return (int) $this->value;
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
