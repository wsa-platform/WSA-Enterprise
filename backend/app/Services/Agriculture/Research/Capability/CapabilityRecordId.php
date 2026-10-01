<?php

namespace App\Services\Agriculture\Research\Capability;

/**
 * Stable Cap cell id — distinct from adr_id / canonical_identity_id / Stage-3 sourceKey.
 */
final readonly class CapabilityRecordId
{
    private function __construct(
        public string $value,
    ) {}

    public static function fromString(string $value): self
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw new CapabilityInvariantViolation('capability_record_id must be a non-empty string.');
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
