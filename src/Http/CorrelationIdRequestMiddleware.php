<?php

namespace LaravelCorrelationId\Http;

use LaravelCorrelationId\CorrelationIdManager;
use LaravelCorrelationId\Tracing\TraceparentGenerator;
use Psr\Http\Message\RequestInterface;

class CorrelationIdRequestMiddleware
{
    public function __construct(
        protected CorrelationIdManager $manager,
        protected TraceparentGenerator $traceparentGenerator
    ) {}

    public function __invoke(RequestInterface $request): RequestInterface
    {
        if (! config('correlation-id.http_client.enabled', true)) {
            return $request;
        }

        if (! $this->manager->has()) {
            return $request;
        }

        $correlationId = $this->manager->get();

        $header = config(
            'correlation-id.header',
            'X-Correlation-ID'
        );

        $request = $request->withHeader(
            $header,
            $correlationId
        );

        if (
            config('correlation-id.w3c.enabled', false)
            && config('correlation-id.w3c.propagate_traceparent', true)
            && $this->manager->hasTraceId()
        ) {

            $traceparent = $this->traceparentGenerator->generate(
                $this->manager->getTraceId()
            );

            if ($traceparent !== null) {
                $request = $request->withHeader(
                    'traceparent',
                    $traceparent
                );
            }
        }

        return $request;
    }
}
