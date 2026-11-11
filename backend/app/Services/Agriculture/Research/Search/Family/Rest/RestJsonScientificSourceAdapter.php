<?php

namespace App\Services\Agriculture\Research\Search\Family\Rest;

use App\Contracts\ScientificSourceAdapterV2Interface;
use App\Services\Agriculture\Research\Search\Contract\DelegatesSearchRequestToSearch;
use App\Services\Agriculture\Research\Search\Contract\ScientificSourceAdapterDescriptor;
use App\Services\Agriculture\Research\Search\Contract\ScientificSourceFailureClassifier;
use App\Services\Agriculture\Research\Search\Contract\ScientificSourceRetryPolicy;
use App\Services\Agriculture\Research\Search\Family\ScientificSourceFamilyAdapter;
use App\Services\Agriculture\Research\Search\Family\ScientificSourceFamilyRecord;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceAccessFamily;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;
use App\Services\Agriculture\Research\Search\ScientificSearchTimeBudget;
use App\Services\Agriculture\Research\Search\ScientificSourceSearchOutcome;
use App\Support\ScientificHttp;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * REST/JSON search family adapter (ADR-023 §8.39, increment R5).
 *
 * One class serves every REST/JSON source; the profile supplies the endpoint, parameters and
 * field paths. A response whose result list is not where the profile says is a malformed
 * response, not an empty one.
 */
final class RestJsonScientificSourceAdapter implements ScientificSourceAdapterV2Interface, ScientificSourceFamilyAdapter
{
    use DelegatesSearchRequestToSearch;

    public function __construct(
        private readonly RestJsonSourceProfile $profile,
    ) {}

    public static function servesAccessFamily(ScientificSourceAccessFamily $family): bool
    {
        return $family === ScientificSourceAccessFamily::REST_API;
    }

    public function profile(): RestJsonSourceProfile
    {
        return $this->profile;
    }

    public function sourceKey(): string
    {
        return $this->profile->sourceKey;
    }

    public function displayName(): string
    {
        return $this->profile->displayName;
    }

    public function descriptor(): ScientificSourceAdapterDescriptor
    {
        return new ScientificSourceAdapterDescriptor(
            sourceKey: $this->profile->sourceKey,
            licensing: $this->profile->licensing,
            retryPolicy: ScientificSourceRetryPolicy::none(),
            maxResultsPerRequest: $this->profile->maxResultsPerRequest,
            requestsPerMinute: $this->profile->requestsPerMinute,
        );
    }

    /**
     * @param  array<string, mixed>  $options  only the search budget is read; provider filters come from the profile
     */
    public function search(string $query, int $limit = 10, array $options = []): ScientificSourceSearchOutcome
    {
        if (trim($query) === '') {
            return $this->outcome(ScientificSourceSearchOutcome::STATUS_EMPTY, error: 'empty_query');
        }

        $credential = null;
        if ($this->profile->credential !== null) {
            $credential = config($this->profile->credential->configKey);
            if (! is_string($credential) || trim($credential) === '') {
                return $this->outcome(ScientificSourceSearchOutcome::STATUS_UNAVAILABLE, error: 'missing_api_key');
            }
        }

        $remaining = isset($options['search_budget_remaining_seconds'])
            ? (float) $options['search_budget_remaining_seconds']
            : ScientificSearchTimeBudget::current()?->remainingSeconds();
        if ($remaining !== null && $remaining < 1.0) {
            return $this->outcome(ScientificSourceSearchOutcome::STATUS_UNAVAILABLE, error: 'time_budget_exhausted');
        }

        $parameters = $this->profile->fixedParameters;
        $parameters[$this->profile->queryParameter] = $query;
        if ($this->profile->limitParameter !== null) {
            $parameters[$this->profile->limitParameter] = $this->requestLimit($limit);
        }
        $request = Http::timeout(ScientificHttp::timeoutSeconds($remaining))->acceptJson();
        if ($credential !== null && $this->profile->credential->placement === RestJsonCredential::PLACEMENT_HEADER) {
            $request = $request->withHeaders([$this->profile->credential->name => $credential]);
        } elseif ($credential !== null) {
            $parameters[$this->profile->credential->name] = $credential;
        }

        $startedNs = hrtime(true);
        try {
            $response = $request->get($this->profile->endpoint, $parameters);
        } catch (\Throwable $exception) {
            $category = ScientificSourceFailureClassifier::classifyThrowable($exception);
            if ($category === null) {
                throw $exception;
            }
            Log::warning('Source-family REST search request failed', [
                'source' => $this->profile->sourceKey,
                'exception' => $exception::class,
            ]);

            return $this->outcome(ScientificSourceSearchOutcome::STATUS_FAILED, error: 'request_exception', startedNs: $startedNs);
        }

        if ($response->status() === 429) {
            return $this->outcome(ScientificSourceSearchOutcome::STATUS_UNAVAILABLE, error: 'rate_limited', httpStatus: 429, startedNs: $startedNs);
        }
        if (! $response->successful()) {
            return $this->outcome(ScientificSourceSearchOutcome::STATUS_FAILED, error: 'http_error', httpStatus: $response->status(), startedNs: $startedNs);
        }

        $payload = $response->json();
        if (! is_array($payload)) {
            return $this->outcome(ScientificSourceSearchOutcome::STATUS_FAILED, error: 'malformed_or_unusable_results', startedNs: $startedNs);
        }
        // Provider envelope (e.g. BHL Status/ErrorMessage): HTTP 200 ≠ operation success.
        if ($this->profile->responseStatusPath !== null) {
            $providerStatus = data_get($payload, $this->profile->responseStatusPath);
            if (! is_string($providerStatus)
                || strcasecmp(trim($providerStatus), $this->profile->responseStatusOkValue) !== 0) {
                return $this->outcome(
                    ScientificSourceSearchOutcome::STATUS_FAILED,
                    error: 'provider_status_rejected',
                    httpStatus: $response->status(),
                    startedNs: $startedNs,
                );
            }
        }
        $items = $this->profile->itemsPath === '' ? $payload : data_get($payload, $this->profile->itemsPath);
        if (! is_array($items) || ! array_is_list($items)) {
            return $this->outcome(ScientificSourceSearchOutcome::STATUS_FAILED, error: 'malformed_or_unusable_results', startedNs: $startedNs);
        }
        if ($items === []) {
            return $this->outcome(ScientificSourceSearchOutcome::STATUS_EMPTY, startedNs: $startedNs);
        }

        $results = [];
        foreach ($items as $item) {
            $result = is_array($item) ? $this->toResult($item) : null;
            if ($result !== null) {
                $results[] = $result;
            }
        }
        if ($results === []) {
            return $this->outcome(ScientificSourceSearchOutcome::STATUS_EMPTY, error: 'malformed_or_unusable_results', startedNs: $startedNs);
        }

        return $this->outcome(ScientificSourceSearchOutcome::STATUS_SUCCESS, results: $results, startedNs: $startedNs);
    }

    /** @param  array<string, mixed>  $item */
    private function toResult(array $item): ?ScientificSearchResult
    {
        $paths = $this->profile->fieldPaths;
        [$identifier, $identifierField] = $this->firstMappedScalar($item, 'identifier');
        [$title] = $this->firstMappedScalar($item, 'title');
        [$doi] = $this->firstMappedScalar($item, 'doi');
        [$url] = $this->firstMappedScalar($item, 'url');
        [$abstract] = $this->firstMappedScalar($item, 'abstract');
        [$published] = $this->firstMappedScalar($item, 'published');
        [$yearRaw] = $this->firstMappedScalar($item, 'year');
        $year = $yearRaw;
        // authors/journal keep prior path semantics below
        $value = static function (string $field) use ($paths, $item): mixed {
            if (! isset($paths[$field])) {
                return null;
            }
            $path = $paths[$field];
            if (is_array($path)) {
                foreach ($path as $candidate) {
                    $found = data_get($item, $candidate);
                    if ($found !== null && $found !== '') {
                        return $found;
                    }
                }

                return null;
            }

            return data_get($item, $path);
        };

        return ScientificSourceFamilyRecord::toResult(
            profile: $this->profile,
            rawTitle: ScientificSourceFamilyRecord::scalar($title),
            originalIdentifier: ScientificSourceFamilyRecord::scalar($identifier),
            originalIdentifierField: $identifierField,
            rawDoi: ScientificSourceFamilyRecord::scalar($doi),
            rawUrl: ScientificSourceFamilyRecord::scalar($url),
            rawAbstract: ScientificSourceFamilyRecord::scalar($abstract),
            publishedAt: ScientificSourceFamilyRecord::scalar($published),
            year: is_int($year) || (is_string($year) && preg_match('/^\d{4}$/', $year) === 1) ? (int) $year : null,
            authors: $this->authors($value('authors')),
            journal: ScientificSourceFamilyRecord::scalar($value('journal')),
            raw: $item,
        );
    }

    /**
     * Resolve a profile field that may list alternate provider paths (first non-empty scalar wins).
     *
     * @param  array<string, mixed>  $item
     * @return array{0: mixed, 1: ?string}
     */
    private function firstMappedScalar(array $item, string $field): array
    {
        if (! isset($this->profile->fieldPaths[$field])) {
            return [null, null];
        }
        $path = $this->profile->fieldPaths[$field];
        $candidates = is_array($path) ? $path : [$path];
        foreach ($candidates as $candidate) {
            $found = data_get($item, $candidate);
            if ($found !== null && $found !== '') {
                return [$found, $candidate];
            }
        }

        return [null, is_array($path) ? ($path[0] ?? null) : $path];
    }

    /** @return list<string> */
    private function authors(mixed $entries): array
    {
        if (! is_array($entries)) {
            return [];
        }
        $authors = [];
        foreach ($entries as $entry) {
            $name = $this->profile->authorNamePath !== null && is_array($entry)
                ? ScientificSourceFamilyRecord::scalar(data_get($entry, $this->profile->authorNamePath))
                : ScientificSourceFamilyRecord::scalar($entry);
            if ($name !== null) {
                $authors[] = $name;
            }
        }

        return $authors;
    }

    private function requestLimit(int $limit): int
    {
        $limit = max(1, $limit);

        return $this->profile->maxResultsPerRequest !== null ? min($limit, $this->profile->maxResultsPerRequest) : $limit;
    }

    /** @param  list<ScientificSearchResult>  $results */
    private function outcome(
        string $status,
        array $results = [],
        ?string $error = null,
        ?int $httpStatus = null,
        ?int $startedNs = null,
    ): ScientificSourceSearchOutcome {
        $observability = ['provider' => $this->profile->sourceKey, 'access_family' => $this->profile->accessFamily()->value];
        if ($startedNs !== null) {
            $observability['latency_ms'] = max(0, (int) ((hrtime(true) - $startedNs) / 1_000_000));
        }

        return new ScientificSourceSearchOutcome(
            sourceKey: $this->profile->sourceKey,
            status: $status,
            results: $results,
            error: $error,
            httpStatus: $httpStatus,
            observability: $observability,
        );
    }
}
