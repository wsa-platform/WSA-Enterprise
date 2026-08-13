<?php

namespace App\Services\Deployment;

use App\Models\Organization;
use App\Models\User;
use App\Services\Authorization\EnterpriseRoleService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ProductionAdminBootstrap
{
    public function __construct(private EnterpriseRoleService $enterpriseRoleService) {}

    public function shouldRun(): bool
    {
        if (! config('deployment.admin.enabled')) {
            return false;
        }

        $email = (string) config('deployment.admin.email');
        $password = (string) config('deployment.admin.password');

        return $email !== '' && $password !== '';
    }

    /**
     * @return array{email: string, organization_slug: string, created: bool}
     */
    public function run(): array
    {
        if (! $this->shouldRun()) {
            throw ValidationException::withMessages([
                'admin' => ['Admin bootstrap is disabled or ADMIN_PASSWORD is not configured.'],
            ]);
        }

        $email = (string) config('deployment.admin.email');
        $password = (string) config('deployment.admin.password');
        $minLength = (int) config('deployment.admin.minimum_password_length', 12);

        Validator::make(
            ['email' => $email, 'password' => $password],
            [
                'email' => ['required', 'email', 'max:255'],
                'password' => ['required', 'string', 'min:'.$minLength],
            ],
        )->validate();

        $existing = User::query()->where('email', $email)->exists();

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => (string) config('deployment.admin.name'),
                'password' => $password,
            ],
        );

        $organization = Organization::updateOrCreate(
            ['slug' => (string) config('deployment.admin.organization_slug')],
            ['name' => (string) config('deployment.admin.organization_name')],
        );

        $organization->members()->syncWithoutDetaching([
            $user->id => ['role' => 'admin', 'is_active' => true],
        ]);

        $this->enterpriseRoleService->assignDefaultOwner($user, $organization);

        return [
            'email' => $user->email,
            'organization_slug' => $organization->slug,
            'created' => ! $existing,
        ];
    }
}
