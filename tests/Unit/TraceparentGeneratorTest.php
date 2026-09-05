<?php

namespace LaravelCorrelationId\Tests\Unit;

use LaravelCorrelationId\Tracing\TraceparentGenerator;
use PHPUnit\Framework\TestCase;

class TraceparentGeneratorTest extends TestCase
{
    public function test_it_generates_traceparent_from_valid_trace_id(): void
    {
        $generator = new TraceparentGenerator;

        $traceparent = $generator->generate(
            '4bf92f3577b34da6a3ce929d0e0e4736'
        );

        $this->assertNotNull($traceparent);

        $this->assertMatchesRegularExpression(
            '/^00-4bf92f3577b34da6a3ce929d0e0e4736-[\da-f]{16}-00$/',
            $traceparent
        );
    }

    public function test_it_returns_null_for_invalid_trace_id(): void
    {
        $generator = new TraceparentGenerator;

        $this->assertNull(
            $generator->generate('client-correlation-id')
        );
    }

    public function test_it_returns_null_for_uuid_correlation_id(): void
    {
        $generator = new TraceparentGenerator;

        $this->assertNull(
            $generator->generate(
                '3723963a-4a1b-4775-86de-6b59aa18e03c'
            )
        );
    }

    public function test_it_returns_null_for_all_zero_trace_id(): void
    {
        $generator = new TraceparentGenerator;

        $this->assertNull(
            $generator->generate(
                '00000000000000000000000000000000'
            )
        );
    }

    public function test_it_generates_new_parent_id_for_each_request(): void
    {
        $generator = new TraceparentGenerator;

        $traceId = '4bf92f3577b34da6a3ce929d0e0e4736';

        $first = $generator->generate($traceId);
        $second = $generator->generate($traceId);

        $this->assertNotSame($first, $second);
    }
}
