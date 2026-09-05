<?php

namespace LaravelCorrelationId\Queue;

use Illuminate\Queue\Events\JobProcessing;
use LaravelCorrelationId\CorrelationIdManager;

class RestoreCorrelationId
{
    public function __construct(protected CorrelationIdManager $manager) {}

    public function handle(JobProcessing $event): void
    {
        if (! config('correlation-id.queue.enabled', true)) {
            return;
        }

        $payload = $event->job->payload();

        $key = config(
            'correlation-id.queue.payload_key',
            'correlation_id'
        );

        $correlationId = $payload[$key] ?? null;

        if (! is_string($correlationId) || $correlationId === '') return;

        $this->manager->set($correlationId);
    }
}
