<?php

namespace LaravelCorrelationId\Tracing;

class TraceparentParser
{
    public function extractTraceId(?string $traceparent): ?string
    {
        if (! is_string($traceparent) || $traceparent === '') {
            return null;
        }

        if (! preg_match(
            '/^[\da-f]{2}-([\da-f]{32})-[\da-f]{16}-[\da-f]{2}$/i',
            $traceparent,
            $matches
        )) {
            return null;
        }

        $traceId = strtolower($matches[1]);

        if ($traceId === str_repeat('0', 32)) {
            return null;
        }

        return $traceId;
    }
}
