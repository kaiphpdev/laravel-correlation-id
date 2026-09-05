<?php

namespace LaravelCorrelationId\Http;

use LaravelCorrelationId\CorrelationIdManager;
use Psr\Http\Message\RequestInterface;

class CorrelationIdRequestMiddleware
{
    public function __construct(
        protected CorrelationIdManager $manager
    ) {}

    public function __invoke(RequestInterface $request): RequestInterface
    {
        if (! config('correlation-id.http_client.enabled', true)) {
            return $request;
        }

        if (! $this->manager->has()) {
            return $request;
        }

        $header = config(
            'correlation-id.header',
            'X-Correlation-ID'
        );

        return $request->withHeader(
            $header,
            $this->manager->get()
        );
    }
}
