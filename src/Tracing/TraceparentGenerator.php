<?php

namespace LaravelCorrelationId\Tracing;

use LaravelCorrelationId\Validation\TraceIdValidator;

class TraceparentGenerator
{
    public function __construct(
        protected TraceIdValidator $validator = new TraceIdValidator
    ) {}

    public function generate(
        string $traceId,
        string $traceFlags = '00'
    ): ?string {
        if (! $this->validator->isValid($traceId)) {
            return null;
        }

        if (! preg_match('/^[\da-f]{2}$/i', $traceFlags)) {
            $traceFlags = '00';
        }

        $parentId = bin2hex(
            random_bytes(8)
        );

        return sprintf(
            '00-%s-%s-%s',
            strtolower($traceId),
            $parentId,
            strtolower($traceFlags)
        );
    }
}
