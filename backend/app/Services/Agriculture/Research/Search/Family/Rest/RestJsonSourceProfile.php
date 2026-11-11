<?php

namespace App\Services\Agriculture\Research\Search\Family\Rest;

use App\Services\Agriculture\Research\Search\Contract\ScientificSourceContractViolation;
use App\Services\Agriculture\Research\Search\Contract\ScientificSourceLicensingDeclaration;
use App\Services\Agriculture\Research\Search\Family\ScientificSourceFamilyProfile;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceAccessFamily;

/**
 * Profile of one source served by the REST/JSON search family (ADR-023 §8.39, increment R5).
 *
 * Field paths are dot paths inside one result item, as the provider documents them. The query
 * text is sent unchanged in the query parameter (D-04); fixed parameters must not be secrets.
 */
final readonly class RestJsonSourceProfile implements ScientificSourceFamilyProfile
{
    public const FIELDS = ['identifier', 'title', 'abstract', 'doi', 'url', 'published', 'year', 'authors', 'journal'];

    public const REQUIRED_FIELDS = ['identifier', 'title'];

    public ScientificSourceLicensingDeclaration $licensing;

    /**
     * @param  array<string, string|list<string>>  $fieldPaths  keyed by FIELDS; identifier and title are required.
     *                                                          A list is tried in order (first non-empty scalar wins).
     * @param  array<string, string>  $fixedParameters  non-secret parameters sent with every request
     * @param  string  $itemsPath  dot path of the result list in the response; '' when the response is the list
     * @param  string|null  $authorNamePath  dot path of the name inside one author entry; null when entries are strings
     */
    public function __construct(
        public string $sourceKey,
        public string $displayName,
        public string $endpoint,
        public string $queryParameter,
        public array $fieldPaths,
        public string $itemsPath = '',
        public ?string $limitParameter = null,
        public ?int $maxResultsPerRequest = null,
        public array $fixedParameters = [],
        public ?string $authorNamePath = null,
        public ?RestJsonCredential $credential = null,
        ?ScientificSourceLicensingDeclaration $licensing = null,
        public ?int $requestsPerMinute = null,
        /**
         * Optional provider envelope status (e.g. BHL `Status`). When set, HTTP success is
         * insufficient: the envelope must equal responseStatusOkValue (default `ok`).
         */
        public ?string $responseStatusPath = null,
        public string $responseStatusOkValue = 'ok',
        public ?string $responseErrorMessagePath = null,
    ) {
        $this->licensing = $licensing ?? ScientificSourceLicensingDeclaration::unresolved();

        if (preg_match('/^[a-z][a-z0-9_]*$/', $sourceKey) !== 1) {
            throw new ScientificSourceContractViolation("Profile sourceKey must be lower-case snake_case; got '{$sourceKey}'.");
        }
        if (trim($displayName) === '') {
            throw new ScientificSourceContractViolation("Profile '{$sourceKey}' requires a display name.");
        }
        self::assertHttpsEndpoint($sourceKey, $endpoint);
        foreach (['queryParameter' => $queryParameter, 'limitParameter' => $limitParameter] as $field => $name) {
            if ($name !== null && preg_match('/^[A-Za-z0-9_.\[\]-]+$/', $name) !== 1) {
                throw new ScientificSourceContractViolation("Profile '{$sourceKey}' {$field} is not a parameter name.");
            }
        }
        foreach ($fieldPaths as $field => $path) {
            if (! in_array($field, self::FIELDS, true)) {
                throw new ScientificSourceContractViolation("Profile '{$sourceKey}' maps unknown field '{$field}'.");
            }
            $candidates = is_array($path) ? $path : [$path];
            if ($candidates === []) {
                throw new ScientificSourceContractViolation("Profile '{$sourceKey}' field '{$field}' needs a non-empty path.");
            }
            foreach ($candidates as $candidate) {
                if (! is_string($candidate) || trim($candidate) === '') {
                    throw new ScientificSourceContractViolation("Profile '{$sourceKey}' field '{$field}' needs a non-empty path.");
                }
            }
        }
        foreach (self::REQUIRED_FIELDS as $field) {
            if (! isset($fieldPaths[$field])) {
                throw new ScientificSourceContractViolation("Profile '{$sourceKey}' must map the '{$field}' field.");
            }
        }
        if ($maxResultsPerRequest !== null && $maxResultsPerRequest < 1) {
            throw new ScientificSourceContractViolation("Profile '{$sourceKey}' maxResultsPerRequest must be at least 1.");
        }
        if ($requestsPerMinute !== null && $requestsPerMinute < 1) {
            throw new ScientificSourceContractViolation("Profile '{$sourceKey}' requestsPerMinute must be at least 1.");
        }
        $reserved = array_filter([
            $queryParameter,
            $limitParameter,
            $credential?->placement === RestJsonCredential::PLACEMENT_QUERY ? $credential->name : null,
        ]);
        if (count($reserved) !== count(array_unique($reserved))) {
            throw new ScientificSourceContractViolation("Profile '{$sourceKey}' uses one parameter name for two purposes.");
        }
        foreach ($fixedParameters as $name => $value) {
            if (! is_string($name) || ! is_string($value)) {
                throw new ScientificSourceContractViolation("Profile '{$sourceKey}' fixed parameters must be string pairs.");
            }
            if (in_array($name, $reserved, true)) {
                throw new ScientificSourceContractViolation("Profile '{$sourceKey}' fixed parameter '{$name}' overrides a reserved parameter.");
            }
        }
        if ($responseStatusPath !== null && trim($responseStatusPath) === '') {
            throw new ScientificSourceContractViolation("Profile '{$sourceKey}' responseStatusPath must be null or non-empty.");
        }
        if (trim($responseStatusOkValue) === '') {
            throw new ScientificSourceContractViolation("Profile '{$sourceKey}' responseStatusOkValue must be non-empty.");
        }
        if ($responseErrorMessagePath !== null && trim($responseErrorMessagePath) === '') {
            throw new ScientificSourceContractViolation("Profile '{$sourceKey}' responseErrorMessagePath must be null or non-empty.");
        }
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
        return ScientificSourceAccessFamily::REST_API;
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
            'endpoint' => $this->endpoint,
            'query_parameter' => $this->queryParameter,
            'limit_parameter' => $this->limitParameter,
            'max_results_per_request' => $this->maxResultsPerRequest,
            'items_path' => $this->itemsPath,
            'field_paths' => $this->fieldPaths,
            'author_name_path' => $this->authorNamePath,
            'fixed_parameters' => $this->fixedParameters,
            'credential' => $this->credential?->toArray(),
            'requests_per_minute' => $this->requestsPerMinute,
            'response_status_path' => $this->responseStatusPath,
            'response_status_ok_value' => $this->responseStatusOkValue,
            'response_error_message_path' => $this->responseErrorMessagePath,
            'licensing' => $this->licensing->toArray(),
        ];
    }

    private static function assertHttpsEndpoint(string $sourceKey, string $endpoint): void
    {
        $parts = parse_url($endpoint);
        if ($parts === false
            || strtolower($parts['scheme'] ?? '') !== 'https'
            || ($parts['host'] ?? '') === ''
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['query'])
            || isset($parts['fragment'])) {
            throw new ScientificSourceContractViolation(
                "Profile '{$sourceKey}' endpoint must be an https URL without credentials, query or fragment."
            );
        }
    }
}
