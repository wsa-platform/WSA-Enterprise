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

    public static function operationalScientificDatabasePath(): string
    {
        return storage_path('app'.DIRECTORY_SEPARATOR.'scientific_record_store.sqlite');
    }

    public static function permittedScientificTestingRoot(): string
    {
        return storage_path('framework'.DIRECTORY_SEPARATOR.'testing');
    }

    /**
     * Fail closed before any scientific_records migration, ingest, rebuild, or delete.
     * $resolvedDatabase is the already-open connection path when one exists; passing it
     * does not open a connection.
     */
    public static function assertScientificRecordStoreIsIsolated(?string $resolvedDatabase = null): void
    {
        $default = (string) config('database.default');
        $defaultDatabase = (string) config("database.connections.{$default}.database");
        if (self::pathsIdentifySameFile($defaultDatabase, self::operationalScientificDatabasePath())) {
            throw new AssertionFailedError('Refusing to run tests because the default connection is the operational scientific store.');
        }

        $connection = config('database.connections.scientific_records');
        if (! is_array($connection)) {
            throw new AssertionFailedError('Refusing to run tests because scientific_records is not configured.');
        }

        self::assertScientificConnectionConfigurationIsIsolated($connection, $resolvedDatabase);
    }

    /**
     * @param  array<string, mixed>  $connection
     */
    public static function assertScientificConnectionConfigurationIsIsolated(array $connection, ?string $resolvedDatabase = null): void
    {
        $driver = (string) ($connection['driver'] ?? '');
        if ($driver !== 'sqlite') {
            throw new AssertionFailedError("Refusing scientific_records driver [{$driver}]; tests require sqlite.");
        }

        $url = trim((string) ($connection['url'] ?? ''));
        if ($url !== '') {
            throw new AssertionFailedError('Refusing scientific_records because a URL would override the test database path.');
        }

        self::assertPathIsIsolatedScientificDatabase((string) ($connection['database'] ?? ''), 'configured');
        if ($resolvedDatabase !== null) {
            self::assertPathIsIsolatedScientificDatabase($resolvedDatabase, 'resolved');
        }
    }

    public static function assertPathIsIsolatedScientificDatabase(string $database, string $role): void
    {
        if ($database === '' || $database === ':memory:') {
            throw new AssertionFailedError("Refusing {$role} scientific database because it has no test-owned file path.");
        }

        if (preg_match('~(^|[\\\\/])\\.\\.([\\\\/]|$)~', $database) === 1) {
            throw new AssertionFailedError("Refusing {$role} scientific database because it contains a parent-directory segment.");
        }

        if (self::pathsIdentifySameFile($database, self::operationalScientificDatabasePath())) {
            throw new AssertionFailedError("Refusing {$role} scientific database because it is the operational store.");
        }

        $resolved = realpath($database);
        $candidate = self::normalizePath($resolved !== false ? $resolved : $database);
        $root = self::normalizePath(self::permittedScientificTestingRoot());
        $resolvedRoot = realpath(self::permittedScientificTestingRoot());
        if ($resolvedRoot !== false) {
            $root = self::normalizePath($resolvedRoot);
        }
        if ($candidate !== $root && ! str_starts_with($candidate, $root.DIRECTORY_SEPARATOR)) {
            throw new AssertionFailedError("Refusing {$role} scientific database [{$database}] because it is outside the testing root.");
        }
    }

    public static function deleteOwnedSqliteFiles(string $path): void
    {
        self::assertPathIsIsolatedScientificDatabase($path, 'cleanup');
        foreach ([$path, $path.'-wal', $path.'-shm'] as $file) {
            if (is_file($file) && ! unlink($file)) {
                throw new AssertionFailedError("Could not remove test-owned sqlite file [{$file}].");
            }
        }
    }

    private static function pathsIdentifySameFile(string $left, string $right): bool
    {
        if ($left === '' || $right === '' || $left === ':memory:' || $right === ':memory:') {
            return false;
        }

        $leftReal = realpath($left);
        $rightReal = realpath($right);
        if ($leftReal !== false && $rightReal !== false) {
            return self::normalizePath($leftReal) === self::normalizePath($rightReal);
        }

        return self::normalizePath($left) === self::normalizePath($right);
    }

    private static function normalizePath(string $path): string
    {
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

        return strtolower(rtrim($path, DIRECTORY_SEPARATOR));
    }
}
