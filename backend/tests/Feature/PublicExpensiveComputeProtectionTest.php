<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * Phase 8A-1 / U8.3 — global public cap + specialized expensive/browse buckets.
 *
 * @group security
 */
class PublicExpensiveComputeProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Organization::create([
            'name' => 'WSA Demo',
            'slug' => 'wsa-demo',
            'is_active' => true,
        ]);

        Http::fake([
            'api.openalex.org/works*' => Http::response(['results' => []], 200),
            'api.crossref.org/works*' => Http::response(['message' => ['items' => []]], 200),
            'api.semanticscholar.org/*' => Http::response(['data' => []], 200),
            'faostatservices.fao.org/*' => Http::response(['message' => 'unavailable'], 503),
            'fenixservices.fao.org/*' => Http::response(['message' => 'unavailable'], 503),
        ]);

        $this->clearPublicLimiters();
    }

    /**
     * Clear the same cache keys Laravel's ThrottleRequests middleware uses for named limiters.
     *
     * Named limiters store hits under md5($limiterName.$limit->key) when
     * ThrottleRequests::$shouldHashKeys is true (Laravel default), where $limit->key
     * is the value from Limit::by() (e.g. "public-expensive-compute:{ip}").
     * Clearing the raw by()-string alone does not reset state.
     */
    private function clearPublicLimiters(): void
    {
        $ip = $this->resolvePublicLimiterIp();

        foreach (['public-global', 'public-expensive-compute', 'public-browse'] as $limiterName) {
            $byKey = $limiterName.':'.$ip;
            // Mirror Illuminate\Routing\Middleware\ThrottleRequests::handleRequestUsingNamedLimiter
            $storageKey = md5($limiterName.$byKey);
            RateLimiter::clear($storageKey);
        }
    }

    /**
     * Prefer the IP from the last HTTP test request; otherwise probe a synthetic request
     * matching MakesHttpRequests defaults (not hard-coded Docker assumptions alone).
     */
    private function resolvePublicLimiterIp(): string
    {
        if ($this->app->bound('request')) {
            $fromApp = trim((string) $this->app['request']->ip());
            if ($fromApp !== '') {
                return $fromApp;
            }
        }

        $probe = \Illuminate\Http\Request::create(
            '/api/v1/public/research-agent/plan',
            'POST',
            [],
            [],
            [],
            $this->transformHeadersToServerVars([])
        );

        $fromProbe = trim((string) $probe->ip());

        return $fromProbe !== '' ? $fromProbe : '127.0.0.1';
    }

    /** TEST 1 — Global public cap blocks combined public traffic. */
    public function test_global_public_cap_blocks_after_combined_budget_exhausted(): void
    {
        config([
            'wsa.public_global_per_minute' => 2,
            'wsa.public_expensive_compute_per_minute' => 60,
            'wsa.public_browse_per_minute' => 60,
        ]);

        $this->postJson('/api/v1/public/research-agent/plan', [
            'query' => 'wheat irrigation arid agriculture',
        ])->assertSuccessful();

        $this->getJson('/api/v1/public/field-crops/taxonomy')->assertSuccessful();

        $blocked = $this->postJson('/api/v1/public/research-agent/plan', [
            'query' => 'maize irrigation arid agriculture',
        ]);
        $blocked->assertStatus(429);
        $blocked->assertJsonPath('error.code', 'public_global_rate_limited');
    }

    /** TEST 2 — Cross-expensive-endpoint sharing of the specialized expensive bucket. */
    public function test_expensive_endpoints_share_named_expensive_bucket(): void
    {
        config([
            'wsa.public_global_per_minute' => 60,
            'wsa.public_expensive_compute_per_minute' => 1,
            'wsa.public_browse_per_minute' => 60,
        ]);

        $this->postJson('/api/v1/public/research-agent/plan', [
            'query' => 'wheat irrigation arid agriculture',
        ])->assertSuccessful();

        $this->postJson('/api/v1/public/research-agent/search', [
            'query' => 'tomato heat stress greenhouse',
        ])->assertStatus(429)->assertJsonPath('error.code', 'public_expensive_compute_rate_limited');

        $this->clearPublicLimiters();
        config(['wsa.public_expensive_compute_per_minute' => 1]);

        $this->postJson('/api/v1/public/research-agent/search', [
            'query' => 'tomato heat stress greenhouse',
        ])->assertSuccessful();
        $this->postJson('/api/v1/public/research-agent/validate', [
            'query' => 'tomato heat stress greenhouse',
        ])->assertStatus(429)->assertJsonPath('error.code', 'public_expensive_compute_rate_limited');
    }

    /** TEST 3 — Expensive + browse cannot bypass the global public cap (regression for 60+60). */
    public function test_expensive_plus_browse_cannot_bypass_global_cap(): void
    {
        config([
            'wsa.public_global_per_minute' => 2,
            'wsa.public_expensive_compute_per_minute' => 60,
            'wsa.public_browse_per_minute' => 60,
        ]);

        $this->postJson('/api/v1/public/research-agent/plan', [
            'query' => 'wheat irrigation arid agriculture',
        ])->assertSuccessful();

        $this->getJson('/api/v1/public/market/categories')->assertSuccessful();

        $this->getJson('/api/v1/public/field-crops/taxonomy')
            ->assertStatus(429)
            ->assertJsonPath('error.code', 'public_global_rate_limited');
    }

    /** TEST 4 — Specialized browse limiter still exists and fires independently of expensive. */
    public function test_browse_named_bucket_still_enforced(): void
    {
        config([
            'wsa.public_global_per_minute' => 60,
            'wsa.public_expensive_compute_per_minute' => 60,
            'wsa.public_browse_per_minute' => 1,
        ]);

        $this->getJson('/api/v1/public/field-crops/taxonomy')->assertSuccessful();
        $this->getJson('/api/v1/public/market/units')->assertStatus(429);
    }

    /** TEST 5 — Authenticated routes are not attached to the public global limiter. */
    public function test_authenticated_farm_list_not_blocked_by_public_global_cap(): void
    {
        config([
            'wsa.public_global_per_minute' => 1,
            'wsa.public_expensive_compute_per_minute' => 60,
            'wsa.public_browse_per_minute' => 60,
        ]);

        $this->postJson('/api/v1/public/research-agent/plan', [
            'query' => 'wheat irrigation arid agriculture',
        ])->assertSuccessful();

        // Exhaust public global.
        $this->getJson('/api/v1/public/field-crops/taxonomy')
            ->assertStatus(429)
            ->assertJsonPath('error.code', 'public_global_rate_limited');

        $org = Organization::where('slug', 'wsa-demo')->firstOrFail();
        $user = User::factory()->create([
            'email' => 'u83-auth@wsa.test',
            'password' => bcrypt('secret'),
        ]);
        $user->organizations()->attach($org->id, ['role' => 'admin', 'is_active' => true]);
        $token = $user->createToken('u83-test')->plainTextToken;

        $authResponse = $this->getJson('/api/v1/farm/farms', [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $org->id,
        ]);

        $this->assertNotSame(
            429,
            $authResponse->status(),
            'Authenticated farm list must not receive public_global_rate_limited'
        );
        $this->assertNotSame(
            'public_global_rate_limited',
            $authResponse->json('error.code')
        );
    }

    /** TEST 6 — Health/live remain outside public limiters. */
    public function test_health_live_outside_public_limiters(): void
    {
        config([
            'wsa.public_global_per_minute' => 1,
            'wsa.public_expensive_compute_per_minute' => 1,
            'wsa.public_browse_per_minute' => 1,
        ]);

        $this->postJson('/api/v1/public/research-agent/plan', [
            'query' => 'wheat irrigation arid agriculture',
        ])->assertSuccessful();

        $this->getJson('/api/v1/health/live')->assertSuccessful();
    }

    /** TEST 7 — query and synthesize share the expensive named bucket. */
    public function test_query_and_synthesize_share_expensive_bucket(): void
    {
        config([
            'wsa.public_global_per_minute' => 60,
            'wsa.public_expensive_compute_per_minute' => 1,
            'wsa.public_browse_per_minute' => 60,
        ]);

        $this->postJson('/api/v1/public/research-agent/query', [
            'query' => 'wheat irrigation arid agriculture',
        ])->assertSuccessful();

        $this->postJson('/api/v1/public/research-agent/synthesize', [
            'query' => 'wheat irrigation arid agriculture',
        ])->assertStatus(429)->assertJsonPath('error.code', 'public_expensive_compute_rate_limited');
    }

    /** Bonus — farming-needs shares expensive bucket with plan. */
    public function test_farming_needs_shares_expensive_bucket(): void
    {
        config([
            'wsa.public_global_per_minute' => 60,
            'wsa.public_expensive_compute_per_minute' => 1,
            'wsa.public_browse_per_minute' => 60,
        ]);

        $this->postJson('/api/v1/public/research-agent/plan', [
            'query' => 'wheat irrigation arid agriculture',
        ])->assertSuccessful();

        $this->getJson('/api/v1/public/field-crops/farming-needs-profile?'.http_build_query([
            'selected_crop_id' => 'wheat',
            'selected_crop_name' => 'Wheat',
            'selected_category_id' => 'cereals',
            'selected_category_name' => 'Cereals',
        ]))->assertStatus(429)->assertJsonPath('error.code', 'public_expensive_compute_rate_limited');
    }
}
