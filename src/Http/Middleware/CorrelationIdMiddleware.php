<?php

namespace LaravelCorrelationId\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use LaravelCorrelationId\CorrelationIdManager;
use LaravelCorrelationId\Contracts\CorrelationIdGenerator;
use Symfony\Component\HttpFoundation\Response;

class CorrelationIdMiddleware
{
    public function __construct(
        protected CorrelationIdManager $manager,
        protected CorrelationIdGenerator $generator
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $header = config('correlation-id.header', 'X-Correlation-ID');

        $correlationId = null;

        if (config('correlation-id.trust_incoming', true)) {
            $correlationId = $request->header($header);
        }

        if (! $correlationId) {
            $correlationId = $this->generator->generate();
        }

        $this->manager->set($correlationId);

        $response = $next($request);

        $response->headers->set(
            $header,
            $correlationId
        );

        return $response;
    }
}