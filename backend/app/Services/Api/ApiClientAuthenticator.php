<?php

namespace App\Services\Api;

use App\Models\ApiClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ApiClientAuthenticator
{
    public function __construct(
        private ApiClientService $apiClientService,
    ) {}

    public function authenticate(Request $request): ?ApiClient
    {
        [$clientId, $secret] = $this->extractCredentials($request);

        if ($clientId === null || $secret === null) {
            return null;
        }

        $client = ApiClient::withoutGlobalScopes()
            ->where('client_id', $clientId)
            ->first();

        if ($client === null || ! $this->apiClientService->verifySecret($client, $secret)) {
            return null;
        }

        $this->touchLastUsed($client);

        return $client;
    }

    /** @return array{0: ?string, 1: ?string} */
    private function extractCredentials(Request $request): array
    {
        $authorization = $request->header('Authorization', '');

        if (str_starts_with($authorization, 'Basic ')) {
            $decoded = base64_decode(substr($authorization, 6), true);

            if ($decoded === false || ! str_contains($decoded, ':')) {
                return [null, null];
            }

            [$clientId, $secret] = explode(':', $decoded, 2);

            return $this->normalizeCredentialPair($clientId, $secret);
        }

        if (str_starts_with($authorization, 'Bearer ')) {
            $token = substr($authorization, 7);

            if (! str_contains($token, ':')) {
                return [null, null];
            }

            [$clientId, $secret] = explode(':', $token, 2);

            return $this->normalizeCredentialPair($clientId, $secret);
        }

        return [null, null];
    }

    /** @return array{0: ?string, 1: ?string} */
    private function normalizeCredentialPair(?string $clientId, ?string $secret): array
    {
        return [
            ($clientId ?? '') !== '' ? $clientId : null,
            ($secret ?? '') !== '' ? $secret : null,
        ];
    }

    private function touchLastUsed(ApiClient $client): void
    {
        $cacheKey = "api_client:last_used:{$client->id}";
        $interval = (int) config('api_clients.last_used_touch_seconds', 60);

        if ($interval <= 0 || ! Cache::add($cacheKey, true, $interval)) {
            return;
        }

        $client->forceFill(['last_used_at' => now()])->save();
    }
}
