<?php

namespace App\Services\Agriculture\Research\Search;

/**
 * Knowledge-type vocabulary for Stage-3 relevance policies.
 *
 * Does not decide scientific truth (Stage 4) or claim relation.
 * Maps questions and source records to knowledge kinds so topical gating
 * does not reject correct non-scholarly records or promote wrong claim types.
 */
final class ScientificKnowledgeType
{
    public const SCHOLARLY_EVIDENCE = 'scholarly_evidence';

    public const TAXONOMIC_IDENTITY = 'taxonomic_identity';

    public const BIODIVERSITY_OCCURRENCE = 'biodiversity_occurrence';

    public const BIBLIOGRAPHIC_HERITAGE = 'bibliographic_heritage';

    public const TRADITIONAL_USE = 'traditional_use';

    public const CHEMICAL_ASSOCIATION = 'chemical_association';

    public const AGRICULTURAL_PRACTICE = 'agricultural_practice';

    public const STATISTICAL_OBSERVATION = 'statistical_observation';

    public const EXPERIMENTAL_EVIDENCE = 'experimental_evidence';

    public const REVIEW_EVIDENCE = 'review_evidence';

    public const UNKNOWN = 'unknown';

    /** @var list<string> */
    public const ALL = [
        self::SCHOLARLY_EVIDENCE,
        self::TAXONOMIC_IDENTITY,
        self::BIODIVERSITY_OCCURRENCE,
        self::BIBLIOGRAPHIC_HERITAGE,
        self::TRADITIONAL_USE,
        self::CHEMICAL_ASSOCIATION,
        self::AGRICULTURAL_PRACTICE,
        self::STATISTICAL_OBSERVATION,
        self::EXPERIMENTAL_EVIDENCE,
        self::REVIEW_EVIDENCE,
        self::UNKNOWN,
    ];

    /**
     * Default record knowledge type by Stage-3 source_key.
     * Adapters may override via relevance_metadata.knowledge_type.
     */
    public static function forSourceKey(string $sourceKey): string
    {
        return match ($sourceKey) {
            'gbif' => self::TAXONOMIC_IDENTITY,
            'bhl' => self::BIBLIOGRAPHIC_HERITAGE,
            'dr_duke' => self::CHEMICAL_ASSOCIATION,
            'agris', 'organic_eprints', 'openalex', 'crossref', 'semantic_scholar' => self::SCHOLARLY_EVIDENCE,
            'fao_stat' => self::STATISTICAL_OBSERVATION,
            'consensus' => self::REVIEW_EVIDENCE,
            default => self::UNKNOWN,
        };
    }
}
