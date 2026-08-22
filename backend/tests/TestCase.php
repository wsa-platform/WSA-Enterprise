<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\ProtectsStagingDatabase;

abstract class TestCase extends BaseTestCase
{
    use ProtectsStagingDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (app()->environment('testing')) {
            Cache::flush();
            $this->app->forgetInstance('bus');
            $this->app->forgetInstance('queue');
        }
    }

    protected function tearDown(): void
    {
        if (app()->environment('testing')) {
            if (Bus::getFacadeRoot() instanceof \Illuminate\Support\Testing\Fakes\BusFake) {
                Bus::swap($this->app->make(\Illuminate\Contracts\Bus\Dispatcher::class));
            }

            if (Queue::getFacadeRoot() instanceof \Illuminate\Support\Testing\Fakes\QueueFake) {
                Queue::swap($this->app->make(\Illuminate\Contracts\Queue\Factory::class));
            }
        }

        parent::tearDown();
    }

    protected function openApiSpecPath(): string
    {
        foreach ([
            env('OPENAPI_SPEC_PATH'),
            base_path('docs/openapi.yaml'),
            base_path('../docs/openapi.yaml'),
        ] as $path) {
            if (is_string($path) && $path !== '' && is_file($path)) {
                return $path;
            }
        }

        return base_path('../docs/openapi.yaml');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function jobSeekerPersonalPayload(array $overrides = []): array
    {
        return array_merge([
            'full_name' => 'Ahmed Mohamed Ali Hassan',
            'email' => 'seeker-personal@wsa.test',
            'phone' => '+966500000001',
            'country' => 'SA',
            'city' => 'Riyadh',
            'date_of_birth' => '1990-01-15',
            'nationality' => 'Saudi',
            'address' => 'Olaya Street',
        ], $overrides);
    }
}
