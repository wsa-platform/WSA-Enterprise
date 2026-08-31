<?php

use App\Jobs\AnalyzeMonitoringIncidentJob;
use App\Jobs\RecordSystemHealthChecksJob;
use App\Models\MonitoringEvent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

Schedule::call(function (): void {
    Cache::put('healthcheck:scheduler:last_run', now()->toIso8601String(), 3600);
})->everyMinute()->name('monitoring-scheduler-heartbeat');

Schedule::job(new RecordSystemHealthChecksJob)->everyFiveMinutes()->name('monitoring-health-snapshot');

Schedule::call(function (): void {
    MonitoringEvent::query()
        ->where('status', MonitoringEvent::STATUS_OPEN)
        ->where('lifecycle_stage', MonitoringEvent::STAGE_DETECTED)
        ->whereNotNull('organization_id')
        ->where('detected_at', '>=', now()->subHour())
        ->each(fn (MonitoringEvent $event) => AnalyzeMonitoringIncidentJob::dispatch($event->id));
})->everyTenMinutes()->name('monitoring-ai-analysis');
