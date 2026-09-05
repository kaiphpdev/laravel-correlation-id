<?php

namespace LaravelCorrelationId\Tests\Unit;

use LaravelCorrelationId\CorrelationIdManager;
use PHPUnit\Framework\TestCase;

class CorrelationIdManagerTest extends TestCase
{
    public function test_it_stores_and_returns_a_correlation_id(): void
    {
        $manager = new CorrelationIdManager;
        $manager->set('abc-123');
        $this->assertSame(
            'abc-123',
            $manager->get()
        );
    }

    public function test_it_reports_when_a_correlation_id_exists(): void
    {
        $manager = new CorrelationIdManager;
        $this->assertFalse($manager->has());
        $manager->set('abc-123');
        $this->assertTrue($manager->has());
    }

    public function test_it_can_clear_the_correlation_id(): void
    {
        $manager = new CorrelationIdManager;
        $manager->set('abc-123');
        $manager->clear();
        $this->assertNull($manager->get());
        $this->assertFalse($manager->has());
    }

    public function test_it_can_store_trace_id(): void
    {
        $manager = new CorrelationIdManager;

        $manager->setTraceId(
            '4bf92f3577b34da6a3ce929d0e0e4736'
        );

        $this->assertTrue($manager->hasTraceId());

        $this->assertSame(
            '4bf92f3577b34da6a3ce929d0e0e4736',
            $manager->getTraceId()
        );
    }

    public function test_clear_removes_trace_id(): void
    {
        $manager = new CorrelationIdManager;

        $manager->set('correlation-id');

        $manager->setTraceId(
            '4bf92f3577b34da6a3ce929d0e0e4736'
        );

        $manager->clear();

        $this->assertFalse($manager->has());
        $this->assertFalse($manager->hasTraceId());

        $this->assertNull($manager->get());
        $this->assertNull($manager->getTraceId());
    }

    public function test_clear_trace_id_does_not_clear_correlation_id(): void
    {
        $manager = new CorrelationIdManager;

        $manager->set('abc-123');

        $manager->setTraceId(
            '4bf92f3577b34da6a3ce929d0e0e4736'
        );

        $manager->clearTraceId();

        $this->assertTrue($manager->has());

        $this->assertSame(
            'abc-123',
            $manager->get()
        );

        $this->assertFalse($manager->hasTraceId());
    }
}
