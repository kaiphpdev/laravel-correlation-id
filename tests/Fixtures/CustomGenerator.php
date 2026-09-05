<?php

namespace LaravelCorrelationId\Tests\Fixtures;

use LaravelCorrelationId\Contracts\CorrelationIdGenerator;

class CustomGenerator implements CorrelationIdGenerator
{
    public function generate(): string
    {
        return 'custom-id-123';
    }
}
