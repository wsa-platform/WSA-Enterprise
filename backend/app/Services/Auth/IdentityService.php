<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Models\UserIdentity;
use Illuminate\Support\Facades\DB;

class IdentityService
{
    public function ensureEmailIdentity(User $user): UserIdentity
    {
        return UserIdentity::firstOrCreate(
            [
                'provider' => UserIdentity::PROVIDER_EMAIL,
                'provider_id' => strtolower($user->email),
            ],
            [
                'user_id' => $user->id,
                'email' => $user->email,
                'verified_at' => $user->email_verified_at,
            ],
        );
    }

    /** @return \Illuminate\Support\Collection<int, UserIdentity> */
    public function listForUser(User $user)
    {
        return UserIdentity::where('user_id', $user->id)->orderBy('provider')->get();
    }

    /** @param  array<string, mixed>  $metadata */
    public function link(User $user, string $provider, string $providerId, ?string $email = null, ?string $phone = null, array $metadata = []): UserIdentity
    {
        return DB::transaction(function () use ($user, $provider, $providerId, $email, $phone, $metadata): UserIdentity {
            $existing = UserIdentity::where('provider', $provider)->where('provider_id', $providerId)->first();
            abort_if($existing !== null && $existing->user_id !== $user->id, 422, 'Identity already linked to another account.');

            return UserIdentity::updateOrCreate(
                ['user_id' => $user->id, 'provider' => $provider],
                [
                    'provider_id' => $providerId,
                    'email' => $email,
                    'phone' => $phone,
                    'verified_at' => now(),
                    'metadata' => $metadata,
                ],
            );
        });
    }

    public function unlink(User $user, int $identityId): void
    {
        $identity = UserIdentity::where('user_id', $user->id)->whereKey($identityId)->firstOrFail();
        abort_if($identity->provider === UserIdentity::PROVIDER_EMAIL && $user->identities()->count() <= 1, 422, 'Cannot remove sole login method.');

        $identity->delete();
    }
}
