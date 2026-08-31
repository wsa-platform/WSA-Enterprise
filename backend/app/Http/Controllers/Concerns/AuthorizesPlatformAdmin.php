<?php

namespace App\Http\Controllers\Concerns;

use App\Services\Authorization\PermissionService;
use Illuminate\Http\Request;

trait AuthorizesPlatformAdmin
{
    protected function authorizePlatformAdmin(Request $request): void
    {
        $permissions = app(PermissionService::class)->permissionsFor(
            $request->user(),
            $this->organization($request),
        );

        abort_unless(in_array('*', $permissions, true), 403, 'Platform administrator access required.');
    }
}
