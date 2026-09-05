<?php

namespace LaravelCorrelationId\Tests\Feature;

use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\Events\JobProcessing;
use LaravelCorrelationId\CorrelationIdManager;
use LaravelCorrelationId\Queue\RestoreCorrelationId;
use LaravelCorrelationId\Tests\TestCase;
use LaravelCorrelationId\Validation\CorrelationIdValidator;
use LaravelCorrelationId\Validation\TraceIdValidator;

class RestoreCorrelationIdTest extends TestCase
{
    public function test_it_restores_correlation_id_from_queue_payload(): void
    {
        $manager = new CorrelationIdManager;

        $listener = $this->createListener(
            $manager
        );

        $event = $this->createJobProcessingEvent([
            'correlation_id' => 'abc-123',
        ]);

        $listener->handle($event);

        $this->assertTrue(
            $manager->has()
        );

        $this->assertSame(
            'abc-123',
            $manager->get()
        );

        $this->assertFalse(
            $manager->hasTraceId()
        );
    }

    public function test_it_restores_w3c_trace_id_from_queue_payload(): void
    {
        config()->set(
            'correlation-id.w3c.enabled',
            true
        );

        $manager = new CorrelationIdManager;

        $listener = $this->createListener(
            $manager
        );

        $traceId = '4bf92f3577b34da6a3ce929d0e0e4736';

        $event = $this->createJobProcessingEvent([
            'correlation_id' => $traceId,
            'trace_id' => $traceId,
        ]);

        $listener->handle($event);

        $this->assertSame(
            $traceId,
            $manager->get()
        );

        $this->assertTrue(
            $manager->hasTraceId()
        );

        $this->assertSame(
            $traceId,
            $manager->getTraceId()
        );
    }

    public function test_it_does_not_restore_invalid_trace_id(): void
    {
        config()->set(
            'correlation-id.w3c.enabled',
            true
        );

        $manager = new CorrelationIdManager;

        $listener = $this->createListener(
            $manager
        );

        $event = $this->createJobProcessingEvent([
            'correlation_id' => 'abc-123',
            'trace_id' => 'invalid-trace-id',
        ]);

        $listener->handle($event);

        $this->assertSame(
            'abc-123',
            $manager->get()
        );

        $this->assertFalse(
            $manager->hasTraceId()
        );

        $this->assertNull(
            $manager->getTraceId()
        );
    }

    public function test_it_clears_stale_state_before_restoring_job_context(): void
    {
        config()->set(
            'correlation-id.w3c.enabled',
            true
        );

        $manager = new CorrelationIdManager;

        $manager->set(
            'stale-correlation-id'
        );

        $manager->setTraceId(
            '11111111111111111111111111111111'
        );

        $listener = $this->createListener(
            $manager
        );

        $event = $this->createJobProcessingEvent([
            'correlation_id' => 'fresh-correlation-id',
        ]);

        $listener->handle($event);

        $this->assertTrue(
            $manager->has()
        );

        $this->assertSame(
            'fresh-correlation-id',
            $manager->get()
        );

        $this->assertFalse(
            $manager->hasTraceId()
        );

        $this->assertNull(
            $manager->getTraceId()
        );
    }

    public function test_it_clears_stale_state_when_queue_propagation_is_disabled(): void
    {
        config()->set(
            'correlation-id.queue.enabled',
            false
        );

        $manager = new CorrelationIdManager;

        $manager->set(
            'stale-correlation-id'
        );

        $manager->setTraceId(
            '11111111111111111111111111111111'
        );

        $listener = $this->createListener(
            $manager
        );

        $job = $this->createMock(
            Job::class
        );

        $job->expects(
            $this->never()
        )->method(
            'payload'
        );

        $event = new JobProcessing(
            'sync',
            $job
        );

        $listener->handle($event);

        $this->assertFalse(
            $manager->has()
        );

        $this->assertNull(
            $manager->get()
        );

        $this->assertFalse(
            $manager->hasTraceId()
        );

        $this->assertNull(
            $manager->getTraceId()
        );
    }

    private function createListener(
        CorrelationIdManager $manager
    ): RestoreCorrelationId {
        return new RestoreCorrelationId(
            $manager,
            $this->app->make(
                CorrelationIdValidator::class
            ),
            new TraceIdValidator
        );
    }

    private function createJobProcessingEvent(
        array $payload
    ): JobProcessing {
        $job = $this->createMock(
            Job::class
        );

        $job->expects(
            $this->once()
        )->method(
            'payload'
        )->willReturn(
            $payload
        );

        return new JobProcessing(
            'sync',
            $job
        );
    }
}
