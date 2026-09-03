<?php

namespace App\Services\Agriculture\Diagnosis\KnowledgeBase;

/**
 * Provenance for a diagnosis knowledge record.
 * Never invent URLs/DOIs — leave null when unavailable.
 */
final class DiagnosisKnowledgeSource
{
    public function __construct(
        public readonly string $label,
        public readonly string $type,
        public readonly ?string $institution = null,
        public readonly ?string $url = null,
        public readonly ?string $doi = null,
        public readonly ?int $publicationYear = null,
        public readonly bool $claimsScientificEvidence = false,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'label' => $this->label,
            'type' => $this->type,
            'institution' => $this->institution,
            'url' => $this->url,
            'doi' => $this->doi,
            'publication_year' => $this->publicationYear,
            'claims_scientific_evidence' => $this->claimsScientificEvidence,
        ];
    }

    /** @param  array<string, mixed>  $data */
    public static function fromArray(array $data): self
    {
        return new self(
            label: (string) ($data['label'] ?? ''),
            type: (string) ($data['type'] ?? 'unspecified'),
            institution: isset($data['institution']) && is_string($data['institution']) ? $data['institution'] : null,
            url: isset($data['url']) && is_string($data['url']) ? $data['url'] : null,
            doi: isset($data['doi']) && is_string($data['doi']) ? $data['doi'] : null,
            publicationYear: isset($data['publication_year']) && is_numeric($data['publication_year'])
                ? (int) $data['publication_year']
                : null,
            claimsScientificEvidence: (bool) ($data['claims_scientific_evidence'] ?? false),
        );
    }
}
