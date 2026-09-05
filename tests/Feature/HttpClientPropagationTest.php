<?php

namespace LaravelCorrelationId\Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use LaravelCorrelationId\CorrelationIdManager;
use LaravelCorrelationId\Tests\TestCase;

class HttpClientPropagationTest extends TestCase
{
    public function test_it_propagates_correlation_id_to_outgoing_http_requests(): void
    {
        Http::fake();

        $manager = $this->app->make(
            CorrelationIdManager::class
        );

        $manager->set('abc-123');

        Http::get('https://example.test');

        Http::assertSent(function (Request $request) {
            return $request->hasHeader(
                'X-Correlation-ID',
                'abc-123'
            );
        });
    }

    public function test_it_does_not_add_header_when_correlation_id_is_missing(): void
    {
        Http::fake();

        Http::get('https://example.test');

        Http::assertSent(function (Request $request) {
            return ! $request->hasHeader(
                'X-Correlation-ID'
            );
        });
    }

    public function test_it_does_not_propagate_id_when_http_client_is_disabled(): void
    {
        config()->set(
            'correlation-id.http_client.enabled',
            false
        );

        Http::fake();

        $manager = $this->app->make(
            CorrelationIdManager::class
        );

        $manager->set('abc-123');

        Http::get('https://example.test');

        Http::assertSent(function (Request $request) {
            return ! $request->hasHeader(
                'X-Correlation-ID'
            );
        });
    }

    public function test_it_uses_custom_header_for_outgoing_requests(): void
    {
        config()->set(
            'correlation-id.header',
            'X-Request-ID'
        );

        Http::fake();

        $manager = $this->app->make(
            CorrelationIdManager::class
        );

        $manager->set('abc-123');

        Http::get('https://example.test');

        Http::assertSent(function (Request $request) {
            return $request->hasHeader(
                'X-Request-ID',
                'abc-123'
            );
        });
    }
}