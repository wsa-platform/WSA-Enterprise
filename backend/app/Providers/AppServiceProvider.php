<?php

namespace App\Providers;

use App\Contracts\AiProviderInterface;
use App\Services\Ai\AiRequestValidator;
use App\Services\Ai\AiResponseNormalizer;
use App\Services\Ai\AiService;
use App\Services\Ai\MockAiProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AiProviderInterface::class, function (): AiProviderInterface {
            return match (config('ai.provider')) {
                default => new MockAiProvider,
            };
        });

        $this->app->singleton(AiService::class, fn ($app) => new AiService(
            $app->make(AiProviderInterface::class),
            $app->make(AiRequestValidator::class),
            $app->make(AiResponseNormalizer::class),
        ));
    }

    public function boot(): void
    {
        //
    }
}
