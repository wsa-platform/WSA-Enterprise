<?php

namespace App\Services\Agriculture\Research\Validation;

use App\Services\Agriculture\Research\Search\ScientificSearchResult;

/**
 * Stage 4 source identity validation — verifies identifiable publication identity.
 */
class ScientificSourceIdentityValidator
{
    /**
     * @return array{status: string, failures: list<string>, identity: array<string, mixed>}
     */
    public function validate(ScientificSearchResult $result): array
    {
        $failures = [];
        $hasDoi = $result->doi !== null && trim((string) $result->doi) !== '';
        $hasUrl = $result->canonicalUrl !== null
            && trim((string) $result->canonicalUrl) !== ''
            && filter_var($result->canonicalUrl, FILTER_VALIDATE_URL);
        $hasIdentifier = $result->sourceIdentifier !== null && trim((string) $result->sourceIdentifier) !== '';

        if ($result->canonicalUrl !== null && trim((string) $result->canonicalUrl) !== ''
            && ! filter_var($result->canonicalUrl, FILTER_VALIDATE_URL)) {
            $failures[] = 'invalid_url';
        }

        if (! $hasDoi && ! $hasUrl && ! $hasIdentifier) {
            $failures[] = 'missing_source_identity';
        }

        if ($hasDoi && ! $this->isPlausibleDoi((string) $result->doi)) {
            $failures[] = 'unverifiable_doi';
        }

        $identity = [
            'doi' => $hasDoi ? $result->doi : null,
            'url' => $hasUrl ? $result->canonicalUrl : null,
            'source_identifier' => $hasIdentifier ? $result->sourceIdentifier : null,
            'source_key' => $result->sourceKey,
        ];

        $status = $failures === [] && ($hasDoi || $hasUrl || $hasIdentifier)
            ? EvidenceValidationStatus::SOURCE_IDENTITY_VALID
            : ($failures !== [] && ! $hasDoi && ! $hasUrl && ! $hasIdentifier
                ? EvidenceValidationStatus::REJECTED
                : EvidenceValidationStatus::DISCOVERED);

        return [
            'status' => $status,
            'failures' => $failures,
            'identity' => $identity,
        ];
    }

    private function isPlausibleDoi(string $doi): bool
    {
        $doi = strtolower(trim($doi));

        return (bool) preg_match('/^10\.\d{4,9}\/\S+$/', $doi);
    }
}
