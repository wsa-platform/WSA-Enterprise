<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;

class RolePolicy extends OrganizationPermissionPolicy
{
    public function viewAny(User $user, int $organizationId): bool
    {
        return $this->allows($user, $organizationId, 'access.manage');
    }

    public function update(User $user, Role $role): bool
    {
        return $this->allows($user, $role->organization_id, 'access.manage');
    }

    public function delete(User $user, Role $role): bool
    {
        return $this->allows($user, $role->organization_id, 'access.manage');
    }
}
