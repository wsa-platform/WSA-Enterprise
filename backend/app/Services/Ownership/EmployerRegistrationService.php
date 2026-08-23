<?php

namespace App\Services\Ownership;

use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Services\Authorization\EnterpriseRoleService;
use App\Services\Authorization\PermissionService;
use App\Services\Recruitment\RecruitmentRoleService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EmployerRegistrationService
{
    public function __construct(
        private RecruitmentRoleService $recruitmentRoles,
        private PermissionService $permissions,
    ) {}

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

        $organization = $this->provisionEmployerWorkspace($user);

        return [
            'user' => $user,
            'organization' => $organization,
        ];
    }

    /**
     * Assign the employer employment-service role to an existing platform account.
     */
    public function activate(User $user): Organization
    {
        $this->recruitmentRoles->assertCanRegisterEmployer($user);

        $existing = $this->employerOrganization($user);
        if ($existing !== null) {
            return $existing;
        }

        return $this->provisionEmployerWorkspace($user);
    }

    private function provisionEmployerWorkspace(User $user): Organization
    {
        $organization = $user->organizations()->wherePivot('is_active', true)->first();

        if ($organization === null) {
            $organization = Organization::create([
                'name' => $user->name."'s Workspace",
                'slug' => $this->uniqueWorkspaceSlug($user->email, $user->id),
            ]);

            $organization->members()->attach($user->id, [
                'role' => 'member',
                'is_active' => true,
            ]);
        }

        EnterpriseRoleService::seedForOrganization($organization->id);

        $managerRole = Role::query()
            ->where('organization_id', $organization->id)
            ->where('slug', 'manager')
            ->first();

        if ($managerRole !== null) {
            $user->roles()->syncWithoutDetaching([
                $managerRole->id => ['organization_id' => $organization->id],
            ]);
        }

        $this->permissions->forget($user, (int) $organization->id);

        return $organization;
    }

    private function employerOrganization(User $user): ?Organization
    {
        foreach ($user->organizations()->wherePivot('is_active', true)->get() as $organization) {
            if ($this->permissions->userCan($user, (int) $organization->id, 'jobs.manage')) {
                return $organization;
            }
        }

        return null;
    }

    private function uniqueWorkspaceSlug(string $email, int $userId): string
    {
        $base = Str::slug(Str::before($email, '@')) ?: 'employer';

        return Str::limit($base, 40, '').'-'.$userId;
    }
}
