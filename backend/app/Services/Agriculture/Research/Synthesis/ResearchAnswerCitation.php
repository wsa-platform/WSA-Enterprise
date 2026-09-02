<?php

namespace App\Services\Agriculture\Research\Synthesis;

/**
 * Citation derived only from validated Stage 4 evidence metadata.
 */
final class ResearchAnswerCitation
{
    /**
     * @param  list<string>  $authors
     */
    public function __construct(
        public readonly string $citationId,
        public readonly string $sourceId,
        public readonly string $evidenceId,
        public readonly string $title,
        public readonly array $authors,
        public readonly ?string $organization,
        public readonly ?string $journal,
        public readonly ?string $doi,
        public readonly ?string $url,
        public readonly ?int $publicationYear,
        public readonly ?string $sourceType,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'citation_id' => $this->citationId,
            'source_id' => $this->sourceId,
            'evidence_id' => $this->evidenceId,
            'title' => $this->title,
            'authors' => $this->authors,
            'organization' => $this->organization,
            'journal' => $this->journal,
            'doi' => $this->doi,
            'url' => $this->url,
            'publication_year' => $this->publicationYear,
            'source_type' => $this->sourceType,
        ];
    }

    /** @return array<string, mixed> */
    public function toScientificReference(): array
    {
        $reference = [
            'source_type' => $this->sourceType ?? 'supporting_verified',
            'organization' => $this->organization ?? '',
            'title' => $this->title,
        ];

        if ($this->url !== null && $this->url !== '') {
            $reference['url'] = $this->url;
        }

        if ($this->doi !== null && $this->doi !== '') {
            $reference['doi'] = $this->doi;
        }

        if ($this->publicationYear !== null) {
            $reference['publication_year'] = $this->publicationYear;
        }

        return $reference;
    }
}
