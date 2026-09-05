<?php

namespace LaravelCorrelationId;

class CorrelationIdManager
{
    protected ?string $correlationId = null;

    protected ?string $traceId = null;

    protected ?string $traceFlags = null;

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

    public function setTraceFlags(string $traceFlags): void
    {
        $this->traceFlags = strtolower($traceFlags);
    }

    public function getTraceFlags(): ?string
    {
        return $this->traceFlags;
    }

    public function hasTraceFlags(): bool
    {
        return $this->traceFlags !== null;
    }

    public function clearTraceId(): void
    {
        $this->traceId = null;
        $this->traceFlags = null;
    }

    public function clear(): void
    {
        $this->correlationId = null;
        $this->traceId = null;
        $this->traceFlags = null;
    }
}
