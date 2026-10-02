<?php

namespace App\Services\Agriculture\Research\IntegrationClassification\Persistence;

/**
 * Storage bigint encoding of persistence_record_id (NOT a domain identity).
 */
final readonly class IntegrationClassificationRecordId
{
    private function __construct(
        public string $value,
    ) {}

    public static function fromInt(int $id): self
    {
        if ($id < 1) {
            throw new IntegrationClassificationInvariantViolation(
                'persistence_record_id must be a positive integer.'
            );
        }

        return new self((string) $id);
    }

    public function toInt(): int
    {
        return (int) $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
