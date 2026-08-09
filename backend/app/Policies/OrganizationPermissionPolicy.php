<?php

namespace App\Policies;

use App\Models\User;
use App\Services\Authorization\PermissionService;

abstract class OrganizationPermissionPolicy
{
    public function __construct(protected PermissionService $permissions) {}

    protected function allows(User $user, int $organizationId, string $permission): bool
    {
        return $this->permissions->userCan($user, $organizationId, $permission);
    }
}
