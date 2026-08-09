<?php

namespace App\Http\Controllers\Concerns;

use App\Services\Authorization\PermissionService;
use Illuminate\Http\Request;

trait AuthorizesOrganizationAccess
{
    use ResolvesOrganization;

    protected function authorizePermission(Request $request, string $permission): void
    {
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
}
