<?php

namespace LaravelCorrelationId\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use LaravelCorrelationId\Contracts\CorrelationIdGenerator;
use LaravelCorrelationId\CorrelationIdManager;
use LaravelCorrelationId\Validation\CorrelationIdValidator;
use Symfony\Component\HttpFoundation\Response;

class CorrelationIdMiddleware
{
    public function __construct(
        protected CorrelationIdManager $manager,
        protected CorrelationIdGenerator $generator,
        protected CorrelationIdValidator $validator
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->manager->clear();

        $header = config(
            'correlation-id.header',
            'X-Correlation-ID'
        );

        $correlationId = null;

        if (config('correlation-id.trust_incoming', true)) {
            $incomingId = $request->header($header);

            if ($this->validator->isValid($incomingId)) {
                $correlationId = $incomingId;
            }
        }

        if (! $correlationId) {
            $correlationId = $this->generator->generate();
        }

        $this->manager->set($correlationId);

        $attributes = config('correlation-id.request_attribute', 'correlation_id');
        $request->attributes->set($attributes, $correlationId);

        try {
            $response = $next($request);

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
