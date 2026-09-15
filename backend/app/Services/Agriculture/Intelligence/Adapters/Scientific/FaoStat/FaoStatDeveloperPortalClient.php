<?php

namespace App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * FAOSTAT Developer Portal HTTP client (faostatservices.fao.org).
 * Never logs tokens, credentials, or Authorization headers.
 */
final class FaoStatDeveloperPortalClient
{
    public const DEFAULT_BASE_URL = 'https://faostatservices.fao.org/api/v1';

    public const DEFAULT_HOST = 'faostatservices.fao.org';

    /** @var list<string> */
    public const SUPPORTED_LANGS = ['en', 'fr', 'es'];

    public function __construct(
        private FaoStatDeveloperPortalTokenManager $tokens,
        private FaoStatCircuitBreaker $circuit,
        private FaoStatOperationalLogger $logger,
    ) {}

    public static function normalizedBaseUrl(): string
    {
        $base = rtrim((string) config('agricultural_intelligence.faostat.base_url', self::DEFAULT_BASE_URL), '/');
        if ($base === '') {
            $base = self::DEFAULT_BASE_URL;
        }

        $scheme = parse_url($base, PHP_URL_SCHEME);
        $host = parse_url($base, PHP_URL_HOST);
        if ($scheme !== 'https' || ! is_string($host) || $host === '') {
            throw new FaoStatPortalException(
                FaoStatErrorCategory::CONFIGURATION_ERROR,
                'invalid_base_url',
            );
        }

        $allowed = (string) config('agricultural_intelligence.faostat.allowed_host', self::DEFAULT_HOST);
        if ($host !== $allowed) {
            throw new FaoStatPortalException(
                FaoStatErrorCategory::CONFIGURATION_ERROR,
                'host_not_allowlisted',
            );
        }

        return $base;
    }

    public static function timeoutSeconds(): int
    {
        return max(1, min(30, (int) config('agricultural_intelligence.faostat.timeout', 15)));
    }

    public static function lang(): string
    {
        $lang = strtolower((string) config('agricultural_intelligence.faostat.lang', 'en'));

        return in_array($lang, self::SUPPORTED_LANGS, true) ? $lang : 'en';
    }

    /**
     * @return list<string>
     */
    public static function allowedDomains(): array
    {
        return FaoStatActivationPolicy::activeDomains();
    }

    /**
     * Inspection (metadata/dimensions/codes) may target a DISCOVERED domain.
     * OpenAlex domain=agri is never a FAOSTAT dataset code.
     */
    public function assertInspectableDomain(string $domain): string
    {
        $code = strtoupper(trim($domain));
        if ($code === FaoStatDomainCatalog::OPENALEX_DOMAIN_AGRI
            || preg_match('/^[A-Z][A-Z0-9]{1,7}$/', $code) !== 1) {
            throw new FaoStatPortalException(
                FaoStatErrorCategory::DOMAIN_NOT_ALLOWED,
                'domain_not_inspectable',
            );
        }

        return $code;
    }

    public function assertDomainAllowed(string $domain): string
    {
        $code = $this->assertInspectableDomain($domain);
        if (! in_array($code, self::allowedDomains(), true)) {
            throw new FaoStatPortalException(
                FaoStatErrorCategory::DOMAIN_NOT_ALLOWED,
                'domain_not_allowed',
            );
        }

        return $code;
    }

    public function ping(): Response
    {
        return $this->send('GET', '/ping', [], false, false, false);
    }

    /**
     * @return array<string, mixed>
     */
    public function getGroupsAndDomains(): array
    {
        $cacheKey = 'faostat.groupsanddomains.'.self::lang();
        $ttl = max(60, (int) config('agricultural_intelligence.faostat.code_cache_ttl', 21600));

        /** @var array<string, mixed> $payload */
        $payload = Cache::remember($cacheKey, $ttl, fn (): array => $this->jsonGet('/'.self::lang().'/groupsanddomains'));

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(string $domain): array
    {
        $domain = $this->assertInspectableDomain($domain);

        return $this->jsonGet('/'.self::lang().'/metadata/'.$domain);
    }

    /**
     * @return array<string, mixed>
     */
    public function getDimensions(string $domain): array
    {
        $domain = $this->assertInspectableDomain($domain);

        return $this->jsonGet('/'.self::lang().'/dimensions/'.$domain.'/');
    }

    /**
     * @return list<array{code: string, label: string}>
     */
    public function getItems(string $domain): array
    {
        return $this->codeList('items', $domain);
    }

    /**
     * @return list<array{code: string, label: string}>
     */
    public function getElements(string $domain): array
    {
        return $this->codeList('elements', $domain);
    }

    /**
     * @return list<array{code: string, label: string}>
     */
    public function getCountries(string $domain): array
    {
        return $this->codeList('countries', $domain);
    }

    /**
     * @return list<array{code: string, label: string}>
     */
    public function getRegions(string $domain): array
    {
        return $this->codeList('regions', $domain);
    }

    /**
     * @return list<array{code: string, label: string}>
     */
    public function getSpecialGroups(string $domain): array
    {
        return $this->codeList('specialgroups', $domain);
    }

    /**
     * @return list<array{code: string, label: string}>
     */
    public function getYears(string $domain): array
    {
        return $this->codeList('years', $domain);
    }

    /**
     * Exact, case-insensitive unique label match. Never invents codes.
     *
     * @return array{status: 'resolved'|'unresolved'|'ambiguous', code: ?string}
     */
    public function resolveUniqueCode(string $dimensionId, string $domain, string $label): array
    {
        $needle = mb_strtolower(trim($label));
        if ($needle === '') {
            return ['status' => 'unresolved', 'code' => null];
        }

        $matches = [];
        foreach ($this->codeList($dimensionId, $domain) as $row) {
            if (mb_strtolower(trim($row['label'])) === $needle) {
                $matches[] = $row['code'];
            }
        }

        if (count($matches) === 1) {
            return ['status' => 'resolved', 'code' => $matches[0]];
        }
        if (count($matches) > 1) {
            return ['status' => 'ambiguous', 'code' => null];
        }

        return ['status' => 'unresolved', 'code' => null];
    }

    /**
     * @param  array<string, string>  $filters
     * @return array{status: int, content_type: string, payload: array<string, mixed>|null, csv: string|null}
     */
    public function getData(string $domain, array $filters, bool $csv = false): array
    {
        $domain = $this->assertDomainAllowed($domain);

        return $this->fetchData($domain, $filters, $csv);
    }

    /**
     * Controlled verification query. Does not activate the domain for search.
     *
     * @param  array<string, string>  $filters
     * @return array{status: int, content_type: string, payload: array<string, mixed>|null, csv: string|null}
     */
    public function getVerificationData(string $domain, array $filters, bool $csv = false): array
    {
        $domain = $this->assertInspectableDomain($domain);

        return $this->fetchData($domain, $filters, $csv);
    }

    /**
     * @param  array<string, string>  $filters
     * @return array{status: int, content_type: string, payload: array<string, mixed>|null, csv: string|null}
     */
    private function fetchData(string $domain, array $filters, bool $csv = false): array
    {
        $query = [];
        foreach (['area', 'item', 'element', 'year'] as $key) {
            if (isset($filters[$key]) && trim((string) $filters[$key]) !== '') {
                $query[$key] = trim((string) $filters[$key]);
            }
        }
        $query['output_type'] = $csv ? 'csv' : 'objects';

        $path = '/'.self::lang().'/data/'.$domain;
        $response = $this->send('GET', $path, $query, true, true);
        $contentType = (string) $response->header('Content-Type');

        if ($csv) {
            return [
                'status' => $response->status(),
                'content_type' => $contentType,
                'payload' => null,
                'csv' => $response->body(),
            ];
        }

        /** @var array<string, mixed> $payload */
        $payload = $response->json() ?? [];

        return [
            'status' => $response->status(),
            'content_type' => $contentType,
            'payload' => $payload,
            'csv' => null,
        ];
    }

    /**
     * @return list<array{code: string, label: string}>
     */
    public function listCodes(string $dimensionId, string $domain): array
    {
        return $this->codeList($dimensionId, $domain);
    }

    /**
     * @return list<array{code: string, label: string}>
     */
    private function codeList(string $dimensionId, string $domain): array
    {
        if ($dimensionId === 'areas') {
            throw new FaoStatPortalException(
                FaoStatErrorCategory::UNKNOWN_UPSTREAM_ERROR,
                'areas_endpoint_forbidden',
            );
        }

        $domain = $this->assertInspectableDomain($domain);
        $cacheKey = 'faostat.codes.'.$dimensionId.'.'.$domain.'.'.self::lang();
        $ttl = max(60, (int) config('agricultural_intelligence.faostat.code_cache_ttl', 21600));

        /** @var list<array{code: string, label: string}> $rows */
        $rows = Cache::remember($cacheKey, $ttl, function () use ($dimensionId, $domain): array {
            $payload = $this->jsonGet('/'.self::lang().'/codes/'.$dimensionId.'/'.$domain.'/');
            $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];
            $out = [];
            foreach ($data as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $code = trim((string) ($row['code'] ?? $row['Code'] ?? ''));
                if ($code === '') {
                    continue;
                }
                $out[] = [
                    'code' => $code,
                    'label' => (string) ($row['label'] ?? $row['Label'] ?? $row['description'] ?? $row['Description'] ?? ''),
                ];
            }

            return $out;
        });

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    private function jsonGet(string $path): array
    {
        $response = $this->send('GET', $path, [], true, true);
        /** @var array<string, mixed> $payload */
        $payload = $response->json() ?? [];

        return $payload;
    }

    /**
     * @param  array<string, scalar>  $query
     */
    private function send(
        string $method,
        string $path,
        array $query,
        bool $authenticate,
        bool $retryOnUnauthorized,
        bool $requireSuccess = true,
    ): Response {
        $url = self::normalizedBaseUrl().$path;
        $this->assertHttpsAllowlisted($url);

        if ($authenticate && $this->circuit->isOpen()) {
            throw new FaoStatPortalException(
                FaoStatErrorCategory::UPSTREAM_SERVER_ERROR,
                'circuit_open',
            );
        }

        $timeout = self::timeoutSeconds();
        $attempt = function (bool $withAuth) use ($method, $url, $query, $timeout): Response {
            $pending = Http::timeout($timeout)->connectTimeout($timeout)->acceptJson();
            if ($withAuth) {
                $pending = $pending->withToken($this->tokens->bearerToken());
            }
            try {
                return $method === 'GET'
                    ? $pending->get($url, $query)
                    : $pending->send($method, $url, ['query' => $query]);
            } catch (ConnectionException $e) {
                $category = str_contains(strtolower($e->getMessage()), 'timed out')
                    ? FaoStatErrorCategory::TIMEOUT
                    : FaoStatErrorCategory::NETWORK_ERROR;
                throw new FaoStatPortalException($category, 'http_connection_error', previous: $e);
            }
        };

        $retriesUsed = 0;
        $max = $this->maxTransientRetries();

        try {
            $response = $attempt($authenticate);
        } catch (FaoStatPortalException $e) {
            if ($retriesUsed < $max && $this->isTransientCategory($e->category)) {
                $retriesUsed++;
                try {
                    $response = $attempt($authenticate);
                } catch (FaoStatPortalException $retry) {
                    $this->tripCircuit($authenticate, $retry->category);
                    throw $retry;
                }
            } else {
                $this->tripCircuit($authenticate, $e->category);
                throw $e;
            }
        }

        if ($authenticate && $retryOnUnauthorized && $response->status() === 401) {
            $this->tokens->invalidate();
            $response = $attempt(true);
        }

        if ($response->status() === 429 && $retriesUsed < $max) {
            $retryAfter = $response->header('Retry-After');
            Log::warning('FAOSTAT portal rate limited', [
                'provider' => 'fao_stat',
                'http_status' => 429,
                'retry_after_present' => $retryAfter !== null && $retryAfter !== '',
            ]);
            $wait = is_numeric($retryAfter) ? (int) $retryAfter : 1;
            $wait = max(1, min(5, $wait));
            sleep($wait);
            $retriesUsed++;
            $response = $attempt($authenticate);
        }
        if ($response->status() === 429) {
            $this->tripCircuit($authenticate, FaoStatErrorCategory::UPSTREAM_SERVER_ERROR);
            throw new FaoStatPortalException(
                FaoStatErrorCategory::UPSTREAM_SERVER_ERROR,
                'rate_limited',
                429,
            );
        }

        if ($response->serverError() && $retriesUsed < $max) {
            $retriesUsed++;
            try {
                $response = $attempt($authenticate);
            } catch (FaoStatPortalException $e) {
                $this->tripCircuit($authenticate, $e->category);
                throw $e;
            }
        }
        if ($response->serverError()) {
            $this->tripCircuit($authenticate, FaoStatErrorCategory::UPSTREAM_SERVER_ERROR);
            throw new FaoStatPortalException(
                FaoStatErrorCategory::UPSTREAM_SERVER_ERROR,
                'http_'.$response->status(),
                $response->status(),
            );
        }

        if (! $requireSuccess) {
            return $response;
        }

        if ($response->status() === 401) {
            throw new FaoStatPortalException(
                FaoStatErrorCategory::AUTHORIZATION_ERROR,
                'unauthorized',
                401,
            );
        }

        if ($response->status() === 400) {
            throw new FaoStatPortalException(
                FaoStatErrorCategory::UNKNOWN_UPSTREAM_ERROR,
                'bad_request',
                400,
            );
        }

        if (! $response->successful()) {
            throw new FaoStatPortalException(
                FaoStatErrorCategory::UNKNOWN_UPSTREAM_ERROR,
                'http_'.$response->status(),
                $response->status(),
            );
        }

        if ($authenticate) {
            $this->circuit->recordSuccess();
        }

        return $response;
    }

    private function maxTransientRetries(): int
    {
        return max(0, min(2, (int) config('agricultural_intelligence.faostat.max_transient_retries', 1)));
    }

    private function isTransientCategory(string $category): bool
    {
        return in_array($category, [
            FaoStatErrorCategory::NETWORK_ERROR,
            FaoStatErrorCategory::TIMEOUT,
            FaoStatErrorCategory::UPSTREAM_SERVER_ERROR,
        ], true);
    }

    private function tripCircuit(bool $authenticate, string $category): void
    {
        if ($authenticate) {
            $this->circuit->recordFailure($category);
        }
    }

    private function assertHttpsAllowlisted(string $url): void
    {
        $scheme = parse_url($url, PHP_URL_SCHEME);
        $host = parse_url($url, PHP_URL_HOST);
        $allowed = (string) config('agricultural_intelligence.faostat.allowed_host', self::DEFAULT_HOST);
        if ($scheme !== 'https' || $host !== $allowed) {
            throw new FaoStatPortalException(
                FaoStatErrorCategory::CONFIGURATION_ERROR,
                'url_not_allowlisted',
            );
        }
    }
}
