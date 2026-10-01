<?php

namespace App\Services\Agriculture\Research\Correlation;

/**
 * aggregator_record_identifier — EXTERNAL record identity; never ADR/canonical/Stage-3 sourceKey.
 */
final readonly class AggregatorRecordReference
{
    private function __construct(
        public string $value,
    ) {}

    public static function fromString(string $value): self
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw new CorrelationInvariantViolation(
                'aggregator_record_identifier must be a non-empty string when present.'
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
