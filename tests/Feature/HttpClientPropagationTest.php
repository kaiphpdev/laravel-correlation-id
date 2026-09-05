<?php

namespace LaravelCorrelationId\Tests\Feature;

use GuzzleHttp\Psr7\Request;
use LaravelCorrelationId\CorrelationIdManager;
use LaravelCorrelationId\Http\CorrelationIdRequestMiddleware;
use LaravelCorrelationId\Tests\TestCase;
use LaravelCorrelationId\Tracing\TraceparentGenerator;

class HttpClientPropagationTest extends TestCase
{
    public function test_it_adds_correlation_id_to_outgoing_request(): void
    {
        $manager = $this->app->make(CorrelationIdManager::class);
        $manager->set('client-correlation-id');

        $middleware = new CorrelationIdRequestMiddleware(
            $manager,
            new TraceparentGenerator
        );

        $request = new Request(
            'GET',
            'https://example.com'
        );

        $request = $middleware($request);

        $this->assertSame(
            'client-correlation-id',
            $request->getHeaderLine('X-Correlation-ID')
        );
    }

    public function test_it_adds_traceparent_when_w3c_is_enabled(): void
    {
        config()->set('correlation-id.w3c.enabled', true);
        config()->set(
            'correlation-id.w3c.propagate_traceparent',
            true
        );

        $traceId = '4bf92f3577b34da6a3ce929d0e0e4736';

        $manager = $this->app->make(CorrelationIdManager::class);

        $manager->set($traceId);
        $manager->setTraceId($traceId);

        $middleware = new CorrelationIdRequestMiddleware(
            $manager,
            new TraceparentGenerator
        );

        $request = new Request(
            'GET',
            'https://example.com'
        );

        $request = $middleware($request);

        $this->assertSame(
            $traceId,
            $request->getHeaderLine('X-Correlation-ID')
        );

        $this->assertMatchesRegularExpression(
            '/^00-'.$traceId.'-[\da-f]{16}-00$/',
            $request->getHeaderLine('traceparent')
        );
    }

    public function test_it_does_not_add_traceparent_for_uuid_correlation_id(): void
    {
        config()->set('correlation-id.w3c.enabled', true);
        config()->set(
            'correlation-id.w3c.propagate_traceparent',
            true
        );

        $correlationId = '3723963a-4a1b-4775-86de-6b59aa18e03c';

        $manager = $this->app->make(CorrelationIdManager::class);
        $manager->set($correlationId);

        $middleware = new CorrelationIdRequestMiddleware(
            $manager,
            new TraceparentGenerator
        );

        $request = new Request(
            'GET',
            'https://example.com'
        );

        $request = $middleware($request);

        $this->assertSame(
            $correlationId,
            $request->getHeaderLine('X-Correlation-ID')
        );

        $this->assertFalse(
            $request->hasHeader('traceparent')
        );
    }

    public function test_it_does_not_add_traceparent_when_w3c_is_disabled(): void
    {
        config()->set('correlation-id.w3c.enabled', false);

        $traceId = '4bf92f3577b34da6a3ce929d0e0e4736';

        $manager = $this->app->make(CorrelationIdManager::class);
        $manager->set($traceId);

        $middleware = new CorrelationIdRequestMiddleware(
            $manager,
            new TraceparentGenerator
        );

        $request = new Request(
            'GET',
            'https://example.com'
        );

        $request = $middleware($request);

        $this->assertSame(
            $traceId,
            $request->getHeaderLine('X-Correlation-ID')
        );

        $this->assertFalse(
            $request->hasHeader('traceparent')
        );
    }

    public function test_it_does_not_modify_request_when_http_client_propagation_is_disabled(): void
    {
        config()->set(
            'correlation-id.http_client.enabled',
            false
        );

        $manager = $this->app->make(CorrelationIdManager::class);
        $manager->set('client-correlation-id');

        $middleware = new CorrelationIdRequestMiddleware(
            $manager,
            new TraceparentGenerator
        );

        $request = new Request(
            'GET',
            'https://example.com'
        );

        $request = $middleware($request);

        $this->assertFalse(
            $request->hasHeader('X-Correlation-ID')
        );

        $this->assertFalse(
            $request->hasHeader('traceparent')
        );
    }

    public function test_it_does_not_modify_request_without_active_correlation_id(): void
    {
        $manager = $this->app->make(CorrelationIdManager::class);
        $manager->clear();

        $middleware = new CorrelationIdRequestMiddleware(
            $manager,
            new TraceparentGenerator
        );

        $request = new Request(
            'GET',
            'https://example.com'
        );

        $request = $middleware($request);

        $this->assertFalse(
            $request->hasHeader('X-Correlation-ID')
        );

        $this->assertFalse(
            $request->hasHeader('traceparent')
        );
    }
}
