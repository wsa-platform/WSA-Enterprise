<?php

namespace App\Policies;

use App\Models\Farm;
use App\Models\User;

class FarmPolicy extends OrganizationPermissionPolicy
{
    public function viewAny(User $user, int $organizationId): bool
    {
        return $this->allows($user, $organizationId, 'farm.view');
    }

    public function create(User $user, int $organizationId): bool
    {
        return $this->allows($user, $organizationId, 'farm.manage');
    }

    public function update(User $user, Farm $farm): bool
    {
        return $this->allows($user, $farm->organization_id, 'farm.manage');
    }

    public function delete(User $user, Farm $farm): bool
    {
        return $this->allows($user, $farm->organization_id, 'farm.manage');
    }
}
