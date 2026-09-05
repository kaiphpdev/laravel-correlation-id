<?php

namespace LaravelCorrelationId;

class CorrelationIdManager
{
    protected ?string $correlationId = null;

    protected ?string $traceId = null;

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

    public function setTraceId(string $traceId): void
    {
        $this->traceId = $traceId;
    }

    public function getTraceId(): ?string
    {
        return $this->traceId;
    }

    public function hasTraceId(): bool
    {
        return $this->traceId !== null;
    }

    public function clearTraceId(): void
    {
        $this->traceId = null;
    }

    public function clear(): void
    {
        $this->correlationId = null;
        $this->traceId = null;
    }
}
