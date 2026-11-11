<?php

namespace App\Services\Agriculture\Research\Search\LocalIndex;

use App\Services\Agriculture\Research\Search\Contract\ScientificSourceContractViolation;
use App\Services\Agriculture\Research\Search\Contract\ScientificSourceLicensingDeclaration;
use App\Services\Agriculture\Research\Search\Contract\ScientificSourceUsageStatus;
use App\Services\Agriculture\Research\Search\Provenance\ScientificSourceRecordProvenance;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;

/**
 * Lossless, strict storage encoding of retained records, their provenance and checkpoints.
 *
 * Encoding stores exactly what the retention policy kept. Decoding rebuilds the domain objects
 * through their own constructors, so a stored licensing declaration is re-validated and an
 * unknown or malformed value is reported as a corrupt record, never repaired or defaulted.
 */
final class ScientificRecordCodec
{
    public const VERSION = 1;

    /** @return array<string, mixed> */
    public static function encodeResult(ScientificSearchResult $result): array
    {
        return [
            'codec_version' => self::VERSION,
            'source_key' => $result->sourceKey,
            'source_identifier' => $result->sourceIdentifier,
            'title' => $result->title,
            'authors' => $result->authors,
            'publication_year' => $result->publicationYear,
            'doi' => $result->doi,
            'canonical_url' => $result->canonicalUrl,
            'abstract' => $result->abstract,
            'journal' => $result->journal,
            'found_by_sources' => $result->foundBySources,
            'relevance_metadata' => $result->relevanceMetadata,
            'raw_metadata' => $result->rawMetadata,
            'relevance_score' => $result->relevanceScore,
            'provenance' => array_map(self::encodeProvenance(...), $result->provenance),
        ];
    }

    /** @param  array<string, mixed>  $data */
    public static function decodeResult(array $data, string $sourceKey): ScientificSearchResult
    {
        if (($data['codec_version'] ?? null) !== self::VERSION) {
            throw LocalStoreFailure::corrupt($sourceKey, 'unknown record codec version');
        }
        $score = $data['relevance_score'] ?? null;

        return new ScientificSearchResult(
            sourceKey: self::string($data, 'source_key', $sourceKey),
            sourceIdentifier: self::nullableString($data, 'source_identifier', $sourceKey),
            title: is_string($data['title'] ?? null) ? $data['title'] : throw LocalStoreFailure::corrupt($sourceKey, 'title'),
            authors: self::strings($data, 'authors', $sourceKey),
            publicationYear: self::nullableInt($data, 'publication_year', $sourceKey),
            doi: self::nullableString($data, 'doi', $sourceKey),
            canonicalUrl: self::nullableString($data, 'canonical_url', $sourceKey),
            abstract: self::nullableString($data, 'abstract', $sourceKey),
            journal: self::nullableString($data, 'journal', $sourceKey),
            foundBySources: self::strings($data, 'found_by_sources', $sourceKey),
            relevanceMetadata: self::nullableArray($data, 'relevance_metadata', $sourceKey),
            rawMetadata: self::nullableArray($data, 'raw_metadata', $sourceKey),
            relevanceScore: is_int($score) || is_float($score) ? (float) $score : ($score === null ? null : throw LocalStoreFailure::corrupt($sourceKey, 'relevance_score')),
            provenance: array_map(
                static fn (mixed $p): ScientificSourceRecordProvenance => is_array($p) ? self::decodeProvenance($p, $sourceKey) : throw LocalStoreFailure::corrupt($sourceKey, 'provenance'),
                array_values(self::nullableArray($data, 'provenance', $sourceKey) ?? []),
            ),
        );
    }

    /** @return array<string, mixed> */
    public static function encodeProvenance(ScientificSourceRecordProvenance $provenance): array
    {
        return [
            'source_key' => $provenance->sourceKey,
            'original_identifier' => $provenance->originalIdentifier,
            'original_identifier_field' => $provenance->originalIdentifierField,
            'record_url' => $provenance->recordUrl,
            'published_at' => $provenance->publishedAt,
            'identifier_basis' => $provenance->identifierBasis,
            'canonical_url_basis' => $provenance->canonicalUrlBasis,
            'transformations' => $provenance->transformations,
            'retrieved_at' => $provenance->retrievedAt,
            'adapter_class' => $provenance->adapterClass,
            'contract_version' => $provenance->contractVersion,
            'access_family' => $provenance->accessFamily,
            'licensing' => $provenance->licensing === null ? null : self::encodeLicensing($provenance->licensing),
        ];
    }

    /** @param  array<string, mixed>  $data */
    public static function decodeProvenance(array $data, string $sourceKey): ScientificSourceRecordProvenance
    {
        $licensing = self::nullableArray($data, 'licensing', $sourceKey);

        return new ScientificSourceRecordProvenance(
            sourceKey: self::string($data, 'source_key', $sourceKey),
            originalIdentifier: self::nullableString($data, 'original_identifier', $sourceKey),
            originalIdentifierField: self::nullableString($data, 'original_identifier_field', $sourceKey),
            recordUrl: self::nullableString($data, 'record_url', $sourceKey),
            publishedAt: self::nullableString($data, 'published_at', $sourceKey),
            identifierBasis: self::nullableString($data, 'identifier_basis', $sourceKey),
            canonicalUrlBasis: self::nullableString($data, 'canonical_url_basis', $sourceKey),
            transformations: self::strings($data, 'transformations', $sourceKey),
            retrievedAt: self::nullableString($data, 'retrieved_at', $sourceKey),
            adapterClass: self::nullableString($data, 'adapter_class', $sourceKey),
            contractVersion: self::nullableString($data, 'contract_version', $sourceKey),
            accessFamily: self::nullableString($data, 'access_family', $sourceKey),
            licensing: $licensing === null ? null : self::decodeLicensing($licensing, $sourceKey),
        );
    }

    /** @return array<string, mixed> */
    public static function encodeLicensing(ScientificSourceLicensingDeclaration $licensing): array
    {
        return $licensing->toArray();
    }

    /** @param  array<string, mixed>  $data */
    public static function decodeLicensing(array $data, string $sourceKey): ScientificSourceLicensingDeclaration
    {
        $usages = [];
        foreach (self::nullableArray($data, 'usages', $sourceKey) ?? [] as $usage => $status) {
            $decoded = is_string($status) ? ScientificSourceUsageStatus::tryFrom($status) : null;
            if (! is_string($usage) || $decoded === null) {
                throw LocalStoreFailure::corrupt($sourceKey, 'licensing usage');
            }
            $usages[$usage] = $decoded;
        }
        try {
            return new ScientificSourceLicensingDeclaration(
                $usages,
                self::nullableString($data, 'licence_identifier', $sourceKey),
                self::nullableString($data, 'terms_reference', $sourceKey),
                self::nullableString($data, 'evidence_reference', $sourceKey),
            );
        } catch (ScientificSourceContractViolation) {
            throw LocalStoreFailure::corrupt($sourceKey, 'licensing declaration');
        }
    }

    /** @return array<string, mixed> includes the resumption token, which HarvestCheckpoint::toArray() omits from reports */
    public static function encodeCheckpoint(HarvestCheckpoint $checkpoint): array
    {
        return [
            'source_key' => $checkpoint->sourceKey,
            'state' => $checkpoint->state,
            'resumption_token' => $checkpoint->resumptionToken,
            'window_from' => $checkpoint->windowFrom,
            'harvest_started_at' => $checkpoint->harvestStartedAt,
            'high_water_mark' => $checkpoint->highWaterMark,
            'pages_committed' => $checkpoint->pagesCommitted,
            'updated_at' => $checkpoint->updatedAt,
        ];
    }

    /** @param  array<string, mixed>  $data */
    public static function decodeCheckpoint(array $data, string $sourceKey): HarvestCheckpoint
    {
        $pages = $data['pages_committed'] ?? null;
        try {
            $checkpoint = new HarvestCheckpoint(
                sourceKey: self::string($data, 'source_key', $sourceKey),
                state: self::string($data, 'state', $sourceKey),
                resumptionToken: self::nullableString($data, 'resumption_token', $sourceKey),
                windowFrom: self::nullableString($data, 'window_from', $sourceKey),
                harvestStartedAt: self::nullableString($data, 'harvest_started_at', $sourceKey),
                highWaterMark: self::nullableString($data, 'high_water_mark', $sourceKey),
                pagesCommitted: is_int($pages) ? $pages : throw LocalStoreFailure::corrupt($sourceKey, 'checkpoint pages_committed'),
                updatedAt: self::string($data, 'updated_at', $sourceKey),
            );
        } catch (LocalIndexInvariantViolation) {
            throw LocalStoreFailure::corrupt($sourceKey, 'checkpoint');
        }
        if ($checkpoint->sourceKey !== $sourceKey) {
            throw LocalStoreFailure::corrupt($sourceKey, 'checkpoint source');
        }

        return $checkpoint;
    }

    /** @param  array<string, mixed>  $data */
    private static function string(array $data, string $key, string $sourceKey): string
    {
        $value = $data[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : throw LocalStoreFailure::corrupt($sourceKey, $key);
    }

    /** @param  array<string, mixed>  $data */
    private static function nullableString(array $data, string $key, string $sourceKey): ?string
    {
        $value = $data[$key] ?? null;

        return $value === null || is_string($value) ? $value : throw LocalStoreFailure::corrupt($sourceKey, $key);
    }

    /** @param  array<string, mixed>  $data */
    private static function nullableInt(array $data, string $key, string $sourceKey): ?int
    {
        $value = $data[$key] ?? null;

        return $value === null || is_int($value) ? $value : throw LocalStoreFailure::corrupt($sourceKey, $key);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<mixed>|null
     */
    private static function nullableArray(array $data, string $key, string $sourceKey): ?array
    {
        $value = $data[$key] ?? null;

        return $value === null || is_array($value) ? $value : throw LocalStoreFailure::corrupt($sourceKey, $key);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    private static function strings(array $data, string $key, string $sourceKey): array
    {
        $value = $data[$key] ?? [];
        if (! is_array($value) || ! array_is_list($value) || array_filter($value, 'is_string') !== $value) {
            throw LocalStoreFailure::corrupt($sourceKey, $key);
        }

        return $value;
    }
}
