<?php

namespace App\Services\Authorization;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class PermissionService
{
    /** @var array<string, list<string>> */
    private const PIVOT_ROLE_PERMISSIONS = [
        'admin' => ['*'],
        'member' => [
            'platform.view',
            'billing.view',
            'farm.view', 'farm.manage',
            'crop.view', 'crop.manage',
            'soil.view', 'soil.manage',
            'diagnosis.view', 'diagnosis.manage',
            'training.view', 'training.manage',
            'library.view', 'library.manage',
            'ai.use',
            'business.view', 'business.manage',
        ],
    ];

    public function userCan(User $user, int $organizationId, string $permission): bool
    {
        $permissions = $this->permissionsFor($user, $organizationId);

        return in_array('*', $permissions, true) || in_array($permission, $permissions, true);
    }

    /** @return list<string> */
    public function permissionsFor(User $user, int $organizationId): array
    {
        $cacheKey = "user_permissions:{$user->id}:{$organizationId}";

        return Cache::remember($cacheKey, 60, function () use ($user, $organizationId): array {
            $membership = $user->organizations()->where('organizations.id', $organizationId)->first();
            abort_unless($membership, 403, 'You do not have access to this organization.');

            $pivotRole = $membership->pivot->role ?? 'member';
            $baseline = self::PIVOT_ROLE_PERMISSIONS[$pivotRole] ?? self::PIVOT_ROLE_PERMISSIONS['member'];

            if (in_array('*', $baseline, true)) {
                return ['*'];
            }

            $assigned = $user->roles()
                ->wherePivot('organization_id', $organizationId)
                ->with('permissions')
                ->get();

            if ($assigned->isNotEmpty()) {
                $fromRoles = $assigned
                    ->flatMap(fn ($role) => $role->permissions->pluck('name'))
                    ->unique()
                    ->values()
                    ->all();

                return $fromRoles === [] ? $baseline : $fromRoles;
            }

            return $baseline;
        });
    }

    public function forget(User $user, int $organizationId): void
    {
        Cache::forget("user_permissions:{$user->id}:{$organizationId}");
    }

    /** @return list<string> */
    public static function catalog(): array
    {
        return config('permissions', []);
    }

    public static function seedNamesForOrganization(int $organizationId): void
    {
        foreach (self::catalog() as $name) {
            Permission::updateOrCreate(
                ['organization_id' => $organizationId, 'name' => $name],
                ['description' => 'Default permission: '.$name]
            );
        }
    }
}
