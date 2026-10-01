<?php

namespace App\Services\Agriculture\Research\Correlation;

/**
 * Opaque question_identity reference — externally supplied; B7 does not mint.
 */
final readonly class QuestionIdentityReference
{
    private function __construct(
        public string $value,
    ) {}

    public static function fromString(string $value): self
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw new CorrelationInvariantViolation('question_identity must be a non-empty opaque reference.');
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
