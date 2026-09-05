<?php

namespace LaravelCorrelationId\Contracts;

interface CorrelationIdGenerator
{
    public function generate(): string;
}
