<?php

declare(strict_types=1);

namespace Tests\Support;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Fail-closed guard for the destructive PostgreSQL suite (phpunit.pgsql.xml): RefreshDatabase and
 * migrate:fresh drop every table in the target database.
 *
 * A *_test name never authorizes a reset on its own. The run must also repeat the database name in
 * DB_TEST_ALLOW_DESTRUCTIVE_RESET, target a loopback host (a disposable server sharing the runner's
 * network namespace), carry no conflicting connection variables, and the server must prove it is
 * dedicated: it may hold no database other than the target and the PostgreSQL system databases.
 *
 * Not guaranteed here: that the server is ephemeral, or that the role lacks privileges. A loopback
 * address can still be a persistent server; the dedicated-server check bounds what can be erased.
 * Messages never contain credentials.
 */
final class PostgresTestDatabaseGuard
{
    public const FORBIDDEN_DATABASES = ['wsa_enterprise'];

    public const LOOPBACK_HOSTS = ['127.0.0.1', '::1', 'localhost'];

    public const SYSTEM_DATABASES = ['postgres', 'template0', 'template1'];

    private const REQUIRED = [
        'DB_TEST_HOST',
        'DB_TEST_PORT',
        'DB_TEST_DATABASE',
        'DB_TEST_USERNAME',
        'DB_TEST_PASSWORD',
        'DB_TEST_ALLOW_DESTRUCTIVE_RESET',
    ];

    /**
     * @param  array<string, mixed>  $env
     * @return array{host: string, port: int, database: string, username: string, password: string}
     */
    public static function validateConfiguration(array $env): array
    {
        foreach (self::REQUIRED as $name) {
            if (self::value($env, $name) === '') {
                throw new RuntimeException("{$name} must be set explicitly.");
            }
        }

        $database = self::value($env, 'DB_TEST_DATABASE');
        if (preg_match('/^[a-z][a-z0-9_]{0,57}_test$/', $database) !== 1 || in_array($database, self::FORBIDDEN_DATABASES, true)) {
            throw new RuntimeException("DB_TEST_DATABASE [{$database}] must match [a-z][a-z0-9_]*_test and must not be a live database.");
        }

        if (self::value($env, 'DB_TEST_ALLOW_DESTRUCTIVE_RESET') !== $database) {
            throw new RuntimeException('DB_TEST_ALLOW_DESTRUCTIVE_RESET must repeat DB_TEST_DATABASE exactly; the suite drops every table in it.');
        }

        $host = strtolower(self::value($env, 'DB_TEST_HOST'));
        if (! in_array($host, self::LOOPBACK_HOSTS, true)) {
            throw new RuntimeException("DB_TEST_HOST [{$host}] must be a loopback address of a disposable server in the runner's network namespace.");
        }

        $port = self::value($env, 'DB_TEST_PORT');
        if (preg_match('/^[1-9][0-9]{0,4}$/', $port) !== 1 || (int) $port > 65535) {
            throw new RuntimeException("DB_TEST_PORT [{$port}] must be a TCP port number.");
        }

        foreach (['DB_URL', 'DATABASE_URL'] as $name) {
            if (self::value($env, $name) !== '') {
                throw new RuntimeException("{$name} must be empty; a connection URL can redirect the pgsql connection.");
            }
        }

        $conflicts = [
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => 'pgsql_testing',
            'DB_HOST' => self::value($env, 'DB_TEST_HOST'),
            'DB_PORT' => $port,
            'DB_DATABASE' => $database,
        ];
        foreach ($conflicts as $name => $expected) {
            $actual = self::value($env, $name);
            if ($actual !== '' && $actual !== $expected) {
                throw new RuntimeException("{$name} [{$actual}] conflicts with the PostgreSQL test target; unset it or set it to [{$expected}].");
            }
        }

        return [
            'host' => self::value($env, 'DB_TEST_HOST'),
            'port' => (int) $port,
            'database' => $database,
            'username' => self::value($env, 'DB_TEST_USERNAME'),
            'password' => (string) $env['DB_TEST_PASSWORD'],
        ];
    }

    /**
     * @param  list<string>  $serverDatabases  Every datname on the server.
     */
    public static function assertDedicatedServer(string $expectedDatabase, string $currentDatabase, array $serverDatabases, ?string $serverAddress): void
    {
        if ($currentDatabase !== $expectedDatabase) {
            throw new RuntimeException("connected to database [{$currentDatabase}], expected [{$expectedDatabase}].");
        }

        $others = array_values(array_diff($serverDatabases, [...self::SYSTEM_DATABASES, $expectedDatabase]));
        if ($others !== []) {
            throw new RuntimeException('the server also hosts ['.implode(', ', $others).']; destructive tests require a dedicated disposable server.');
        }

        if ($serverAddress !== null && ! in_array($serverAddress, ['127.0.0.1', '::1'], true)) {
            throw new RuntimeException("the server answered on [{$serverAddress}], not on a loopback address.");
        }
    }

    /**
     * Read-only inspection of the configured server; runs before any schema is touched.
     *
     * @param  array{host: string, port: int, database: string, username: string, password: string}  $config
     */
    public static function assertServerIsDedicated(array $config): void
    {
        try {
            $pdo = new PDO(
                sprintf('pgsql:host=%s;port=%d;dbname=%s;connect_timeout=5', $config['host'], $config['port'], $config['database']),
                $config['username'],
                $config['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
            );
            $row = $pdo->query(
                'SELECT current_database() AS current_database, host(inet_server_addr()) AS server_address, '
                .'(SELECT json_agg(datname ORDER BY datname) FROM pg_database) AS server_databases'
            )->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new RuntimeException('cannot inspect the PostgreSQL test server (SQLSTATE '.$e->getCode().').');
        }

        self::assertDedicatedServer(
            $config['database'],
            (string) $row['current_database'],
            json_decode((string) $row['server_databases'], true) ?: [],
            $row['server_address'] === null ? null : (string) $row['server_address'],
        );
    }

    /**
     * @param  array<string, mixed>  $env
     */
    private static function value(array $env, string $name): string
    {
        $value = $env[$name] ?? null;

        return is_string($value) ? trim($value) : '';
    }
}
