<?php

namespace Tests\Unit\Support;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Support\PostgresTestDatabaseGuard;

/**
 * Pure checks: no database connection is opened.
 */
final class PostgresTestDatabaseGuardTest extends TestCase
{
    private const SECRET = 'do-not-echo-this-password';

    public function test_valid_configuration_is_accepted(): void
    {
        $config = PostgresTestDatabaseGuard::validateConfiguration($this->env());

        $this->assertSame([
            'host' => '127.0.0.1',
            'port' => 5432,
            'database' => 'wsa_ic_persistence_test',
            'username' => 'ic_test',
            'password' => self::SECRET,
        ], $config);
    }

    public function test_consistent_pinned_variables_are_accepted(): void
    {
        $config = PostgresTestDatabaseGuard::validateConfiguration($this->env([
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => 'pgsql_testing',
            'DB_HOST' => '127.0.0.1',
            'DB_PORT' => '5432',
            'DB_DATABASE' => 'wsa_ic_persistence_test',
            'DB_URL' => '',
        ]));

        $this->assertSame('wsa_ic_persistence_test', $config['database']);
    }

    /**
     * @param  array<string, string|null>  $overrides
     */
    #[DataProvider('rejectedConfigurations')]
    public function test_rejected_configuration_fails_closed_without_echoing_the_password(array $overrides, string $expectedMessage): void
    {
        try {
            PostgresTestDatabaseGuard::validateConfiguration($this->env($overrides));
            $this->fail('Expected the configuration to be refused.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString($expectedMessage, $e->getMessage());
            $this->assertStringNotContainsString(self::SECRET, $e->getMessage());
        }
    }

    /**
     * @return array<string, array{array<string, string|null>, string}>
     */
    public static function rejectedConfigurations(): array
    {
        return [
            'missing host' => [['DB_TEST_HOST' => null], 'DB_TEST_HOST must be set'],
            'blank password' => [['DB_TEST_PASSWORD' => '  '], 'DB_TEST_PASSWORD must be set'],
            'missing destructive opt-in' => [['DB_TEST_ALLOW_DESTRUCTIVE_RESET' => null], 'DB_TEST_ALLOW_DESTRUCTIVE_RESET must be set'],
            'live database name' => [['DB_TEST_DATABASE' => 'wsa_enterprise', 'DB_TEST_ALLOW_DESTRUCTIVE_RESET' => 'wsa_enterprise'], 'must match'],
            'name without _test suffix' => [['DB_TEST_DATABASE' => 'wsa_ic', 'DB_TEST_ALLOW_DESTRUCTIVE_RESET' => 'wsa_ic'], 'must match'],
            'name with suffix inside' => [['DB_TEST_DATABASE' => 'wsa_test_live', 'DB_TEST_ALLOW_DESTRUCTIVE_RESET' => 'wsa_test_live'], 'must match'],
            'uppercase or quoted name' => [['DB_TEST_DATABASE' => 'Prod"_test', 'DB_TEST_ALLOW_DESTRUCTIVE_RESET' => 'Prod"_test'], 'must match'],
            'opt-in names another database' => [['DB_TEST_ALLOW_DESTRUCTIVE_RESET' => 'other_test'], 'must repeat DB_TEST_DATABASE'],
            'generic opt-in value' => [['DB_TEST_ALLOW_DESTRUCTIVE_RESET' => 'true'], 'must repeat DB_TEST_DATABASE'],
            'remote host' => [['DB_TEST_HOST' => 'db.internal.example'], 'must be a loopback address'],
            'compose service host' => [['DB_TEST_HOST' => 'postgres'], 'must be a loopback address'],
            'docker gateway host' => [['DB_TEST_HOST' => '172.17.0.1'], 'must be a loopback address'],
            'non-numeric port' => [['DB_TEST_PORT' => '5432;host=x'], 'must be a TCP port number'],
            'out-of-range port' => [['DB_TEST_PORT' => '70000'], 'must be a TCP port number'],
            'DB_URL set' => [['DB_URL' => 'pgsql://elsewhere/wsa_enterprise'], 'DB_URL must be empty'],
            'DATABASE_URL set' => [['DATABASE_URL' => 'pgsql://elsewhere/wsa_enterprise'], 'DATABASE_URL must be empty'],
            'production APP_ENV' => [['APP_ENV' => 'production'], 'APP_ENV [production] conflicts'],
            'other default connection' => [['DB_CONNECTION' => 'pgsql'], 'DB_CONNECTION [pgsql] conflicts'],
            'other DB_DATABASE' => [['DB_DATABASE' => 'wsa_enterprise'], 'DB_DATABASE [wsa_enterprise] conflicts'],
            'other DB_HOST' => [['DB_HOST' => 'postgres'], 'DB_HOST [postgres] conflicts'],
        ];
    }

    public function test_dedicated_server_is_accepted(): void
    {
        PostgresTestDatabaseGuard::assertDedicatedServer(
            'wsa_ic_persistence_test',
            'wsa_ic_persistence_test',
            ['postgres', 'template0', 'template1', 'wsa_ic_persistence_test'],
            '127.0.0.1',
        );
        PostgresTestDatabaseGuard::assertDedicatedServer('wsa_ic_persistence_test', 'wsa_ic_persistence_test', ['wsa_ic_persistence_test'], null);

        $this->addToAssertionCount(2);
    }

    /**
     * @param  list<string>  $databases
     */
    #[DataProvider('rejectedServers')]
    public function test_non_dedicated_server_is_refused(string $current, array $databases, ?string $address, string $expectedMessage): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($expectedMessage);

        PostgresTestDatabaseGuard::assertDedicatedServer('wsa_ic_persistence_test', $current, $databases, $address);
    }

    /**
     * @return array<string, array{string, list<string>, string|null, string}>
     */
    public static function rejectedServers(): array
    {
        $system = ['postgres', 'template0', 'template1'];

        return [
            'connected to another database' => ['wsa_enterprise', [...$system, 'wsa_enterprise', 'wsa_ic_persistence_test'], '127.0.0.1', 'expected [wsa_ic_persistence_test]'],
            'shared server with live database' => ['wsa_ic_persistence_test', [...$system, 'wsa_enterprise', 'wsa_ic_persistence_test'], '127.0.0.1', 'also hosts [wsa_enterprise]'],
            'shared server with another test database' => ['wsa_ic_persistence_test', [...$system, 'other_test', 'wsa_ic_persistence_test'], '127.0.0.1', 'also hosts [other_test]'],
            'non-loopback server address' => ['wsa_ic_persistence_test', [...$system, 'wsa_ic_persistence_test'], '172.18.0.5', 'not on a loopback address'],
        ];
    }

    /**
     * @param  array<string, string|null>  $overrides
     * @return array<string, string>
     */
    private function env(array $overrides = []): array
    {
        $env = array_replace([
            'DB_TEST_HOST' => '127.0.0.1',
            'DB_TEST_PORT' => '5432',
            'DB_TEST_DATABASE' => 'wsa_ic_persistence_test',
            'DB_TEST_USERNAME' => 'ic_test',
            'DB_TEST_PASSWORD' => self::SECRET,
            'DB_TEST_ALLOW_DESTRUCTIVE_RESET' => 'wsa_ic_persistence_test',
        ], $overrides);

        return array_filter($env, fn (?string $value): bool => $value !== null);
    }
}
