<?php

namespace App\Services\Agriculture\Research\Search;

/**
 * Provider-independent evidence modality. Distinct from sourceKey.
 */
final class ScientificEvidenceModality
{
    public const DIRECT_STATISTICAL = 'DIRECT_STATISTICAL_EVIDENCE';

    public const SCHOLARLY = 'SCHOLARLY_EVIDENCE';

    public const OFFICIAL_DOCUMENT = 'OFFICIAL_DOCUMENT_EVIDENCE';

    public const WEB = 'WEB_EVIDENCE';

    public const OTHER = 'OTHER';

    public static function fromResult(ScientificSearchResult $result): string
    {
        $meta = is_array($result->relevanceMetadata) ? $result->relevanceMetadata : [];
        $raw = is_array($result->rawMetadata) ? $result->rawMetadata : [];
        $type = strtoupper(trim((string) ($meta['evidence_type'] ?? $raw['evidence_type'] ?? '')));
        $family = strtolower(trim((string) ($meta['evidence_family'] ?? $raw['evidence_family'] ?? '')));

        if ($type === self::DIRECT_STATISTICAL
            || ($meta['not_literature'] ?? false) === true
            || $family === 'official_statistics') {
            return self::DIRECT_STATISTICAL;
        }

        if ($family === 'official' || $family === 'official_document') {
            return self::OFFICIAL_DOCUMENT;
        }

        if ($family === 'web') {
            return self::WEB;
        }

        if ($result->doi !== null && trim($result->doi) !== '') {
            return self::SCHOLARLY;
        }
        if ($result->journal !== null && trim($result->journal) !== '') {
            return self::SCHOLARLY;
        }
        if ($result->authors !== []) {
            return self::SCHOLARLY;
        }

        return self::OTHER;
    }

    public static function isDirectStatistical(ScientificSearchResult $result): bool
    {
        return self::fromResult($result) === self::DIRECT_STATISTICAL;
    }
}
