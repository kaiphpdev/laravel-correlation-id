<?php

namespace LaravelCorrelationId\Tests\Unit;

use DateTimeImmutable;
use LaravelCorrelationId\CorrelationIdManager;
use LaravelCorrelationId\Logging\CorrelationIdProcessor;
use Monolog\Level;
use Monolog\LogRecord;
use Orchestra\Testbench\TestCase;

class CorrelationIdProcessorTest extends TestCase
{
    public function test_it_adds_correlation_id_to_log_record(): void
    {
        $manager = new CorrelationIdManager;

        $manager->set('abc-123');

        $processor = new CorrelationIdProcessor($manager);

        $record = new LogRecord(
            datetime: new DateTimeImmutable,
            channel: 'testing',
            level: Level::Info,
            message: 'Test message'
        );

        $processedRecord = $processor($record);

        $this->assertSame(
            'abc-123',
            $processedRecord->extra['correlation_id']
        );
    }

    public function test_it_does_not_modify_record_when_id_is_missing(): void
    {
        $manager = new CorrelationIdManager;

        $processor = new CorrelationIdProcessor($manager);

        $record = new LogRecord(
            datetime: new DateTimeImmutable,
            channel: 'testing',
            level: Level::Info,
            message: 'Test message'
        );

        $processedRecord = $processor($record);

        $this->assertArrayNotHasKey(
            'correlation_id',
            $processedRecord->extra
        );
    }

    public function test_it_does_not_add_id_when_logging_is_disabled(): void
    {
        config()->set(
            'correlation-id.logging.enabled',
            false
        );

        $manager = new CorrelationIdManager;

        $manager->set('abc-123');

        $processor = new CorrelationIdProcessor($manager);

        $record = new LogRecord(
            datetime: new DateTimeImmutable,
            channel: 'testing',
            level: Level::Info,
            message: 'Test message'
        );

        $processedRecord = $processor($record);

        $this->assertArrayNotHasKey(
            'correlation_id',
            $processedRecord->extra
        );
    }

    public function test_it_uses_the_configured_logging_key(): void
    {
        config()->set(
            'correlation-id.logging.key',
            'request_id'
        );

        $manager = new CorrelationIdManager;

        $manager->set('abc-123');

        $processor = new CorrelationIdProcessor($manager);

        $record = new LogRecord(
            datetime: new DateTimeImmutable,
            channel: 'testing',
            level: Level::Info,
            message: 'Test message'
        );

        $processedRecord = $processor($record);

        $this->assertSame(
            'abc-123',
            $processedRecord->extra['request_id']
        );
    }
}
