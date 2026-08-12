<?php

namespace App\Http\Controllers\Concerns;

use App\Services\Authorization\PermissionService;
use Illuminate\Http\Request;

trait AuthorizesOrganizationAccess
{
    use ResolvesOrganization;

    protected function authorizePermission(Request $request, string $permission): void
    {
        if ($request->attributes->get('auth_via') === 'api_client') {
            $client = \App\Http\Middleware\AuthenticateApiPrincipal::apiClient($request);
            abort_unless(
                $client !== null && app(\App\Services\Api\ApiClientAuthorizer::class)->clientCan($client, $permission),
                403,
                'This action is unauthorized.'
            );

            return;
        }

        abort_unless(
            app(PermissionService::class)->userCan(
                $request->user(),
                $this->organization($request),
                $permission
            ),
            403,
            'This action is unauthorized.'
        );
    }

    /** @param list<string> $permissions */
    protected function authorizeAnyPermission(Request $request, array $permissions): void
    {
        if ($request->attributes->get('auth_via') === 'api_client') {
            $client = \App\Http\Middleware\AuthenticateApiPrincipal::apiClient($request);
            $authorizer = app(\App\Services\Api\ApiClientAuthorizer::class);
            foreach ($permissions as $permission) {
                if ($client !== null && $authorizer->clientCan($client, $permission)) {
                    return;
                }
            }

            abort(403, 'This action is unauthorized.');

            return;
        }

        $service = app(PermissionService::class);
        $user = $request->user();
        $organizationId = $this->organization($request);

        foreach ($permissions as $permission) {
            if ($service->userCan($user, $organizationId, $permission)) {
                return;
            }
        }

        abort(403, 'This action is unauthorized.');
    }
}
