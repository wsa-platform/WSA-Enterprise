<?php

namespace App\Services\Agriculture\Research\Validation;

use App\Services\Agriculture\Research\Search\ScientificSearchResult;

/**
 * Stage 4 metadata validation — records deficiencies without fabrication.
 */
class ScientificMetadataValidator
{
    /**
     * @return array{status: string, failures: list<string>, fields: array<string, bool>}
     */
    public function validate(ScientificSearchResult $result): array
    {
        $failures = [];
        $fields = [
            'title' => trim($result->title) !== '',
            'publication_year' => $result->publicationYear !== null,
            'authors' => $result->authors !== [],
            'journal' => $result->journal !== null && trim((string) $result->journal) !== '',
            'abstract' => $result->abstract !== null && trim((string) $result->abstract) !== '',
            'doi' => $result->doi !== null && trim((string) $result->doi) !== '',
            'canonical_url' => $result->canonicalUrl !== null && trim((string) $result->canonicalUrl) !== '',
        ];

        if (! $fields['title']) {
            $failures[] = 'missing_title';
        }

        if (! $fields['abstract'] && ! $fields['journal']) {
            $failures[] = 'incomplete_metadata';
        }

        if ($result->canonicalUrl !== null && trim((string) $result->canonicalUrl) !== ''
            && ! filter_var($result->canonicalUrl, FILTER_VALIDATE_URL)) {
            $failures[] = 'invalid_url';
            $fields['canonical_url'] = false;
        }

        if ($result->doi !== null && trim((string) $result->doi) !== ''
            && ! $this->isPlausibleDoi((string) $result->doi)) {
            $failures[] = 'unverifiable_doi';
            $fields['doi'] = false;
        }

        if (! $fields['authors']) {
            $failures[] = 'missing_author_metadata';
        }

        $status = $fields['title']
            ? EvidenceValidationStatus::METADATA_VALID
            : EvidenceValidationStatus::REJECTED;

        return [
            'status' => $status,
            'failures' => $failures,
            'fields' => $fields,
        ];
    }

    private function isPlausibleDoi(string $doi): bool
    {
        $doi = strtolower(trim($doi));

        return (bool) preg_match('/^10\.\d{4,9}\/\S+$/', $doi);
    }
}
