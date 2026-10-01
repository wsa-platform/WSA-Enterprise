<?php

namespace App\Services\Agriculture\Research\Correlation;

/**
 * Retrieval event metadata — retrieval_timestamp is NOT a correlation identity.
 */
final readonly class RetrievalEventReference
{
    private function __construct(
        public string $retrievedAt,
    ) {}

    public static function at(string $retrievedAt): self
    {
        $trimmed = trim($retrievedAt);
        if ($trimmed === '') {
            throw new CorrelationInvariantViolation(
                'retrieval_timestamp must be non-empty when a retrieval event is recorded.'
            );
        }

        return new self($trimmed);
    }

    public function __toString(): string
    {
        return $this->retrievedAt;
    }
}
