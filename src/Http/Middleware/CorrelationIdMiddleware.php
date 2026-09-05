<?php

namespace LaravelCorrelationId\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use LaravelCorrelationId\Contracts\CorrelationIdGenerator;
use LaravelCorrelationId\CorrelationIdManager;
use LaravelCorrelationId\Tracing\TraceparentParser;
use LaravelCorrelationId\Validation\CorrelationIdValidator;
use Symfony\Component\HttpFoundation\Response;

class CorrelationIdMiddleware
{
    public function __construct(
        protected CorrelationIdManager $manager,
        protected CorrelationIdGenerator $generator,
        protected CorrelationIdValidator $validator,
        protected TraceparentParser $traceparentParser
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->manager->clear();

        $header = config(
            'correlation-id.header',
            'X-Correlation-ID'
        );

        $correlationId = null;
        $traceId = null;
        $traceContext = null;

        if (
            config('correlation-id.w3c.enabled', false)
            && config('correlation-id.w3c.accept_traceparent', true)
        ) {
            $traceContext = $this->traceparentParser->parse(
                $request->header('traceparent')
            );

            if ($traceContext !== null) {
                $traceId = $traceContext['trace_id'];
                $correlationId = $traceId;
            }
        }

        if (
            ! $correlationId
            && config('correlation-id.trust_incoming', true)
        ) {
            $incomingId = $request->header(
                $header
            );

            if ($this->validator->isValid($incomingId)) {
                $correlationId = $incomingId;
            }
        }

        if (! $correlationId) {
            $correlationId = $this->generator->generate();
        }

        /*
         * Store the active correlation ID.
         */
        $this->manager->set(
            $correlationId
        );

        /*
         * Store W3C trace context only when the incoming
         * traceparent was valid.
         */
        if (
            $traceId !== null
            && $traceContext !== null
        ) {
            $this->manager->setTraceId(
                $traceId
            );

            $this->manager->setTraceFlags(
                $traceContext['trace_flags']
            );
        }

        $attribute = config(
            'correlation-id.request_attribute',
            'correlation_id'
        );

        $request->attributes->set(
            $attribute,
            $correlationId
        );

        try {
            $response = $next(
                $request
            );

            $response->headers->set(
                $header,
                $correlationId
            );

            return $response;
        } finally {
            $this->manager->clear();
        }
    }
}
