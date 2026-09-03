<?php

namespace App\Services\Agriculture\Diagnosis\KnowledgeBase;

/**
 * Validation outcome for a knowledge record payload.
 */
final class DiagnosisKnowledgeValidationResult
{
    /**
     * @param  list<string>  $errors
     * @param  list<string>  $warnings
     */
    public function __construct(
        public readonly bool $valid,
        public readonly array $errors = [],
        public readonly array $warnings = [],
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'valid' => $this->valid,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
        ];
    }
}
