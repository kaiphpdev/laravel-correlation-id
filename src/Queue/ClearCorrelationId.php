<?php

namespace LaravelCorrelationId\Queue;

use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobProcessed;
use LaravelCorrelationId\CorrelationIdManager;

class ClearCorrelationId
{
    public function __construct(
        protected CorrelationIdManager $manager
    ) {}

    public function handleProcessed(JobProcessed $event): void
    {
        $this->manager->clear();
    }

    public function handleException(JobExceptionOccurred $event): void
    {
        $this->manager->clear();
    }
}
