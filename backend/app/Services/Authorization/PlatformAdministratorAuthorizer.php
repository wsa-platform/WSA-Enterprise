<?php

namespace App\Services\Authorization;

use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Http\Request;

class PlatformAdministratorAuthorizer
{
    public const ACTION_DENIED = 'admin.denied';

    public function __construct(
        private PlatformRbacService $rbac,
        private AuditService $audit,
    ) {}

    public function isPlatformAdministrator(?User $user): bool
    {
        return $user !== null && $user->isPlatformAdministrator();
    }

    public function hasPermission(User $user, string $permission): bool
    {
        if (! $this->isPlatformAdministrator($user)) {
            return false;
        }

        return in_array($permission, $this->rbac->permissionsFor($user), true);
    }

    public function deny(Request $request, ?User $user, string $permission): void
    {
        $this->audit->record(
            action: self::ACTION_DENIED,
            organizationId: null,
            userId: $user?->id,
            newValues: [
                'permission' => $permission,
                'path' => $request->path(),
            ],
            request: $request,
        );

        abort(403, 'Platform administrator access required.');
    }
}
