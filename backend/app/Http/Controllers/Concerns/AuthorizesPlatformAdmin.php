<?php

namespace App\Http\Controllers\Concerns;

use App\Services\Authorization\PlatformAdministratorAuthorizer;
use Illuminate\Http\Request;

trait AuthorizesPlatformAdmin
{
    protected function authorizePlatformAdmin(Request $request, ?string $permission = null): void
    {
        $user = $request->user();
        abort_unless($user !== null, 401, 'Unauthenticated.');

        $authorizer = app(PlatformAdministratorAuthorizer::class);

        if (! $authorizer->isPlatformAdministrator($user)) {
            $authorizer->deny($request, $user, $permission ?? 'platform.access');
        }

        if ($permission !== null && ! $authorizer->hasPermission($user, $permission)) {
            $authorizer->deny($request, $user, $permission);
        }
    }
}
