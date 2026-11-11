<?php

namespace App\Services\Agriculture\Research\Search\Family;

use App\Services\Agriculture\Research\Search\Provenance\ScientificSourceRecordProvenance;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;

/**
 * Builds the normalized result and its provenance record for every source family (ADR-023 §8.39).
 *
 * Inputs are the provider's values as returned; nothing missing is derived or invented, and
 * every change made to a value is named in the record's transformations.
 */
final class ScientificSourceFamilyRecord
{
    private const DOI_PATTERN = '#^(?:https?://(?:dx\.)?doi\.org/|doi:\s*)?(10\.\d{4,9}/\S+)$#i';

    /**
     * @param  list<string>  $authors
     * @param  array<string, mixed>  $raw  the provider record as received
     */
    public static function toResult(
        ScientificSourceFamilyProfile $profile,
        ?string $rawTitle,
        ?string $originalIdentifier,
        ?string $originalIdentifierField,
        ?string $rawDoi,
        ?string $rawUrl,
        ?string $rawAbstract,
        ?string $publishedAt,
        ?int $year,
        array $authors,
        ?string $journal,
        array $raw,
        ?string $retrievedAt = null,
        ?string $adapterClass = null,
        ?string $contractVersion = null,
    ): ?ScientificSearchResult {
        $title = self::cleanText($rawTitle);
        if ($title === null) {
            return null;
        }

        $rawDoi = $rawDoi !== null ? trim($rawDoi) : null;
        $bareDoi = self::bareDoi($rawDoi);
        $doi = $bareDoi !== null ? strtolower($bareDoi) : null;
        $url = self::httpUrl($rawUrl);
        $abstract = self::cleanText($rawAbstract);
        $originalIdentifier = self::trimmedOrNull($originalIdentifier);
        $publishedAt = self::trimmedOrNull($publishedAt);
        $canonicalUrl = $url ?? ($doi !== null ? 'https://doi.org/'.$doi : null);

        $provenance = new ScientificSourceRecordProvenance(
            sourceKey: $profile->sourceKey(),
            originalIdentifier: $originalIdentifier,
            originalIdentifierField: $originalIdentifier !== null ? $originalIdentifierField : null,
            recordUrl: $url,
            publishedAt: $publishedAt,
            identifierBasis: match (true) {
                $doi !== null => ScientificSourceRecordProvenance::IDENTIFIER_DOI,
                $originalIdentifier !== null => ScientificSourceRecordProvenance::IDENTIFIER_PROVIDER,
                $url !== null => ScientificSourceRecordProvenance::IDENTIFIER_URL,
                default => null,
            },
            canonicalUrlBasis: match (true) {
                $url !== null => ScientificSourceRecordProvenance::URL_PROVIDER,
                $doi !== null => ScientificSourceRecordProvenance::URL_DOI_RESOLVER,
                default => null,
            },
            transformations: array_keys(array_filter([
                'title_tags_and_whitespace_stripped' => $title !== $rawTitle,
                'doi_resolver_prefix_removed' => $bareDoi !== null && $bareDoi !== $rawDoi,
                'doi_lowercased' => $bareDoi !== null && $doi !== $bareDoi,
                'abstract_tags_and_whitespace_stripped' => $abstract !== null && $abstract !== $rawAbstract,
            ])),
            retrievedAt: $retrievedAt,
            adapterClass: $adapterClass,
            contractVersion: $contractVersion,
            accessFamily: $profile->accessFamily()->value,
            licensing: $profile->licensing(),
        );

        $authors = array_values(array_unique(array_filter(
            array_map(static fn (string $author): ?string => self::cleanText($author), $authors),
            static fn (?string $author): bool => $author !== null,
        )));

        return new ScientificSearchResult(
            sourceKey: $profile->sourceKey(),
            sourceIdentifier: $doi ?? $originalIdentifier ?? $url,
            title: $title,
            authors: $authors,
            publicationYear: $year ?? self::yearFrom($publishedAt),
            doi: $doi,
            canonicalUrl: $canonicalUrl,
            abstract: $abstract,
            journal: self::cleanText($journal),
            foundBySources: [$profile->sourceKey()],
            rawMetadata: [$profile->sourceKey() => $raw],
            provenance: [$provenance],
        );
    }

    /** The DOI inside a bare, `doi:` or resolver-URL value, as written; null when there is none. */
    public static function bareDoi(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return preg_match(self::DOI_PATTERN, trim($value), $match) === 1 ? $match[1] : null;
    }

    public static function httpUrl(?string $value): ?string
    {
        $value = $value !== null ? trim($value) : '';

        return preg_match('#^https?://\S+$#i', $value) === 1 ? $value : null;
    }

    /** Tags removed and whitespace collapsed; null when nothing is left. */
    public static function cleanText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $stripped = strip_tags($value);
        $text = trim(preg_replace('/\s+/u', ' ', $stripped) ?? $stripped);

        return $text !== '' ? $text : null;
    }

    /** A string or integer as given; the first element of a list (providers often wrap titles in one). */
    public static function scalar(mixed $value): ?string
    {
        if (is_array($value) && array_is_list($value)) {
            $value = $value[0] ?? null;
        }
        if (! is_string($value) && ! is_int($value)) {
            return null;
        }
        $value = (string) $value;

        return trim($value) !== '' ? $value : null;
    }

    private static function trimmedOrNull(?string $value): ?string
    {
        $value = $value !== null ? trim($value) : '';

        return $value !== '' ? $value : null;
    }

    /** The year a date string starts with, when it starts with four digits. */
    public static function yearFrom(?string $date): ?int
    {
        return $date !== null && preg_match('/^(\d{4})(?:$|[-\/T ])/', trim($date), $match) === 1
            ? (int) $match[1]
            : null;
    }
}
