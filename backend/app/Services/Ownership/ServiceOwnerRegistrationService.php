<?php

namespace App\Services\Ownership;

use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Services\Authorization\EnterpriseRoleService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ServiceOwnerRegistrationService
{
    public function __construct(private EnterpriseRoleService $enterpriseRoles) {}

    /**
     * @param  array{name: string, email: string, password: string}  $data
     * @return array{user: User, organization: Organization}
     */
    public function register(array $data): array
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $organization = Organization::create([
            'name' => $data['name']."'s Workspace",
            'slug' => $this->uniqueWorkspaceSlug($data['email'], $user->id),
        ]);

        $organization->members()->attach($user->id, [
            'role' => 'member',
            'is_active' => true,
        ]);

        EnterpriseRoleService::seedForOrganization($organization->id);

        $memberRole = Role::query()
            ->where('organization_id', $organization->id)
            ->where('slug', 'member')
            ->first();

        if ($memberRole !== null) {
            $user->roles()->syncWithoutDetaching([
                $memberRole->id => ['organization_id' => $organization->id],
            ]);
        }

        return [
            'user' => $user,
            'organization' => $organization,
        ];
    }

    private function uniqueWorkspaceSlug(string $email, int $userId): string
    {
        $base = Str::slug(Str::before($email, '@')) ?: 'workspace';

        return Str::limit($base, 40, '').'-'.$userId;
    }
}
