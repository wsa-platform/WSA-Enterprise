<?php

namespace App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * In-memory FAOSTAT Developer Portal token manager.
 * Re-login only — no undocumented refresh endpoint.
 */
final class FaoStatDeveloperPortalTokenManager
{
    private const EXPIRY_FRACTION = 0.8;

    private ?string $accessToken = null;

    private ?int $expiresAtUnix = null;

    public function reset(): void
    {
        $this->accessToken = null;
        $this->expiresAtUnix = null;
    }

    public function hasUsableToken(): bool
    {
        return $this->accessToken !== null
            && $this->expiresAtUnix !== null
            && time() < $this->expiresAtUnix;
    }

    public function invalidate(): void
    {
        $this->reset();
    }

    /**
     * @return array{expires_in: int, token_type: string, refresh_token_present: bool}
     */
    public function obtainToken(): array
    {
        if ($this->hasUsableToken()) {
            return [
                'expires_in' => max(0, (int) $this->expiresAtUnix - time()),
                'token_type' => 'Bearer',
                'refresh_token_present' => false,
            ];
        }

        $username = (string) config('agricultural_intelligence.faostat.username', '');
        $password = (string) config('agricultural_intelligence.faostat.password', '');
        if ($username === '' || $password === '') {
            throw new FaoStatPortalException(
                FaoStatErrorCategory::AUTHENTICATION_ERROR,
                'credentials_unavailable',
            );
        }

        $base = FaoStatDeveloperPortalClient::normalizedBaseUrl();
        $timeout = FaoStatDeveloperPortalClient::timeoutSeconds();

        try {
            $response = Http::timeout($timeout)
                ->asForm()
                ->acceptJson()
                ->post($base.'/auth/login', [
                    'username' => $username,
                    'password' => $password,
                ]);
        } catch (ConnectionException $e) {
            $category = str_contains(strtolower($e->getMessage()), 'timed out')
                ? FaoStatErrorCategory::TIMEOUT
                : FaoStatErrorCategory::NETWORK_ERROR;
            throw new FaoStatPortalException(
                $category,
                'login_network_error',
                previous: $e,
            );
        }

        if ($response->status() === 400) {
            throw new FaoStatPortalException(
                FaoStatErrorCategory::AUTHENTICATION_ERROR,
                'login_rejected',
                $response->status(),
            );
        }

        if ($response->status() === 401) {
            throw new FaoStatPortalException(
                FaoStatErrorCategory::AUTHORIZATION_ERROR,
                'login_unauthorized',
                $response->status(),
            );
        }

        if ($response->serverError()) {
            throw new FaoStatPortalException(
                FaoStatErrorCategory::UPSTREAM_SERVER_ERROR,
                'login_upstream_error',
                $response->status(),
            );
        }

        if (! $response->successful()) {
            throw new FaoStatPortalException(
                FaoStatErrorCategory::UNKNOWN_UPSTREAM_ERROR,
                'login_failed',
                $response->status(),
            );
        }

        /** @var array<string, mixed> $payload */
        $payload = $response->json() ?? [];
        $result = is_array($payload['AuthenticationResult'] ?? null) ? $payload['AuthenticationResult'] : [];
        $token = trim((string) ($result['AccessToken'] ?? ''));
        if ($token === '') {
            throw new FaoStatPortalException(
                FaoStatErrorCategory::AUTHENTICATION_ERROR,
                'access_token_missing',
            );
        }

        $expiresIn = max(1, (int) ($result['ExpiresIn'] ?? 3600));
        $this->accessToken = $token;
        $this->expiresAtUnix = time() + (int) floor($expiresIn * self::EXPIRY_FRACTION);

        return [
            'expires_in' => $expiresIn,
            'token_type' => (string) ($result['TokenType'] ?? 'Bearer'),
            'refresh_token_present' => trim((string) ($result['RefreshToken'] ?? '')) !== '',
        ];
    }

    public function bearerToken(): string
    {
        $this->obtainToken();

        return (string) $this->accessToken;
    }

    public function expiresAtUnix(): ?int
    {
        return $this->expiresAtUnix;
    }
}
