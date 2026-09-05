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
            'trace_id'
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
            $payload['trace_id']
        );
    }

    private function createQueuePayload(TestJob $job): array
    {
        $connection = Queue::connection();

        $reflection = new \ReflectionClass($connection);

        $method = $reflection->getMethod(
            'createPayload'
        );

        $method->setAccessible(true);

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
