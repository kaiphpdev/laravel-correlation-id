<?php

namespace LaravelCorrelationId\Tests\Unit;

use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\Events\JobProcessing;
use LaravelCorrelationId\CorrelationIdManager;
use LaravelCorrelationId\Queue\RestoreCorrelationId;
use Orchestra\Testbench\TestCase;

class RestoreCorrelationIdTest extends TestCase
{
    public function test_it_restores_correlation_id_from_job_payload(): void
    {
        $job = $this->createMock(Job::class);

        $job->method('payload')
            ->willReturn([
                'correlation_id' => 'abc-123',
            ]);

        $event = new JobProcessing('database', $job);
        $manager = new CorrelationIdManager();
        $listener = new RestoreCorrelationId($manager);
        $listener->handle($event);
        $this->assertSame('abc-123', $manager->get());
    }

    public function test_it_does_not_restore_when_queue_propagation_is_disabled(): void
    {
        config()->set(
            'correlation-id.queue.enabled',
            false
        );

        $job = $this->createMock(Job::class);

        $job->method('payload')
            ->willReturn([
                'correlation_id' => 'abc-123',
            ]);

        $event = new JobProcessing(
            'database',
            $job
        );

        $manager = new CorrelationIdManager();

        $listener = new RestoreCorrelationId(
            $manager
        );

        $listener->handle($event);

        $this->assertFalse(
            $manager->has()
        );
    }
}
