<?php

namespace LaravelCorrelationId\Logging;

use LaravelCorrelationId\CorrelationIdManager;
use Monolog\LogRecord;

class CorrelationIdProcessor
{
    public function __construct(protected CorrelationIdManager $manager) {}

    public function __invoke(LogRecord $record): LogRecord
    {
        if (! $this->manager->has()) {
            return $record;
        }

        $record->extra['correlation_id'] = $this->manager->get();
        return $record;
    }
}
