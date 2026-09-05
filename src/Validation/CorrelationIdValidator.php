<?php

namespace LaravelCorrelationId\Validation;

class CorrelationIdValidator
{
    public function isValid(?string $correlationId): bool
    {
        if ($correlationId === null || $correlationId === '') {
            return false;
        }

        $maxLength = (int) config(
            'correlation-id.incoming.max_length',
            128
        );

        if (strlen($correlationId) > $maxLength) {
            return false;
        }

        $pattern = config(
            'correlation-id.incoming.pattern',
            '/^[A-Za-z0-9._:-]+$/'
        );

        return preg_match($pattern, $correlationId) === 1;
    }
}
