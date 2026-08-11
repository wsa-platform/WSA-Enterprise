<?php

namespace App\Services\Authorization;

use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;

class EnterpriseRoleService
{
    /** @return list<string> */
    public static function slugs(): array
    {
        return array_keys(config('enterprise_roles.labels', []));
    }

    public static function seedForOrganization(int $organizationId): void
    {
        PermissionService::seedNamesForOrganization($organizationId);

        $permissionIds = Permission::where('organization_id', $organizationId)
            ->pluck('id', 'name');

        foreach (config('enterprise_roles.labels', []) as $slug => $label) {
            $role = Role::updateOrCreate(
                ['organization_id' => $organizationId, 'slug' => $slug],
                [
                    'name' => $label,
                    'description' => 'System enterprise role: '.$label,
                ]
            );

            $names = config('enterprise_roles.'.$slug, []);
            if (in_array('*', $names, true)) {
                $role->permissions()->sync($permissionIds->values());
            } else {
                $role->permissions()->sync(
                    collect($names)
                        ->map(fn (string $name) => $permissionIds->get($name))
                        ->filter()
                        ->values()
                );
            }
        }
    }

    public function userHasRoleSlug(User $user, int $organizationId, string $slug): bool
    {
        return $user->roles()
            ->wherePivot('organization_id', $organizationId)
            ->where('roles.slug', $slug)
            ->exists();
    }

    public function canAssignRole(User $actor, int $organizationId, Role $targetRole): bool
    {
        $privileged = config('enterprise_roles.privileged_slugs', ['owner', 'admin']);
        $slug = $targetRole->slug;

        if ($slug === null || ! in_array($slug, $privileged, true)) {
            return true;
        }

        if ($this->userHasRoleSlug($actor, $organizationId, 'owner')) {
            return true;
        }

        if ($this->userHasRoleSlug($actor, $organizationId, 'admin')) {
            return $slug !== 'owner';
        }

        $membership = $actor->organizations()->where('organizations.id', $organizationId)->first();
        if ($membership && ($membership->pivot->role ?? 'member') === 'admin' && $slug !== 'owner') {
            return true;
        }

        return false;
    }

    public function assignDefaultOwner(User $user, Organization $organization): void
    {
        self::seedForOrganization($organization->id);

        $ownerRole = Role::where('organization_id', $organization->id)
            ->where('slug', 'owner')
            ->first();

        if ($ownerRole) {
            $user->roles()->syncWithoutDetaching([
                $ownerRole->id => ['organization_id' => $organization->id],
            ]);
        }
    }

    public function isSystemRole(Role $role): bool
    {
        return $role->slug !== null
            && array_key_exists($role->slug, config('enterprise_roles.labels', []));
    }

    public function isCatalogPermission(string $name): bool
    {
        return in_array($name, config('permissions', []), true);
    }

    public function userIsOwner(User $user, int $organizationId): bool
    {
        return $this->userHasRoleSlug($user, $organizationId, 'owner');
    }

    public function countOwners(int $organizationId): int
    {
        return User::query()
            ->whereHas('roles', function ($query) use ($organizationId): void {
                $query->where('roles.organization_id', $organizationId)
                    ->where('roles.slug', 'owner')
                    ->where('role_user.organization_id', $organizationId);
            })
            ->whereHas('organizations', function ($query) use ($organizationId): void {
                $query->where('organizations.id', $organizationId);
            })
            ->count();
    }
}
