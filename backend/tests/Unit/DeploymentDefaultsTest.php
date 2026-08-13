<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DeploymentDefaultsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        require base_path('config/_deployment.php');
    }

    #[DataProvider('cacheStoreProvider')]
    public function test_default_cache_store_for_environment(
        string $appEnv,
        ?string $cacheStore,
        ?string $redisHost,
        string $expected,
    ): void {
        $this->withEnv([
            'APP_ENV' => $appEnv,
            'CACHE_STORE' => $cacheStore,
            'REDIS_HOST' => $redisHost,
            'REDIS_URL' => null,
        ], function () use ($expected): void {
            $this->assertSame($expected, wsa_default_cache_store());
        });
    }

    #[DataProvider('sessionDriverProvider')]
    public function test_default_session_driver_for_environment(
        string $appEnv,
        ?string $sessionDriver,
        ?string $redisHost,
        string $expected,
    ): void {
        $this->withEnv([
            'APP_ENV' => $appEnv,
            'SESSION_DRIVER' => $sessionDriver,
            'REDIS_HOST' => $redisHost,
            'REDIS_URL' => null,
        ], function () use ($expected): void {
            $this->assertSame($expected, wsa_default_session_driver());
        });
    }

    public static function cacheStoreProvider(): array
    {
        return [
            'production without redis uses file cache' => ['production', null, null, 'file'],
            'production with redis uses redis cache' => ['production', null, 'redis', 'redis'],
            'explicit cache store wins' => ['production', 'database', null, 'database'],
            'testing uses array cache' => ['testing', null, null, 'array'],
            'local without redis uses database cache' => ['local', null, null, 'database'],
        ];
    }

    public static function sessionDriverProvider(): array
    {
        return [
            'production without redis uses file sessions' => ['production', null, null, 'file'],
            'production with redis uses redis sessions' => ['production', null, 'redis', 'redis'],
            'explicit session driver wins' => ['production', 'database', null, 'database'],
            'testing uses array sessions' => ['testing', null, null, 'array'],
        ];
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function withEnv(array $values, callable $callback): void
    {
        $original = [];
        foreach ($values as $key => $value) {
            $original[$key] = $_ENV[$key] ?? getenv($key);
            if ($value === null) {
                unset($_ENV[$key], $_SERVER[$key]);
                putenv($key);
            } else {
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
                putenv("{$key}={$value}");
            }
        }

        try {
            $callback();
        } finally {
            foreach ($original as $key => $value) {
                if ($value === false || $value === null) {
                    unset($_ENV[$key], $_SERVER[$key]);
                    putenv($key);
                } else {
                    $_ENV[$key] = $value;
                    $_SERVER[$key] = $value;
                    putenv("{$key}={$value}");
                }
            }
        }
    }
}
