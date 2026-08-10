<?php

namespace App\Policies;

use App\Models\AiRequest;
use App\Models\User;

class AiRequestPolicy extends OrganizationPermissionPolicy
{
    public function viewAny(User $user, int $organizationId): bool
    {
        return $this->allows($user, $organizationId, 'ai.use');
    }

    public function create(User $user, int $organizationId): bool
    {
        return $this->allows($user, $organizationId, 'ai.use');
    }

    public function view(User $user, AiRequest $request): bool
    {
        return $this->allows($user, $request->organization_id, 'ai.use');
    }

    public function cancel(User $user, AiRequest $request): bool
    {
        return $this->allows($user, $request->organization_id, 'ai.use');
    }
}
