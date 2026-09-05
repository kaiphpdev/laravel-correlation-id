<?php

namespace LaravelCorrelationId\Tracing;

use LaravelCorrelationId\Validation\TraceIdValidator;

class TraceparentGenerator
{
    public function __construct(
        protected TraceIdValidator $validator = new TraceIdValidator
    ) {}

    public function generate(string $traceId): ?string
    {
        if (! $this->validator->isValid($traceId)) {
            return null;
        }

        $parentId = bin2hex(random_bytes(8));

        return sprintf(
            '00-%s-%s-00',
            strtolower($traceId),
            $parentId
        );
    }
}
