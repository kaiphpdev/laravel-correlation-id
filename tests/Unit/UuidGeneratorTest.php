<?php

namespace LaravelCorrelationId\Tests\Unit;

use Illuminate\Support\Str;
use LaravelCorrelationId\Generators\UuidGenerator;
use PHPUnit\Framework\TestCase;

class UuidGeneratorTest extends TestCase
{
    public function test_it_generates_a_valid_uuid(): void
    {
        $generator = new UuidGenerator;

        $correlationId = $generator->generate();

        $this->assertTrue(
            Str::isUuid($correlationId)
        );
    }
}
