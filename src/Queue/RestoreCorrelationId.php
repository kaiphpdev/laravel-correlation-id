<?php

namespace LaravelCorrelationId\Queue;

use Illuminate\Queue\Events\JobProcessing;
use LaravelCorrelationId\CorrelationIdManager;
use LaravelCorrelationId\Tracing\TraceparentGenerator;
use LaravelCorrelationId\Validation\CorrelationIdValidator;
use LaravelCorrelationId\Validation\TraceIdValidator;

class RestoreCorrelationId
{
    public function __construct(
        protected CorrelationIdManager $manager,
        protected CorrelationIdValidator $validator,
        // protected TraceparentGenerator $traceparentGenerator,
        protected TraceIdValidator $traceIdValidator
    ) {}

    public function handle(JobProcessing $event): void
    {
        $this->manager->clear();

        if (! config('correlation-id.queue.enabled', true)) {
            return;
        }

        $payload = $event->job->payload();

        $correlationKey = config(
            'correlation-id.queue.payload_key',
            'correlation_id'
        );

        $correlationId = $payload[$correlationKey] ?? null;

        if (! $this->validator->isValid($correlationId)) {
            return;
        }

        $this->manager->set($correlationId);

        if (! config('correlation-id.w3c.enabled', false)) {
            return;
        }

        $traceKey = config(
            'correlation-id.queue.trace_payload_key',
            'trace_id'
        );

        $traceId = $payload[$traceKey] ?? null;

        if (! is_string($traceId)) {
            return;
        }

        if (! $this->traceIdValidator->isValid($traceId)) {
            return;
        }

        $this->manager->setTraceId($traceId);
    }
}
