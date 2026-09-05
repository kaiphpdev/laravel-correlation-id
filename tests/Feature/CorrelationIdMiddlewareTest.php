<?php

namespace LaravelCorrelationId\Tests\Feature;

use Illuminate\Support\Facades\Route;
use LaravelCorrelationId\Tests\TestCase;

class CorrelationIdMiddlewareTest extends TestCase
{
    protected function defineRoutes($router): void
    {
        Route::middleware('correlation-id')
            ->get('/test', function () {
                return response()->json([
                    'message' => 'ok',
                ]);
            });
    }

    public function test_it_generates_a_correlation_id_when_header_is_missing(): void
    {
        $response = $this->get('/test');

        $response->assertOk();

        $response->assertHeader(
            'X-Correlation-ID'
        );
    }

    public function test_it_uses_an_existing_correlation_id(): void
    {
        $response = $this
            ->withHeader('X-Correlation-ID', 'existing-id-123')
            ->get('/test');

        $response->assertOk();

        $response->assertHeader(
            'X-Correlation-ID',
            'existing-id-123'
        );
    }

    public function test_it_ignores_incoming_id_when_trust_is_disabled(): void
    {
        config()->set(
            'correlation-id.trust_incoming',
            false
        );

        $response = $this
            ->withHeader('X-Correlation-ID', 'external-id')
            ->get('/test');

        $response->assertOk();

        $generatedId = $response->headers->get(
            'X-Correlation-ID'
        );

        $this->assertNotNull($generatedId);

        $this->assertNotSame(
            'external-id',
            $generatedId
        );
    }
}