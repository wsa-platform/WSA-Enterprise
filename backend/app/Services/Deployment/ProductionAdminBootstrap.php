<?php

namespace App\Services\Deployment;

use App\Models\Organization;
use App\Models\User;
use App\Services\Authorization\EnterpriseRoleService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ProductionAdminBootstrap
{
    public function __construct(private EnterpriseRoleService $enterpriseRoleService) {}

    public function shouldRun(): bool
    {
        if (! $this->bootstrapEnabled()) {
            return false;
        }

        return $this->adminEmail() !== '' && $this->adminPassword() !== '';
    }

    public function adminEmail(): string
    {
        return (string) (env('ADMIN_EMAIL') ?: config('deployment.admin.email', 'admin@wsa.test'));
    }

    public function adminPassword(): string
    {
        return (string) (env('ADMIN_PASSWORD') ?? '');
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

        $email = $this->adminEmail();
        $password = $this->adminPassword();
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
                'password' => Hash::make($password),
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

    public function adminExists(): bool
    {
        $email = $this->adminEmail();

        return $email !== '' && User::query()->where('email', $email)->exists();
    }

    private function bootstrapEnabled(): bool
    {
        if (env('ADMIN_BOOTSTRAP_ENABLED') !== null) {
            return filter_var(env('ADMIN_BOOTSTRAP_ENABLED'), FILTER_VALIDATE_BOOL);
        }

        return (bool) config('deployment.admin.enabled', env('APP_ENV') === 'production');
    }
}
