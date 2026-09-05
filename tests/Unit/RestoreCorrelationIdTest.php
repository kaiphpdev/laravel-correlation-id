<?php

namespace LaravelCorrelationId\Tests\Unit;

use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\Events\JobProcessing;
use LaravelCorrelationId\CorrelationIdManager;
use LaravelCorrelationId\Queue\RestoreCorrelationId;
use LaravelCorrelationId\Validation\CorrelationIdValidator;
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
        $manager = new CorrelationIdManager;
        $listener = new RestoreCorrelationId(
            $manager,
            new CorrelationIdValidator
        );
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

        $manager = new CorrelationIdManager;

        $listener = new RestoreCorrelationId(
            $manager,
            new CorrelationIdValidator
        );

        $listener->handle($event);

        $this->assertFalse(
            $manager->has()
        );
    }

    public function test_it_clears_stale_id_when_job_has_no_correlation_id(): void
    {
        $job = $this->createMock(Job::class);

        $job->method('payload')
            ->willReturn([]);

        $event = new JobProcessing(
            'database',
            $job
        );

        $manager = new CorrelationIdManager;

        $manager->set('old-id');

        $listener = new RestoreCorrelationId(
            $manager,
            new CorrelationIdValidator
        );

        $listener->handle($event);

        $this->assertFalse(
            $manager->has()
        );
    }

    public function test_it_restores_id_using_custom_payload_key(): void
    {
        config()->set(
            'correlation-id.queue.payload_key',
            'request_trace_id'
        );

        $job = $this->createMock(Job::class);

        $job->method('payload')
            ->willReturn([
                'request_trace_id' => 'abc-123',
            ]);

        $event = new JobProcessing(
            'database',
            $job
        );

        $manager = new CorrelationIdManager;

        $listener = new RestoreCorrelationId(
            $manager,
            new CorrelationIdValidator
        );

        $listener->handle($event);

        $this->assertSame(
            'abc-123',
            $manager->get()
        );
    }

    public function test_it_does_not_restore_invalid_correlation_id(): void
    {
        $job = $this->createMock(Job::class);

        $job->method('payload')
            ->willReturn([
                'correlation_id' => 'invalid correlation id',
            ]);

        $event = new JobProcessing(
            'database',
            $job
        );

        $manager = new CorrelationIdManager;

        $listener = new RestoreCorrelationId(
            $manager,
            new CorrelationIdValidator
        );

        $listener->handle($event);

        $this->assertFalse(
            $manager->has()
        );
    }
}
