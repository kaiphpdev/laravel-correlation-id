<?php

namespace LaravelCorrelationId\Validation;

class TraceIdValidator
{
    public function isValid(?string $traceId): bool
    {
        if (! is_string($traceId)) {
            return false;
        }

        if (! preg_match('/^[\da-f]{32}$/i', $traceId)) {
            return false;
        }

        return strtolower($traceId) !== str_repeat('0', 32);
    }
}
