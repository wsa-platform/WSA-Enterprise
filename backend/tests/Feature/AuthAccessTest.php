<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_token_for_valid_credentials(): void
    {
        $organization = Organization::create(['name' => 'Tenant', 'slug' => 'tenant']);
        $user = User::create(['name' => 'User', 'email' => 'user@wsa.test', 'password' => Hash::make('password')]);
        $organization->members()->attach($user->id, ['role' => 'admin']);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'user@wsa.test',
            'password' => 'password',
            'device_name' => 'test',
        ])->assertOk()->assertJsonStructure(['token', 'user']);
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        User::create(['name' => 'User', 'email' => 'user@wsa.test', 'password' => Hash::make('password')]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'user@wsa.test',
            'password' => 'wrong-password',
        ])->assertUnprocessable();
    }

    public function test_protected_routes_require_authentication(): void
    {
        $this->getJson('/api/v1/dashboard')->assertUnauthorized();
    }
}
