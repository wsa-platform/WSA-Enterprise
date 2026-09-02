<?php

namespace App\Services\Agriculture\Research\Validation;

use App\Services\Agriculture\Research\Search\ScientificSearchResult;
use App\Services\Agriculture\ScientificSourceRegistry;
use App\Services\Agriculture\ScientificSourceValidator;

/**
 * Stage 4 source quality/trust validation — extends existing ScientificSourceValidator.
 */
class ScientificSourceQualityValidator
{
    public function __construct(
        private ScientificSourceValidator $sourceValidator,
        private ScientificSearchResultSourceMapper $sourceMapper,
    ) {}

    /**
     * @return array{status: string, failures: list<string>, source: array<string, mixed>, confidence_level: string, trusted: bool}
     */
    public function validate(ScientificSearchResult $result): array
    {
        $source = $this->sourceMapper->map($result);
        $failures = [];
        $sourceType = strtolower(trim((string) ($source['source_type'] ?? '')));

        if ($sourceType === '' || ! ScientificSourceRegistry::isApprovedSourceType($sourceType)) {
            $failures[] = 'unsupported_source_type';
        }

        $confidenceLevel = ScientificSourceRegistry::confidenceLevelFor($sourceType);
        if ($confidenceLevel === ScientificSourceRegistry::LEVEL_UNVERIFIED) {
            $failures[] = 'source_trust_failure';
        }

        if (trim((string) ($source['organization'] ?? '')) === '') {
            $failures[] = 'missing_source_identity';
        }

        if (trim((string) ($source['title'] ?? '')) === '') {
            $failures[] = 'missing_title';
        }

        $trusted = $this->sourceValidator->isVerifiedSource($source);

        if ($this->isUntrustedInstitution($result)) {
            $failures[] = 'source_trust_failure';
            $trusted = false;
        }

        if (! $trusted) {
            $failures[] = 'source_validation_failure';
        }

        $status = $trusted
            ? EvidenceValidationStatus::SCIENTIFICALLY_TRUSTWORTHY
            : EvidenceValidationStatus::REJECTED;

        return [
            'status' => $status,
            'failures' => $failures,
            'source' => $source,
            'confidence_level' => $confidenceLevel,
            'trusted' => $trusted,
        ];
    }

    private function isUntrustedInstitution(ScientificSearchResult $result): bool
    {
        $raw = $result->rawMetadata ?? [];
        if ($result->sourceKey !== 'openalex' || ! is_array($raw['openalex'] ?? null)) {
            return false;
        }

        $authorships = is_array($raw['openalex']['authorships'] ?? null) ? $raw['openalex']['authorships'] : [];
        foreach ($authorships as $authorship) {
            if (! is_array($authorship)) {
                continue;
            }
            $institutions = is_array($authorship['institutions'] ?? null) ? $authorship['institutions'] : [];
            foreach ($institutions as $institution) {
                if (! is_array($institution)) {
                    continue;
                }
                if (($institution['type'] ?? '') === 'company') {
                    return true;
                }
            }
        }

        return false;
    }
}
