<?php

namespace Tests\Unit\Support;

use App\Support\ScientificHttp;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ScientificHttpRateLimitBudgetTest extends TestCase
{
    public function test_huge_retry_after_is_not_affordable_inside_search_budget(): void
    {
        Http::fake([
            'example.test/*' => Http::response(['error' => 'rate'], 429, ['Retry-After' => '8944']),
        ]);
        $response = Http::get('https://example.test/works');

        $this->assertSame(8944.0, ScientificHttp::retryAfterHeaderSeconds($response));
        $this->assertFalse(ScientificHttp::canAffordRetry(45.0, $response, 1, 15));
        $this->assertSame(8.0, ScientificHttp::retryDelaySeconds($response, 1));
    }

    public function test_zero_retry_after_still_allows_bounded_retries(): void
    {
        Http::fake([
            'example.test/*' => Http::response(['error' => 'rate'], 429, ['Retry-After' => '0']),
        ]);
        $response = Http::get('https://example.test/works');

        $this->assertSame(0.0, ScientificHttp::retryAfterHeaderSeconds($response));
        $this->assertTrue(ScientificHttp::canAffordRetry(45.0, $response, 1, 15));
        $this->assertTrue(ScientificHttp::canAffordRetry(null, $response, 1, 15));
    }

    public function test_http_timeout_is_capped_to_remaining_budget(): void
    {
        $this->assertSame(15, ScientificHttp::timeoutSeconds());
        $this->assertSame(4, ScientificHttp::timeoutSeconds(4.9));
        $this->assertSame(1, ScientificHttp::timeoutSeconds(0.4));
    }
}
