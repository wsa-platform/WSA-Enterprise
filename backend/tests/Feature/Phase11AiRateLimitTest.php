<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase11AiRateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        config(['ai.rate_limit_per_minute' => 5]);
    }

    public function test_ai_endpoints_are_rate_limited_per_organization(): void
    {
        $organization = Organization::first();
        $admin = User::where('email', 'admin@wsa.test')->first();
        $token = $admin->createToken('test')->plainTextToken;
        $headers = [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
        ];

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/ai/requests', [
                'request_type' => 'library_summary',
                'input' => ['content' => 'Rate limit test '.$i],
            ], $headers)->assertSuccessful();
        }

        $this->postJson('/api/v1/ai/requests', [
            'request_type' => 'library_summary',
            'input' => ['content' => 'Should be limited'],
        ], $headers)->assertStatus(429);
    }
}
