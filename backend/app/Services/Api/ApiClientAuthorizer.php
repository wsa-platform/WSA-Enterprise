<?php

namespace App\Services\Api;

use App\Models\ApiClient;

class ApiClientAuthorizer
{
    public function clientCan(ApiClient $client, string $permission): bool
    {
        $scopes = $client->scopes ?? [];

        if ($scopes === []) {
            return false;
        }

        /** @var array<string, array{permissions?: list<string>}> $scopeCatalog */
        $scopeCatalog = config('api_clients.scopes', []);

        foreach ($scopes as $scope) {
            /** @var list<string> $permissions */
            $permissions = $scopeCatalog[$scope]['permissions'] ?? [];

            if (in_array('*', $permissions, true) || in_array($permission, $permissions, true)) {
                return true;
            }
        }

        return false;
    }
}
