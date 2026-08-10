<?php

namespace App\Services\Authorization;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PermissionCacheInvalidator
{
    public function forgetUser(User $user, int $organizationId): void
    {
        app(PermissionService::class)->forget($user, $organizationId);
    }

    public function forgetOrganization(int $organizationId): void
    {
        $userIds = DB::table('organization_user')
            ->where('organization_id', $organizationId)
            ->pluck('user_id');

        foreach ($userIds as $userId) {
            Cache::forget("user_permissions:{$userId}:{$organizationId}");
        }
    }

    public function forgetRole(Role $role): void
    {
        $userIds = DB::table('role_user')
            ->where('role_id', $role->id)
            ->where('organization_id', $role->organization_id)
            ->pluck('user_id');

        foreach ($userIds as $userId) {
            Cache::forget("user_permissions:{$userId}:{$role->organization_id}");
        }
    }
}
