<?php

namespace App\Services\Agriculture\Research\Correlation;

/**
 * Optional opaque variant_id — present only when a query variant exists.
 * Not a substitute for question_identity, csq_identity, or path_id.
 */
final readonly class VariantIdentityReference
{
    private function __construct(
        public string $value,
    ) {}

    public static function fromString(string $value): self
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw new CorrelationInvariantViolation('variant_id must be a non-empty string when present.');
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
