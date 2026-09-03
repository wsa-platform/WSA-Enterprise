<?php

namespace App\Services\Agriculture\Diagnosis\KnowledgeBase;

/**
 * High-level management reference — never includes dosages or pesticide rates.
 */
final class DiagnosisManagementReference
{
    /**
     * @param  list<string>  $safetyNotes
     */
    public function __construct(
        public readonly string $summary,
        public readonly array $safetyNotes = [],
        public readonly bool $requiresLocalAdvisor = true,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'summary' => $this->summary,
            'safety_notes' => $this->safetyNotes,
            'requires_local_advisor' => $this->requiresLocalAdvisor,
        ];
    }

    /** @param  array<string, mixed>  $data */
    public static function fromArray(array $data): self
    {
        $notes = [];
        if (isset($data['safety_notes']) && is_array($data['safety_notes'])) {
            foreach ($data['safety_notes'] as $note) {
                if (is_string($note) && trim($note) !== '') {
                    $notes[] = trim($note);
                }
            }
        }

        return new self(
            summary: (string) ($data['summary'] ?? ''),
            safetyNotes: $notes,
            requiresLocalAdvisor: (bool) ($data['requires_local_advisor'] ?? true),
        );
    }
}
