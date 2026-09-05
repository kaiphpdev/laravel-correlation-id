<?php

namespace LaravelCorrelationId\Queue;

use Illuminate\Queue\Events\JobProcessing;
use LaravelCorrelationId\CorrelationIdManager;
use LaravelCorrelationId\Validation\CorrelationIdValidator;

class RestoreCorrelationId
{
    public function __construct(
        protected CorrelationIdManager $manager,
        protected CorrelationIdValidator $validator
    ) {}

    public function handle(JobProcessing $event): void
    {
        $this->manager->clear();
        if (! config('correlation-id.queue.enabled', true)) {
            return;
        }

        $payload = $event->job->payload();

        $key = config(
            'correlation-id.queue.payload_key',
            'correlation_id'
        );

        $correlationId = $payload[$key] ?? null;

        if (!$this->validator->isValid($correlationId)) return;

        $this->manager->set($correlationId);
    }
}
