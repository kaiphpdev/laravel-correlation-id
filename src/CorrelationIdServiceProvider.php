<?php

namespace LaravelCorrelationId;

use Illuminate\Support\ServiceProvider;
use LaravelCorrelationId\Contracts\CorrelationIdGenerator;
use LaravelCorrelationId\Generators\UuidGenerator;
use LaravelCorrelationId\Http\Middleware\CorrelationIdMiddleware;

use Illuminate\Log\LogManager;
use LaravelCorrelationId\Logging\CorrelationIdProcessor;


class CorrelationIdServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/correlation-id.php',
            'correlation-id'
        );

        $this->app->singleton(CorrelationIdManager::class);

        $this->app->bind(
            CorrelationIdGenerator::class,
            UuidGenerator::class
        );
    }

    public function boot(): void
    {
        $this->app['router']->aliasMiddleware(
            'correlation-id',
            CorrelationIdMiddleware::class
        );

        $this->publishes([
            __DIR__ . '/../config/correlation-id.php'
            => config_path('correlation-id.php'),
        ], 'correlation-id-config');

        $this->app->booted(function (): void {
            $this->registerLogProcessor();
        });
    }
    protected function registerLOgProcessor(): void
    {
        $logManager = $this->app->make(LogManager::class);


        $processor = $this->app->make(CorrelationIdProcessor::class);

        $logManager->getLogger()->pushProcessor($processor);
    }
}
