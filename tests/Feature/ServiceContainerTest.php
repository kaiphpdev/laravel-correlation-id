<?php

namespace LaravelCorrelationId\Tests\Feature;

use LaravelCorrelationId\CorrelationIdManager;
use LaravelCorrelationId\Contracts\CorrelationIdGenerator;
use LaravelCorrelationId\Generators\UuidGenerator;
use LaravelCorrelationId\Tests\TestCase;
use LaravelCorrelationId\Tests\Fixtures\CustomGenerator;
use InvalidArgumentException;
use LaravelCorrelationId\Tests\Fixtures\InvalidGenerator;

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
    public function test_custom_generator_can_be_configured(): void
    {
        config()->set(
            'correlation-id.generator',
            CustomGenerator::class
        );

        $generator = $this->app->make(
            CorrelationIdGenerator::class
        );

        $this->assertInstanceOf(
            CustomGenerator::class,
            $generator
        );

        $this->assertSame(
            'custom-id-123',
            $generator->generate()
        );
    }

    public function test_invalid_generator_configuration_throws_exception(): void
    {
        config()->set(
            'correlation-id.generator',
            InvalidGenerator::class
        );

        $this->expectException(
            InvalidArgumentException::class
        );

        $this->expectExceptionMessage(
            'must implement'
        );

        $this->app->make(
            CorrelationIdGenerator::class
        );
    }
}
