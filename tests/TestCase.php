<?php

namespace LaravelCorrelationId\Tests;

use LaravelCorrelationId\CorrelationIdServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            CorrelationIdServiceProvider::class,
        ];
    }
}
