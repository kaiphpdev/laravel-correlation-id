<?php

namespace LaravelCorrelationId\Tests\Unit;

use LaravelCorrelationId\Validation\CorrelationIdValidator;
use Orchestra\Testbench\TestCase;

class CorrelationIdValidatorTest extends TestCase
{
    public function test_it_accepts_a_valid_correlation_id(): void
    {
        $validator = new CorrelationIdValidator();

        $this->assertTrue(
            $validator->isValid('abc-123')
        );
    }

    public function test_it_rejects_an_empty_correlation_id(): void
    {
        $validator = new CorrelationIdValidator();

        $this->assertFalse(
            $validator->isValid('')
        );
    }

    public function test_it_rejects_a_correlation_id_that_is_too_long(): void
    {
        config()->set(
            'correlation-id.incoming.max_length',
            10
        );

        $validator = new CorrelationIdValidator();

        $this->assertFalse(
            $validator->isValid('12345678901')
        );
    }

    public function test_it_rejects_invalid_characters(): void
    {
        $validator = new CorrelationIdValidator();

        $this->assertFalse(
            $validator->isValid('abc 123')
        );
    }

    public function test_it_respects_a_custom_pattern(): void
    {
        config()->set(
            'correlation-id.incoming.pattern',
            '/^[0-9]+$/'
        );

        $validator = new CorrelationIdValidator();

        $this->assertTrue(
            $validator->isValid('123456')
        );

        $this->assertFalse(
            $validator->isValid('abc-123')
        );
    }
}