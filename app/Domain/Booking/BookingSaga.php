<?php

namespace App\Domain\Booking;

use App\Domain\Events\BookingActivatedEvent;
use App\Domain\Events\BookingConfirmedEvent;
use App\Domain\Events\DepositCapturedEvent;
use App\Domain\Events\EventBus;
use App\Domain\Events\InvoiceCreatedEvent;
use App\Domain\Events\PaymentFailedEvent;
use App\Domain\Events\PaymentSucceededEvent;
use App\Models\Booking;
use App\Models\BookingSaga as BookingSagaModel;
use App\Models\SagaExecutionLog;
use App\Services\InvoiceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class BookingSaga
{
    public function __construct(private readonly BookingStateMachine $stateMachine)
    {
    }

    public function onPaymentSucceeded(PaymentSucceededEvent $event): void
    {
        $this->run($event->bookingId(), $event->traceId(), 'payment_succeeded', $event->payload, function (Booking $booking, BookingSagaModel $saga) use ($event) {
            if ($booking->isPending()) {
                $this->stateMachine->transition($booking, Booking::STATUS_CONFIRMED, [
                    'source' => 'booking_saga',
                    'source_event_id' => $event->sourceEventId(),
                    'trace_id' => $event->traceId(),
                ]);
            }

            $this->markStep($saga, 'booking_confirmed', 'running', $event->payload);

            app(EventBus::class)->dispatch(new BookingConfirmedEvent(array_merge($event->payload, [
                'booking_status' => $booking->fresh()->status,
            ]), $event->traceId(), $event->sourceEventId()));
        });
    }

    public function onBookingConfirmed(BookingConfirmedEvent $event): void
    {
        $this->run($event->bookingId(), $event->traceId(), 'booking_confirmed', $event->payload, function (Booking $booking, BookingSagaModel $saga) use ($event) {
            $invoice = app(InvoiceService::class)->firstOrCreateForPaymentProcessing($booking);

            $this->markStep($saga, 'invoice_created', 'running', array_merge($event->payload, [
                'invoice_id' => $invoice->id,
            ]));

            app(EventBus::class)->dispatch(new InvoiceCreatedEvent(array_merge($event->payload, [
                'invoice_id' => $invoice->id,
            ]), $event->traceId(), $event->sourceEventId()));
        });
    }

    public function onInvoiceCreated(InvoiceCreatedEvent $event): void
    {
        $this->run($event->bookingId(), $event->traceId(), 'invoice_created', $event->payload, function (Booking $booking, BookingSagaModel $saga) use ($event) {
            $this->markStep($saga, 'deposit_checkpoint', 'running', $event->payload);

            if ($booking->getSecurityDepositEffectiveState() === Booking::SECURITY_DEPOSIT_STATUS_CAPTURED) {
                app(EventBus::class)->dispatch(new DepositCapturedEvent($event->payload, $event->traceId(), $event->sourceEventId()));

                return;
            }

            $this->markStep($saga, 'completed', 'completed', $event->payload);
        });
    }

    public function onDepositCaptured(DepositCapturedEvent $event): void
    {
        $this->run($event->bookingId(), $event->traceId(), 'deposit_captured', $event->payload, function (Booking $booking, BookingSagaModel $saga) use ($event) {
            $this->markStep($saga, 'completed', 'completed', $event->payload);
        });
    }

    public function onBookingActivated(BookingActivatedEvent $event): void
    {
        $this->run($event->bookingId(), $event->traceId(), 'booking_activated', $event->payload, function (Booking $booking, BookingSagaModel $saga) use ($event) {
            if ($booking->isConfirmed()) {
                $this->stateMachine->transition($booking, Booking::STATUS_ACTIVE, [
                    'source' => 'booking_saga',
                    'source_event_id' => $event->sourceEventId(),
                    'trace_id' => $event->traceId(),
                ]);
            }

            $this->markStep($saga, 'booking_activated', 'running', $event->payload);
        });
    }

    public function onPaymentFailed(PaymentFailedEvent $event): void
    {
        $this->run($event->bookingId(), $event->traceId(), 'payment_failed', $event->payload, function (Booking $booking, BookingSagaModel $saga) use ($event) {
            $compensation = $this->compensatePaymentFailure($booking, $event);

            $saga->forceFill([
                'compensation_status' => 'completed',
                'compensation_payload' => $compensation,
                'retry_count' => $saga->retry_count + 1,
            ])->save();

            $this->markStep($saga, 'compensation_completed', 'failed', $event->payload, 'Payment failed; compensation completed.');
        });
    }

    public function retryFailed(string $sagaId): void
    {
        $saga = BookingSagaModel::where('saga_id', $sagaId)->firstOrFail();

        if ($saga->status !== 'failed') {
            return;
        }

        $saga->forceFill([
            'status' => 'running',
            'retry_count' => $saga->retry_count + 1,
            'failure_root_cause' => null,
        ])->save();

        $this->log($saga, $saga->current_step, 'retry_scheduled', $saga->payload ?? []);
    }

    private function run(?int $bookingId, string $traceId, string $step, array $payload, callable $callback): void
    {
        if (!$bookingId) {
            return;
        }

        DB::transaction(function () use ($bookingId, $traceId, $step, $payload, $callback) {
            $booking = Booking::whereKey($bookingId)->lockForUpdate()->firstOrFail();
            $saga = BookingSagaModel::firstOrCreate(
                ['booking_id' => $booking->id, 'trace_id' => $traceId],
                [
                    'saga_id' => (string) Str::uuid(),
                    'current_step' => 'started',
                    'status' => 'running',
                    'payload' => $payload,
                ]
            );

            if ($saga->status === 'completed' && $step !== 'booking_activated') {
                return;
            }

            $this->log($saga, $step, 'started', $payload);

            try {
                $callback($booking, $saga);
                $this->log($saga->fresh(), $step, 'succeeded', $payload);
            } catch (Throwable $e) {
                $this->markStep($saga, $step, 'failed', $payload, $e->getMessage());
                $this->log($saga->fresh(), $step, 'failed', $payload, $e->getMessage());

                throw $e;
            }
        }, 3);
    }

    private function markStep(BookingSagaModel $saga, string $step, string $status, array $payload, ?string $failure = null): void
    {
        $saga->forceFill([
            'current_step' => $step,
            'status' => $status,
            'payload' => $payload,
            'failure_root_cause' => $failure,
            'completed_at' => $status === 'completed' ? now() : $saga->completed_at,
        ])->save();

        Log::info('booking.saga.step', [
            'saga_id' => $saga->saga_id,
            'booking_id' => $saga->booking_id,
            'trace_id' => $saga->trace_id,
            'step' => $step,
            'status' => $status,
            'failure_root_cause' => $failure,
        ]);
    }

    private function compensatePaymentFailure(Booking $booking, PaymentFailedEvent $event): array
    {
        $actions = [];

        if ($booking->isConfirmed()) {
            $this->stateMachine->transition($booking, Booking::STATUS_CANCELLED, [
                'source' => 'booking_saga_compensation',
                'source_event_id' => $event->sourceEventId(),
                'trace_id' => $event->traceId(),
            ]);

            $actions[] = 'booking_confirmation_rolled_back_to_cancelled';
        }

        if (!empty($event->payload['payment_intent_id'])) {
            $actions[] = 'refund_payment_intent_requested_logically';
        }

        if (app(\App\Services\SecurityDepositService::class)->shouldRequestLogicalRelease($booking)) {
            $actions[] = 'release_deposit_requested_logically';
        }

        Log::warning('booking.saga.compensation_completed', [
            'booking_id' => $booking->id,
            'trace_id' => $event->traceId(),
            'source_event_id' => $event->sourceEventId(),
            'actions' => $actions,
        ]);

        return [
            'actions' => $actions,
            'source_event_id' => $event->sourceEventId(),
            'trace_id' => $event->traceId(),
        ];
    }

    private function log(BookingSagaModel $saga, string $step, string $status, array $context, ?string $failure = null): void
    {
        SagaExecutionLog::create([
            'saga_id' => $saga->saga_id,
            'booking_id' => $saga->booking_id,
            'trace_id' => $saga->trace_id,
            'step' => $step,
            'status' => $status,
            'context' => $context,
            'failure_root_cause' => $failure,
        ]);
    }
}
