<?php

namespace Tests\Support;

use PHPUnit\Framework\AssertionFailedError;

final class DatabaseSafety
{
    public static function assertProcessEnvWillNotTouchLiveDatabase(): void
    {
        $appEnv = (string) (getenv('APP_ENV') ?: '');
        $database = (string) (getenv('DB_DATABASE') ?: '');

        if ($appEnv !== 'testing') {
            throw new AssertionFailedError('PHPUnit must run with APP_ENV=testing.');
        }

        $forbidden = array_filter(array_map(
            trim(...),
            explode(',', (string) (getenv('FORBIDDEN_TEST_DATABASES') ?: 'wsa_enterprise'))
        ));

        if (in_array($database, $forbidden, true)) {
            throw new AssertionFailedError(
                "Refusing to boot tests against live database [{$database}]. "
                .'PHPUnit must use sqlite :memory: or an isolated test database.'
            );
        }
    }

    public static function assertTestingDatabaseIsIsolated(): void
    {
        if (! app()->environment('testing')) {
            throw new AssertionFailedError('PHPUnit must run with APP_ENV=testing.');
        }

        $connection = (string) config('database.default');
        $database = (string) config("database.connections.{$connection}.database");

        $forbidden = array_filter(array_map(
            trim(...),
            explode(',', (string) env('FORBIDDEN_TEST_DATABASES', 'wsa_enterprise'))
        ));

        if (in_array($database, $forbidden, true)) {
            throw new AssertionFailedError(
                "Refusing to run destructive tests against staging database [{$database}]. "
                .'Use the isolated test database (wsa_enterprise_test) or sqlite :memory:.'
            );
        }
    }
}
