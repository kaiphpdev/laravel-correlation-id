<?php

namespace LaravelCorrelationId\Tests\Feature;

use LaravelCorrelationId\CorrelationIdManager;
use LaravelCorrelationId\Contracts\CorrelationIdGenerator;
use LaravelCorrelationId\Generators\UuidGenerator;
use LaravelCorrelationId\Tests\TestCase;

class ServiceContainerTest extends TestCase
{
    public function test_correlation_id_manager_is_registered_as_a_singleton(): void
    {
        $first = $this->app->make(
            CorrelationIdManager::class
        );

        $second = $this->app->make(
            CorrelationIdManager::class
        );

        $this->assertSame(
            $first,
            $second
        );
    }

    public function test_default_generator_is_uuid_generator(): void
    {
        $generator = $this->app->make(
            CorrelationIdGenerator::class
        );

        $this->assertInstanceOf(
            UuidGenerator::class,
            $generator
        );
    }
}