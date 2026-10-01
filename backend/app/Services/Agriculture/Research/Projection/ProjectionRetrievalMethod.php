<?php

namespace App\Services\Agriculture\Research\Projection;

/**
 * Intended retrieval_method metadata — does not execute retrieval.
 */
final readonly class ProjectionRetrievalMethod
{
    public const UNKNOWN = 'UNKNOWN';

    private function __construct(
        public string $value,
    ) {}

    public static function of(string $value): self
    {
        $trimmed = trim($value);

        return new self($trimmed === '' ? self::UNKNOWN : $trimmed);
    }

    public static function unknown(): self
    {
        return new self(self::UNKNOWN);
    }

    public function isUnknown(): bool
    {
        return strtoupper($this->value) === self::UNKNOWN;
    }
}
