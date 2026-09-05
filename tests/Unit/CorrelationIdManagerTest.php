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
}
