<?php

namespace LaravelCorrelationId;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Log\LogManager;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Queue;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;
use LaravelCorrelationId\Contracts\CorrelationIdGenerator;
use LaravelCorrelationId\Exceptions\CorrelationIdExceptionResponse;
use LaravelCorrelationId\Generators\UuidGenerator;
use LaravelCorrelationId\Http\CorrelationIdRequestMiddleware;
use LaravelCorrelationId\Http\Middleware\CorrelationIdMiddleware;
use LaravelCorrelationId\Logging\CorrelationIdProcessor;
use LaravelCorrelationId\Queue\ClearCorrelationId;
use LaravelCorrelationId\Queue\RestoreCorrelationId;

class CorrelationIdServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/correlation-id.php',
            'correlation-id'
        );

        $this->app->singleton(CorrelationIdManager::class);

        $this->app->bind(
            CorrelationIdGenerator::class,
            function ($app) {
                $generator = config(
                    'correlation-id.generator',
                    UuidGenerator::class
                );

                $instance = $app->make($generator);

                if (! $instance instanceof CorrelationIdGenerator) {
                    throw new \InvalidArgumentException(
                        sprintf(
                            'Correlation ID generator [%s] must implement [%s].',
                            $generator,
                            CorrelationIdGenerator::class
                        )
                    );
                }

                return $instance;
            }
        );
    }

    public function boot(): void
    {
        $this->app['router']->aliasMiddleware(
            'correlation-id',
            CorrelationIdMiddleware::class
        );

        Http::globalRequestMiddleware(
            $this->app->make(CorrelationIdRequestMiddleware::class)
        );

        $this->publishes([
            __DIR__.'/../config/correlation-id.php' => config_path('correlation-id.php'),
        ], 'correlation-id-config');

        $this->app->booted(function (): void {
            $this->registerLogProcessor();
        });

        $this->app->afterResolving(
            ExceptionHandler::class,
            function (ExceptionHandler $handler): void {
                if (! method_exists($handler, 'respondUsing')) {
                    return;
                }

                $handler->respondUsing(
                    function ($response, \Throwable $exception, $request) {
                        return $this->app
                            ->make(CorrelationIdExceptionResponse::class)
                            ->addHeader($request, $response);
                    }
                );
            }
        );

        Queue::createPayloadUsing(function (): array {
            if (! config('correlation-id.queue.enabled', true)) {
                return [];
            }

            $manager = $this->app->make(
                CorrelationIdManager::class
            );

            if (! $manager->has()) {
                return [];
            }

            $payload = [];

            $correlationKey = config(
                'correlation-id.queue.payload_key',
                'correlation_id'
            );

            $payload[$correlationKey] = $manager->get();

            if (
                config('correlation-id.w3c.enabled', false)
                && $manager->hasTraceId()
            ) {
                $traceKey = config(
                    'correlation-id.queue.trace_payload_key',
                    'trace_id'
                );

                $payload[$traceKey] = $manager->getTraceId();
            }

            return $payload;
        });

        Event::listen(
            JobProcessing::class,
            RestoreCorrelationId::class
        );

        Event::listen(
            JobProcessed::class,
            [ClearCorrelationId::class, 'handleProcessed']
        );

        Event::listen(
            JobExceptionOccurred::class,
            [ClearCorrelationId::class, 'handleException']
        );
    }

    protected function registerLOgProcessor(): void
    {
        $logManager = $this->app->make(LogManager::class);

        $processor = $this->app->make(CorrelationIdProcessor::class);

        $logManager->getLogger()->pushProcessor($processor);
    }
}
