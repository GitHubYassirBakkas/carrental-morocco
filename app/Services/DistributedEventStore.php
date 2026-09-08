<?php

namespace App\Services;

use App\Domain\Events\DomainEvent;
use App\Models\EventStream;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DistributedEventStore
{
    public function append(DomainEvent $event): EventStream
    {
        $bookingId = $event->bookingId();
        $eventKey = $this->eventKey($event);

        return DB::transaction(function () use ($event, $bookingId, $eventKey) {
            $existing = EventStream::where('event_key', $eventKey)->first();

            if ($existing) {
                return $existing;
            }

            $version = null;

            if ($bookingId) {
                $version = ((int) EventStream::where('booking_id', $bookingId)
                    ->lockForUpdate()
                    ->max('version')) + 1;
            }

            try {
                return EventStream::create([
                    'event_key' => $eventKey,
                    'event_name' => $event->name(),
                    'booking_id' => $bookingId,
                    'version' => $version,
                    'trace_id' => $event->traceId(),
                    'correlation_id' => $event->payload['correlation_id'] ?? $event->traceId(),
                    'source_event_id' => $event->sourceEventId(),
                    'payload' => $event->payload,
                ]);
            } catch (QueryException $e) {
                $existing = EventStream::where('event_key', $eventKey)->first();

                if ($existing) {
                    return $existing;
                }

                throw $e;
            }
        }, 3);
    }

    public function markProcessed(EventStream $stream): void
    {
        $stream->forceFill(['processed_at' => now()])->save();
    }

    private function eventKey(DomainEvent $event): string
    {
        return implode(':', [
            $event->name(),
            $event->bookingId() ?: 'global',
            $event->sourceEventId() ?: ($event->payload['payment_intent_id'] ?? Str::uuid()),
            $event->traceId(),
        ]);
    }
}
