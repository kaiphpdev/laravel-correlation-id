<?php

namespace LaravelCorrelationId;

class CorrelationIdManager
{
    protected ?string $correlationId = null;

    public function set(string $correlationId): void
    {
        $this->correlationId = $correlationId;
    }

    public function get(): ?string
    {
        return $this->correlationId;
    }

    public function has(): bool
    {
        return $this->correlationId !== null;
    }

    public function clear(): void
    {
        $this->correlationId = null;
    }
}
