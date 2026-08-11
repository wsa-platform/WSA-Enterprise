<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Monitoring\HealthCheckService;
use App\Services\Monitoring\MonitoringEventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HealthController extends Controller
{
    public function __construct(
        private HealthCheckService $healthChecks,
        private MonitoringEventService $monitoringEvents,
    ) {}

    public function live(): JsonResponse
    {
        return response()->json($this->healthChecks->live());
    }

    public function ready(Request $request): JsonResponse
    {
        $result = $this->healthChecks->ready();
        $healthy = ($result['status'] ?? 'degraded') === 'ok';

        if (! $healthy && config('monitoring.record_incidents_on_ready_failure', true)) {
            $this->monitoringEvents->recordReadinessFailures($result['checks'] ?? [], $request);
        }

        return response()->json($result, $healthy ? 200 : 503);
    }

    /**
     * Backward-compatible health endpoint used by smoke checks and legacy clients.
     */
    public function legacy(Request $request): JsonResponse
    {
        $result = $this->healthChecks->ready();
        $healthy = ($result['status'] ?? 'degraded') === 'ok';

        if (! $healthy && config('monitoring.record_incidents_on_ready_failure', true)) {
            $this->monitoringEvents->recordReadinessFailures($result['checks'] ?? [], $request);
        }

        if ($healthy) {
            return response()->json(['status' => 'ok']);
        }

        return response()->json([
            'status' => 'degraded',
            'checks' => $result['checks'] ?? [],
        ], 503);
    }
}
