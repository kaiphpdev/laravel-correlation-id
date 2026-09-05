<?php

namespace LaravelCorrelationId\Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use LaravelCorrelationId\CorrelationIdManager;
use LaravelCorrelationId\Tests\Fixtures\CustomGenerator;
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

        Route::middleware('correlation-id')
            ->get('/test-exception', function () {
                throw new \RuntimeException('Test exception');
            });

        Route::middleware('correlation-id')
            ->get('/test-request-attribute', function (Request $request) {
                return response()->json([
                    'correlation_id' => $request->attributes->get('correlation_id'),
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

    public function test_it_generates_a_new_id_when_incoming_id_is_invalid(): void
    {
        $response = $this
            ->withHeader(
                'X-Correlation-ID',
                'invalid correlation id'
            )
            ->get('/test');

        $response->assertOk();

        $generatedId = $response->headers->get(
            'X-Correlation-ID'
        );

        $this->assertNotNull($generatedId);

        $this->assertNotSame(
            'invalid correlation id',
            $generatedId
        );
    }

    public function test_it_clears_correlation_id_after_request(): void
    {
        $manager = $this->app->make(
            CorrelationIdManager::class
        );

        $this
            ->withHeader('X-Correlation-ID', 'abc-123')
            ->get('/test')
            ->assertOk();

        $this->assertFalse(
            $manager->has()
        );
    }

    public function test_it_clears_correlation_id_when_request_throws_exception(): void
    {
        $this->withoutExceptionHandling();

        $manager = $this->app->make(
            CorrelationIdManager::class
        );

        try {
            $this
                ->withHeader('X-Correlation-ID', 'abc-123')
                ->get('/test-exception');
        } catch (\RuntimeException $exception) {
            $this->assertSame(
                'Test exception',
                $exception->getMessage()
            );
        }

        $this->assertFalse(
            $manager->has()
        );
    }

    public function test_exception_response_contains_correlation_id(): void
    {
        $response = $this
            ->withHeader('X-Correlation-ID', 'abc-123')
            ->get('/test-exception');

        $response->assertStatus(500);

        $response->assertHeader(
            'X-Correlation-ID',
            'abc-123'
        );
    }

    public function test_it_stores_correlation_id_in_request_attributes(): void
    {
        $response = $this
            ->withHeader('X-Correlation-ID', 'abc-123')
            ->get('/test-request-attribute');

        $response->assertOk();

        $response->assertJson([
            'correlation_id' => 'abc-123',
        ]);
    }

    public function test_it_uses_configured_request_attribute_name(): void
    {
        config()->set(
            'correlation-id.request_attribute',
            'request_id'
        );

        Route::middleware('correlation-id')
            ->get('/test-custom-attribute', function (Request $request) {
                return response()->json([
                    'request_id' => $request->attributes->get(
                        'request_id'
                    ),
                ]);
            });

        $response = $this
            ->withHeader('X-Correlation-ID', 'abc-123')
            ->get('/test-custom-attribute');

        $response->assertOk();

        $response->assertJson([
            'request_id' => 'abc-123',
        ]);
    }

    public function test_it_uses_configured_generator_when_creating_new_id(): void
    {
        config()->set(
            'correlation-id.generator',
            CustomGenerator::class
        );

        $response = $this->get('/test');

        $response->assertOk();

        $response->assertHeader(
            'X-Correlation-ID',
            'custom-id-123'
        );
    }

    public function test_exception_response_uses_configured_header(): void
    {
        config()->set(
            'correlation-id.header',
            'X-Request-ID'
        );

        $response = $this
            ->withHeader(
                'X-Request-ID',
                'abc-123'
            )
            ->get('/test-exception');

        $response->assertStatus(500);

        $response->assertHeader(
            'X-Request-ID',
            'abc-123'
        );
    }

    public function test_exception_response_contains_generated_correlation_id(): void
    {
        $response = $this->get(
            '/test-exception'
        );

        $response->assertStatus(500);

        $correlationId = $response
            ->headers
            ->get('X-Correlation-ID');

        $this->assertNotNull(
            $correlationId
        );

        $this->assertNotSame(
            '',
            $correlationId
        );
    }

    public function test_it_clears_stale_correlation_id_before_processing_request(): void
    {
        $manager = $this->app->make(CorrelationIdManager::class);

        $manager->set('stale-id');

        $response = $this
            ->withHeader(
                'X-Correlation-ID',
                'current-id'
            )->get('/test-request-attribute');

        $response->assertOk();
        $response->assertJson([
            'correlation_id' => 'current-id',
        ]);

        $this->assertFalse(
            $manager->has()
        );
    }

    public function test_stale_id_is_not_reused_when_new_request_has_no_header(): void
    {

        $manager = $this->app->make(CorrelationIdManager::class);
        $manager->set('stale-id');

        $response = $this->get('/test-request-attribute');

        $response->assertOk();

        $generatedId = $response->json('correlation_id');

        $this->assertNotNull($generatedId);
        $this->assertNotSame('stale-id', $generatedId);

        $this->assertFalse($manager->has());
    }
}
