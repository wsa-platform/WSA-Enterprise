<?php

namespace App\Services\Agriculture\Research\Search\Provenance;

use App\Services\Agriculture\Research\Search\Contract\ScientificSourceLicensingDeclaration;

/**
 * Provenance of one source record behind a normalized result (ADR-023 §8.38).
 *
 * Fields hold only what the provider returned or what this platform did to it; null means
 * unknown. An identifier or URL that the platform derived names its basis so it never passes
 * as the provider's own. Licensing is unresolved until the adapter declares it with evidence.
 */
final readonly class ScientificSourceRecordProvenance
{
    public const IDENTIFIER_PROVIDER = 'provider_identifier';

    public const IDENTIFIER_DOI = 'doi';

    public const IDENTIFIER_URL = 'url';

    public const IDENTIFIER_DERIVED_HASH = 'derived_title_year_hash';

    public const IDENTIFIER_DERIVED_CODES = 'derived_dimension_codes';

    public const URL_PROVIDER = 'provider_url';

    public const URL_PROVIDER_RECORD = 'provider_record_identifier';

    public const URL_DOI_RESOLVER = 'doi_resolver';

    public const URL_OPEN_ACCESS_PDF = 'provider_open_access_pdf';

    public const URL_CONSTRUCTED = 'constructed_from_codes';

    /**
     * @param  list<string>  $transformations
     */
    public function __construct(
        public string $sourceKey,
        public ?string $originalIdentifier = null,
        public ?string $originalIdentifierField = null,
        public ?string $recordUrl = null,
        public ?string $publishedAt = null,
        public ?string $identifierBasis = null,
        public ?string $canonicalUrlBasis = null,
        public array $transformations = [],
        public ?string $retrievedAt = null,
        public ?string $adapterClass = null,
        public ?string $contractVersion = null,
        public ?string $accessFamily = null,
        public ?ScientificSourceLicensingDeclaration $licensing = null,
    ) {}

    /** Fills only what is still unknown; a record already stamped keeps its own retrieval. */
    public function withRetrievalContext(
        string $retrievedAt,
        string $adapterClass,
        ?string $contractVersion,
        ?string $accessFamily,
        ScientificSourceLicensingDeclaration $licensing,
    ): self {
        return new self(
            sourceKey: $this->sourceKey,
            originalIdentifier: $this->originalIdentifier,
            originalIdentifierField: $this->originalIdentifierField,
            recordUrl: $this->recordUrl,
            publishedAt: $this->publishedAt,
            identifierBasis: $this->identifierBasis,
            canonicalUrlBasis: $this->canonicalUrlBasis,
            transformations: $this->transformations,
            retrievedAt: $this->retrievedAt ?? $retrievedAt,
            adapterClass: $this->adapterClass ?? $adapterClass,
            contractVersion: $this->contractVersion ?? $contractVersion,
            accessFamily: $this->accessFamily ?? $accessFamily,
            licensing: $this->licensing ?? $licensing,
        );
    }

    public function effectiveLicensing(): ScientificSourceLicensingDeclaration
    {
        return $this->licensing ?? ScientificSourceLicensingDeclaration::unresolved();
    }

    public function identityKey(): string
    {
        return $this->sourceKey.'|'.($this->originalIdentifier ?? '').'|'.($this->recordUrl ?? '');
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'source_key' => $this->sourceKey,
            'original_identifier' => $this->originalIdentifier,
            'original_identifier_field' => $this->originalIdentifierField,
            'record_url' => $this->recordUrl,
            'published_at' => $this->publishedAt,
            'identifier_basis' => $this->identifierBasis,
            'canonical_url_basis' => $this->canonicalUrlBasis,
            'transformations' => $this->transformations,
            'retrieved_at' => $this->retrievedAt,
            'adapter' => $this->adapterClass,
            'contract_version' => $this->contractVersion,
            'access_family' => $this->accessFamily,
            'licensing' => $this->effectiveLicensing()->toArray(),
        ];
    }
}
