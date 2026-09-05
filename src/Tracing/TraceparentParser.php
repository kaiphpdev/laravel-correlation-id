<?php

namespace LaravelCorrelationId\Tracing;

use LaravelCorrelationId\Validation\TraceIdValidator;

class TraceparentParser
{
    public function __construct(
        protected TraceIdValidator $traceIdValidator
    ) {}

    public function extractTraceId(?string $traceparent): ?string
    {
        if (! is_string($traceparent) || $traceparent === '') {
            return null;
        }

        if (! preg_match(
            '/^([\da-f]{2})-([\da-f]{32})-([\da-f]{16})-([\da-f]{2})$/i',
            $traceparent,
            $matches
        )) {
            return null;
        }

        $version = strtolower($matches[1]);
        $traceId = strtolower($matches[2]);
        $parentId = strtolower($matches[3]);

        if ($version === 'ff') {
            return null;
        }

        if (! $this->traceIdValidator->isValid($traceId)) {
            return null;
        }

        if ($parentId === str_repeat('0', 16)) {
            return null;
        }

        return $traceId;
    }
}