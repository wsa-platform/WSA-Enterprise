<?php

namespace App\Services\Api;

use App\Models\ApiClient;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ApiClientService
{
    /** @param  list<string>  $scopes */
    public function create(
        int $organizationId,
        string $name,
        ?int $createdBy = null,
        array $scopes = [],
    ): array {
        $plainSecret = Str::random(48);
        $clientId = (string) Str::uuid();

        $client = ApiClient::withoutGlobalScopes()->create([
            'organization_id' => $organizationId,
            'name' => $name,
            'client_id' => $clientId,
            'secret_hash' => Hash::make($plainSecret),
            'scopes' => $scopes,
            'created_by' => $createdBy,
        ]);

        return [
            'client' => $client,
            'client_secret' => $plainSecret,
        ];
    }

    public function verifySecret(ApiClient $client, string $plainSecret): bool
    {
        if (! $client->isActive()) {
            return false;
        }

        return Hash::check($plainSecret, $client->secret_hash);
    }

    public function revoke(ApiClient $client): ApiClient
    {
        $client->update(['revoked_at' => now()]);

        return $client->fresh();
    }
}
