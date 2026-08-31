<?php

namespace App\Jobs;

use App\Models\MonitoringEvent;
use App\Models\SystemHealthCheck;
use App\Services\Monitoring\HealthCheckService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RecordSystemHealthChecksJob implements ShouldQueue
{
    use Queueable;

    public function handle(HealthCheckService $healthChecks): void
    {
        $checks = $healthChecks->runAllChecks();
        $now = now();

        foreach ($checks as $component => $check) {
            SystemHealthCheck::create([
                'component' => $component,
                'status' => ($check['healthy'] ?? false) ? 'healthy' : 'failed',
                'message' => $check['message'] ?? null,
                'checked_at' => $now,
            ]);

            if (($check['healthy'] ?? false) !== true) {
                MonitoringEvent::create([
                    'organization_id' => null,
                    'component' => $component,
                    'status' => MonitoringEvent::STATUS_OPEN,
                    'severity' => 'warning',
                    'lifecycle_stage' => MonitoringEvent::STAGE_DETECTED,
                    'detected_at' => $now,
                    'details' => ['message' => $check['message'] ?? 'Health check failed'],
                ]);
            }
        }
    }
}
