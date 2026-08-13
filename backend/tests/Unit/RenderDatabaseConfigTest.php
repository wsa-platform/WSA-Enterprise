<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RenderDatabaseConfigTest extends TestCase
{
    #[DataProvider('sslModeProvider')]
    public function test_pgsql_sslmode_for_environment(
        string $appEnv,
        ?string $dbSslMode,
        string $expected,
    ): void {
        $config = $this->loadDatabaseConfig($appEnv, $dbSslMode);

        $this->assertSame($expected, $config['connections']['pgsql']['sslmode']);
    }

    public static function sslModeProvider(): array
    {
        return [
            'production defaults to require' => ['production', null, 'require'],
            'explicit ssl mode wins' => ['production', 'verify-full', 'verify-full'],
            'local defaults to prefer' => ['local', null, 'prefer'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function loadDatabaseConfig(string $appEnv, ?string $dbSslMode): array
    {
        $original = [
            'APP_ENV' => $_ENV['APP_ENV'] ?? getenv('APP_ENV'),
            'DB_SSLMODE' => $_ENV['DB_SSLMODE'] ?? getenv('DB_SSLMODE'),
        ];

        putenv("APP_ENV={$appEnv}");
        $_ENV['APP_ENV'] = $appEnv;
        $_SERVER['APP_ENV'] = $appEnv;

        if ($dbSslMode === null) {
            putenv('DB_SSLMODE');
            unset($_ENV['DB_SSLMODE'], $_SERVER['DB_SSLMODE']);
        } else {
            putenv("DB_SSLMODE={$dbSslMode}");
            $_ENV['DB_SSLMODE'] = $dbSslMode;
            $_SERVER['DB_SSLMODE'] = $dbSslMode;
        }

        try {
            return require dirname(__DIR__, 2).'/config/database.php';
        } finally {
            foreach ($original as $key => $value) {
                if ($value === false || $value === null) {
                    putenv($key);
                    unset($_ENV[$key], $_SERVER[$key]);
                } else {
                    putenv("{$key}={$value}");
                    $_ENV[$key] = $value;
                    $_SERVER[$key] = $value;
                }
            }
        }
    }
}
