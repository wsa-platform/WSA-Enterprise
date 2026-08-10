<?php

namespace App\Http\Middleware;

use App\Models\ApiClient;
use App\Services\Api\ApiClientAuthenticator;
use App\Services\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * Accept Sanctum bearer tokens (users) or API client bearer/basic credentials.
 */
class AuthenticateApiPrincipal
{
    public function __construct(
        private ApiClientAuthenticator $apiClientAuthenticator,
        private TenantContext $tenant,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() !== null) {
            $request->attributes->set('auth_via', 'sanctum');

            return $next($request);
        }

        if ($this->authenticateApiClient($request)) {
            return $next($request);
        }

        if ($this->authenticateSanctumUser($request)) {
            return $next($request);
        }

        return response()->json(['message' => 'Unauthenticated.'], 401);
    }

    private function authenticateApiClient(Request $request): bool
    {
        $client = $this->apiClientAuthenticator->authenticate($request);

        if ($client === null) {
            return false;
        }

        $headerOrgId = $request->header('X-Organization-Id');

        if ($headerOrgId !== null && $headerOrgId !== '' && (int) $headerOrgId !== $client->organization_id) {
            abort(403, 'API client credentials are not valid for the requested organization.');
        }

        $request->attributes->set('auth_via', 'api_client');
        $request->attributes->set('api_client_id', $client->id);
        $request->attributes->set('organization_id', $client->organization_id);
        $this->tenant->setOrganizationId($client->organization_id);

        return true;
    }

    private function authenticateSanctumUser(Request $request): bool
    {
        $token = $request->bearerToken();

        if ($token === null || str_contains($token, ':')) {
            return false;
        }

        $accessToken = PersonalAccessToken::findToken($token);

        if ($accessToken === null) {
            return false;
        }

        $user = $accessToken->tokenable;
        $request->setUserResolver(fn () => $user);
        $request->attributes->set('auth_via', 'sanctum');

        return true;
    }

    public static function apiClient(Request $request): ?ApiClient
    {
        if ($request->attributes->get('auth_via') !== 'api_client') {
            return null;
        }

        $clientId = $request->attributes->get('api_client_id');

        return $clientId !== null
            ? ApiClient::withoutGlobalScopes()->find($clientId)
            : null;
    }
}
