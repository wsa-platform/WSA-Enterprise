<?php

namespace App\Services\Organization;

use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use App\Services\Authorization\PermissionCacheInvalidator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InvitationService
{
    public function __construct(private PermissionCacheInvalidator $permissionCache) {}

    public function create(
        Organization $organization,
        string $email,
        string $role,
        User $invitedBy,
    ): OrganizationInvitation {
        $email = strtolower(trim($email));

        abort_unless(in_array($role, ['admin', 'member'], true), 422, 'Invalid membership role.');

        $existingMember = User::where('email', $email)
            ->whereHas('organizations', fn ($query) => $query->whereKey($organization->id))
            ->exists();

        abort_if($existingMember, 422, 'This user is already a member of the organization.');

        OrganizationInvitation::query()
            ->where('organization_id', $organization->id)
            ->where('email', $email)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->delete();

        return OrganizationInvitation::create([
            'organization_id' => $organization->id,
            'email' => $email,
            'token' => hash('sha256', Str::random(64).microtime(true)),
            'role' => $role,
            'invited_by' => $invitedBy->id,
            'expires_at' => now()->addDays(7),
        ]);
    }

    /**
     * @return array{user: User, organization: Organization, token: string}
     */
    public function accept(string $token, ?string $name, string $password, string $deviceName = 'web'): array
    {
        $invitation = OrganizationInvitation::query()
            ->where('token', $token)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->first();

        if ($invitation === null) {
            throw ValidationException::withMessages([
                'token' => ['This invitation is invalid or has expired.'],
            ]);
        }

        $organization = $invitation->organization;
        $user = User::where('email', $invitation->email)->first();

        if ($user === null) {
            $resolvedName = trim((string) $name);
            if ($resolvedName === '') {
                throw ValidationException::withMessages([
                    'name' => ['Name is required for new accounts.'],
                ]);
            }

            $user = User::create([
                'name' => $resolvedName,
                'email' => $invitation->email,
                'password' => Hash::make($password),
            ]);
        } else {
            if (! Hash::check($password, $user->password)) {
                throw ValidationException::withMessages([
                    'password' => ['The provided password is incorrect for this existing account.'],
                ]);
            }
        }

        if (! $user->organizations()->whereKey($organization->id)->exists()) {
            $user->organizations()->attach($organization->id, [
                'role' => $invitation->role,
                'is_active' => true,
            ]);
        }

        $this->permissionCache->forgetUser($user, $organization->id);

        $invitation->update(['accepted_at' => now()]);

        return [
            'user' => $user,
            'organization' => $organization,
            'token' => $user->createToken($deviceName)->plainTextToken,
        ];
    }

    public function revoke(OrganizationInvitation $invitation): void
    {
        abort_if($invitation->accepted_at !== null, 422, 'Invitation has already been accepted.');
        $invitation->delete();
    }
}
