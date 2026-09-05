<?php

namespace LaravelCorrelationId\Tests\Feature;

use Illuminate\Support\Facades\Queue;
use LaravelCorrelationId\CorrelationIdManager;
use LaravelCorrelationId\Tests\Fixtures\TestJob;
use LaravelCorrelationId\Tests\TestCase;

class QueuePayloadTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set(
            'queue.default',
            'sync'
        );
    }

    public function test_it_adds_correlation_id_to_queue_payload(): void
    {
        $manager = $this->app->make(
            CorrelationIdManager::class
        );

        $manager->set('abc-123');

        $payload = $this->createQueuePayload(
            new TestJob
        );

        $this->assertSame(
            'abc-123',
            $payload['correlation_id']
        );
    }

    public function test_it_does_not_add_correlation_id_when_none_exists(): void
    {
        $payload = $this->createQueuePayload(
            new TestJob
        );

        $this->assertArrayNotHasKey(
            'correlation_id',
            $payload
        );
    }

    public function test_it_does_not_add_payload_value_when_queue_propagation_is_disabled(): void
    {
        config()->set(
            'correlation-id.queue.enabled',
            false
        );

        $manager = $this->app->make(
            CorrelationIdManager::class
        );

        $manager->set('abc-123');

        $payload = $this->createQueuePayload(
            new TestJob
        );

        $this->assertArrayNotHasKey(
            'correlation_id',
            $payload
        );
    }

    public function test_it_uses_configured_queue_payload_key(): void
    {
        config()->set(
            'correlation-id.queue.payload_key',
            'request_trace_id'
        );

        $manager = $this->app->make(
            CorrelationIdManager::class
        );

        $manager->set('abc-123');

        $payload = $this->createQueuePayload(
            new TestJob
        );

        $this->assertSame(
            'abc-123',
            $payload['request_trace_id']
        );
    }

    public function test_it_adds_trace_id_to_queue_payload_when_w3c_trace_exists(): void
    {
        config()->set(
            'correlation-id.w3c.enabled',
            true
        );

        $manager = $this->app->make(
            CorrelationIdManager::class
        );

        $traceId = '4bf92f3577b34da6a3ce929d0e0e4736';

        $manager->set($traceId);
        $manager->setTraceId($traceId);

        $payload = $this->createQueuePayload(
            new TestJob
        );

        $this->assertSame(
            $traceId,
            $payload['correlation_id']
        );

        $this->assertSame(
            $traceId,
            $payload['trace_id']
        );
    }

    public function test_it_does_not_add_trace_id_for_normal_correlation_id(): void
    {
        config()->set(
            'correlation-id.w3c.enabled',
            true
        );

        $manager = $this->app->make(
            CorrelationIdManager::class
        );

        $manager->set(
            'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'
        );

        $payload = $this->createQueuePayload(
            new TestJob
        );

        $this->assertArrayHasKey(
            'correlation_id',
            $payload
        );

        $this->assertArrayNotHasKey(
            'trace_id',
            $payload
        );
    }

    public function test_it_uses_configured_trace_payload_key(): void
    {
        config()->set(
            'correlation-id.w3c.enabled',
            true
        );

        config()->set(
            'correlation-id.queue.trace_payload_key',
            'w3c_trace_id'
        );

        $manager = $this->app->make(
            CorrelationIdManager::class
        );

        $traceId = '4bf92f3577b34da6a3ce929d0e0e4736';

        $manager->set($traceId);
        $manager->setTraceId($traceId);

        $payload = $this->createQueuePayload(
            new TestJob
        );

        $this->assertSame(
            $traceId,
            $payload['w3c_trace_id']
        );
    }

    public function test_it_adds_trace_flags_to_queue_payload(): void
    {
        config()->set(
            'correlation-id.w3c.enabled',
            true
        );

        $manager = $this->app->make(
            CorrelationIdManager::class
        );

        $traceId = '4bf92f3577b34da6a3ce929d0e0e4736';

        $manager->set($traceId);
        $manager->setTraceId($traceId);
        $manager->setTraceFlags('01');

        $payload = $this->createQueuePayload(
            new TestJob
        );

        $this->assertSame(
            $traceId,
            $payload['trace_id']
        );

        $this->assertSame(
            '01',
            $payload['trace_flags']
        );
    }

    public function test_it_does_not_add_trace_flags_without_trace_context(): void
    {
        config()->set(
            'correlation-id.w3c.enabled',
            true
        );

        $manager = $this->app->make(
            CorrelationIdManager::class
        );

        $manager->set('abc-123');

        $payload = $this->createQueuePayload(
            new TestJob
        );

        $this->assertArrayNotHasKey(
            'trace_flags',
            $payload
        );
    }

    public function test_it_uses_configured_trace_flags_payload_key(): void
    {
        config()->set(
            'correlation-id.w3c.enabled',
            true
        );

        config()->set(
            'correlation-id.queue.trace_flags_payload_key',
            'w3c_trace_flags'
        );

        $manager = $this->app->make(
            CorrelationIdManager::class
        );

        $traceId = '4bf92f3577b34da6a3ce929d0e0e4736';

        $manager->set($traceId);
        $manager->setTraceId($traceId);
        $manager->setTraceFlags('01');

        $payload = $this->createQueuePayload(
            new TestJob
        );

        $this->assertSame(
            '01',
            $payload['w3c_trace_flags']
        );

        $this->assertArrayNotHasKey(
            'trace_flags',
            $payload
        );
    }

    private function createQueuePayload(TestJob $job): array
    {
        $connection = Queue::connection();

        $reflection = new \ReflectionClass(
            $connection
        );

        $method = $reflection->getMethod(
            'createPayload'
        );

        $method->setAccessible(
            true
        );

        $payload = $method->invoke(
            $connection,
            $job,
            'default'
        );

        return json_decode(
            $payload,
            true
        );
    }
}
