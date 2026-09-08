<?php

namespace App\Domain\Events;

use Illuminate\Support\Str;

abstract class DomainEvent
{
    public function __construct(
        public readonly array $payload,
        public readonly ?string $traceId = null,
        public readonly ?string $sourceEventId = null,
    ) {}

    public function name(): string
    {
        return class_basename(static::class);
    }

    public function traceId(): string
    {
        return $this->traceId ?: ($this->payload['trace_id'] ?? (string) Str::uuid());
    }

    public function correlationId(): string
    {
        return $this->payload['correlation_id'] ?? $this->traceId();
    }

    public function sourceEventId(): ?string
    {
        return $this->sourceEventId ?: ($this->payload['event_id'] ?? $this->payload['source_event_id'] ?? null);
    }

    public function bookingId(): ?int
    {
        return isset($this->payload['booking_id']) ? (int) $this->payload['booking_id'] : null;
    }
}
