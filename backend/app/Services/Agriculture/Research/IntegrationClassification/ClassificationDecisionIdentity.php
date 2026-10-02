<?php

namespace App\Services\Agriculture\Research\IntegrationClassification;

use App\Services\Agriculture\Research\IntegrationClassification\Persistence\IntegrationClassificationInvariantViolation;

/**
 * Opaque IC-owned classification decision identity.
 */
final readonly class ClassificationDecisionIdentity
{
    private function __construct(
        public string $value,
    ) {}

    public static function fromString(string $value): self
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw new IntegrationClassificationInvariantViolation(
                'classification_decision_identity must be a non-empty string.'
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
