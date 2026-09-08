<?php

namespace App\Domain\Events;

use App\Domain\Booking\BookingSaga;
use App\Models\EventTimeline;
use App\Models\OutboxEvent;
use App\Services\DistributedEventStore;
use App\Services\GlobalIdempotencyService;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

class EventBus
{
    public function __construct(
        private readonly BookingSaga $bookingSaga,
        private readonly DistributedEventStore $eventStore,
        private readonly GlobalIdempotencyService $idempotency,
    ) {
    }

    public function dispatch(DomainEvent $event): void
    {
        $traceId = $event->traceId();
        $stream = $this->eventStore->append($event);
        $idempotency = $this->idempotency->begin('domain_event', (string) $stream->id, [
            'event_name' => $event->name(),
            'booking_id' => $event->bookingId(),
            'trace_id' => $traceId,
            'correlation_id' => $event->correlationId(),
        ]);

        if (in_array($idempotency['status'], ['completed', 'processing'], true) || $stream->processed_at) {
            Log::info('domain.event.skipped_duplicate', [
                'event_stream_id' => $stream->id,
                'event_name' => $event->name(),
                'booking_id' => $event->bookingId(),
                'trace_id' => $traceId,
                'correlation_id' => $event->correlationId(),
                'idempotency_status' => $idempotency['status'],
            ]);

            return;
        }

        EventTimeline::create([
            'trace_id' => $traceId,
            'event_name' => $event->name(),
            'source_event_id' => $event->sourceEventId(),
            'aggregate_type' => $event->bookingId() ? 'booking' : null,
            'aggregate_id' => $event->bookingId(),
            'payload' => $event->payload,
        ]);

        Log::info('domain.event.dispatched', [
            'trace_id' => $traceId,
            'correlation_id' => $event->correlationId(),
            'event_stream_id' => $stream->id,
            'event_version' => $stream->version,
            'event_name' => $event->name(),
            'source_event_id' => $event->sourceEventId(),
            'booking_id' => $event->bookingId(),
        ]);

        try {
            match (true) {
                $event instanceof PaymentSucceededEvent => $this->bookingSaga->onPaymentSucceeded($event),
                $event instanceof PaymentFailedEvent => $this->bookingSaga->onPaymentFailed($event),
                $event instanceof BookingConfirmedEvent => $this->bookingSaga->onBookingConfirmed($event),
                $event instanceof InvoiceCreatedEvent => $this->bookingSaga->onInvoiceCreated($event),
                $event instanceof DepositCapturedEvent => $this->bookingSaga->onDepositCaptured($event),
                $event instanceof BookingActivatedEvent => $this->bookingSaga->onBookingActivated($event),
                default => null,
            };

            $this->eventStore->markProcessed($stream);

            if ($idempotency['record'] ?? null) {
                $this->idempotency->complete($idempotency['record']);
            }
        } catch (Throwable $e) {
            if ($idempotency['record'] ?? null) {
                $this->idempotency->fail($idempotency['record']);
            }

            throw $e;
        }
    }

    public function dispatchOutbox(OutboxEvent $outboxEvent): void
    {
        $this->dispatch($this->fromOutbox($outboxEvent));
    }

    private function fromOutbox(OutboxEvent $outboxEvent): DomainEvent
    {
        $payload = array_merge($outboxEvent->payload ?? [], [
            'trace_id' => $outboxEvent->trace_id,
            'source_event_id' => $outboxEvent->source_event_id,
        ]);

        return match ($outboxEvent->event_type) {
            'payment.succeeded', 'rental_payment_synchronized' => new PaymentSucceededEvent(
                $payload,
                $outboxEvent->trace_id,
                $outboxEvent->source_event_id
            ),
            'payment.failed' => new PaymentFailedEvent($payload, $outboxEvent->trace_id, $outboxEvent->source_event_id),
            'booking.confirmed' => new BookingConfirmedEvent($payload, $outboxEvent->trace_id, $outboxEvent->source_event_id),
            'booking.activated' => new BookingActivatedEvent($payload, $outboxEvent->trace_id, $outboxEvent->source_event_id),
            'invoice.created' => new InvoiceCreatedEvent($payload, $outboxEvent->trace_id, $outboxEvent->source_event_id),
            'deposit.captured' => new DepositCapturedEvent($payload, $outboxEvent->trace_id, $outboxEvent->source_event_id),
            'security_deposit_synchronized' => $this->depositEventFromOutbox($outboxEvent, $payload),
            default => throw new InvalidArgumentException('Unknown outbox event type: ' . $outboxEvent->event_type),
        };
    }

    private function depositEventFromOutbox(OutboxEvent $outboxEvent, array $payload): DomainEvent
    {
        if (($payload['new_status'] ?? null) === 'captured') {
            return new DepositCapturedEvent($payload, $outboxEvent->trace_id, $outboxEvent->source_event_id);
        }

        return new PaymentSucceededEvent($payload, $outboxEvent->trace_id, $outboxEvent->source_event_id);
    }
}
