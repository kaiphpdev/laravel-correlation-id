<?php

namespace LaravelCorrelationId;

use Illuminate\Support\ServiceProvider;
use LaravelCorrelationId\Contracts\CorrelationIdGenerator;
use LaravelCorrelationId\Generators\UuidGenerator;

class CorrelationIdServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CorrelationIdManager::class);

        $this->app->bind(
            CorrelationIdGenerator::class,
            UuidGenerator::class
        );
    }
    public function boot(): void {}
}
