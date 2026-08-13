<?php

namespace App\Services\Ownership;

use App\Models\User;
use App\Services\Authorization\PermissionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class ServiceOwnershipAuthorizer
{
    public function __construct(private PermissionService $permissions) {}

    /** @param  array<string, mixed>  $payload */
    public function stripOwnerKeys(array $payload): array
    {
        foreach (config('service_ownership.forbidden_request_owner_keys', []) as $key) {
            unset($payload[$key]);
        }

        return $payload;
    }

    /** @param  array<string, mixed>  $payload */
    public function assignOwnerFromSession(array $payload, User $owner): array
    {
        $payload = $this->stripOwnerKeys($payload);
        $payload[config('service_ownership.owner_column')] = $owner->id;

        return $payload;
    }

    public function canSupervise(User $user, int $organizationId): bool
    {
        return $this->permissions->userCan(
            $user,
            $organizationId,
            config('service_ownership.supervise_permission', 'services.supervise'),
        );
    }

    /** @param  Builder<Model>  $query */
    public function scopeAccessibleServices(Builder $query, User $user, int $organizationId): Builder
    {
        if ($this->canSupervise($user, $organizationId)) {
            return $query;
        }

        return $query->where(config('service_ownership.owner_column'), $user->id);
    }

    public function authorizeManage(User $user, Model $record, int $organizationId, string $modulePermission): void
    {
        abort_unless(
            $this->permissions->userCan($user, $organizationId, $modulePermission),
            403,
            'This action is unauthorized.',
        );

        $this->assertOwnedByUser($user, $record, $organizationId);
    }

    public function assertOwnedByUser(User $user, Model $record, int $organizationId): void
    {
        abort_unless(
            (int) $record->getAttribute('organization_id') === $organizationId,
            404,
        );

        if ($this->canSupervise($user, $organizationId)) {
            return;
        }

        $ownerColumn = config('service_ownership.owner_column');
        if (! Schema::hasColumn($record->getTable(), $ownerColumn)) {
            return;
        }

        $ownerId = $record->getAttribute($ownerColumn);
        abort_unless(
            $ownerId !== null && (int) $ownerId === $user->id,
            403,
            $ownerId === null
                ? 'This legacy service has no assigned owner. Contact an administrator.'
                : 'You can only manage services that you own.',
        );
    }

    /** @param  class-string<Model>  $modelClass @param  array<string, mixed>  $attributes */
    public function createOwnedModel(string $modelClass, array $attributes, User $owner): Model
    {
        return $modelClass::unguarded(fn () => $modelClass::create(
            $this->assignOwnerFromSession($attributes, $owner),
        ));
    }

    /** @param  array<string, mixed>  $data @param  array<string, class-string<Model>>  $relations */
    public function assertAccessibleRelations(User $user, int $organizationId, array $data, array $relations): void
    {
        if ($this->canSupervise($user, $organizationId)) {
            return;
        }

        $ownerColumn = config('service_ownership.owner_column');

        foreach ($relations as $field => $modelClass) {
            $id = $data[$field] ?? null;

            if ($id === null) {
                continue;
            }

            $record = $modelClass::query()
                ->where('organization_id', $organizationId)
                ->find($id);

            abort_unless($record !== null, 422, "The selected {$field} is invalid for this organization.");

            if (! Schema::hasColumn($record->getTable(), $ownerColumn)) {
                continue;
            }

            $ownerId = $record->getAttribute($ownerColumn);
            abort_unless(
                $ownerId !== null && (int) $ownerId === $user->id,
                403,
                "You can only use {$field} records that you own.",
            );
        }
    }
}
