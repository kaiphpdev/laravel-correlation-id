<?php

namespace LaravelCorrelationId\Tests\Unit;

use LaravelCorrelationId\Validation\TraceIdValidator;
use PHPUnit\Framework\TestCase;

class TraceIdValidatorTest extends TestCase
{
    public function test_it_accepts_valid_trace_id(): void
    {
        $validator = new TraceIdValidator;

        $this->assertTrue(
            $validator->isValid(
                '4bf92f3577b34da6a3ce929d0e0e4736'
            )
        );
    }

    public function test_it_accepts_uppercase_trace_id(): void
    {
        $validator = new TraceIdValidator;

        $this->assertTrue(
            $validator->isValid(
                '4BF92F3577B34DA6A3CE929D0E0E4736'
            )
        );
    }

    public function test_it_rejects_invalid_length(): void
    {
        $validator = new TraceIdValidator;

        $this->assertFalse(
            $validator->isValid('abc123')
        );
    }

    public function test_it_rejects_non_hexadecimal_value(): void
    {
        $validator = new TraceIdValidator;

        $this->assertFalse(
            $validator->isValid(
                'zzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzz'
            )
        );
    }

    public function test_it_rejects_all_zero_trace_id(): void
    {
        $validator = new TraceIdValidator;

        $this->assertFalse(
            $validator->isValid(
                '00000000000000000000000000000000'
            )
        );
    }

    public function test_it_rejects_null(): void
    {
        $validator = new TraceIdValidator;

        $this->assertFalse(
            $validator->isValid(null)
        );
    }
}
