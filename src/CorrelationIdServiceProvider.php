<?php

namespace LaravelCorrelationId;

use Illuminate\Support\ServiceProvider;
use LaravelCorrelationId\Contracts\CorrelationIdGenerator;
use LaravelCorrelationId\Generators\UuidGenerator;
use LaravelCorrelationId\Http\Middleware\CorrelationIdMiddleware;

use Illuminate\Log\LogManager;
use LaravelCorrelationId\Logging\CorrelationIdProcessor;
use Illuminate\Support\Facades\Http;
use LaravelCorrelationId\Http\CorrelationIdRequestMiddleware;
use Illuminate\Contracts\Debug\ExceptionHandler;
use LaravelCorrelationId\Exceptions\CorrelationIdExceptionResponse;
use Illuminate\Queue\Queue;

use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Event;
use LaravelCorrelationId\Queue\ClearCorrelationId;
use LaravelCorrelationId\Queue\RestoreCorrelationId;


class CorrelationIdServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/correlation-id.php',
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
            __DIR__ . '/../config/correlation-id.php'
            => config_path('correlation-id.php'),
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
                    function ($response, $exception, $request) {
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

            if (! $manager->has()) return [];

            $key = config(
                'correlation-id.queue.payload_key',
                'correlation_id'
            );

            return [
                $key => $manager->get(),
            ];
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
