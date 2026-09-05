<?php

namespace LaravelCorrelationId\Tests\Unit;

use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\Events\JobProcessed;
use LaravelCorrelationId\CorrelationIdManager;
use LaravelCorrelationId\Queue\ClearCorrelationId;
use PHPUnit\Framework\TestCase;

class ClearCorrelationIdTest extends TestCase
{
    public function test_it_clears_correlation_id_after_job_is_processed(): void
    {
        $manager = new CorrelationIdManager();

        $manager->set('abc-123');

        $job = $this->createMock(Job::class);

        $event = new JobProcessed(
            'database',
            $job
        );

        $listener = new ClearCorrelationId(
            $manager
        );

        $listener->handleProcessed($event);

        $this->assertFalse(
            $manager->has()
        );
    }
}