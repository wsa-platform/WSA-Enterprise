<?php

declare(strict_types=1);

/**
 * Opt-in PostgreSQL bootstrap for phpunit.pgsql.xml (also loaded by tests/Pgsql/bin workers).
 *
 * Every check in Tests\Support\PostgresTestDatabaseGuard runs before any test, and therefore before
 * RefreshDatabase or migrate:fresh can touch the schema. Never falls back to sqlite. Every
 * pgsql-family variable is pinned to the test server so an accidental use of the `pgsql`
 * connection still lands on the test database.
 */
require_once dirname(__DIR__).'/vendor/autoload.php';

try {
    $config = \Tests\Support\PostgresTestDatabaseGuard::validateConfiguration(getenv());
} catch (RuntimeException $e) {
    fwrite(STDERR, 'PostgreSQL test bootstrap refused: '.$e->getMessage()."\n");
    exit(1);
}

$forced = [
    'APP_ENV' => 'testing',
    'DB_CONNECTION' => 'pgsql_testing',
    'DB_HOST' => $config['host'],
    'DB_PORT' => (string) $config['port'],
    'DB_DATABASE' => $config['database'],
    'DB_USERNAME' => $config['username'],
    'DB_PASSWORD' => $config['password'],
    'DB_URL' => '',
    'DATABASE_URL' => '',
    'CACHE_STORE' => 'array',
    'QUEUE_CONNECTION' => 'sync',
    'FORBIDDEN_TEST_DATABASES' => implode(',', \Tests\Support\PostgresTestDatabaseGuard::FORBIDDEN_DATABASES),
];

foreach ($forced as $name => $value) {
    putenv($name.'='.$value);
    $_ENV[$name] = $value;
    $_SERVER[$name] = $value;
}

try {
    \Tests\Support\PostgresTestDatabaseGuard::assertServerIsDedicated($config);
} catch (RuntimeException $e) {
    fwrite(STDERR, 'PostgreSQL test bootstrap refused: '.$e->getMessage()."\n");
    exit(1);
}
