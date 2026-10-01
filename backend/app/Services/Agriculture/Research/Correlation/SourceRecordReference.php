<?php

namespace App\Services\Agriculture\Research\Correlation;

/**
 * original_source_identifier — provenance/reference only; ≠ adr_id / canonical / path / projection.
 */
final readonly class SourceRecordReference
{
    private function __construct(
        public string $value,
    ) {}

    public static function fromString(string $value): self
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw new CorrelationInvariantViolation(
                'original_source_identifier must be a non-empty string when present.'
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
