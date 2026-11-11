<?php

namespace App\Services\Agriculture\Research\Search\Family\OaiPmh;

use App\Services\Agriculture\Research\Search\Contract\ScientificSourceContractViolation;
use App\Services\Agriculture\Research\Search\Contract\ScientificSourceLicensingDeclaration;
use App\Services\Agriculture\Research\Search\Family\ScientificSourceFamilyProfile;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceAccessFamily;

/**
 * Profile of one OAI-PMH 2.0 repository (ADR-023 §8.39, increment R5).
 *
 * Only Dublin Core is harvested: OAI-PMH 2.0 requires every repository to disseminate
 * `oai_dc`, so no other format is assumed. OAI-PMH has no search verb; a repository of this
 * family supports bulk access (harvesting), never query search.
 */
final readonly class OaiPmhSourceProfile implements ScientificSourceFamilyProfile
{
    public const METADATA_PREFIX = 'oai_dc';

    public const GRANULARITY_DAY = 'YYYY-MM-DD';

    public const GRANULARITY_SECOND = 'YYYY-MM-DDThh:mm:ssZ';

    public ScientificSourceLicensingDeclaration $licensing;

    /**
     * @param  string  $datestampGranularity  the granularity the repository's Identify response declares
     */
    public function __construct(
        public string $sourceKey,
        public string $displayName,
        public string $baseUrl,
        public ?string $setSpec = null,
        ?ScientificSourceLicensingDeclaration $licensing = null,
        public ?int $requestsPerMinute = null,
        public string $datestampGranularity = self::GRANULARITY_DAY,
        /**
         * Optional https URL template with `{local_id}` for the OAI identifier's local part
         * (segment after the last colon). Used when Dublin Core does not supply an absolute URL
         * (e.g. Organic Eprints summary pages: https://orgprints.org/{local_id}).
         */
        public ?string $recordUrlTemplate = null,
    ) {
        if (! in_array($datestampGranularity, [self::GRANULARITY_DAY, self::GRANULARITY_SECOND], true)) {
            throw new ScientificSourceContractViolation("Profile '{$sourceKey}' datestamp granularity must be an OAI-PMH 2.0 granularity.");
        }
        $this->licensing = $licensing ?? ScientificSourceLicensingDeclaration::unresolved();

        if (preg_match('/^[a-z][a-z0-9_]*$/', $sourceKey) !== 1) {
            throw new ScientificSourceContractViolation("Profile sourceKey must be lower-case snake_case; got '{$sourceKey}'.");
        }
        if (trim($displayName) === '') {
            throw new ScientificSourceContractViolation("Profile '{$sourceKey}' requires a display name.");
        }
        $parts = parse_url($baseUrl);
        if ($parts === false
            || strtolower($parts['scheme'] ?? '') !== 'https'
            || ($parts['host'] ?? '') === ''
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['query'])
            || isset($parts['fragment'])) {
            throw new ScientificSourceContractViolation(
                "Profile '{$sourceKey}' baseUrl must be an https URL without credentials, query or fragment."
            );
        }
        if ($setSpec !== null && preg_match('/^[A-Za-z0-9\-_.!~*\'():]+$/', $setSpec) !== 1) {
            throw new ScientificSourceContractViolation("Profile '{$sourceKey}' setSpec is not a valid OAI-PMH setSpec.");
        }
        if ($requestsPerMinute !== null && $requestsPerMinute < 1) {
            throw new ScientificSourceContractViolation("Profile '{$sourceKey}' requestsPerMinute must be at least 1.");
        }
        if ($recordUrlTemplate !== null) {
            if (! str_contains($recordUrlTemplate, '{local_id}')) {
                throw new ScientificSourceContractViolation(
                    "Profile '{$sourceKey}' recordUrlTemplate must contain the '{local_id}' placeholder."
                );
            }
            $templateParts = parse_url(str_replace('{local_id}', '0', $recordUrlTemplate));
            if ($templateParts === false
                || strtolower($templateParts['scheme'] ?? '') !== 'https'
                || ($templateParts['host'] ?? '') === ''
                || isset($templateParts['user'])
                || isset($templateParts['pass'])
                || isset($templateParts['query'])
                || isset($templateParts['fragment'])) {
                throw new ScientificSourceContractViolation(
                    "Profile '{$sourceKey}' recordUrlTemplate must expand to an https URL without credentials, query or fragment."
                );
            }
        }
    }

    /**
     * Maps an OAI identifier (`oai:…:local`) to a stable record URL when a template is configured.
     */
    public function recordUrlForOaiIdentifier(string $identifier): ?string
    {
        if ($this->recordUrlTemplate === null) {
            return null;
        }
        $trimmed = trim($identifier);
        if ($trimmed === '' || ! str_contains($trimmed, ':')) {
            return null;
        }
        $localId = substr($trimmed, (int) strrpos($trimmed, ':') + 1);
        if ($localId === '' || preg_match('/^[A-Za-z0-9._~-]+$/', $localId) !== 1) {
            return null;
        }

        return str_replace('{local_id}', $localId, $this->recordUrlTemplate);
    }

    public function sourceKey(): string
    {
        return $this->sourceKey;
    }

    public function displayName(): string
    {
        return $this->displayName;
    }

    public function accessFamily(): ScientificSourceAccessFamily
    {
        return ScientificSourceAccessFamily::OAI_PMH;
    }

    public function licensing(): ScientificSourceLicensingDeclaration
    {
        return $this->licensing;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'source_key' => $this->sourceKey,
            'display_name' => $this->displayName,
            'access_family' => $this->accessFamily()->value,
            'base_url' => $this->baseUrl,
            'metadata_prefix' => self::METADATA_PREFIX,
            'set_spec' => $this->setSpec,
            'datestamp_granularity' => $this->datestampGranularity,
            'requests_per_minute' => $this->requestsPerMinute,
            'record_url_template' => $this->recordUrlTemplate,
            'licensing' => $this->licensing->toArray(),
        ];
    }
}
