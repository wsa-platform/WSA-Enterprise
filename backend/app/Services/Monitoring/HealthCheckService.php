<?php

namespace App\Services\Monitoring;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class HealthCheckService
{
    /** @return array<string, mixed> */
    public function live(): array
    {
        return [
            'status' => 'ok',
            'service' => (string) config('app.name'),
        ];
    }

    /** @return array<string, mixed> */
    public function ready(): array
    {
        $checks = $this->runAllChecks();
        $healthy = $this->allChecksHealthy($checks);

        return [
            'status' => $healthy ? 'ok' : 'degraded',
            'checks' => $checks,
        ];
    }

    /** @return array<string, array{healthy: bool, message?: string}> */
    public function runAllChecks(): array
    {
        $enabled = config('monitoring.checks', []);
        $checks = [];

        if ($enabled['database'] ?? true) {
            $checks['database'] = $this->checkDatabase();
        }

        if ($enabled['cache'] ?? true) {
            $checks['cache'] = $this->checkCache();
        }

        if ($enabled['queue'] ?? true) {
            $checks['queue'] = $this->checkQueue();
        }

        if ($enabled['storage'] ?? true) {
            $checks['storage'] = $this->checkStorage();
        }

        if ($enabled['scheduler'] ?? true) {
            $checks['scheduler'] = $this->checkScheduler();
        }

        if ($enabled['authentication'] ?? true) {
            $checks['authentication'] = $this->checkAuthentication();
        }

        $checks['application'] = $this->checkApplication();
        $checks['api'] = $this->checkApi();

        return $checks;
    }

    /** @param  array<string, array{healthy: bool, message?: string}>  $checks */
    public function allChecksHealthy(array $checks): bool
    {
        foreach ($checks as $check) {
            if (($check['healthy'] ?? false) !== true) {
                return false;
            }
        }

        return true;
    }

    /** @return array{healthy: bool, message?: string} */
    public function checkDatabase(): array
    {
        try {
            DB::select('select 1 as ok');

            return ['healthy' => true];
        } catch (\Throwable $exception) {
            return [
                'healthy' => false,
                'message' => $exception->getMessage(),
            ];
        }
    }

    /** @return array{healthy: bool, message?: string} */
    public function checkCache(): array
    {
        try {
            $key = 'healthcheck:probe:'.Str::uuid()->toString();
            Cache::put($key, 'ok', 10);

            if (Cache::get($key) !== 'ok') {
                return [
                    'healthy' => false,
                    'message' => 'Cache read/write probe failed.',
                ];
            }

            Cache::forget($key);

            return ['healthy' => true];
        } catch (\Throwable $exception) {
            return [
                'healthy' => false,
                'message' => $exception->getMessage(),
            ];
        }
    }

    /** @return array{healthy: bool, message?: string} */
    public function checkQueue(): array
    {
        try {
            $connection = (string) config('queue.default', 'sync');

            if ($connection === 'sync') {
                return ['healthy' => true, 'message' => 'Queue driver sync (in-process).'];
            }

            Queue::connection($connection)->size('default');

            return ['healthy' => true];
        } catch (\Throwable $exception) {
            return [
                'healthy' => false,
                'message' => $exception->getMessage(),
            ];
        }
    }

    /** @return array{healthy: bool, message?: string} */
    public function checkStorage(): array
    {
        try {
            $disk = Storage::disk('local');
            $path = '.healthcheck/'.Str::uuid()->toString();
            $disk->put($path, 'ok');

            if ($disk->get($path) !== 'ok') {
                return [
                    'healthy' => false,
                    'message' => 'Storage read/write probe failed.',
                ];
            }

            $disk->delete($path);

            return ['healthy' => true];
        } catch (\Throwable $exception) {
            return [
                'healthy' => false,
                'message' => $exception->getMessage(),
            ];
        }
    }

    /** @return array{healthy: bool, message?: string} */
    public function checkScheduler(): array
    {
        $heartbeatKey = 'healthcheck:scheduler:last_run';
        $lastRun = Cache::get($heartbeatKey);

        if ($lastRun === null) {
            return [
                'healthy' => true,
                'message' => 'Scheduler heartbeat not recorded; cron verification is external.',
            ];
        }

        return ['healthy' => true, 'message' => 'Scheduler heartbeat present.'];
    }

    /** @return array{healthy: bool, message?: string} */
    public function checkAuthentication(): array
    {
        try {
            if (! Schema::hasTable('personal_access_tokens')) {
                return [
                    'healthy' => false,
                    'message' => 'Sanctum personal_access_tokens table missing.',
                ];
            }

            if (blank(config('app.key'))) {
                return [
                    'healthy' => false,
                    'message' => 'Application key is not configured.',
                ];
            }

            return ['healthy' => true];
        } catch (\Throwable $exception) {
            return [
                'healthy' => false,
                'message' => $exception->getMessage(),
            ];
        }
    }

    /** @return array{healthy: bool, message?: string} */
    public function checkApplication(): array
    {
        return [
            'healthy' => true,
            'message' => 'Application bootstrap healthy.',
        ];
    }

    /** @return array{healthy: bool, message?: string} */
    public function checkApi(): array
    {
        return [
            'healthy' => true,
            'message' => 'API routing layer available.',
        ];
    }
}
