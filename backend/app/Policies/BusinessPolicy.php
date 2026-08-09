<?php

namespace App\Policies;

use App\Models\User;

class BusinessPolicy extends OrganizationPermissionPolicy
{
    public function viewAny(User $user, int $organizationId): bool
    {
        return $this->allows($user, $organizationId, 'business.view');
    }

    public function manage(User $user, int $organizationId): bool
    {
        return $this->allows($user, $organizationId, 'business.manage');
    }
}
