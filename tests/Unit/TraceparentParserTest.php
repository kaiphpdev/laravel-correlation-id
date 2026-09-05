<?php

namespace LaravelCorrelationId\Tests\Unit;

use LaravelCorrelationId\Tracing\TraceparentParser;
use LaravelCorrelationId\Validation\TraceIdValidator;
use PHPUnit\Framework\TestCase;

class TraceparentParserTest extends TestCase
{
    protected TraceparentParser $parser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parser = new TraceparentParser(
            new TraceIdValidator
        );
    }

    public function test_it_extracts_trace_id_from_valid_traceparent(): void
    {
        $traceId = $this->parser->extractTraceId(
            '00-4bf92f3577b34da6a3ce929d0e0e4736-00f067aa0ba902b7-01'
        );

        $this->assertSame(
            '4bf92f3577b34da6a3ce929d0e0e4736',
            $traceId
        );
    }

    public function test_it_returns_null_for_invalid_traceparent(): void
    {
        $traceId = $this->parser->extractTraceId(
            'invalid-traceparent'
        );

        $this->assertNull(
            $traceId
        );
    }

    public function test_it_returns_null_for_all_zero_trace_id(): void
    {
        $traceId = $this->parser->extractTraceId(
            '00-00000000000000000000000000000000-00f067aa0ba902b7-01'
        );

        $this->assertNull(
            $traceId
        );
    }

    public function test_it_returns_null_for_all_zero_parent_id(): void
    {
        $traceId = $this->parser->extractTraceId(
            '00-4bf92f3577b34da6a3ce929d0e0e4736-0000000000000000-01'
        );

        $this->assertNull(
            $traceId
        );
    }

    public function test_it_normalizes_uppercase_trace_id(): void
    {
        $traceId = $this->parser->extractTraceId(
            '00-4BF92F3577B34DA6A3CE929D0E0E4736-00F067AA0BA902B7-01'
        );

        $this->assertSame(
            '4bf92f3577b34da6a3ce929d0e0e4736',
            $traceId
        );
    }

    public function test_it_returns_null_for_empty_traceparent(): void
    {
        $traceId = $this->parser->extractTraceId(
            ''
        );

        $this->assertNull(
            $traceId
        );
    }

    public function test_it_returns_null_for_null_traceparent(): void
    {
        $traceId = $this->parser->extractTraceId(
            null
        );

        $this->assertNull(
            $traceId
        );
    }

    public function test_it_parses_trace_context_from_valid_traceparent(): void
    {
        $context = $this->parser->parse(
            '00-4bf92f3577b34da6a3ce929d0e0e4736-00f067aa0ba902b7-01'
        );

        $this->assertNotNull(
            $context
        );

        $this->assertSame(
            '00',
            $context['version']
        );

        $this->assertSame(
            '4bf92f3577b34da6a3ce929d0e0e4736',
            $context['trace_id']
        );

        $this->assertSame(
            '00f067aa0ba902b7',
            $context['parent_id']
        );

        $this->assertSame(
            '01',
            $context['trace_flags']
        );
    }

    public function test_it_preserves_sampled_trace_flag(): void
    {
        $context = $this->parser->parse(
            '00-4bf92f3577b34da6a3ce929d0e0e4736-00f067aa0ba902b7-01'
        );

        $this->assertNotNull(
            $context
        );

        $this->assertSame(
            '01',
            $context['trace_flags']
        );
    }

    public function test_it_parses_unsampled_trace_flag(): void
    {
        $context = $this->parser->parse(
            '00-4bf92f3577b34da6a3ce929d0e0e4736-00f067aa0ba902b7-00'
        );

        $this->assertNotNull(
            $context
        );

        $this->assertSame(
            '00',
            $context['trace_flags']
        );
    }

    public function test_it_returns_null_for_forbidden_version(): void
    {
        $context = $this->parser->parse(
            'ff-4bf92f3577b34da6a3ce929d0e0e4736-00f067aa0ba902b7-01'
        );

        $this->assertNull(
            $context
        );
    }
}
