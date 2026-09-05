<?php

namespace LaravelCorrelationId\Generators;

use Illuminate\Support\Str;
use LaravelCorrelationId\Contracts\CorrelationIdGenerator;


class UuidGenerator implements CorrelationIdGenerator
{
    public function generate(): string
    {
        return (string) Str::uuid();
    }
}
