<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Services\Deployment\ProductionAdminBootstrap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProductionAdminBootstrapTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'deployment.admin.enabled' => true,
            'deployment.admin.email' => 'bootstrap-admin@wsa.test',
            'deployment.admin.password' => 'bootstrap-password-12',
            'deployment.admin.name' => 'Bootstrap Admin',
            'deployment.admin.organization_name' => 'Bootstrap Org',
            'deployment.admin.organization_slug' => 'bootstrap-org',
            'deployment.admin.minimum_password_length' => 12,
        ]);
    }

    public function test_bootstrap_creates_admin_with_hashed_password_and_organization(): void
    {
        $result = app(ProductionAdminBootstrap::class)->run();

        $this->assertTrue($result['created']);
        $this->assertSame('bootstrap-admin@wsa.test', $result['email']);

        $user = User::where('email', 'bootstrap-admin@wsa.test')->firstOrFail();
        $this->assertTrue(Hash::check('bootstrap-password-12', $user->password));
        $this->assertSame('Bootstrap Admin', $user->name);

        $organization = Organization::where('slug', 'bootstrap-org')->firstOrFail();
        $this->assertTrue($organization->members()
            ->where('users.id', $user->id)
            ->wherePivot('role', 'admin')
            ->wherePivot('is_active', true)
            ->exists());
    }

    public function test_bootstrap_is_idempotent_and_updates_password(): void
    {
        app(ProductionAdminBootstrap::class)->run();

        config(['deployment.admin.password' => 'updated-password-99']);
        $result = app(ProductionAdminBootstrap::class)->run();

        $this->assertFalse($result['created']);
        $this->assertSame(1, User::where('email', 'bootstrap-admin@wsa.test')->count());
        $this->assertTrue(Hash::check('updated-password-99', User::firstWhere('email', 'bootstrap-admin@wsa.test')->password));
    }

    public function test_bootstrap_skips_when_password_is_not_configured(): void
    {
        config(['deployment.admin.password' => null]);

        $this->assertFalse(app(ProductionAdminBootstrap::class)->shouldRun());

        Artisan::call('deploy:bootstrap-admin');
        $this->assertStringContainsString('skipped', Artisan::output());
        $this->assertSame(0, User::count());
    }

    public function test_login_succeeds_after_admin_bootstrap(): void
    {
        app(ProductionAdminBootstrap::class)->run();

        $this->postJson('/api/v1/auth/login', [
            'email' => 'bootstrap-admin@wsa.test',
            'password' => 'bootstrap-password-12',
            'device_name' => 'test',
        ])->assertOk()->assertJsonStructure(['token', 'user']);
    }
}
