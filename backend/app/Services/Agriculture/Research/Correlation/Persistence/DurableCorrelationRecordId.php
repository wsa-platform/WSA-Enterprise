<?php

namespace App\Services\Agriculture\Research\Correlation\Persistence;

/**
 * Storage-owned persistence_record_id.
 *
 * Encoding (storage implementation only): unsigned bigint matching Laravel `$table->id()`.
 * This is NOT a domain identity and MUST NOT equal adr_id / path_id / projection_identity / etc.
 */
final readonly class DurableCorrelationRecordId
{
    private function __construct(
        public string $value,
    ) {}

    public static function fromString(string $value): self
    {
        $trimmed = trim($value);
        if ($trimmed === '' || ! ctype_digit($trimmed) || $trimmed === '0') {
            throw new DurableCorrelationInvariantViolation(
                'persistence_record_id must be a positive integer string (storage bigint encoding).'
            );
        }

        return new self($trimmed);
    }

    public static function fromInt(int $value): self
    {
        if ($value < 1) {
            throw new DurableCorrelationInvariantViolation(
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
