<?php

namespace App\Services\Ownership;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class UserGlobalOwnershipAuthorizer
{
    /** @param  array<string, mixed>  $payload */
    public function stripOwnerKeys(array $payload): array
    {
        foreach (config('user_global_ownership.forbidden_request_owner_keys', []) as $key) {
            unset($payload[$key]);
        }

        return $payload;
    }

    /** @param  array<string, mixed>  $payload */
    public function assignOwnerFromSession(array $payload, User $owner): array
    {
        $payload = $this->stripOwnerKeys($payload);
        $payload[config('user_global_ownership.owner_column', 'user_id')] = $owner->id;

        return $payload;
    }

    /** @param  Builder<Model>  $query */
    public function scopeOwnedByUser(Builder $query, User $user): Builder
    {
        return $query->where(config('user_global_ownership.owner_column', 'user_id'), $user->id);
    }

    public function assertOwnedByUser(User $user, Model $record): void
    {
        $column = config('user_global_ownership.owner_column', 'user_id');
        abort_unless(
            (int) $record->getAttribute($column) === $user->id,
            403,
            'You can only access resources that belong to your account.',
        );
    }
}
