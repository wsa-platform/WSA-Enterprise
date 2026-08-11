<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HealthController
{
    public function live(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'service' => 'wsa-enterprise',
        ]);
    }

    public function ready(): JsonResponse
    {
        $checks = [
            'database' => false,
            'cache' => false,
        ];

        try {
            DB::select('select 1');
            $checks['database'] = true;
        } catch (\Throwable) {
        }

        try {
            $key = 'healthcheck:' . bin2hex(random_bytes(8));
            Cache::put($key, 'ok', 10);
            $checks['cache'] = Cache::get($key) === 'ok';
            Cache::forget($key);
        } catch (\Throwable) {
        }

        $healthy = ! in_array(false, $checks, true);

        return response()->json([
            'status' => $healthy ? 'ok' : 'degraded',
            'service' => 'wsa-enterprise',
            'checks' => $checks,
        ], $healthy ? 200 : 503);
    }
}
