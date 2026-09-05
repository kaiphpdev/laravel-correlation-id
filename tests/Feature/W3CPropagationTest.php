<?php

namespace LaravelCorrelationId\Tests\Feature;

use GuzzleHttp\Psr7\Request as PsrRequest;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Http\Request;
use Illuminate\Queue\Events\JobProcessing;
use LaravelCorrelationId\CorrelationIdManager;
use LaravelCorrelationId\Http\CorrelationIdRequestMiddleware;
use LaravelCorrelationId\Http\Middleware\CorrelationIdMiddleware;
use LaravelCorrelationId\Queue\RestoreCorrelationId;
use LaravelCorrelationId\Tests\TestCase;
use LaravelCorrelationId\Tracing\TraceparentGenerator;
use LaravelCorrelationId\Validation\CorrelationIdValidator;
use LaravelCorrelationId\Validation\TraceIdValidator;
use Symfony\Component\HttpFoundation\Response;

class W3CPropagationTest extends TestCase
{
    public function test_w3c_context_survives_request_queue_and_outgoing_http_flow(): void
    {
        config()->set(
            'correlation-id.w3c.enabled',
            true
        );

        config()->set(
            'correlation-id.w3c.accept_traceparent',
            true
        );

        config()->set(
            'correlation-id.w3c.propagate_traceparent',
            true
        );

        $traceId = '4bf92f3577b34da6a3ce929d0e0e4736';

        $incomingTraceparent = sprintf(
            '00-%s-00f067aa0ba902b7-01',
            $traceId
        );

        // 1. Incoming HTTP request

        $request = Request::create(
            '/test',
            'GET'
        );

        $request->headers->set(
            'traceparent',
            $incomingTraceparent
        );

        $this->assertSame(
            $incomingTraceparent,
            $request->header('traceparent')
        );

        $manager = new CorrelationIdManager;

        $this->app->instance(
            CorrelationIdManager::class,
            $manager
        );

        $middleware = $this->app->make(
            CorrelationIdMiddleware::class
        );

        $capturedCorrelationId = null;
        $capturedTraceId = null;
        $capturedTraceFlags = null;

        $response = $middleware->handle(
            $request,
            function () use (
                $manager,
                &$capturedCorrelationId,
                &$capturedTraceId,
                &$capturedTraceFlags
            ): Response {
                $capturedCorrelationId = $manager->get();
                $capturedTraceId = $manager->getTraceId();
                $capturedTraceFlags = $manager->getTraceFlags();

                return new Response('OK');
            }
        );

        $this->assertSame(
            $traceId,
            $capturedCorrelationId
        );

        $this->assertSame(
            $traceId,
            $capturedTraceId
        );

        $this->assertSame(
            '01',
            $capturedTraceFlags
        );

        /*
         * Middleware must clean request-scoped state.
         */
        $this->assertFalse(
            $manager->has()
        );

        $this->assertFalse(
            $manager->hasTraceId()
        );

        /*
         * ---------------------------------------------------------
         * 2. Simulate queue payload created during request
         * ---------------------------------------------------------
         *
         * The actual QueuePayloadTest already verifies Laravel's
         * createPayloadUsing hook. Here we carry the context that
         * was captured while the request was active.
         */

        $payload = [
            'correlation_id' => $capturedCorrelationId,
            'trace_id' => $capturedTraceId,
            'trace_flags' => $capturedTraceFlags,
        ];

        /*
         * ---------------------------------------------------------
         * 3. Queue worker restores context
         * ---------------------------------------------------------
         */

        $job = $this->createMock(
            Job::class
        );

        $job->expects(
            $this->once()
        )->method(
            'payload'
        )->willReturn(
            $payload
        );

        $event = new JobProcessing(
            'database',
            $job
        );

        $restoreListener = new RestoreCorrelationId(
            $manager,
            $this->app->make(
                CorrelationIdValidator::class
            ),
            new TraceIdValidator
        );

        $restoreListener->handle(
            $event
        );

        $this->assertSame(
            $traceId,
            $manager->get()
        );

        $this->assertSame(
            $traceId,
            $manager->getTraceId()
        );

        $this->assertSame(
            '01',
            $manager->getTraceFlags()
        );

        /*
         * ---------------------------------------------------------
         * 4. Outgoing HTTP request
         * ---------------------------------------------------------
         */

        $httpMiddleware = new CorrelationIdRequestMiddleware(
            $manager,
            new TraceparentGenerator(
                new TraceIdValidator
            )
        );

        $outgoingRequest = new PsrRequest(
            'GET',
            'https://example.com'
        );

        $outgoingRequest = $httpMiddleware(
            $outgoingRequest
        );

        $outgoingTraceparent = $outgoingRequest->getHeaderLine(
            'traceparent'
        );

        $this->assertMatchesRegularExpression(
            sprintf(
                '/^00-%s-[\da-f]{16}-01$/',
                $traceId
            ),
            $outgoingTraceparent
        );

        /*
         * The correlation header should also survive.
         */
        $this->assertSame(
            $traceId,
            $outgoingRequest->getHeaderLine(
                'X-Correlation-ID'
            )
        );
    }
}
