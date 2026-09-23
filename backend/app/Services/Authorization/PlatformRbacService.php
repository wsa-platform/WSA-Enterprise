<?php

namespace App\Services\Authorization;

use App\Models\PlatformPermission;
use App\Models\PlatformRole;
use App\Models\User;

class PlatformRbacService
{
    /** @return list<string> */
    public static function permissionCatalog(): array
    {
        return config('platform_rbac.permissions', []);
    }

    public function bootstrapCatalog(): void
    {
        foreach (self::permissionCatalog() as $name) {
            PlatformPermission::query()->updateOrCreate(
                ['name' => $name],
                ['description' => 'Platform permission: '.$name],
            );
        }

        $permissionIds = PlatformPermission::query()->pluck('id', 'name');

        foreach (config('platform_rbac.roles', []) as $slug => $definition) {
            $role = PlatformRole::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $definition['name'] ?? $slug,
                    'description' => $definition['description'] ?? null,
                ],
            );

            $names = $definition['permissions'] ?? [];
            if ($names === '*' || (is_array($names) && in_array('*', $names, true))) {
                $role->permissions()->sync($permissionIds->values());

                continue;
            }

            $role->permissions()->sync(
                collect($names)
                    ->map(fn (string $name) => $permissionIds->get($name))
                    ->filter()
                    ->values()
            );
        }
    }

    public function grantPlatformAdministrator(User $user): void
    {
        $this->bootstrapCatalog();

        $user->forceFill(['is_platform_administrator' => true])->save();

        $role = PlatformRole::query()->where('slug', 'platform_administrator')->first();
        if ($role !== null) {
            $user->platformRoles()->syncWithoutDetaching([$role->id]);
        }
    }

    public function grantPlatformRole(User $user, string $slug): void
    {
        $this->bootstrapCatalog();

        $role = PlatformRole::query()->where('slug', $slug)->firstOrFail();
        $user->platformRoles()->syncWithoutDetaching([$role->id]);
    }

    /** @param  list<int>  $permissionIds */
    public function createRole(string $slug, string $name, ?string $description, array $permissionIds = []): PlatformRole
    {
        $this->bootstrapCatalog();

        $role = PlatformRole::query()->create([
            'slug' => $slug,
            'name' => $name,
            'description' => $description,
        ]);

        if ($permissionIds !== []) {
            $role->permissions()->sync($permissionIds);
        }

        return $role->load('permissions:id,name');
    }

    /** @param  list<int>|null  $permissionIds */
    public function updateRole(PlatformRole $role, array $attributes, ?array $permissionIds = null): PlatformRole
    {
        $this->bootstrapCatalog();

        $payload = [];
        if (isset($attributes['name'])) {
            $payload['name'] = $attributes['name'];
        }
        if (array_key_exists('description', $attributes)) {
            $payload['description'] = $attributes['description'];
        }
        if (isset($attributes['slug']) && ! in_array($role->slug, ['platform_administrator', 'platform_auditor'], true)) {
            $payload['slug'] = $attributes['slug'];
        }
        if ($payload !== []) {
            $role->update($payload);
        }
        if ($permissionIds !== null) {
            $role->permissions()->sync($permissionIds);
        }

        return $role->fresh()->load('permissions:id,name');
    }

    /** @return list<string> */
    public function permissionsFor(User $user): array
    {
        if (! $user->isPlatformAdministrator()) {
            return [];
        }

        return $user->platformRoles()
            ->with('permissions')
            ->get()
            ->flatMap(fn (PlatformRole $role) => $role->permissions->pluck('name'))
            ->unique()
            ->values()
            ->all();
    }
}
