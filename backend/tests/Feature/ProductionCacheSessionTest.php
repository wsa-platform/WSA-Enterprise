<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProductionCacheSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_works_with_file_cache_and_session_drivers(): void
    {
        config([
            'cache.default' => 'file',
            'session.driver' => 'file',
        ]);

        $organization = Organization::create(['name' => 'Tenant', 'slug' => 'tenant']);
        $user = User::create([
            'name' => 'User',
            'email' => 'user@wsa.test',
            'password' => Hash::make('password'),
        ]);
        $organization->members()->attach($user->id, ['role' => 'admin']);

        Cache::put('login-flow:probe', 'ok', 60);
        $this->assertSame('ok', Cache::get('login-flow:probe'));

        $this->postJson('/api/v1/auth/login', [
            'email' => 'user@wsa.test',
            'password' => 'password',
            'device_name' => 'test',
        ])->assertOk()->assertJsonStructure(['token', 'user']);
    }

    public function test_file_cache_does_not_query_cache_table(): void
    {
        config(['cache.default' => 'file']);

        Cache::put('health:cache-probe', 'ready', 60);

        $this->assertSame('ready', Cache::get('health:cache-probe'));
        $this->assertFalse(Cache::supportsTags());
    }
}
