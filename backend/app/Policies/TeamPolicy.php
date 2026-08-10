<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;

class TeamPolicy extends OrganizationPermissionPolicy
{
    public function viewAny(User $user, int $organizationId): bool
    {
        return $this->allows($user, $organizationId, 'access.manage');
    }

    public function create(User $user, int $organizationId): bool
    {
        return $this->allows($user, $organizationId, 'access.manage');
    }

    public function update(User $user, Team $team): bool
    {
        return $this->allows($user, $team->organization_id, 'access.manage');
    }

    public function delete(User $user, Team $team): bool
    {
        return $this->allows($user, $team->organization_id, 'access.manage');
    }
}
