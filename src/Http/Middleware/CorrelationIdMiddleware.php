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
        $correlationId = $request->header('X-Correlation-ID');

        if (! $correlationId) {
            $correlationId = $this->generator->generate();
        }

        $this->manager->set($correlationId);

        $response = $next($request);

        $response->headers->set(
            'X-Correlation-ID',
            $correlationId
        );

        return $response;
    }
}