<?php

namespace LaravelCorrelationId\Logging;

use LaravelCorrelationId\CorrelationIdManager;
use Monolog\LogRecord;

class CorrelationIdProcessor
{
    public function __construct(
        protected CorrelationIdManager $manager
    ) {}

    public function __invoke(LogRecord $record): LogRecord
    {
        if (! config('correlation-id.logging.enabled', true)) {
            return $record;
        }

        if (! $this->manager->has()) {
            return $record;
        }

        $key = config(
            'correlation-id.logging.key',
            'correlation_id'
        );

        $record->extra[$key] = $this->manager->get();

        return $record;
    }
}
