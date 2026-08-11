<?php

namespace App\Policies;

use App\Models\Permission;
use App\Models\User;

class PermissionPolicy extends OrganizationPermissionPolicy
{
    public function viewAny(User $user, int $organizationId): bool
    {
        return $this->allows($user, $organizationId, 'access.manage');
    }

    public function update(User $user, Permission $permission): bool
    {
        return $this->allows($user, $permission->organization_id, 'access.manage');
    }

    public function delete(User $user, Permission $permission): bool
    {
        return $this->allows($user, $permission->organization_id, 'access.manage');
    }
}
