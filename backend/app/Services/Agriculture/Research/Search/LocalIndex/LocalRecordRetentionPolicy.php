<?php

namespace App\Services\Agriculture\Research\Search\LocalIndex;

use App\Services\Agriculture\Research\Search\Contract\ScientificSourceLicensingDeclaration;
use App\Services\Agriculture\Research\Search\Contract\ScientificSourceUsage;
use App\Services\Agriculture\Research\Search\Provenance\ScientificSourceRecordProvenance;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;

/**
 * Decides what of a harvested record may be stored and indexed (ADR-023 §8.40).
 *
 * Unknown is not permission, and CONDITIONAL is treated as not permitted because no
 * condition can be checked here. Nothing is harvested unless retrieval is permitted and
 * nothing is stored unless metadata storage is permitted. Abstracts and the provider's raw
 * payload are kept only when full-text storage is permitted; display is gated separately by
 * excerpt_display at presentation time.
 */
final class LocalRecordRetentionPolicy
{
    public const REFUSED_RETRIEVAL = 'licence_retrieval_not_permitted';

    public const REFUSED_STORAGE = 'licence_metadata_storage_not_permitted';

    public const REFUSED_NO_IDENTIFIER = 'no_provider_identifier';

    /** The reason a source may not be ingested at all, or null when it may. */
    public function sourceRefusal(ScientificSourceLicensingDeclaration $licensing): ?string
    {
        return match (true) {
            ! $licensing->permits(ScientificSourceUsage::RETRIEVAL) => self::REFUSED_RETRIEVAL,
            ! $licensing->permits(ScientificSourceUsage::METADATA_STORAGE) => self::REFUSED_STORAGE,
            default => null,
        };
    }

    /**
     * @return array{record: ?ScientificSearchResult, refusal: ?string}
     */
    public function retain(ScientificSearchResult $result): array
    {
        $provenance = $result->primaryProvenance();
        if ($provenance === null || $provenance->originalIdentifier === null) {
            return ['record' => null, 'refusal' => self::REFUSED_NO_IDENTIFIER];
        }
        $licensing = $provenance->effectiveLicensing();
        $refusal = $this->sourceRefusal($licensing);
        if ($refusal !== null) {
            return ['record' => null, 'refusal' => $refusal];
        }
        if ($licensing->permits(ScientificSourceUsage::FULL_TEXT_STORAGE)) {
            return ['record' => $result, 'refusal' => null];
        }

        $transformations = $provenance->transformations;
        if ($result->abstract !== null) {
            $transformations[] = 'abstract_not_retained_licence';
        }
        if ($result->rawMetadata !== null) {
            $transformations[] = 'raw_metadata_not_retained_licence';
        }
        $records = [];
        foreach ($result->provenance as $record) {
            $records[] = $record === $provenance ? new ScientificSourceRecordProvenance(
                sourceKey: $record->sourceKey,
                originalIdentifier: $record->originalIdentifier,
                originalIdentifierField: $record->originalIdentifierField,
                recordUrl: $record->recordUrl,
                publishedAt: $record->publishedAt,
                identifierBasis: $record->identifierBasis,
                canonicalUrlBasis: $record->canonicalUrlBasis,
                transformations: array_values(array_unique($transformations)),
                retrievedAt: $record->retrievedAt,
                adapterClass: $record->adapterClass,
                contractVersion: $record->contractVersion,
                accessFamily: $record->accessFamily,
                licensing: $record->licensing,
            ) : $record;
        }

        return ['record' => new ScientificSearchResult(
            sourceKey: $result->sourceKey,
            sourceIdentifier: $result->sourceIdentifier,
            title: $result->title,
            authors: $result->authors,
            publicationYear: $result->publicationYear,
            doi: $result->doi,
            canonicalUrl: $result->canonicalUrl,
            abstract: null,
            journal: $result->journal,
            foundBySources: $result->foundBySources,
            relevanceMetadata: $result->relevanceMetadata,
            rawMetadata: null,
            relevanceScore: $result->relevanceScore,
            provenance: $records,
        ), 'refusal' => null];
    }
}
