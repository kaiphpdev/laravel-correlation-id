<?php

namespace LaravelCorrelationId\Tests\Unit;

use LaravelCorrelationId\Tracing\TraceparentParser;
use PHPUnit\Framework\TestCase;

class TraceparentParserTest extends TestCase
{
    public function test_it_extracts_trace_id_from_valid_traceparent(): void
    {
        $parser = new TraceparentParser;

        $traceId = $parser->extractTraceId('00-4bf92f3577b34da6a3ce929d0e0e4736-00f067aa0ba902b7-01');

        $this->assertSame('4bf92f3577b34da6a3ce929d0e0e4736', $traceId);
    }

    public function test_it_rejects_invalid_traceparent(): void
    {
        $parser = new TraceparentParser;

        $this->assertNull(
            $parser->extractTraceId('invalid')
        );
    }

    public function test_it_rejects_all_zero_trace_id(): void
    {
        $parser = new TraceparentParser;

        $this->assertNull(
            $parser->extractTraceId(
                '00-00000000000000000000000000000000-00f067aa0ba902b7-01'
            )
        );
    }
}
