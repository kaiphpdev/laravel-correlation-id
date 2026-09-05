<?php

namespace LaravelCorrelationId\Facades;

use Illuminate\Support\Facades\Facade;
use LaravelCorrelationId\CorrelationIdManager;
use Override;

class CorrelationId extends Facade {
    #[Override]
    protected static function getFacadeAccessor(): string
    {
        return CorrelationIdManager::class;
    }
}