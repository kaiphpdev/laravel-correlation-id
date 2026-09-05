<?php

namespace LaravelCorrelationId\Tests\Feature;

use LaravelCorrelationId\CorrelationIdManager;
use LaravelCorrelationId\Facades\CorrelationId;
use LaravelCorrelationId\Tests\TestCase;

class CorrelationIdFacadeTest extends TestCase
{
    public function test_facade_returns_current_correlation_id(): void
    {
        $manager = $this->app->make(CorrelationIdManager::class);

        $manager->set('abc-123');

        $this->assertSame('abc-123', CorrelationId::get());
    }

    public function test_facade_can_check_if_correlation_id_exists(): void
    {
        $this->assertFalse(
            CorrelationId::has()
        );

        CorrelationId::set('abc-123');

        $this->assertTrue(
            CorrelationId::has()
        );
    }
}
