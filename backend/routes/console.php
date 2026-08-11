<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

Schedule::call(function (): void {
    Cache::put('healthcheck:scheduler:last_run', now()->toIso8601String(), 3600);
})->everyMinute()->name('monitoring-scheduler-heartbeat');
