<?php

namespace LaravelCorrelationId\Tracing;

class TraceparentGenerator
{
    public function generate(string $traceId): ?string
    {
        if (! $this->isValidTraceId($traceId)) {
            return null;
        }

        $parentId = bin2hex(random_bytes(8));

        return sprintf(
            '00-%s-%s-00',
            strtolower($traceId),
            $parentId
        );
    }

    private function isValidTraceId(string $traceId): bool
    {
        if (! preg_match('/^[\da-f]{32}$/i', $traceId)) {
            return false;
        }

        return strtolower($traceId) !== str_repeat('0', 32);
    }
}
